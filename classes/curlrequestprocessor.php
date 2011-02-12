<?php
/**
 * RequestProcessor using CURL.
 */
class CURLRequestProcessor extends RequestProcessor
{

	/**
	 * Temporary buffer for headers.
	 */
	private $headers = '';

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
			CURLOPT_SSL_VERIFYPEER	=> $config['ssl']['verify_peer'],
			CURLOPT_SSL_VERIFYHOST	=> $config['ssl']['verify_host'],
			CURLOPT_BUFFERSIZE		=> $config['buffer_size'],
			CURLOPT_HTTPHEADER		=> $merged_headers,	// headers to send
			CURLOPT_FAILONERROR		=> true,		// if the status code is >= 400, fail
		);

		if ( $this->can_followlocation && $config['follow_redirects'] ) {
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
		if ( $config['proxy']['server'] && ! in_array( $urlbits['host'], $config['proxy']['exceptions'] ) ) {
			$options[CURLOPT_PROXY] = $config['proxy']['server'] . ':' . $config['proxy']['port'];	// Validation of the existence of the port should take place in the Options form
			if ( $config['proxy']['username'] ) {
				$options[CURLOPT_PROXYUSERPWD] = $config['proxy']['username'] . ':' . $config['proxy']['password'];
				switch ( $config['proxy']['auth_type'] ) {
					case 'basic':
						$options[CURLOPT_PROXYAUTH] = CURLAUTH_BASIC;
						break;
					case 'digest':
						$options[CURLOPT_PROXYAUTH] = CURLAUTH_DIGEST;
						break;
				}
			}
			
			// if it's a socks proxy, we have to tell curl that
			if ( $config['proxy']['type'] == 'socks' ) {
				$options[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
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
			throw new Exception( _t( 'CURL Error. Unable to create temporary file name.' ) );
		}

		$fh = @fopen( $tmp, 'w+b' );
		if ( ! $fh ) {
			throw new Exception( _t( 'CURL Error. Unable to open temporary file.' ) );
		}

		curl_setopt( $ch, CURLOPT_FILE, $fh );

		$success = curl_exec( $ch );

		if ( $success ) {
			rewind( $fh );
			$body = stream_get_contents( $fh );
		}
		fclose( $fh );
		unset( $fh );

		if ( isset( $tmp ) && file_exists( $tmp ) ) {
			unlink( $tmp );
		}

		if ( curl_errno( $ch ) !== 0 ) {
			
			// get the number and error before we close the handle so we don't error out
			$errno = curl_errno( $ch );
			$error = curl_error( $ch );
			
			// before we throw an exception, just to be nice
			curl_close( $ch );
			
			switch ( $errno ) {
				
				case CURLE_OPERATION_TIMEOUTED:
					// the request timed out
					throw new RemoteRequest_Timeout( $error, $errno );
					break;
					
				default:
					throw new Exception( _t( 'CURL Error %1$d: %2$s', array( $errno, $error ) ) );
					break;
				
			}
			
		}

		curl_close( $ch );

		$this->response_headers = $this->headers;		// really redundant now, since we could write directly to response_headers, but makes it more clear where they came from
		$this->response_body = $body;
		$this->executed = true;

		return true;
	}

	/**
	 * cURL will hand each header received by the *response* to this method, so we use it
	 * to conveniently capture them for storing in case the user wants them.
	 * 
	 * @param $ch resource The cURL handle from curl_init() that is executing.
	 * @param $str string The header received from the response. Should always be a single header at a time.
	 * 
	 * @return int The length of the header. Used by cURL to report the header_size returned by the curl_getinfo() method.
	 */
	public function _headerfunction( $ch, $str )
	{
		
		$header = trim( $str );
		
		// don't save blank lines we might be handed - there's usually one after the headers
		if ( $header != '' ) {
		
			// break the header up into field and value
			$pieces = explode( ': ', $header, 2 );
			
			if ( count( $pieces ) > 1 ) {
				// if the header was a key: value format, store it keyed in the array
				$this->headers[ $pieces[0] ] = $pieces[1];
			}
			else {
				// some headers (like the HTTP version in use) aren't keyed, so just store it keyed as itself
				$this->headers[ $pieces[0] ] = $pieces[0];
			}
			
		}

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
