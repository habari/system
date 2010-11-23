<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
<ol class="tree">
<?php
if($firstnode = reset($options)) {
	$lastright = $lastleft = reset($options)->mptt_left;
	$indent = 0;
	$stack = array();
	foreach ( $options as $key => $term ) {
		while ( count( $stack ) > 0 && end( $stack )->mptt_right < $term->mptt_left ) {
			array_pop( $stack );
			echo '</ol>';
		}
		echo '<ol><li id="tree_item_' . $key .'">
			<div>' .  Utils::htmlspecialchars($term->term_display) . '</div>
		</li>';
		$stack[] = $term;
	}
}
?>
</ol>
<input type="hidden" name="<?php echo $field; ?>_submitted" value="1">
<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
</div>
