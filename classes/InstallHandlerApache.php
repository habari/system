<?php
/**
 * @package Habari
 *
 */

/**
 * InstallHandler for Apache
 *
 */
class InstallHandlerApache extends InstallHandler
{
    protected $rewriteTemplate = 'htaccess';

    /**
     * checks for the presence of an .htaccess file
     * invokes write_rewrite_config() as needed
     */
    public function check_rewrite_config()
    {
        // default is assume we have mod_rewrite
        $this->handler_vars['no_mod_rewrite'] = false;

        // If this is the mod_rewrite check request, then bounce it as a success.
        if ( strpos( $_SERVER['REQUEST_URI'], 'check_mod_rewrite' ) !== false ) {
            echo 'ok';
            exit;
        }

        $result = false;
        if ( file_exists( HABARI_PATH . '/.htaccess') ) {
            $htaccess = file_get_contents( HABARI_PATH . '/.htaccess');
            if ( false === strpos( $htaccess, 'HABARI' ) ) {
                // the Habari block does not exist in this file
                // so try to create it
                $result = $this->write_rewrite_config( true );
            }
            else {
                // the Habari block exists
                $result = true;
            }
        }
        else {
            // no .htaccess exists.  Try to create one
            $result = $this->write_rewrite_config( false );
        }
        if ( $result ) {
            // the Habari block exists, but we need to make sure
            // it is correct.
            // Check that the rewrite rules actually do the job.
            $test_ajax_url = Site::get_url( 'habari' ) . '/check_mod_rewrite';
            $rr = new RemoteRequest( $test_ajax_url, 'POST', 20 );
            $rr_result = $rr->execute();
            if ( ! $rr->executed() ) {
                $result = $this->write_rewrite_config( true, true, true );
            }
        }
        return $result;
    }

    /**
     * returns an array of .htaccess declarations used by Habari
     */
    public function rewrite_config()
    {
        $htaccess = array(
            'open_block' => '### HABARI START',
            'engine_on' => 'RewriteEngine On',
            'rewrite_cond_f' => 'RewriteCond %{REQUEST_FILENAME} !-f',
            'rewrite_cond_d' => 'RewriteCond %{REQUEST_FILENAME} !-d',
            'rewrite_base' => '#RewriteBase /',
            'rewrite_rule' => 'RewriteRule . index.php [PT]',
            'hide_habari' => 'RewriteRule ^(system/(classes|locale|schema|$)) index.php [PT]',
            'close_block' => '### HABARI END',
        );
        $rewrite_base = trim( dirname( $_SERVER['SCRIPT_NAME'] ), '/\\' );
        if ( $rewrite_base != '' ) {
            $htaccess['rewrite_base'] = 'RewriteBase /' . $rewrite_base;
        }

        return $htaccess;
    }

    /**
     * attempts to write the .htaccess file if none exists
     * or to write the Habari-specific portions to an existing .htaccess
     * @param bool whether an .htaccess file already exists or not
     * @param bool whether to remove and re-create any existing Habari block
     * @param bool whether to try a rewritebase in the .htaccess
     **/
    public function write_rewrite_config( $exists = FALSE, $update = FALSE, $rewritebase = TRUE )
    {
        $htaccess = $this->rewrite_config();
        if ( $rewritebase ) {
            $rewrite_base = trim( dirname( $_SERVER['SCRIPT_NAME'] ), '/\\' );
            $htaccess['rewrite_base'] = 'RewriteBase /' . $rewrite_base;
        }
        $file_contents = "\n" . implode( "\n", $htaccess ) . "\n";

        if ( ! $exists ) {
            if ( ! is_writable( HABARI_PATH ) ) {
                // we can't create the file
                return false;
            }
        }
        else {
            if ( ! is_writable( HABARI_PATH . '/.htaccess' ) ) {
                // we can't update the file
                return false;
            }
        }
        if ( $update ) {
            // we're updating an existing but incomplete .htaccess
            // care must be take only to remove the Habari bits
            $htaccess = file_get_contents(HABARI_PATH . '/.htaccess');
            $file_contents = preg_replace('%### HABARI START.*?### HABARI END%ims', $file_contents, $htaccess);
            // Overwrite the existing htaccess with one that includes the modified Habari rewrite block
            $fmode = 'w';
        }
        else {
            // Append the Habari rewrite block to the existing file.
            $fmode = 'a';
        }
        //Save the htaccess
        if ( $fh = fopen( HABARI_PATH . '/.htaccess', $fmode ) ) {
            if ( FALSE === fwrite( $fh, $file_contents ) ) {
                return false;
            }
            fclose( $fh );
        }
        else {
            return false;
        }

        return true;
    }
}
