<?php

	namespace Habari;

	class SessionStorage extends \ArrayObject {

		public $changed = false;
		public $id = null;
		/** @var SessionStorage $parent */
		public $parent = null;

		/**
		 * Construct a new array object
		 * @link http://php.net/manual/en/arrayobject.construct.php
		 * @param array|object $input The input parameter accepts an array or an Object.
		 * @param int $flags Flags to control the behaviour of the ArrayObject object.
		 * @param string $iterator_class Specify the class that will be used for iteration of the ArrayObject object. ArrayIterator is the default class used.
		 */
		public function __construct($input=array(), $flags=\ArrayObject::STD_PROP_LIST, $iterator_class='\ArrayIterator') {
			foreach($input as $key=>$value) {
				if(is_array($value)) {
					$input[$key] = new self($value, $flags, $iterator_class);
				}
			}
			parent::__construct($input, $flags, $iterator_class);
		}

		/**
		 * Offset to set
		 * @link http://php.net/manual/en/arrayaccess.offsetset.php
		 * @param mixed $offset The offset to assign the value to.
		 * @param mixed $value  The value to set.
		 * @return void
		 */
		public function offsetSet($offset, $value)
		{
			if($value instanceof \ArrayIterator) {
				$value = $value->getArrayCopy();
			}
			if(is_array($value)) {
				$value = new SessionStorage($value);
				$value->set_parent($this);
			}
			parent::offsetSet($offset, $value);

			// mark the session as changed, so we trigger a write
			$this->change();

			// if we don't have a session_id already, we are "starting" a session
			if ( is_null( $this->id ) ) {
				Session::create();
			}
		}

		/**
		 * Set the parent of this object
		 * @param SessionStorage $parent The parent object of this object
		 */
		public function set_parent($parent)
		{
			$this->parent = $parent;
		}

		/**
		 * Mark this session as having been changed and needing to be saved
		 */
		public function change()
		{
			$this->changed = true;
			if($this->parent) {
				$this->parent->change();
			}
		}

		/**
		 * Get this object as an array instead of an ArrayObject
		 * @return array The whole storage array as an array
		 */
		public function getArrayCopy()
		{
			$cp = parent::getArrayCopy();
			foreach($cp as &$e) {
				if($e instanceof SessionStorage) {
					$e = $e->getArrayCopy();
				}
			}
			return $cp;
		}


	}

?>