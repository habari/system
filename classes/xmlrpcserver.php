<?php

/**
 * A handler for incoming XMLRPC calls - an XMLRPC server 
 * 
 * @package Habari
 */

class XMLRPCServer extends ActionHandler
{

	/**
	 * Handle all incoming XMLRPC requests.
	 */	 	
	public function act_xmlrpc_call()
	{
		if( $_SERVER['REQUEST_METHOD'] != 'POST' ) {
			$this->output_fault( _t('This XML-RPC server only accepts POST requests.'), 1 );
		}
		$input= file_get_contents( 'php://input' );
		
		$xml= new SimpleXMLElement( $input );
		
		$function = $xml->methodName;
		foreach($xml->xpath('//params/param/value/*') as $param) {
			$params[] = XMLRPCUtils::decode_args($param);
		}

		$returnvalue= false;
		
		Plugins::register(array($this, 'system_listMethods'), 'filter', 'xmlrpc_system.listMethods');

		$returnvalue = Plugins::filter('xmlrpc_' . $function, $returnvalue, $params, $this);

		$response = new SimpleXMLElement('<?xml version="1.0"?'.'><methodResponse><params><param></param></params></methodResponse>');
		XMLRPCUtils::encode_arg($response->params->param, $returnvalue);
		
		ob_end_clean();
		header('Content-Type: text/xml');
		echo trim($response->asXML());
		exit;
	}

	/**
	 * A plugin sink to return a list of XML-RPC methods on a call to system.listMethods  
	 * Allows plugins to add their own methods to the list.
	 * @param mixed $returnvalue The value that will be returned to the remote caller.
	 * @param mixed $params The parameters that were called with the remote call.
	 * @return array An array of supported XML-RPC methods.
	 **/	 	 	 
	public function system_listMethods($returnvalue, $params)
	{
		$res = array(
			'system.listMethods',
		);
		$res = Plugins::filter('xmlrpc_methods', $res);
		return $res;
	}
	
	
	/**
	 * Send an XML-RPC fault output and quit.
	 * @param string $string A human-readable error string.
	 * @param integer $code A machine-readable code.
	 **/	 	 	 	
	private function output_fault($string, $code)
	{
		$xmltext = '<?xml version="1.0"?'.'><methodResponse><fault><value><struct><member><name>faultCode</name><value><int>' . $code . '</int></value></member><member><name>faultString</name><value><string>' . $string . '</string></value></member></struct></value></fault></methodResponse>';
		ob_end_clean();
		header('Content-Type: text/xml');
		echo trim($xmltext);
		exit;
	}	
}

?>
