<?php

/**
 * RequestProcessor using CURL.
 */
class CURLRequestProcessor implements RequestProcessor
{
	private $response_body= '';
	private $response_headers= '';
	private $executed= FALSE;
	
	public function execute( $method, $url, $headers, $body, $timeout )
	{
		$merged_headers= array();
		foreach ( $headers as $k => $v ) {
			$merged_headers[]= $k . ': ' . $v;
		}
		
		$ch= curl_init();
		
		curl_setopt( $ch, CURLOPT_URL, $url ); // The URL.
		curl_setopt( $ch, CURLOPT_HEADER, true ); // The header of the response.
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true ); // Follow 302's and the like.
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // Return the data from the stream.
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $merged_headers ); // headers to send
		
		if ( $method === 'POST' ) {
			curl_setopt( $ch, CURLOPT_POST, true ); // POST mode.
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
		}
		
		$data= curl_exec( $ch );
		
		if ( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) !== 200 ) {
			return Error::raise( sprintf( 'Bad return code (%1$d) for: %2$s', 
				curl_getinfo( $ch, CURLINFO_HTTP_CODE ), 
				$url ) 
			);
		}
		
		curl_close( $ch );
		
		list( $header, $body )= explode( "\r\n\r\n", $data, 2 );
		
		$this->response_headers= $header;
		$this->response_body= $body;
		$this->executed= TRUE;
		
		return TRUE;
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