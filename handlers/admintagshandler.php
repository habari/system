<?php
/**
 * @package Habari
 *
 */

namespace Habari;

/**
 * Habari AdminTagsHandler Class
 * Handles tag-related actions in the admin
 *
 */
class AdminTagsHandler extends AdminHandler
{
	// Use an array to store translated facet strings so we have them in only one place
	private static $facets = array();
	private static $facet_values = array();
	private static $orderby_translate = array();

	public function __construct()
	{
		self::$facets = array(
			_t('More than .. posts') => 'morethan',
			_t('Less than .. posts') => 'lessthan',
			_t('Order by') => 'orderby',
		);
		self::$facet_values = array(
			'orderby' => array(
				_t('Publication date (descending)') => 'pubdate_desc',
				_t('Publication date (ascending)') => 'pubdate_asc',
				_t('Post count (descending)') => 'count_desc',
				_t('Post count (ascending)') => 'count_asc',
				_t('Alphabetical (descending)') => 'alphabetical_desc',
				_t('Alphabetical (ascending)') => 'alphabetical_asc',
			),
		);
		// We could avoid "translating" for sure, but it adds an additional layer of security, so we keep it
		self::$orderby_translate = array(
			'pubdate' => 'pubdate',
			'count' => 'count',
			'alphabetical' => 'term_display',
		);
		return parent::__construct();
	}

	/**
	 * Handle POST requests for /admin/tags
	 */
	public function post_tags()
	{
		return $this->get_tags();
	}

	/**
	 * Handle GET requests for /admin/tags to display the tags.
	 */
	public function get_tags()
	{
		$this->theme->wsse = Utils::WSSE();

		$this->theme->tags = Tags::vocabulary()->get_tree( 'term_display asc' );
		$this->theme->max = Tags::vocabulary()->max_count();
		$this->theme->min = Tags::vocabulary()->min_count();

		$form = new FormUI('tags');

		$form->append(FormControlFacet::create('search')
			->set_property('data-facet-config', array(
				// #tag_collection is the object the manager function works on - the corresponding AJAX function will replace its content
				'onsearch' => 'deselect_all(); $("#tag_collection").manager("update", self.data("visualsearch").searchQuery.facets());',
				'facetsURL' => URL::get('admin_ajax_facets', array('context' => 'facets', 'page' => 'tags', 'component' => 'facets')),
				'valuesURL' => URL::get('admin_ajax_facets', array('context' => 'facets', 'page' => 'tags', 'component' => 'values')),
			))
		);
		
		$aggregate = FormControlAggregate::create('selected_items')->set_selector("#tag_collection input")->label('0 Selected');
		$aggr_wrap = FormControlWrapper::create('tag_controls_aggregate')
			->add_class('aggregate_wrapper');
		$aggr_wrap->append($aggregate);

		$delete = FormControlDropbutton::create('delete_dropbutton');
		$delete->append(
			FormControlButton::create('action')
				->set_caption(_t('Delete selected'))
				->set_properties(array(
					'title' => _t('Delete selected'),
					'value' => 'delete',
				))
		);

		$rename_text = FormControlText::create('rename_text');
		
		$rename = FormControlDropbutton::create('rename_dropbutton');
		$rename->append(
			FormControlButton::create('action')
				->set_caption(_t('Rename selected'))
				->set_properties(array(
					'title' => _t('Rename selected'),
					'value' => 'rename',
				))
		);

		$tag_controls = $form->append(FormControlWrapper::create('tag_controls'))
			->add_class("container tag_controls");
		$tag_controls->append($aggr_wrap);
		$tag_controls->append($rename_text);
		$tag_controls->append($rename);
		$tag_controls->append($delete);
		$tag_controls->append(FormControlWrapper::create('selected_tags')
			->set_setting('wrap_element', 'ul')
			->set_property('id', 'selected_tags')
		);

		if(count($this->theme->tags) > 0) {
			$tag_collection = $form->append(FormControlWrapper::create('tag_collection')
				->add_class('container items')
				->set_setting('wrap_element', 'ul')
				->set_property('id', 'tag_collection')
			);
			
			$listitems = $this->get_tag_listitems();
			foreach($listitems as $item) {
				$tag_collection->append($item);
			}
		}
		else {
			$tag_collection = $form->append(FormControlStatic::create('<p>' . _t('No tags could be found to match the query criteria.') . '</p>'));
		}
		
		$form->on_success(array($this, 'process_tags'));

		$this->theme->form = $form;

		Stack::add('admin_header_javascript', 'visualsearch' );
		Stack::add('admin_header_javascript', 'manage-js' );
		Stack::add('admin_stylesheet', 'visualsearch-css');
		Stack::add('admin_stylesheet', 'visualsearch-datauri-css');

		$this->display( 'tags' );
	}

