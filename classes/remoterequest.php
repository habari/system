<?php
/**
 * @package Habari
 *
 */

/**
 * Holds the basic RemoteRequest functionality.
 *
 * Interface for Request Processors. RemoteRequest uses a RequestProcessor to
 * do the actual work.
 *
 */
interface RequestProcessor
{
	public function execute( $method, $url, $headers, $body, $timeout );

	public function get_response_body();
	public function get_response_headers();
}

/**
 * Generic class to make outgoing HTTP requests.
 *
 */
class RemoteRequest
{
	private $method = 'GET';
	private $url;
	private $params = array();
	private $headers = array();
	private $postdata = array();
	private $files = array();
	private $body = '';
	private $timeout = 180;
	private $processor = null;
	private $executed = false;

	private $response_body = '';
	private $response_headers = '';

	private $user_agent = 'Habari';

	/**
	 * @param string $url URL to request
	 * @param string $method Request method to use (default 'GET')
	 * @param int $timeuot Timeout in seconds (default 180)
	 */
	public function __construct( $url, $method = 'GET', $timeout = 180 )
	{
		$this->method = strtoupper( $method );
		$this->url = $url;
		$this->set_timeout( $timeout );

		$this->user_agent .= '/' . Version::HABARI_VERSION;
		$this->add_header( array( 'User-Agent' => $this->user_agent ) );

		// can't use curl's followlocation in safe_mode with open_basedir, so
		// fallback to srp for now
		if ( function_exists( 'curl_init' )
			 && ! ( ini_get( 'safe_mode' ) && ini_get( 'open_basedir' ) ) ) {
			$this->processor = new CURLRequestProcessor;
		}
		else {
			$this->processor = new SocketRequestProcessor;
		}
	}

	/**
	 * DO NOT USE THIS FUNCTION.
	 * This function is only to be used by the test case for RemoteRequest!
	 */
	public function __set_processor( $processor )
	{
		$this->processor = $processor;
	}

	/**
	 * Add a request header.
	 * @param mixed $header The header to add, either as a string 'Name: Value' or an associative array 'name'=>'value'
	 */
	public function add_header( $header )
	{
		if ( is_array( $header ) ) {
			$this->headers = array_merge( $this->headers, $header );
		}
		else {
			list( $k, $v )= explode( ': ', $header );
			$this->headers[$k] = $v;
		}
	}

	/**
	 * Add a list of headers.
	 * @param array $headers List of headers to add.
	 */
	public function add_headers( $headers )
	{
		foreach ( $headers as $header ) {
			$this->add_header( $header );
		}
	}

	/**
	 * Set the request body.
	 * Only used with POST requests, will raise a warning if used with GET.
	 * @param string $body The request body.
	 */
	public function set_body( $body )
	{
		if ( $this->method !== 'POST' ) {
			throw new Exception( _t('Trying to add a request body to a non-POST request.') );
		}

		$this->body = $body;
	}

	/**
	 * Set the request query parameters (i.e., the URI's query string).
	 * Will be merged with existing query info from the URL.
	 * @param array $params
	 */
	public function set_params( $params )
	{
		if ( ! is_array( $params ) )
			$params = parse_str( $params );

		$this->params = $params;
	}

	/**
	 * Set the timeout.
	 * @param int $timeout Timeout in seconds
	 */
	public function set_timeout( $timeout )
	{
		$this->timeout = $timeout;
		return $this->timeout;
	}

	/**
	 * set postdata
	 *
	 * @access public
	 * @param mixed $name
	 * @param string $value
	 */
	public function set_postdata($name, $value = null)
	{
		if (is_array($name)) {
			$this->postdata = array_merge($this->postdata, $name);
		}
		else {
			$this->postdata[$name] = $value;
		}
	}

	/**
	 * set file
	 *
	 * @access public
	 * @param string $name
	 * @param string $filename
	 * @param string $content_type
	 */
	public function set_file($name, $filename, $content_type = null, $override_filename = null)
	{
		if (!file_exists($filename)) {
			throw new Exception( _t('File %s not found.', array($filename)) );
		}
		if (empty($content_type)) $content_type = 'application/octet-stream';
		$this->files[$name] = array('filename' => $filename, 'content_type' => $content_type, 'override_filename' => $override_filename);
		$this->headers['Content-Type'] = 'multipart/form-data';
	}

