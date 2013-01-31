<?php

namespace Habari;

if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); }

/**
 * Create blocks commonly used by site owners.
 *
 */
class CoreBlocks extends Plugin
{
	private $allblocks = array();

	/**
	 * When the plugin is initialized, register the block templates and set up supporting data.
	 */
	function action_init()
	{

		$this->allblocks = array(
			'recent_comments' => _t( 'Recent Comments' ),
			'recent_posts' => _t( 'Recent Posts' ),
			'monthly_archives' => _t( 'Monthly Archives' ),
			'category_archives' => _t( 'Category Archives' ),
			'tag_archives' => _t( 'Tag Archives' ),
			'meta_links' => _t( 'Meta Links' ),
			'search_form' => _t( 'Search Form' ),
			'text' => _t( 'Text' ),
		);

		foreach ( array_keys( $this->allblocks ) as $blockname ) {
			$this->add_template( "block.$blockname", dirname( __FILE__ ) . "/block.$blockname.php" );
		}
		$this->add_template( "block.dropdown.category_archives", dirname( __FILE__ ) . "/block.dropdown.category_archives.php" );
		$this->add_template( "block.dropdown.tag_archives", dirname( __FILE__ ) . "/block.dropdown.tag_archives.php" );
		$this->add_template( "block.dropdown.monthly_archives", dirname( __FILE__ ) . "/block.dropdown.monthly_archives.php" );
	}

	/**
	 * Encapsulate the list of URLs the block can display
	 * @return array A list of URLs that can be displayed
	 */
	function meta_urls()
	{
		return array(
			_t( 'Site Feed' ) => URL::get( 'atom_feed', array( 'index' => '1' ) ),
			_t( 'Comments Feed' ) => URL::get( 'atom_feed_comments' ),
			'Habari' => 'http://habariproject.org/',
		);
	}

	/**
	 * Add available blocks to the list of possible block types.
	 *
	 * @param array $block_list an Associative array of the internal names and display names of blocks
	 *
	 * @return array The modified $block_list array
	 */
	public function filter_block_list( $block_list )
	{
		$allblocks = $this->allblocks;
		foreach ( $allblocks as $blockname => $nicename ) {
			$block_list[ $blockname ] = _t( $nicename );
		}
		return $block_list;
	}

	/**
	 * Recent Comments
	 *
	 * Allow configuration of the number of recent comments to show
	 *
	 * @param FormUI $form The configuration form for this block
	 * @param Block $block The block instance to be configured
	 */
	public function action_block_form_recent_comments( $form, $block )
	{
		$content = $form->append('text', 'quantity', $block, _t( 'Comments to show:' ) );
	}

	/**
	 * Recent Comments
	 *
	 * Handle recent comment block output
	 *
	 * @param Block $block The block instance to be configured
	 * @param Theme $theme The active theme
	 */
	public function action_block_content_recent_comments( $block, $theme )
	{
		if ( ! $limit = $block->quantity ) {
			$limit = 5;
		};

		$offset = 0;
		$published_posts = 0;
		$valid_comments = array();
		// prevent endless looping if there are fewer comments than $limit
		$comments_remain = true;

		while ( $published_posts < $limit && $comments_remain ) {
			$comments = Comments::get( array(
				'limit' => $limit - $published_posts,
				'status' => Comment::STATUS_APPROVED,
				'type' => Comment::COMMENT,
				'offset' => $offset,
				'orderby' => 'date DESC',
			) );
			// check the posts
			foreach ( $comments as $key => $comment ) {
				if ( ( $comment->post->status ) == Post::status( 'published' ) ) {
					$valid_comments[] = $comments[ $key ];
					++$published_posts;
				}
				++$offset;
			}
			// stop looping if out of comments
			if ( count( $comments ) === 0 ) {
				$comments_remain = false;
			}
		}

		$block->recent_comments = $valid_comments;
	}

