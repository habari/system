<?php
	$tags = Tags::get();
	$max = Tags::max_count();
	foreach ($tags as $tag) : ?>
		<span id="<?php echo 'tag_' . $tag->id ?>" class="item tag wt<?php echo $max > 0 ? round(($tag->count * 10)/$max) : 0; ?>"> 
		 	<span class="checkbox"><input type="checkbox" class="checkbox" name="checkbox_ids[<?php echo $tag->id; ?>]" id="checkbox_ids[<?php echo $tag->id; ?>]"></span><label for="checkbox_ids[<?php echo $tag->id; ?>]"><?php echo $tag->tag; ?></label><sup><?php echo $tag->count; ?></sup> 
		 </span>
<?php endforeach; ?>
