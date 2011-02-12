<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<ul>
<?php foreach($taglist as $tag): ?>
	<li><a href="<?php echo $tag->url; ?>" title="<?php echo $tag->text; ?>" rel="tag" style="font-size: 125%;"><?php echo $tag->text; ?></a></li>
<?php endforeach; ?>
</ul>