	/**
	 * Recent Posts
	 *
	 * Allow configuration of the number of recent posts to show
	 *
	 * @param FormUI $form The configuration form for this block
	 * @param Block $block The block instance to be configured
	 */
	public function action_block_form_recent_posts( $form, $block )
	{
		$content = $form->append('text', 'quantity', $block, _t( 'Posts to show:' ) );

		$content_types = Post::list_active_post_types();

		unset($content_types['any']);

		foreach ( $content_types as $k => $v ) {
			$content_types[ $k ] = Plugins::filter( 'post_type_display', $k, 'plural' );
		}

		$form->append('checkboxes', 'content_types', $block, _t( 'Content Types to Include:' ), $content_types );

	}

	/**
	 * Recent Posts
	 *
	 * Handle recent post block output
	 *
	 * @param Block $block The block instance to be configured
	 * @param Theme $theme The active theme
	 */
	public function action_block_content_recent_posts( $block, $theme )
	{
		if ( ! $limit = $block->quantity ) {
			$limit = 5;
		};

		if ( empty( $block->content_types ) ) {
			$block->content_types = array( 'entry' );
		}

		$block->recent_posts = Posts::get( array(
			'limit' => $limit,
			'status' => 'published',
			'content_type' => $block->content_types,
			'orderby' => 'pubdate DESC',
		) );
	}

	/**
	 * Monthly Archives
	 *
	 * Allow configuration of the monthly archive options
	 *
	 * @param FormUI $form The configuration form for this block
	 * @param Block $block The block instance to be configured
	 */
	public function action_block_form_monthly_archives( $form, $block )
	{
		$content = $form->append( 'checkbox', 'full_names', $block, _t( 'Display full month names:' ) );
		$content = $form->append( 'checkbox', 'show_counts', $block, _t( 'Append post count:' ) );
		$content = $form->append( 'select', 'style', $block, _t( 'Preferred Output Style:' ),
			array('dropdown' => _t( 'Dropdown' ), 'list' => _t( 'List' ) ) );
	}

	/**
	 * Monthly Archives
	 *
	 * Handle monthly archive block output
	 *
	 * @param Block $block The block instance to be configured
	 * @param Theme $theme The active theme
	 */
	public function action_block_content_monthly_archives( $block, $theme )
	{
		$months = array();
		$results = Posts::get( array(
			'content_type' => 'entry',
			'status' => 'published',
			'month_cts' => 1 )
			);

		foreach ( $results as $result ) {
			if ( $block->full_names ) {
				$display_month = DateTime::create()->set_date( $result->year, $result->month, 1 )->get( 'F' );
			}
			else {
				$display_month = DateTime::create()->set_date( $result->year, $result->month, 1 )->get( 'M' );
			}

			$count = '';
			if ( $block->show_counts ) {
				$count = " (" . $result->ct . ")";
			}

			$result->month = str_pad( $result->month, 2, 0, STR_PAD_LEFT );
			$url = URL::get( 'display_entries_by_date', array( 'year' => $result->year, 'month' => $result->month ) );
			$months[] = array(
				'display_month' => $display_month,
				'count' => $count,
				'year' => $result->year,
				'url' => $url,
				);
		}
		$block->months = $months;
	}

	/**
	 * Monthly Archives
	 *
	 * @param Type $type The block template to be used
	 * @param Block $block The block instance to be configured
	 */
	function filter_block_content_type_monthly_archives( $types, $block )
	{
		array_unshift( $types, $newtype = "block.{$block->style}.{$block->type}" );
		if ( isset( $block->title ) ) {
			array_unshift( $types, "block.{$block->style}.{$block->type}." . Utils::slugify( $block->title ) );
		}
		return $types;
	}


	/**
	 * Tag Archives
	 *
	 * Allow configuration of the tag archive options
	 *
	 * @param FormUI $form The configuration form for this block
	 * @param Block $block The block instance to be configured
	 */
	public function action_block_form_tag_archives( $form, $block )
	{
		$content = $form->append( 'checkbox', 'show_counts', $block, _t( 'Append post count:' ) );
		$content = $form->append( 'select', 'style', $block, _t( 'Preferred Output Style:' ),
			array('dropdown' => _t( 'Dropdown' ), 'list' => _t( 'List' ) ) );
	}

