<?php
/**
 * @package Habari
 *
 */

/**
 * XMLRPC Client
 */
class RPCClient
{
	private $url;
	private $method;
	private $params;
	private $request_body;
	private $result = false;

	/**
	 * @param string URL
	 * @param string method to call
	 * @param string method's arguments
	 */
	function __construct( $url, $method, $params )
	{
		if ( ! function_exists( 'xmlrpc_encode_request' ) ) {
			return Error::raise( _t( 'xmlrpc extension not found' ) );
		}
		$this->url = $url;
		$this->method = $method;
		$this->params = $params;

		$this->request_body = xmlrpc_encode_request( $method, $params );
	}

	/**
	 * Execute the request. Populates result field.
	 */
	public function execute()
	{
		$rr = new RemoteRequest( $this->url, 'POST' );
		$rr->add_header( 'Content-Type: text/xml;charset=utf-8' );
		$rr->set_body( $this->request_body );

		// should throw an error on failure
		$rr->execute();
		// in that case, we should never get here

		$this->result = xmlrpc_decode( $rr->get_response_body() );
	}

	/**
	 * Return the (decoded) result of the request, or false if the result was invalid.
	 */
	public function get_result()
	{
		return $this->result;
	}
}

?>