	/**
	 * A little housekeeping.
	 */
	private function prepare()
	{
		// remove anchors (#foo) from the URL
		$this->url = $this->strip_anchors( $this->url );
		// merge query params from the URL with params given
		$this->url = $this->merge_query_params( $this->url, $this->params );

		if ( $this->method === 'POST' ) {
			if ( !isset( $this->headers['Content-Type'] ) || ( $this->headers['Content-Type'] == 'application/x-www-form-urlencoded' ) ) {
				// TODO should raise a warning
				$this->add_header( array( 'Content-Type' => 'application/x-www-form-urlencoded' ) );

				if ( $this->body != '' && count($this->postdata) > 0 ) {
					$this->body .= '&';
				}
				$this->body .= http_build_query( $this->postdata, '', '&' );
			}
			elseif ( $this->headers['Content-Type'] == 'multipart/form-data' ) {
				$boundary = md5( Utils::nonce() );
				$this->headers['Content-Type'] .= '; boundary=' . $boundary;

				$parts = array();
				if ( $this->postdata && is_array( $this->postdata ) ) {
					reset( $this->postdata );
					while ( list( $name, $value ) = each( $this->postdata ) ) {
						$parts[] = "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n{$value}\r\n";
					}
				}

				if ( $this->files && is_array( $this->files ) ) {
					reset( $this->files );
					while ( list( $name, $fileinfo ) = each( $this->files ) ) {
						$filename = basename( $fileinfo['filename'] );
						if ( !empty( $fileinfo['override_filename'] ) ) {
							$filename = $fileinfo['override_filename'];
						}
						$part = "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"\r\n";
						$part .= "Content-Type: {$fileinfo['content_type']}\r\n\r\n";
						$part .= file_get_contents( $fileinfo['filename'] ) . "\r\n";
						$parts[] = $part;
					}
				}

				if ( !empty( $parts ) ) {
					$this->body = "--{$boundary}\r\n" . join("--{$boundary}\r\n", $parts) . "--{$boundary}--\r\n";
				}
			}
			$this->add_header( array( 'Content-Length' => strlen( $this->body ) ) );
		}
	}

	/**
	 * Actually execute the request.
	 * On success, returns true and populates the response_body and response_headers fields.
	 * On failure, throws Exception.
	 * 
	 * @throws Exception
	 */
	public function execute()
	{
		$this->prepare();
		$result = $this->processor->execute( $this->method, $this->url, $this->headers, $this->body, $this->timeout );

		if ( $result ) { // XXX exceptions?
			$this->response_headers = $this->processor->get_response_headers();
			$this->response_body = $this->processor->get_response_body();
			$this->executed = true;

			return true;
		}
		else {
			// processor->execute should throw an Exception which would bubble up
			$this->executed = false;

			return $result;
		}
	}

	public function executed()
	{
		return $this->executed;
	}

	/**
	 * Return the response headers. Raises a warning and returns '' if the request wasn't executed yet.
	 * @todo This should probably just call the selected processor's method, which throws its own error.
	 */
	public function get_response_headers()
	{
		if ( !$this->executed ) {
			throw new Exception( _t('Unable to fetch response headers for a pending request.') );
		}

		return $this->response_headers;
	}

	/**
	 * Return the response body. Raises a warning and returns '' if the request wasn't executed yet.
	 * @todo This should probably just call the selected processor's method, which throws its own error.
	 */
	public function get_response_body()
	{
		if ( !$this->executed ) {
			throw new Exception( _t('Unable to fetch response body for a pending request.') );
		}

		return $this->response_body;
	}

	/**
	 * Remove anchors (#foo) from given URL.
	 */
	private function strip_anchors( $url )
	{
		return preg_replace( '/(#.*?)?$/', '', $url );
	}

	/**
	 * Call the filter hook.
	 */
	private function __filter( $data, $url )
	{
		return Plugins::filter( 'remoterequest', $data, $url );
	}

	/**
	 * Merge query params from the URL with given params.
	 * @param string $url The URL
	 * @param string $params An associative array of parameters.
	 */
	private function merge_query_params( $url, $params )
	{
		$urlparts = InputFilter::parse_url( $url );

		if ( ! isset( $urlparts['query'] ) ) {
			$urlparts['query'] = '';
		}

		if ( ! is_array( $params ) ) {
			parse_str( $params, $params );
		}

		$urlparts['query'] = http_build_query( array_merge( Utils::get_params( $urlparts['query'] ), $params ), '', '&' );

		return InputFilter::glue_url( $urlparts );
	}

	/**
	 * Static helper function to quickly fetch an URL, with semantics similar to
	 * PHP's file_get_contents. Does not support
	 *
	 * Returns the content on success or false if an error occurred.
	 *
	 * @param string $url The URL to fetch
	 * @param bool $use_include_path whether to search the PHP include path first (unsupported)
	 * @param resource $context a stream context to use (unsupported)
	 * @param int $offset how many bytes to skip from the beginning of the result
	 * @param int $maxlen how many bytes to return
	 * @return string description
	 */
	public static function get_contents( $url, $use_include_path = false, $context = null, $offset =0, $maxlen = -1 )
	{
		try {
			$rr = new RemoteRequest( $url );
			if ( $rr->execute() === true) {
				return ( $maxlen != -1
					? MultiByte::substr( $rr->get_response_body(), $offset, $maxlen )
					: MultiByte::substr( $rr->get_response_body(), $offset ) );
			}
			else {
				return false;
			}
		}
		catch ( Exception $e ) {
			// catch any exceptions to try and emulate file_get_contents() as closely as possible.
			// if you want more control over the errors, instantiate RemoteRequest manually
			return false;
		}
	}

}

?>
