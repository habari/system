<?php

class AdminPage
{
	protected $request_method;
	protected $handler;
	protected $handler_vars = array();
	protected $theme;
	
	public function __construct( $request_method, AdminHandler $handler, Theme $theme = NULL )
	{
		$this->request_method = $request_method;
		$this->handler = $handler;
		$this->handler_vars = $handler->handler_vars;
		$this->theme = $theme;
	}
	
	// @todo fix allow list for new action methods.
	public function __call( $method, $args )
	{
		$class = get_class($this);
		Plugins::act( "{$class}_{$method}", $this, $args );
		header( 'HTTP/1.1 405 Method Not Allowed', true, 405 );
		// Build list of accepted methods and send allow header as per spec..
		// Seperate list for AJAX and non-AJAX
		$allow = array();
		foreach ( get_class_methods($this) as $fn ) {
			if ( strpos($fn, 'act_') !== 0 ) {
				continue;
			}
			if ( strpos($fn, 'act_ajax') === strpos($method, 'act_ajax') ) {
				$allow[] = strtoupper( substr( $fn, strrpos($fn, '_') + 1 ) );
			}
			elseif ( strpos($fn, 'act_request') === strpos($method, 'act_request') ) {
				$allow[] = strtoupper( substr( $fn, strrpos($fn, '_') + 1 ) );
			}
		}
		header( 'Allow: ' . implode(',', $allow) );
		_e( '%s Request Method Not Allowed Here.', array( strtoupper($this->request_method) ) );
	}
	
	public function act( $action, $method )
	{
		$this->{"act_{$action}_{$method}"}();
	}
	
	public function act_ajax( $action, $method )
	{
		$this->{"act_ajax_{$action}_{$method}"}();
	}
	
	protected function display( $template_name )
	{
		$this->theme->display( $template_name );
	}
}