	/**
	 * Tag Archives
	 *
	 * Handle tag archive block output
	 *
	 * @param Block $block The block instance to be configured
	 * @param Theme $theme The active theme
	 */
	public function action_block_content_tag_archives( $block, $theme )
	{
		$tags = array();
		$results = Tags::vocabulary()->get_tree();

		foreach( $results as $result ) {

			$count = '';
			if ( $block->show_counts ) {
				$count = " (" . Posts::count_by_tag( $result->term_display, "published") . ")";
			}

			$url = URL::get( 'display_entries_by_tag', array( 'tag' => $result->term ) );
			$tags[] = array(
				'tag' => $result->term_display,
				'count' => $count,
				'url' => $url,
				);
		}

		$block->tags = $tags;
	}

	/**
	 * Tag Archives
	 *
	 * @param Type $type The block template to be used
	 * @param Block $block The block instance to be configured
	 */
	function filter_block_content_type_tag_archives( $types, $block )
	{
		array_unshift( $types, $newtype = "block.{$block->style}.{$block->type}" );
		if ( isset( $block->title ) ) {
			array_unshift( $types, "block.{$block->style}.{$block->type}." . Utils::slugify( $block->title ) );
		}
		return $types;
	}

	/**
	 * Meta Links
	 *
	 * Allow configuration of the meta links options
	 *
	 * @param FormUI $form The configuration form for this block
	 * @param Block $block The block instance to be configured
	 */
	public function action_block_form_meta_links( $form, $block )
	{
		$content = $form->append('checkboxes', 'links', $block, _t( 'Links to show:' ), array_flip( $this->meta_urls() ) );
	}

	/**
	 * Meta Links
	 *
	 * Handle meta links block output
	 *
	 * @param Block $block The block instance to be configured
	 * @param Theme $theme The active theme
	 */
	public function action_block_content_meta_links( $block, $theme )
	{
		$list = array();
		if ( User::identify()->loggedin ) {
			$list[ Site::get_url( 'logout' ) ] = _t( 'Logout' );
		}
		else {
			$list[ Site::get_url( 'login' ) ] = _t( 'Login' );
		}
		$meta_urls = array_flip( $this->meta_urls() );
		$links = $block->links;
		if ( count( $links ) > 0 ) {
			foreach ( $links as $link ) {
				$list[ $link ] = $meta_urls[ $link ];
			}
		}
		$block->list = $list;
	}

	/**
	 * Search Form
	 *
	 * Allow configuration of the search form options
	 *
	 * @param FormUI $form The configuration form for this block
	 * @param Block $block The block instance to be configured
	 */
	public function action_block_form_search_form( $form, $block )
	{
		$content = $form->append( 'text', 'button', $block, _t( 'Button:' ) );
	}

	/**
	 * Search Form
	 *
	 * Handle search form block output
	 *
	 * @param Block $block The block instance to be configured
	 * @param Theme $theme The active theme
	 */
	public function action_block_content_search_form( $block, $theme )
	{
		$block->form = '<form method="get" id="searchform" action="' . URL::get( 'display_search' ) .
			'"><p><input type="text" id="s" name="criteria" value="' . ( isset( $theme->criteria ) ? htmlentities( $theme->criteria, ENT_COMPAT, 'UTF-8' ) : '' ) .
			'"><input type="submit" id="searchsubmit" value="' . ( isset( $block->button ) ? $block->button : _t( 'Search' ) ) . '"></p></form>';
	}

	/**
	 * Text
	 *
	 * Display unformatted text without any markup.
	 * If you require HTML output, try the Simple Block plugin instead.
	 *
	 * @param FormUI $form The configuration form for this block
	 * @param Block $block The block instance to be configured
	 * Configuration form with one big textarea. Raw to allow JS/HTML/etc. Insert them at your own peril.
	 */
	public function action_block_form_text( $form, $block )
	{
		$content = $form->append( 'textarea', 'content', $block, _t( 'Content:' ) );
		$content->rows = 5;
	}

	/**
	 * Text
	 *
	 * Display unformatted text without any markup.
	 * If you require HTML output, try the Simple Block plugin instead.
	 *
	 * @param Block $block The block instance to be configured
	 * @param Theme $theme The active theme
	 */
	public function action_block_content_text( $block, $theme )
	{
		return $block;
	}
}
?>
