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
		list( $headers, $body )= _request( $method, $url, $headers, $body, $timeout );
		
		$this->response_headers= $headers;
		$this->response_body= $body;
		$this->executed=TRUE;
		
		return TRUE;
	}
	
	private function _request( $method, $url, $headers, $body, $timeout )
	{
		$urlbits= parse_url( $url );
		
		if ( !isset( $urlbits['port'] ) ) {
			$urlbits['port']= 80;
		}
		
		return _work( $method, $urlbits, $headers, $body, $timeout );
	}
	
	private function _work( $method, $urlbits, $headers, $body, $timeout )
	{
		$_errno= 0;
		$_errstr= '';
		
		$fp= fsockopen( $urlbits['host'], $urlbits['port'], $_errno, $_errstr, $timeout );
		
		// timeout to fsockopen() only applies for connecting
		stream_set_timeout( $fp, $timeout );
		
		if ( !$fp ) {
			return Error::raise( sprintf( 'Could not connect to %s:%d. Aborting.', $urlbits['host'], $urlbits['port'] ) );
		}
		
		// fix host
		$headers['Host']= $urlbits['host'];
		
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
		
		if ( ! fwrite( $fp, implode( "\r\n", $request ) ) ) {
			return Error::raise( 'Error writing to socket.' );
		}
		
		$in= '';
		
		while ( ! feof( $fp ) ) {
			$in.= fgets( $fp, 1024 );
		}
		
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

