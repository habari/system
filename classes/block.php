<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Block class
 * Block class for theme display of pluggable content
 *
 * @property string $type The type of block this is
 * @property string $title The title of this block
 * @property mixed $data The data associated to this block
 * @property integer $id The id of this block in the database
 *
 */
class Block extends QueryRecord implements IsContent, FormStorage
{
	public $_first = false;
	public $_last = false;
	public $_area_index = 0;
	public $_area = '';
	private $data_values = array( '_show_title' => true );

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
	 * @param string $name Name of property to return
	 * @return mixed The requested field value
	 */
	public function __get( $name )
	{
		switch($name) {
			case 'css_classes':
				$classes = array();
				if(array_key_exists( $name, $this->data_values )) {
					$classes = $this->data_values[$name];
				}
				if(is_string($classes)) {
					$classes = explode(' ', $classes);
				}
				if($this->_first) {
					$classes[] = 'first';
				}
				if($this->_last) {
					$classes[] = 'last';
				}
				if($this->_area_index) {
					$classes[] = 'index_' . $this->_area_index;
				}
				$classes[] = 'block-type-' . Utils::slugify($this->type);
				$classes[] = 'block-title-' . Utils::slugify($this->title);
				$classes[] = 'block';
				$classes = Plugins::filter('block_classes', $classes, $this);
				return implode(' ', $classes);
				break;
			default:
			if ( array_key_exists( $name, $this->data_values ) ) {
				return $this->data_values[$name];
			}
			else {
				return parent::__get( $name );
			}
		}
	}

	/**
	 * Overrides QueryRecord __set to implement custom object properties
	 *
	 * @param string $name Name of property to return
	 * @param mixed $value The value to set the property to
	 * @return mixed The value of the property
	 */
	public function __set( $name, $value )
	{
		switch ( $name ) {
			case 'id':
			case 'title':
			case 'data':
				parent::__set( $name, $value );
				$this->unserialize_data();
				return parent::__get( $name );
				break;
			case 'type':
				return parent::__set( $name, $value );
				break;
			default:
				$this->data_values[ $name ] = $value;
				return $this->data_values[ $name ];
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
	public function __isset( $name )
	{
		return ( isset( $this->data_values[$name] ) || parent::__isset( $name ) );
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
	public function fetch( $theme )
	{
		Plugins::act( 'block_content_' . $this->type, $this, $theme );
		Plugins::act( 'block_content', $this, $theme );
		$output = implode( '', $theme->content_return( $this ) );
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
		$types = array(
			'block.' . $this->type,
			'block',
		);
		if ( isset( $this->title ) ) {
			array_unshift( $types, 'block.' . $this->type . '.' . Utils::slugify( $this->title ) );
		}
		if ( isset( $this->_area ) ) {
			$areas = array();
			foreach ( $types as $type ) {
				$areas[] = $this->_area . '.' . $type;
			}
			$types = array_merge( $areas, $types );
		}
		$types = Plugins::filter( 'block_content_type_' . $this->type, $types, $this );
		$types = Plugins::filter( 'block_content_type', $types, $this );
		return $types;
	}

	/**
	 * Unserialize the stored block data
	 */
	public function unserialize_data()
	{
		if ( trim( $this->data ) != '' ) {
			$this->data_values = unserialize( $this->data );
		}
	}

	/**
	 * Saves form fields that are tied to this block.  Implements FormStorage.
	 *
	 * @param string $key The name of the form field to store.
	 * @param mixed $value The value of the form field
	 */
	public function field_save( $key, $value )
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
	public function field_load( $key )
	{
		return $this->$key;
	}

	/**
	 * Insert this block into the database
	 *
	 * @return boolean|null True on success, null if the action wasn't allowed
	 */
	public function insert()
	{
		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'block_insert_allow', $allow, $this );
		if ( ! $allow ) {
			return null;
		}
		Plugins::act( 'block_insert_before', $this );

		$this->data = serialize( $this->data_values );
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
	 * @return boolean|null True on success, null if the update isn't allowed
	 */
	public function update()
	{
		$allow = true;
		$allow = Plugins::filter( 'block_update_allow', $allow, $this );
		if ( ! $allow ) {
			return null;
		}
		Plugins::act( 'block_update_before', $this );

		$this->data = serialize( $this->data_values );
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

		$result = parent::deleteRecord( '{blocks_areas}', array( 'block_id'=>$this->id ) );
		$result = $result && parent::deleteRecord( '{blocks}', array( 'id'=>$this->id ) );

		EventLog::log( _t( 'Block %1$s (%2$s) deleted.', array( $this->id, $this->title ) ), 'info', 'content', 'habari' );

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
		$form = new FormUI( 'block-' . $this->id, 'block' );
		$form->on_success( array( $this, 'save_block' ) );

		Plugins::act( 'block_form_' . $this->type, $form, $this );
		Plugins::act( 'block_form', $form, $this );
		return $form;
	}

	/**
	 * Display a standard success message upon saving the form
	 *
	 * @param FormUI $form The form that will be saved
	 * @return bool Returning false tells the form that the save was handled
	 */
	public function save_block( FormUI $form )
	{
		$form->save();
		return false;
	}

	/**
	 * Add this block to a particular area in the theme
	 * 
	 * @param string $area The name of the area to add to
	 * @param integer $order The position of the block within the area
	 * @param string $scope The scope id into which to add this block
	 */
	public function add_to_area( $area, $order = null, $scope = null )
	{
		if( is_null( $scope ) ) {
			$scope = 0;
		}
		if( is_null( $order ) || ! is_int( $order ) ) {
			$order = DB::get_value( 'SELECT max(display_order) + 1 FROM {blocks_areas} WHERE area = :area AND scope_id = :scope', array( 'area' => $area, 'scope' => $scope ) );
			if( is_null( $order ) ) {
				$order = 1;
			}
		}
		else {
			DB::query( 'UPDATE {blocks_areas} SET display_order = display_order + 1 WHERE area = :area AND scope_id = :scope AND display_order >= :order', array( 'area' => $area, 'scope' => $scope, 'order' => $order ) );
		}
		
		// If the block isn't saved in the database, insert it.
		if( !$this->id ) {
			$this->insert();
		}
			
		$result = DB::query( 'INSERT INTO {blocks_areas} (block_id, area, scope_id, display_order) VALUES (:block_id, :area, :scope_id, :display_order)', array( 'block_id' => $this->id, 'area' => $area, 'scope_id' => $scope, 'display_order' => $order ) );
	}

	/**
	 * Convert this block into a string, just in case there isn't a template associated to this block type
	 * @return string The string representation of this content, as a bad fallback
	 */
	public function __toString()
	{
		return $this->title;
	}
}


?>
