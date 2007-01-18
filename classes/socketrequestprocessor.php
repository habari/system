<?php

/**
 * RequestProcessor using sockets (fsockopen).
 */
class SocketRequestProcessor implements RequestProcessor
{
	private $response_body= '';
	private $response_headers= '';
	private $executed= FALSE;
	
	/**
	 * Maximum number of redirects to follow.
	 */
	private $max_redirs= 5;
	
	private $redir_count= 0;
	
	public function execute( $method, $url, $headers, $body, $timeout )
	{
		list( $response_headers, $response_body )= $this->_request( $method, $url, $headers, $body, $timeout );
		
		$this->response_headers= $response_headers;
		$this->response_body= $response_body;
		$this->executed=TRUE;
		
		return TRUE;
	}
	
	private function _request( $method, $url, $headers, $body, $timeout )
	{
		$urlbits= parse_url( $url );
		
		if ( !isset( $urlbits['port'] ) ) {
			$urlbits['port']= 80;
		}
		
		return $this->_work( $method, $urlbits, $headers, $body, $timeout );
	}
	
	private function _work( $method, $urlbits, $headers, $body, $timeout )
	{
		$_errno= 0;
		$_errstr= '';
		
		$fp= fsockopen( $urlbits['host'], $urlbits['port'], $_errno, $_errstr, $timeout );
		
		if ( !$fp ) {
			return Error::raise( sprintf( 'Could not connect to %s:%d. Aborting.', $urlbits['host'], $urlbits['port'] ) );
		}
		
		// timeout to fsockopen() only applies for connecting
		stream_set_timeout( $fp, $timeout );
		
		// fix headers
		$headers['Host']= $urlbits['host'];
		$headers['Connection']= 'close';
		
		// merge headers into a list
		$merged_headers= array();
		foreach ( $headers as $k => $v ) {
			$merged_headers[]= $k . ': ' . $v;
		}
		
		// build the request
		$request= array();
		
		$request[]= "{$method} {$urlbits['path']} HTTP/1.1";
		$request= array_merge( $request, $merged_headers );
		
		$request[]= '';
		
		if ( $method === 'POST' ) {
			$request[]= $body;
		}
		
		$request[]= '';
		
		$out= implode( "\r\n", $request );
				
		if ( ! fwrite( $fp, $out, strlen( $out ) ) ) {
			return Error::raise( 'Error writing to socket.' );
		}
		
		$in= '';
		
		while ( ! feof( $fp ) ) {
			$in.= fgets( $fp, 1024 );
		}
		
		fclose( $fp );
		
		list( $header, $body )= explode( "\r\n\r\n", $in );
		
		preg_match( '|^HTTP/1\.[01] ([1-5][0-9][0-9]) ?(.*)|', $header[0], $status_matches );
		
		if ( $status_matches[1] == '301' || $status_matches[1] == '302' ) {
			if ( preg_match( '|^Location: (.+)$|si', $header, $location_matches ) ) {
				$redirect_url= $location_matches[1];
				
				$redirect_urlbits= parse_url( $redirect_url );
				
				if ( !isset( $redirect_url['host'] ) ) {
					$redirect_urlbits['host']= $urlbits['host'];
				}
				
				$this->redir_count++;
				
				if ( $this->redir_count > $this->max_redirs ) {
					return Error::raise( 'Maximum number of redirections exceeded.' );
				}
				
				return $this->_work( $method, $urlbits, $headers, $body, $timeout );
			}
			else {
				return Error::raise( 'Redirection response without Location: header.' );
			}
		}
		
		return array( $header, $body );
	}

	
	public function get_response_body()
	{
		if ( ! $this->executed ) {
			return Error::raise( 'Request did not yet execute.' );
		}
		
		return $this->response_body;
	}
	
	public function get_response_headers()
	{
		if ( ! $this->executed ) {
			return Error::raise( 'Request did not yet execute.' );
		}
		
		return $this->response_headers;
	}
}

?>

