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
	 * Maximum number of redirects to follow.
	 */
	private $max_redirs = 5;

	/**
	 * Temporary buffer for headers.
	 */
	private $_headers = '';

	public function __construct()
	{
		if ( ini_get( 'safe_mode' ) || ini_get( 'open_basedir' ) ) {
			$this->can_followlocation = false;
		}
	}

	public function execute( $method, $url, $headers, $body, $timeout )
	{
		$merged_headers = array();
		foreach ( $headers as $k => $v ) {
			$merged_headers[] = $k . ': ' . $v;
		}

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $url ); // The URL.
		curl_setopt( $ch, CURLOPT_HEADERFUNCTION, array(&$this, '_headerfunction' ) ); // The header of the response.
		curl_setopt( $ch, CURLOPT_MAXREDIRS, $this->max_redirs ); // Maximum number of redirections to follow.
		curl_setopt( $ch, CURLOPT_CRLF, true ); // Convert UNIX newlines to \r\n.
		if ( $this->can_followlocation ) {
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true ); // Follow 302's and the like.
		}
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $merged_headers ); // headers to send

		if ( $method === 'POST' ) {
			curl_setopt( $ch, CURLOPT_POST, true ); // POST mode.
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
		}

		$fh = tmpfile();
		curl_setopt( $ch, CURLOPT_FILE, $fh );

		$success = curl_exec( $ch );

		if( $success ) {
			$body = stream_get_contents( $fh );
		}
		fclose( $fh );

		if ( curl_errno( $ch ) !== 0 ) {
			return Error::raise( sprintf( _t('%s: CURL Error %d: %s'), __CLASS__, curl_errno( $ch ), curl_error( $ch ) ),
				E_USER_WARNING );
		}

		if ( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) !== 200 ) {
			return Error::raise( sprintf( _t('Bad return code (%1$d) for: %2$s'), 
				curl_getinfo( $ch, CURLINFO_HTTP_CODE ), 
				$url ),
				E_USER_WARNING
			);
		}

		curl_close( $ch );

		// this fixes an E_NOTICE in the array_pop
		$tmp_headers = explode("\r\n\r\n", substr( $this->_headers, 0, -4 ) );

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
			return Error::raise( _t('Request did not yet execute.') );
		}
		
		return $this->response_body;
	}

	public function get_response_headers()
	{
		if ( ! $this->executed ) {
			return Error::raise( _t('Request did not yet execute.') );
		}

		return $this->response_headers;
	}
}

?>
