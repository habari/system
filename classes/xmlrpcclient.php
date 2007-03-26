<?php

/**
 * Create an XMLRPC client connection
 * 
 * To Use:
 * <code>
 * // Create the object using the XMLRPC entrypoint and scope.
 * $rpc= new XMLRPCClient('http://rpc.pingomatic.com', 'weblogUpdates');
 * // Make a weblogUpdates.ping call on the client.
 * $result= $rpc->ping('Blog name', 'http://example.com');
 * 
 * // Change the scope on the existing client object.
 * $rpc->set_scope('system');
 * // Make a system.listMethods call on the client.
 * $methods= $rpc->listMethods();
 * </code>
 *
 * @package Habari
 * @todo Handle XMLRPC fault results by returning some error value. 
 */

class XMLRPCClient
{
	private $entrypoint;
	private $scope = 'system';

	/**
	 * Create the XMLRPCClient
	 * 
	 * @param string $xmlrpc_entrypoint The entrypoint of te remote server
	 */
	public function __construct($xmlrpc_entrypoint, $scope = null) 
	{
		$this->entrypoint = $xmlrpc_entrypoint;
		if(isset($scope)) {
			$this->scope = $scope;
		}
	}
	
	/**
	 * Set the scope of any subsequent function calls
	 * The default scope is 'system'.
	 * 
	 * @param string $scope The scope to use
	 */
	public function set_scope($scope) 
	{
		$this->scope = $scope;
	}
	
	/**
	 * Encode a variable value into the parameters of the XML tree
	 * 
	 * @param SimpleXMLElement $params The parameter to add the value elements to.
	 * @param mixed $arg The value to encode
	 */
	private function _encode_arg($params, $arg) 
	{
		switch(true) {
		case is_array($arg):
			$data = $params->add_child('array')->add_child('data');
			foreach($arg as $element) {
				$this->_encode_arg($data, $element);
			}
			break;
		case ($arg instanceof XMLRPCDate):
			$params->addchild('value')->addchild('dateTime.iso8601', date('c', strtotime($arg->date)));
			break;				
		case ($arg instanceof XMLRPCBinary):
			$params->addchild('value')->addchild('base64', base64_encode($arg->data));
			break;				
		case ($arg instanceof XMLRPCStruct):
			$struct = $params->addchild('struct');
			$object_vars = $arg->get_fields();
			foreach($object_vars as $field) {
				$member = $struct->add_child('member');
				$member->addchild('name', $field);
				$this->_encode_arg($member, $arg->$field);
			}
			break;				
		case is_object($arg):
			$struct = $params->add_child('struct');
			$object_vars = get_object_vars($arg);
			foreach($object_vars as $key=>$value) {
				$member = $struct->add_child('member');
				$member->addchild('name', $key);
				$this->_encode_arg($member, $value);
			}
			break;
		case is_integer($arg):
			$params->addchild('value')->addchild('i4', $arg);
			break;
		case is_bool($arg):
			$params->addchild('value')->addchild('boolean', $arg ? '1' : '0');
			break;
		case is_string($arg):
			$params->addchild('value')->addchild('string', $arg);
			break;
		case is_float($arg):
			$params->addchild('value')->addchild('double', $arg);
			break;
		}
	}
	
	/**
	 * Decode the value of a response parameter using the datatype specified in the XML element.
	 * 
	 * @param SimpleXMLElement $value A "value" element from the XMLRPC response
	 * @return mixed The value of the element, decoded from the datatype specified in the xml element
	 */
	private function _decode_args($value)
	{
		switch($value->getName()) {
		case 'array':
			$result_array = array();
			foreach($value->xpath('//data/value/*') as $array_value) {
				$result_array[] = $this->_decode_args($array_value);
			}
			return $result_array;
			break;
		case 'struct':
			$result_struct = new XMLRPCStruct();
			foreach($value->xpath('//member') as $struct_value) {
				$property_name = (string)$struct_value->name;
				$children = $struct_value->value->children();
				if(count($children) > 0) {
					$result_struct->$property_name = $this->_decode_args($children[0]);
				}
				else {
					$result_struct->$property_name = (string)$struct_value->value;
				}
			}
			return $result_struct;
			break;
		case 'string':
			return (string)$value;
		case 'i4':
		case 'integer':
			return (int)$value;
		case 'double':
			return (double)$value;
		case 'boolean':
			return ((int)$value == 1) ? true : false;
		case 'dateTime.iso8601':
			return strtotime((string)$value);
		}
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
	public function __call($fname, $args) 
	{
		if($this->scope != '') {
			$rpc_method = "{$this->scope}.{$fname}";
		}
		else {
			$rpc_method = $fname;
		}
	
		$rpx = new SimpleXMLElement('<methodCall/>');
		$rpx->addChild('methodName', $rpc_method);
		if(count($args) > 0) {
			$params = $rpx->addchild('params');
			foreach($args as $arg) {
				$param = $params->addchild('param');
				$this->_encode_arg($param, $arg);
			}
		}

		$request= new RemoteRequest($this->entrypoint, 'POST');
		$request->add_header('Content-Type: text/xml');
		$request->set_body($rpx->asXML());
		
		if($request->execute()) {
			$response = $request->get_response_body();
			try {
				$bit = ini_get('error_reporting');
				error_reporting($bit && !E_WARNING);
				$responsexml = new SimpleXMLElement($response);
				error_reporting($bit);
				return $this->_decode_args(reset($responsexml->xpath('/methodResponse/params/param/value/*')));
			}
			catch (Exception $e){
				Utils::debug($response);
				error_reporting($bit);
				return false;
			}
		}
	}

}

?>