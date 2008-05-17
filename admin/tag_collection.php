<?php
	$tags= Tags::get();
	$max= Tags::max_count();
	foreach ($tags as $tag) : ?>
		<a href="#" id="<?php echo 'tag_' . $tag->id ?>" class="tag wt<?php echo round(($tag->count * 10)/$max); ?>"><span><?php echo $tag->tag; ?></span><sup><?php echo $tag->count; ?></sup></a>
<?php endforeach; ?>
