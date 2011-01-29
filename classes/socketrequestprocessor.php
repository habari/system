<?php
/**
 * @package Habari
 *
 */

/**
 * RequestProcessor using sockets (fsockopen).
 */
class SocketRequestProcessor implements RequestProcessor
{
	private $response_body = '';
	private $response_headers = '';
	private $executed = false;

	private $redir_count = 0;

	public function execute( $method, $url, $headers, $body, $config )
	{
		// let any exceptions thrown just bubble up
		$result = $this->_request( $method, $url, $headers, $body, $config );

		if ( $result ) {
			list( $response_headers, $response_body )= $result;
			$this->response_headers = $response_headers;
			$this->response_body = $response_body;
			$this->executed = true;

			return true;
		}
		else {
			return $result;
		}
	}

	private function _request( $method, $url, $headers, $body, $config )
	{
		$urlbits = InputFilter::parse_url( $url );

		return $this->_work( $method, $urlbits, $headers, $body, $config );
	}

	/**
	 * @todo Does not honor timeouts on the actual request, only on the connect() call.
	 * @todo Does not use MultiByte-safe methods for parsing input and output - we don't know what the data we're screwing up is!
	 */
	private function _work( $method, $urlbits, $headers, $body, $config )
	{
		$_errno = 0;
		$_errstr = '';

		if ( !isset( $urlbits['port'] ) || $urlbits['port'] == 0 ) {
			if ( array_key_exists( $urlbits['scheme'], Utils::scheme_ports() ) ) {
				$urlbits['port'] = Utils::scheme_ports( $urlbits['scheme'] );
			}
			else {
				// todo: Error::raise()?
				$urlbits['port'] = 80;
			}
		}

		if ( !in_array( $urlbits['scheme'], stream_get_transports() ) ) {
			$transport = ( $urlbits['scheme'] == 'https' ) ? 'ssl' : 'tcp';
		}
		else {
			$transport = $urlbits['scheme'];
		}
		
		if ( $config['proxy']['server'] && ! in_array( $urlbits['host'], $config['proxy']['exceptions'] ) ) {
			$fp = @fsockopen( $transport . '://' . $config['proxy']['server'], $config['proxy']['port'], $_errno, $_errstr, $config['connect_timeout'] );
		}
		else {
			$fp = @fsockopen( $transport . '://' . $urlbits['host'], $urlbits['port'], $_errno, $_errstr, $config['connect_timeout'] );
		}

		if ( $fp === false ) {
			if ( $config['proxy']['server'] ) {
				throw new Exception( _t( 'Error %d: %s while connecting to %s:%d', array( $_errno, $_errstr, $config['proxy']['server'], $config['proxy']['port'] ) ) );
			}
			else {
				throw new Exception( _t( 'Error %d: %s while connecting to %s:%d', array( $_errno, $_errstr, $urlbits['host'], $urlbits['port'] ) ) );
			}
		}

		// timeout to fsockopen() only applies for connecting
		stream_set_timeout( $fp, $config['timeout'] );

		// fix headers
		if ( $config['proxy']['server'] && ! in_array( $urlbits['host'], $config['proxy']['exceptions'] ) ) {
			$headers['Host'] = "{$config['proxy']['server']}:{$config['proxy']['port']}";
			if ( $config['proxy']['username'] ) {
				$headers['Proxy-Authorization'] = 'Basic ' . base64_encode( "{$config['proxy']['username']}:{$config['proxy']['password']}" );
			}
		} else {
			$headers['Host'] = $urlbits['host'];
		}
		
		$headers['Connection'] = 'close';

		// merge headers into a list
		$merged_headers = array();
		foreach ( $headers as $k => $v ) {
			$merged_headers[] = $k . ': ' . $v;
		}

		// build the request
		$request = array();
		$resource = $urlbits['path'];
		if ( isset( $urlbits['query'] ) ) {
			$resource.= '?' . $urlbits['query'];
		}
		
		if ( $config['proxy']['server'] && ! in_array( $urlbits['host'], $config['proxy']['exceptions'] ) ) {
			$resource = $urlbits['scheme'] . '://' . $urlbits['host'] . $resource;
		}

		$request[] = "{$method} {$resource} HTTP/1.1";

		$request = array_merge( $request, $merged_headers );

		$request[] = '';

		if ( $method === 'POST' ) {
			$request[] = $body;
		}

		$request[] = '';

		$out = implode( "\r\n", $request );

		if ( ! fwrite( $fp, $out, strlen( $out ) ) ) {
			throw new Exception( _t( 'Error writing to socket.' ) );
		}

		$in = '';

		while ( ! feof( $fp ) ) {
			$in.= fgets( $fp, 1024 );
		}

		fclose( $fp );

		list( $header, $body )= explode( "\r\n\r\n", $in );

		// to make the following REs match $ correctly
		// and thus not break parse_url
		$header = str_replace( "\r\n", "\n", $header );

		preg_match( '#^HTTP/1\.[01] ([1-5][0-9][0-9]) ?(.*)#', $header, $status_matches );

		if ( $status_matches[1] == '301' || $status_matches[1] == '302' ) {
			if ( preg_match( '|^Location: (.+)$|mi', $header, $location_matches ) ) {
				$redirect_url = $location_matches[1];

				$redirect_urlbits = InputFilter::parse_url( $redirect_url );

				if ( !isset( $redirect_url['host'] ) ) {
					$redirect_urlbits['host'] = $urlbits['host'];
				}

				$this->redir_count++;

				if ( $this->redir_count > $config['max_redirs'] ) {
					throw new Exception( _t( 'Maximum number of redirections exceeded.' ) );
				}

				return $this->_work( $method, $redirect_urlbits, $headers, $body, $config );
			}
			else {
				throw new Exception( _t( 'Redirection response without Location: header.' ) );
			}
		}

		if ( preg_match( '|^Transfer-Encoding:.*chunked.*|mi', $header ) ) {
			$body = $this->_unchunk( $body );
		}

		return array( $header, $body );
	}

	private function _unchunk( $body )
	{
		/* see <http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html> */
		$result = '';
		$chunk_size = 0;

		do {
			$chunk = explode( "\r\n", $body, 2 );
			list( $chunk_size_str, )= explode( ';', $chunk[0], 2 );
			$chunk_size = hexdec( $chunk_size_str );

			if ( $chunk_size > 0 ) {
				$result .= MultiByte::substr( $chunk[1], 0, $chunk_size );
				$body = MultiByte::substr( $chunk[1], $chunk_size+1 );
			}
		}
		while ( $chunk_size > 0 );
		// this ignores trailing header fields

		return $result;
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
