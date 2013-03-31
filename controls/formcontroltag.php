<?php

namespace Habari;

/**
 * A control to display a single tag for output via FormUI
 */
class FormControlTag extends FormControl
{
	/**
	 * Override the FormControl constructor to support more parameters
	 *
	 * @param string $name Name of this control
	 * @param string $tag A tag object
	 * @param string $template A template to use for display
	 */
	public function __construct()
	{
		$args = func_get_args();
		list( $name, $tag, $template ) = array_merge( $args, array_fill( 0, 3, null ) );

		$this->name = $name;
		$this->tag = $tag;
		$this->template = isset( $template ) ? $template : 'tabcontrol_tag';
	}

	/**
	 * Produce HTML output for all this fieldset and all contained controls
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	function get( $forvalidation = true )
	{
		$theme = $this->get_theme( $forvalidation );
		$max = Tags::vocabulary()->max_count();

		$tag = $this->tag;

		$theme->class = 'tag_'.$tag->term;
		$theme->id = $tag->id;
		$theme->weight = $max > 0 ? round( ( $tag->count * 10 )/$max ) : 0;
		$theme->caption = $tag->term_display;
		$theme->count = $tag->count;

		return $theme->fetch( $this->get_template(), true );
	}

}

?>