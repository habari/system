<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Block class
 * Block class for theme display of pluggable content
 *
 */
class Block extends QueryRecord implements IsContent, FormStorage
{
	private $data_values = array();

	/**
	 * Constructor for the Block class.
	 * @param array $paramarray an associative array of initial block field values.
	 */
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields,
			$this->newfields
		);
		parent::__construct( $paramarray );

		$this->exclude_fields( 'id' );
		$this->unserialize_data();
	}

	/**
	 * Overrides QueryRecord __get to implement custom object properties
	 *
	 * @param string Name of property to return
	 * @return mixed The requested field value
	 */
	public function __get($name)
	{
		if ( array_key_exists($name, $this->data_values) ) {
			return $this->data_values[$name];
		}
		else {
			return parent::__get($name);
		}
	}

	/**
	 * Overrides QueryRecord __set to implement custom object properties
	 *
	 * @param string Name of property to return
	 * @return mixed The requested field value
	 */
	public function __set($name, $value)
	{
		switch ( $name ) {
			case 'id':
			case 'title':
			case 'data':
			case 'type':
				return parent::__set($name, $value);
				break;
			default:
				$this->data_values[$name] = $value;
				return $this->data_values[$name];
				break;
		}
	}

	/**
	 * Overrides QueryRecord __isset, returns whether this Block has the named
	 * data. Falls back to QueryRecord's __isset.
	 *
	 * @param string $name The name of the parameter
	 * @return boolean True if the value is set, false if not
	 */
	public function __isset($name)
	{
		return ( isset($this->data_values[$name]) || parent::__isset($name) );
	}

	/**
	 * Return the defined database columns for a Block.
	 * @return array Array of columns in the Block table
	 */
	public static function default_fields()
	{
		return array(
			'id' => 0,
			'title' => '',
			'data' => '',
			'type' => '',
		);
	}

	/**
	 * Render and return the block content
	 *
	 * @param Theme $theme the theme object with which the block will be rendered
	 * @return string The rendered block content
	 */
	public function fetch($theme)
	{
		Plugins::act('block_content_' . $block->type, $block);
		$output .= implode( '', $theme->content_return($block));
		return $output;
	}

	/**
	 * Return the content types that this object represents
	 *
	 * @see IsContent
	 * @return array An array of strings representing the content type of this object
	 */
	public function content_type()
	{
		return array(
			'block.' . $this->type,
			'block',
		);
	}

	/**
	 * Unserialize the stored block data
	 */
	public function unserialize_data()
	{
		if ( trim($this->data) != '' ) {
			$this->data_values = unserialize($this->data);
		}
	}

	/**
	 * Saves form fields that are tied to this block.  Implements FormStorage.
	 *
	 * @param string $key The name of the form field to store.
	 * @param mixed $value The value of the form field
	 */
	public function field_save($key, $value)
	{
		$this->$key = $value;
		$this->update();
	}

	/**
	 * Load the form value from the block
	 *
	 * @param string $key The name of the form field to load
	 * @return mixed The value of the block for the form
	 */
	public function field_load($key)
	{
		return $this->$key;
	}

	/**
	 * Insert this block into the database
	 *
	 * @return boolean True on success
	 */
	public function insert()
	{
		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'block_insert_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		Plugins::act( 'block_insert_before', $this );

		$this->data = serialize($this->data_values);
		$result = parent::insertRecord( DB::table( 'blocks' ) );

		// Make sure the id is set in the block object to match the row id
		$this->newfields['id'] = DB::last_insert_id();

		// Update the block's fields with anything that changed
		$this->fields = array_merge( $this->fields, $this->newfields );

		// We've inserted the block, reset newfields
		$this->newfields = array();

		EventLog::log( _t( 'New block %1$s: %2$s', array( $this->id, $this->title ) ), 'info', 'content', 'habari' );

		// Let plugins act after we write to the database
		Plugins::act( 'block_insert_after', $this );
		return $result;
	}

	/**
	 * Update this block in the database
	 *
	 * @return boolean True on success
	 */
	public function update()
	{
		$allow = true;
		$allow = Plugins::filter( 'block_update_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		Plugins::act( 'block_update_before', $this );

		$this->data = serialize($this->data_values);
		$result = parent::updateRecord( DB::table( 'blocks' ), array( 'id' => $this->id ) );

		$this->fields = array_merge( $this->fields, $this->newfields );
		$this->newfields = array();

		Plugins::act( 'block_update_after', $this );
		return $result;
	}

	/**
	 * Delete this block
	 *
	 */
	public function delete()
	{
		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'block_delete_allow', $allow, $this );
		if ( !$allow ) {
			return false;
		}
		Plugins::act( 'block_delete_before', $this );

		DB::query( "DELETE FROM {blocks} WHERE id=?", array( $this->id ) );

		$result = parent::deleteRecord( '{blocks}', array( 'id'=>$this->id ) );

		EventLog::log( sprintf(_t('Block %1$s (%2$s) deleted.'), $this->id, $this->title), 'info', 'content', 'habari' );

		// Let plugins act after we write to the database
		Plugins::act( 'block_delete_after', $this );
		return $result;
	}

	/**
	 * Get the form used to update this block
	 *
	 * @return FormUI The altered FormUI element that edits this block
	 */
	public function get_form()
	{
		$form = new FormUI('block-' . $this->id , 'block');
		Plugins::act('block_form_' . $this->type, $form, $this);
		return $form;
	}

}


?>
