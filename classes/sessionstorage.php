<?php

	namespace Habari;

	class SessionStorage extends \ArrayObject {

		public $changed = false;
		public $id = null;

		/**
		 * (PHP 5 &gt;= 5.0.0)<br/>
		 * Offset to set
		 * @link http://php.net/manual/en/arrayaccess.offsetset.php
		 * @param mixed $offset <p>
		 * The offset to assign the value to.
		 * </p>
		 * @param mixed $value <p>
		 * The value to set.
		 * </p>
		 * @return void
		 */
		public function offsetSet($offset, $value)
		{
			parent::offsetSet($offset, $value);

			// mark the session as changed, so we trigger a write
			$this->changed = true;

			// if we don't have a session_id already, we are "starting" a session
			if ( is_null( $this->id ) ) {
				Session::create();
			}
		}

	}

?>