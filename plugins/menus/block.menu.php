<?php
echo Format::term_tree(
	$content->tree,
	$content->vocabulary->name,
	array(
		'itemcallback' => $content->render_menu_item,
		'linkwrapper' => $content->wrapper,
		'treeattr' => array(
			'class' => $content->list_class,
		),
		'theme' => $theme,
	)
);
?>
