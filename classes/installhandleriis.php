<?php
/**
 * @package Habari
 *
 */

/**
 * InstallHandler for Microsoft IIS 7+
 *
 */
class InstallHandlerIIS extends InstallHandler
{
    protected $rewriteTemplate = 'webconfig';

    /**
     * Removed all instances to calling for rewrite and returns True,
     * just like it did before when we encountered something other than
     * Apache
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
        if ( file_exists( HABARI_PATH . '/web.config') ) {
            $htaccess = file_get_contents( HABARI_PATH . '/web.config');
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
     * Returns an empty array since we don't know how to handle this server's
     * rewrite config
     */
    public function rewrite_config()
    {
        $htaccess = array(
            
            'xml_begin_file'   => '<configuration><system.webServer><rewrite><rules>',
            'open_block'        => '<!-- HABARI START -->',
            'rule_files_name'         => '<rule name="Habari - Serve Existing Files" stopProcessing="true">',
            'rule_files_match'         => '<match url="^.*$" />',
            'rule_files_conditions_open'   => '<conditions logicalGrouping="MatchAny">',
            'rule_files_conditions_f' => '<add input="{REQUEST_FILENAME}" matchType="IsFile" pattern="" ignoreCase="false" />',
            'rule_files_conditions_d' => '<add input="{REQUEST_FILENAME}" matchType="IsDirectory" pattern="" ignoreCase="false" />',
            'rule_filesconditions_close' => '</conditions>',
            'rule_files_action' => '<action type="None" />',
            'rule_files_close'  => '</rule>',
            'rule_rewrite_name' => '<rule name="Habari - Rewrite to index.php" stopProcessing="true">',
            'rule_rewrite_match'    => '<match url="^.*$" />',
            'rule_rewrite_action'   => '<action type="Rewrite" url="index.php" />',
            'rule_rewrite_close'    => '</rule>',
            'close_block' => '<!-- HABARI END -->',
            'xml_close_file'   => '</rules></rewrite></system.webServer></configuration>',
        );

        return $htaccess;
    }

    /**
     * Fakes writing a config file since we don't know what to do for this server
     * @param bool whether web.config file already exists or not
     * @param bool whether to remove and re-create any existing Habari block (not used by IIS)
     * @param bool whether to try a rewritebase in the web.config (not used)
     **/
    public function write_rewrite_config( $exists = FALSE, $update = FALSE, $rewritebase = TRUE )
    {
        $htaccess = $this->rewrite_config();
        
        // Forcing Write mode since we can't really append XML
        $fmode = 'w';
        
        if ( ! $exists ) {
            if ( ! is_writable( HABARI_PATH ) ) {
                // we can't create the file
                return false;
            }
        }
        else {
            if ( ! is_writable( HABARI_PATH . '/web.config' ) ) {
                // we can't update the file
                return false;
            }
        }
        
        if ( $update ) {
            // we're updating an existing but incomplete web.config
            // care must be take only to remove the Habari bits

            // Remove the opening and closing parts of the entire XML file
            unset($htaccess['xml_begin_file']);
            unset($htaccess['open_block']);
            unset($htaccess['close_block']);
            unset($htaccess['xml_close_file']);
            $existingConfig = file_get_contents(HABARI_PATH . '/web.config');
            $file_contents = preg_replace('%<!-- HABARI START -->.*?<!-- HABARI END -->%ims', $existingConfig, implode("\n", $htaccess));
        } else {
            $file_contents = "\n" . implode( "\n", $htaccess ) . "\n";
        }

        //Save the htaccess
        if ( $fh = fopen( HABARI_PATH . '/web.config', $fmode ) ) {
            $xml = simplexml_load_string($file_contents);
            $file_contents = $xml->asXML();

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
