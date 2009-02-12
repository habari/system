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
 * XMLRPC Client
 */
class RPCClient
{
	private $url;
	private $method;
	private $params;
	private $request_body;
	private $result = FALSE;

	/**
	 * @param string URL
	 * @param string method to call
	 * @param string method's arguments
	 */
	function __construct( $url, $method, $params )
	{
		if ( ! function_exists( 'xmlrpc_encode_request' ) ) {
			return Error::raise( _t('xmlrpc extension not found') );
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
		$rr->add_header( 'Content-Type: text/xml' );
		$rr->set_body( $this->request_body );
		
		// should throw an error on failure
		$rr->execute();
		// in that case, we should never get here
		
		$this->result = xmlrpc_decode($rr->get_response_body());
	}
	
	/**
	 * Return the (decoded) result of the request, or FALSE if the result was invalid.
	 */
	public function get_result()
	{
		return $this->result;
	}
}

?>
