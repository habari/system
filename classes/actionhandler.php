<?php

/**
 * A base class handler for URL-based actions.
 *  
 * @package Habari
 **/  
class ActionHandler {
	public $action= '';               // string name of action
	public $handler_vars= array();    // internal array of handler variables (state info)

	/**
	 * All handlers must implement act() to conform to handler API.
	 * This is the default implementation of act(), which attempts
	 * to call a class member method of $this->act_$action().  Any
	 * subclass is welcome to override this default implementation.
	 *
	 * @param string $action the action that was in the URL rule
	 */	 	 	 	 
	public function act($action) {
		$this->action= $action;
		
		$action_method= 'act_' . $action;
		$before_action_method= 'before_' . $action_method;
		$after_action_method= 'after_' . $action_method;
		
		if (method_exists($this, $action_method)) {
			if (method_exists($this, $before_action_method)) {
				$this->$before_action_method();
			}
			Plugins::act( $before_action_method );
			
			$this->$action_method();
			
			Plugins::act( $after_action_method );
			if (method_exists($this, $after_action_method)) {
				$this->$after_action_method();
			}
		}
	}

	/**
	 * Helper method to convert calls to $handler->my_action()
	 * to $handler->act('my_action');
	 * 
	 * @param string $function function name
	 * @param array $args function arguments
	 */
	public function __call($function, $args) {
		$this->handler_vars= array_merge($this->handler_vars, $args);
		return $this->act($function);
	}
	
}

?>
