<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
<ol class="tree">
<?php
	$stack = array();
	foreach( $options as $key => $term) {
		if( count( $stack ) > 0) {
			while( end($stack) < $term->mptt_right && count($stack) > 0) {
				echo str_repeat( '</ol>', ( $term->mptt_left - end( $stack ) ) / 2);
				array_pop( $stack );
			}
		}
		echo '<li id="tree_item_' . $key . '"><div>' . $term->term_display . '</div></li>';

		if( $term->mptt_left + 1 != $term->mptt_right ) {
		echo '<ol>';
		}

		$stack[] = $term->mptt_right;
	}?>
</ol>
<input type="hidden" name="<?php echo $field; ?>_submitted" value="1">
<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
</div>