	/**
	 * Generate FormUI checkboxes wrapped in listitems for the tag collection. Is used with and without AJAX
	 */
	public function get_tag_listitems()
	{
		if(count($this->theme->tags) < 1) {
			return null;
		}

		$listitems = array();

		// Calculation preparation for statistical weighting
		$count_range = $this->theme->max - $this->theme->min;
		if($count_range > 5) {
			$p10 = $this->theme->min + $count_range / 10;
			$p25 = $this->theme->min + $count_range / 4;
			$p50 = $this->theme->min + $count_range / 2;
			$p75 = $this->theme->min + $count_range / 100 * 75;
			$p90 = $this->theme->min + $count_range / 100 * 90;
		}

		foreach($this->theme->tags as $tag) {
			// The actual weighting happens through classifying into one of 6 statistically relevant areas
			$weight = ($tag->count < $p10) ? 1 : (($tag->count < $p25) ? 2 : (($tag->count < $p50) ? 3 : (($tag->count < $p75) ? 4 : (($tag->count < $p90) ? 5 : 6))));
			$listitems[] = FormControlCheckbox::create('tag_' . $tag->id)
				->set_returned_value($tag->id)
				->set_property('name', 'tags[]')
				->label($tag->term_display . '<span class="count"><a href="' . URL::get( 'admin', array( 'page' => 'posts', 'search' => 'tag:'. $tag->tag_text_searchable) ) . '" title="' . Utils::htmlspecialchars( _t( 'Manage posts tagged %1$s', array( $tag->term_display ) ) ) . '">' . $tag->count .'</a></span>')
				->set_template('control.label.outsideright')
				->set_setting('wrap', '<li class="tag_' . $tag->id . ' item tag wt' . $weight . '">%s</li>');
		}

		return $listitems;
	}

	/**
	 * Handles submitted tag forms and processes tag actions
	 * @param FormUI $form The tag form
	 */
	public function process_tags( $form )
	{
		if( $_POST['action'] == 'delete' ) {
			$tag_names = array();
			foreach ( $form->selected_items->value as $id ) {
				// We only collect the names so we can display them - deletion could also happen directly using the id
				$tag = Tags::get_by_id( $id );
				$tag_names[] = $tag->term_display;
				Tags::vocabulary()->delete_term( $tag );
			}
			Session::notice( _n( _t( 'Tag %s has been deleted.', array( implode( '', $tag_names ) ) ), _t( '%d tags have been deleted.', array( count( $tag_names ) ) ), count( $tag_names ) ) );
		}
		elseif( $_POST['action'] == 'rename' ) {
			if ( !isset( $_POST['rename_text'] ) || empty( $_POST['rename_text'] ) ) {
				Session::error( _t( 'Error: New name not specified.' ) );
			}
			else {
				$tag_names = array();
				foreach ( $form->selected_items->value as $id ) {
					$tag = Tags::get_by_id( $id );
					$tag_names[] = $tag->term_display;
				}
				Tags::vocabulary()->merge( $_POST['rename_text'], $tag_names );
				Session::notice( sprintf( _n('Tag %1$s has been renamed to %2$s.', 'Tags %1$s have been renamed to %2$s.', count( $tag_names ) 			), implode( $tag_names, ', ' ), $_POST['rename_text'] ) );
			}
		}

		Utils::redirect( URL::get( 'display_tags' ) );
	}

	/**
	 * Handles AJAX from /admin/tags
	 * Used to search for, delete and rename tags
	 */
	public function ajax_tags()
	{
		Utils::check_request_method( array( 'POST', 'HEAD' ) );

		$this->create_theme();

		$params = $_POST['query'];

		// Get a usable array with filter parameters from the odd syntax we received from the faceted search
		$fetch_params = array();
		if(isset($params)) {
			foreach($params as $param) {
				$key = key($param);
				// Revert translation
				if($key != 'text') {
					$key = self::$facets[$key];
				}
				$value = current($param);
				if(array_key_exists($key, $fetch_params)) {
					$fetch_params[$key] = Utils::single_array($fetch_params[$key]);
					$fetch_params[$key][] = $value;
				}
				else {
					$fetch_params[$key] = $value;
				}
			}
		}

		// Grab facets / params
		$search = (array_key_exists('text', $fetch_params)) ? $fetch_params['text'] : null;
		$min = (array_key_exists('morethan', $fetch_params)) ? $fetch_params['morethan'] + 1 : 0;
		$max = (array_key_exists('lessthan', $fetch_params)) ? $fetch_params['lessthan'] - 1 : null;
		$orderby_code = (array_key_exists('orderby', $fetch_params)) ? $fetch_params['orderby'] : null;
		$orderby = isset($orderby_code) ? explode('_', self::$facet_values['orderby'][$orderby_code]) : ['alphabetical', 'asc'];

		$this->theme->tags = Tags::get_by_frequency(null, null, $min, $max, $search, self::$orderby_translate[$orderby[0]], $orderby[1] == 'asc');
		// Create FormUI elements (list items) from the filtered tag list
		$this->theme->max = Tags::vocabulary()->max_count();
		$this->theme->min = Tags::vocabulary()->min_count();
		
		$output = '';
		if(count($this->theme->tags) > 0) {
			$listitems = $this->get_tag_listitems();

			// Get HTML from FormUI
			foreach($listitems as $listitem) {
				$output .= $listitem->get($this->theme);
			}
		}

		$ar = new AjaxResponse();
		$ar->html('#tag_collection', $output);
		$ar->out();
	}

	/**
	 * Plugin hook filter for the facet list
	 * @param array $facets An array of facets for the current faceted search
	 * @return array The array of facets
	 */
	public static function filter_facets($facets) {
		return array_merge($facets, array_keys(self::$facets));
	}

	/**
	 * Plugin hook filter for the values of a faceted search
	 * @param array $values The incoming array of values for this facet
	 * @param string $facet The selected facet
	 * @param string $q A string filter for facet values
	 * @return array The returned list of possible values
	 */
	public static function filter_facetvalues($values, $facet, $q) {
		return array_merge( $values, ( isset(self::$facet_values[self::$facets[$facet]]) ? array_keys(self::$facet_values[self::$facets[$facet]]) : [] ) );
	}
}
?>
