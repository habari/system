<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
<ol class="tree">
<?php foreach($options as $key => $text) : ?>
	<li id="tree_item_<?php echo $key; ?>">
		<div><?php echo Utils::htmlspecialchars($text); ?></div>
	</li>
<?php endforeach; ?>
</ol>
<input type="hidden" name="<?php echo $field; ?>_submitted" value="1">
<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
</div>
