<?php
class HabariError extends ErrorException {
	
	protected $context;
	
	public function __construct( $message = null, $code = null, $severity = null, $filename = null, $lineno = null, $context = null ) {
		parent::__construct( $message, $code, $severity, $filename, $lineno );
		$this->context = $context;
	}
	
}
?>