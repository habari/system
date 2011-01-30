<?php
/**
 * @package Habari
 *
 */

/**
 * Create an XMLRPC client connection
 *
 * To Use:
 * <code>
 * // Create the object using the XMLRPC entrypoint and scope.
 * $rpc= new XMLRPCClient( 'http://rpc.pingomatic.com', 'weblogUpdates' );
 * // Make a weblogUpdates.ping call on the client.
 * $result= $rpc->ping( 'Blog name', 'http://example.com' );
 *
 * // Change the scope on the existing client object.
 * $rpc->set_scope( 'system' );
 * // Make a system.listMethods call on the client.
 * $methods= $rpc->listMethods();
 * </code>
 *
 * @todo Handle XMLRPC fault results by returning some error value.
 */
class XMLRPCClient
{
	private $entrypoint;
	private $scope = 'system';

	/**
	 * Create the XMLRPCClient
	 *
	 * @param string $xmlrpc_entrypoint The entrypoint of the remote server
	 */
	public function __construct( $xmlrpc_entrypoint, $scope = null )
	{
		$this->entrypoint = $xmlrpc_entrypoint;
		if ( isset( $scope ) ) {
			$this->scope = $scope;
		}
	}

	/**
	 * Set the scope of any subsequent function calls
	 * The default scope is 'system'.
	 *
	 * @param string $scope The scope to use
	 */
	public function set_scope( $scope )
	{
		$this->scope = $scope;
	}

	/**
	 * Allow method overloading for this class.
	 * This method allows any method name to be called on this object.  The method
	 * called is the method called via RPC, within the scope defined in $this->scope.
	 *
	 * @param string $fname The function name to call
	 * @param array $args An array of arguments that were called with the function
	 * @return array The result array
	 */
	public function __call( $fname, $args )
	{
		if ( $this->scope != '' ) {
			$rpc_method = "{$this->scope}.{$fname}";
		}
		else {
			$rpc_method = $fname;
		}

		$rpx = new SimpleXMLElement( '<methodCall/>' );
		$rpx->addChild( 'methodName', $rpc_method );
		if ( count( $args ) > 0 ) {
			$params = $rpx->addchild( 'params' );
			foreach ( $args as $arg ) {
				$param = $params->addchild( 'param' );
				XMLRPCUtils::encode_arg( $param, $arg );
			}
		}

		$request = new RemoteRequest( $this->entrypoint, 'POST' );
		$request->add_header( 'Content-Type: text/xml;charset=utf-8' );
		$request->set_body( $rpx->asXML() );

		$request->execute();

		if ( $request->executed() ) {
			$response = $request->get_response_body();
			// @todo this should use the MultiByte class, not directly call mb_string functions
			$enc = mb_detect_encoding( $response );
			$responseutf8 = mb_convert_encoding( $response, 'UTF-8', $enc );
			try {
				// @todo this should use libxml_use_internal_errors() instead of trying to hide the PHP warning see the plugin info parsing code for an example
				$bit = ini_get( 'error_reporting' );
				error_reporting( $bit && !E_WARNING );
				$responsexml = new SimpleXMLElement( $responseutf8 );
				error_reporting( $bit );
				$tmp = $responsexml->xpath( '//params/param/value' );
				if ( !$responsestruct = reset( $tmp ) ) {
					$tmp = $responsexml->xpath( '//fault/value' );
					if ( !$responsestruct = reset( $tmp ) ) {
						throw new Exception( _t( 'Invalid XML response.' ) );
					}
				}
				return XMLRPCUtils::decode_args( $responsestruct );
			}
			catch ( Exception $e ){
				//Utils::debug( $response, $e );
				error_reporting( $bit );
				return false;
			}
		}
	}

	/**
	 * Allow scoped functions to be called in shorthand
	 * Example:
	 * <code>
	 * // Create the XMLRPC object
	 * $rpc= new XMLRPCClient( 'http://rpc.pingomatic.com' );
	 * // Call weblogUpdates.ping RPC call
	 * $rpc->weblogUpdates->ping( 'Blog name', 'http://example.com' );
	 * </code>
	 *
	 * @param string $scope The scope to set this object to.
	 * @return XMLRPCClient This object instance
	 **/
	public function __get( $scope )
	{
		$this->set_scope( $scope );
		return $this;
	}

	/**
	 * Convenience method to create a new XMLRPCClient object
	 * Example:
	 * <code>
	 * XMLRPCClient::open( 'http://rpc.pingomatic.com' )->weblogUpdates->ping( 'Blog name', 'http://example.com' );
	 * </code>
	 * @param string $xmlrpc_entrypoint The entrypoint of the remote server
	 * @return XMLRPCClient The created client object
	 **/
	public static function open( $xmlrpc_entrypoint )
	{
		return new XMLRPCClient( $xmlrpc_entrypoint );
	}

}

?>
