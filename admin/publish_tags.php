				<div class="container">
					<p class="column span-5"><input type="button" value="<?php _e('Clear'); ?>" id="clear"></p>
				</div>
				<hr>
				<div class="container">
					<ul id="tag-list" class="column span-19">
					<?php foreach( Tags::get() as $taglist ) { ?>
						<li id="<?php echo $taglist->tag; ?>"><?php echo $taglist->tag; ?></li>
					<?php } ?>
					</ul>
				</div>
