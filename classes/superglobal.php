<?php

/**
 * SuperGlobals class
 *
 */

class SuperGlobal extends ArrayObject
{
	protected $values = array();

	public function __construct(array $array)
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
		
		if (!$revert) {
			// our work has already been done
			return;
		}
		
		if ( get_magic_quotes_gpc() ) {
			$_GET = Utils::stripslashes($_GET);
			$_POST = Utils::stripslashes($_POST);
			$_COOKIE = Utils::stripslashes($_COOKIE);
		}

		$_GET = new SuperGlobal($_GET);
		$_POST = new SuperGlobal($_POST);
		$_COOKIE = new SuperGlobal($_COOKIE);
		unset($_REQUEST);

		$revert = false;
	}

	public function offsetGet($index)
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
	 * Merges the contents of one or more arrays or ArrayObjects with this SuperGlobal
	 *
	 * @param mixed One or more array-like structures to merge into this array.
	 * @return SuperGlobal The merged array
	 */
	public function merge()
	{
		$args = func_get_args();
		$cp = $this->getArrayCopy();
		foreach($args as $ary) {
			if(is_array($ary)) {
				foreach($ary as $key => $value) {
					if(is_numeric($key)) {
						$cp[] = $value;
					}
					else {
						$cp[$key] = $value;
					}
				}
			}
			elseif($ary instanceof ArrayObject) {
				$arycp = $ary->getArrayCopy();  // Don't trigger offsetGet for ArrayObject
				foreach($ary as $key => $value) {
					if(is_numeric($key)) {
						$cp[] = $value;
					}
					else {
						$cp[$key] = $value;
					}
				}
			}
			else {
				$cp[] = $ary;
			}
		}
		return new SuperGlobal($cp);
	}

	/**
	 * Filters this SuperGlobal based on an array or arrays of keys
	 *
	 * @param mixed An array of key values that should be returned, or a string of a key value to be returned
	 * @return SuperGlobal The values from this array that match the supplied keys
	 */
	public function filter_keys()
	{
		$keys = array();
		$args = func_get_args();
		foreach($args as $ary) {
			if(!is_array($ary)) {
				$ary = array($ary);
			}
			$keys = array_merge($keys, array_values($ary));
		}
		$cp = $this->getArrayCopy();
		$cp = array_intersect_key($cp, array_flip($keys));
		return new SuperGlobal($cp);
	}
}

?>