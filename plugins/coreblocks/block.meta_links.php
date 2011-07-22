<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } ?>
<ul id="meta_links">
	<?php $links = $content->list; foreach( $links as $label => $href): ?>
	<li><a href="<?php echo $label; ?>"><?php echo $href; ?></a></li>
	<?php endforeach; ?>
</ul>
