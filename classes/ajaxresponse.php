<?php
/**
 * @package Habari
 *
 */

/**
 * Handles creating ajax responses, creating them in the standard format.
 *
 */
class AjaxResponse
{
	
	private $data;
	private $response_code;
	private $message;
	private $callback = null;
	private $html = null;
	
	/* By default, we have a successful operation, with no data to return. */
	function __construct( $response_code = 200, $message = null, $data = null )
	{
		$this->response_code = $response_code;
		$this->message = $message;
		$this->data = $data;
	}
	
	public function __set( $var, $val )
	{
		switch ( $var ) {
			case 'response_code':
			case 'message':
			case 'data':
			case 'callback':
				$this->$var = $val;
				break;
		}
	}
	
	public function html( $name, $value )
	{
		if ( empty( $this->html ) ) {
			$this->html = array( $name => $value );
		}
		else {
			$this->html[$name] = $value;
		}
	}

	public function out()
	{
		$ret_array = array(
			'response_code' => $this->response_code,
			'message' => $this->message,
			'data' => $this->data,
		);

		// if some callback js has been provided, include that too.
		if ( $this->callback != null ) {
			$ret_array['habari_callback'] = $this->callback;
		}
		if ( !empty( $this->html ) ) {
			$ret_array['html'] = $this->html;
		}
		
		header( 'Content-type: application/json' );
		echo json_encode( $ret_array );
	}

}

?>
