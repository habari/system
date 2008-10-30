<?php
class HabariError extends ErrorException {
	
	protected $context;
	
	public function __construct( $message = null, $code = null, $severity = null, $filename = null, $lineno = null, $context = null ) {
		parent::__construct( $message, $code, $severity, $filename, $lineno );
		$this->context = $context;
	}
	
	public function getContext() {
		return $this->context;
	}
	
	public function setCode($code) {
		$this->code = $code;
	}
	
	public function setFile($filename) {
		$this->file = $filename;
	}
	
	public function setLine($lineno) {
		$this->line = $lineno;
	}
	
	public function setContext($context) {
		$this->context = $context;
	}
	
}

/*
I don't think we need specialized errors, errors can't be specialized via trigger_error()
and we don't want people throwing errors, they should throw exceptions if needed.

class DBError extends HabariError {}
class PluginError extends HabariError {}
class QueryError extends HabariError {}
class SessionError extends HabariError {}
class ThemeError extends HabariError {}
*/
?>