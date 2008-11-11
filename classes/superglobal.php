<?php

/**
 * SuperGlobals class
 *
 */

class SuperGlobal extends ArrayObject
{
	protected $values = array();

	function SuperGlobal($array)
	{
		$values['default'] = array();
		parent::__construct($array);
	}

	/**
	 * Convert $_GET, $_POST, and $_COOKIE into SuperGlobal instances, also kill $_REQUEST
	 *
	 * @return
	 */
	public static function process_gpc()
	{
		/* We should only revert the magic quotes once per page hit */
		static $revert = true;
		if ( get_magic_quotes_gpc() && $revert) {
			$_GET = Utils::stripslashes($_GET);
			$_POST = Utils::stripslashes($_POST);
			$_COOKIE = Utils::stripslashes($_COOKIE);
			$revert = false;
		}

		$_GET = new SuperGlobal($_GET);
		$_POST = new SuperGlobal($_POST);
		$_COOKIE = new SuperGlobal($_COOKIE);
		unset($_REQUEST);
	}

	function offsetGet($index)
	{
		$cp = $this->getArrayCopy();
		if(isset($cp[$index])) {

			if($cp[$index] instanceof String) {
				return $cp[$index];
			}
			else {
				$cp[$index] = new String($cp[$index]);
				$this[$index] = $cp[$index];
				return $cp[$index];
			}
		}
	}

	/**
	 * Merges the contents of one or more arrays or ArrayObjects into this SuperGlobal
	 *
	 * @param mixed One or more array-like structures to merge into this array.
	 */
	function merge()
	{
		$args = func_get_args();
		foreach($args as $ary) {
			if(is_array($ary)) {
				foreach($ary as $key => $value) {
					if(is_numeric($key)) {
						$this[] = $value;
					}
					else {
						$this[$key] = $value;
					}
				}
			}
			elseif($ary instanceof ArrayObject) {
				$arycp = $ary->getArrayCopy();  // Don't trigger offsetGet for ArrayObject
				foreach($ary as $key => $value) {
					if(is_numeric($key)) {
						$this[] = $value;
					}
					else {
						$this[$key] = $value;
					}
				}
			}
			else {
				$this[] = $ary;
			}
		}
	}
}

?>