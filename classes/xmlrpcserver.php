<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
<?php
/**
 * @package Habari
 *
 */

/**
 * A handler for incoming XMLRPC calls - an XMLRPC server
 *
 */
class XMLRPCServer extends ActionHandler
{

	/**
	 * Handle all incoming XMLRPC requests.
	 */
	public function act_xmlrpc_call()
	{
		if( $_SERVER['REQUEST_METHOD'] != 'POST' ) {
			$exception = new XMLRPCException(1);
			$exception->output_fault_xml(); // dies here
		}
		$input = file_get_contents( 'php://input' );

		$xml = new SimpleXMLElement( $input );

		$function = $xml->methodName;
		$params = array();
		$foundParams = $xml->xpath( '//params/param/value' );
		if ( is_array( $foundParams ) ) {
			foreach( $foundParams as $param ) {
				$params[] = XMLRPCUtils::decode_args( $param );
			}
		}

		$returnvalue = false;

		Plugins::register(array($this, 'system_listMethods'), 'xmlrpc', 'system.listMethods');
		$returnvalue = Plugins::xmlrpc("{$function}", $returnvalue, $params, $this);

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

}

?>
