<?php
/**
 * @package Habari
 *
 */

/**
 * A custom exception for XMLRPC failure results
 */
class XMLRPCException extends Exception
{
	/**
	 * The exception constructor, called to create this excpetion
	 * @param integer $code The error code to produce
	 * @param string $message Optional The message to display with the error
	 **/
	public function __construct( $code, $message = null )
	{
		// make sure everything is assigned properly
		if ( empty( $message ) ) {
			$message = $this->get_message( $code );
		}
		parent::__construct( $message, $code );
	}
	
	/**
	 * Return a test-based error description for a numeric error code
	 * @param integer $code The error code to search for
	 * @return string A localized text-based error message.
	 **/
	private function get_message( $code )
	{
		switch ( $code ) {

		//Generic XMLRPC errors
			case -32700:
				return _t( 'parse error. not well formed' );
			case -32701:
				return _t( 'parse error. unsupported encoding' );
			case -32702:
				return _t( 'parse error. invalid character for encoding' );
			case -32600:
				return _t( 'server error. invalid xml-rpc. not conforming to spec.' );
			case -32601:
				return _t( 'server error. requested method not found' );
			case -32602:
				return _t( 'server error. invalid method parameters' );
			case -32603:
				return _t( 'server error. internal xml-rpc error' );
			case -32500:
				return _t( 'application error' );
			case -32400:
				return _t( 'system error' );
			case -32300:
				return _t( 'transport error' );

			// Pingback errors
			case 16:
				return _t( 'The source URI does not exist.' );
			case 17:
				return _t( 'The source URI does not contain a link to the target URI, and so cannot be used as a source.' );
			case 32:
				return _t( 'The specified target URI does not exist.' );
			case 33:
				return _t( 'The specified target URI cannot be used as a target.' );
			case 48:
				return _t( 'The pingback has already been registered.' );
			case 49:
				return _t( 'Access denied.' );
			case 50:
				return _t( 'The server could not communicate with an upstream server, or received an error from an upstream server, and therefore could not complete the request.' );

			// Additional standard errors
			case 1:
				return _t( 'This XMLRPC server only accepts POST requests.' );

			default:
				return Plugins::filter( 'xmlrpcexception_get_message', _t( 'Unknown XMLRPC Exception' ), $code );
		}
	}
	
	/**
	 * Represent this exception as a string
	 **/
	public function __toString()
	{
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

	/**
	 * Send an XML-RPC fault output and quit.
	 **/
	public function output_fault_xml()
	{
		$xmltext = '<?xml version="1.0"?'.'><methodResponse><fault><value><struct><member><name>faultCode</name><value><int>' . $this->code . '</int></value></member><member><name>faultString</name><value><string>' . $this->message . '</string></value></member></struct></value></fault></methodResponse>';
		ob_end_clean();
		header( 'Content-Type: text/xml;charset=utf-8' );
		echo trim( $xmltext );
		exit;
	}
}
?>
