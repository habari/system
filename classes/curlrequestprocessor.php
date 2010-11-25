<?php
/**
 * @package Habari
 *
 */

/**
 * RequestProcessor using CURL.
 */
class CURLRequestProcessor implements RequestProcessor
{
	private $response_body = '';
	private $response_headers = '';
	private $executed = false;

	private $can_followlocation = true;

	/**
	 * Temporary buffer for headers.
	 */
	private $_headers = '';

	public function __construct()
	{
		if ( ini_get( 'safe_mode' ) || ini_get( 'open_basedir' ) ) {
			$this->can_followlocation = false;
		}

		if ( !defined( 'FILE_CACHE_LOCATION' ) ) {
			define( 'FILE_CACHE_LOCATION', HABARI_PATH . '/user/cache/' );
		}
	}

	public function execute( $method, $url, $headers, $body, $config )
	{
		$merged_headers = array();
		foreach ( $headers as $k => $v ) {
			$merged_headers[] = $k . ': ' . $v;
		}

		$ch = curl_init();
		
		$options = array(
			CURLOPT_URL				=> $url,	// The URL
			CURLOPT_HEADERFUNCTION	=> array( &$this, '_headerfunction' ), // The header of the response.
			CURLOPT_MAXREDIRS		=> $config['max_redirects'], // Maximum number of redirections to follow.
			CURLOPT_CONNECTTIMEOUT	=> $config['connect_timeout'],
			CURLOPT_TIMEOUT			=> $config['timeout'],
			CURLOPT_SSL_VERIFYPEER	=> $config['ssl_verify_peer'],
			CURLOPT_SSL_VERIFYHOST	=> $config['ssl_verify_host'],
			CURLOPT_BUFFERSIZE		=> $config['buffer_size'],
			CURLOPT_HTTPHEADER		=> $merged_headers,	// headers to send
			CURLOPT_FOLLOWLOCATION	=> true,
			CURLOPT_RETURNTRANSFER	=> true,
		);

		if ( $this->can_followlocation ) {
			$options[CURLOPT_FOLLOWLOCATION] = true; // Follow 302's and the like.
		}

		if ( $method === 'POST' ) {
			$options[CURLOPT_POST] = true; // POST mode.
			$options[CURLOPT_POSTFIELDS] = $body;
		}
		else {
			$options[CURLOPT_CRLF] = true; // Convert UNIX newlines to \r\n
		}

		// set proxy, if needed
		$urlbits = InputFilter::parse_url( $url );
        if ( $config['proxy_server'] && ! in_array( $urlbits['host'], $config['proxy_exceptions'] ) ) {
            $options[CURLOPT_PROXY] = $config['proxy_server'] . ':' . $config['proxy_port'];	// Validation of the existence of the port should take place in the Options form
            if ( $config['proxy_username'] ) {
                $options[CURLOPT_PROXYUSERPWD] = $config['proxy_username'] . ':' . $config['proxy_password'];
                switch ( strtolower( $config['proxy_auth_scheme'] ) ) {
                    case 'basic':
                        $options[CURLOPT_PROXYAUTH] = CURLAUTH_BASIC;
                        break;
                    case 'digest':
                        $options[CURLOPT_PROXYAUTH] = CURLAUTH_DIGEST;
						break;
                }
            }
        }
		
		curl_setopt_array( $ch, $options );

		/**
		 * @todo Possibly find a way to generate a temp file without needing the user
		 * to set write permissions on cache directory
		 *
		 * @todo Fallback to using the the old way if the cache directory isn't writable
		 * 
		 * @todo How about trying to use the system-defined temp directory? We could at least try, even if safe_mode or something breaks it. - chrismeller
		 */
		$tmp = tempnam( FILE_CACHE_LOCATION, 'RR' );
		if ( ! $tmp ) {
			throw new Exception( _t('CURL Error. Unable to create temporary file name.') );
		}

		$fh = @fopen( $tmp, 'w+b' );
		if ( ! $fh ) {
			throw new Exception( _t('CURL Error. Unable to open temporary file.') );
		}

		curl_setopt( $ch, CURLOPT_FILE, $fh );

		$success = curl_exec( $ch );

		if ( $success ) {
			rewind( $fh );
			$body = stream_get_contents( $fh );
		}
		fclose( $fh );
		unset( $fh );

		if ( isset( $tmp ) && file_exists ($tmp ) ) {
			unlink( $tmp );
		}

		if ( curl_errno( $ch ) !== 0 ) {
			throw new Exception( _t( 'CURL Error %d: %s', array(curl_errno( $ch ), curl_error( $ch ) ) ) );
		}

		if ( substr( curl_getinfo( $ch, CURLINFO_HTTP_CODE ), 0, 1 ) != 2 ) {
			throw new Exception( _t( 'Bad return code (%d) for: %s', array( curl_getinfo( $ch, CURLINFO_HTTP_CODE ), $url ) ) );
		}

		curl_close( $ch );

		// this fixes an E_NOTICE in the array_pop
		$tmp_headers = explode( "\r\n\r\n", MultiByte::substr( $this->_headers, 0, -4 ) );

		$this->response_headers = array_pop( $tmp_headers );
		$this->response_body = $body;
		$this->executed = true;

		return true;
	}

	public function _headerfunction( $ch, $str )
	{
		$this->_headers.= $str;

		return strlen( $str );
	}

	public function get_response_body()
	{
		if ( ! $this->executed ) {
			throw new Exception( _t( 'Unable to get response body. Request did not yet execute.' ) );
		}

		return $this->response_body;
	}

	public function get_response_headers()
	{
		if ( ! $this->executed ) {
			throw new Exception( _t( 'Unable to get response headers. Request did not yet execute.' ) );
		}

		return $this->response_headers;
	}
}

?>
