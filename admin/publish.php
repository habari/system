<?php include('header.php'); ?>
<form name="create-content" id="create-content" method="post" action="<?php URL::out( 'admin', 'page=publish' ); ?>">

<div class="publish">

	<div class="container">

		<?php
			Session::messages_out();
			if ( isset( $slug ) ) {
				$post= Post::get( array( 'slug' => $slug, 'status' => Post::status( 'any' ) ) );
				$tags= htmlspecialchars( Utils::implode_quoted( ',', $post->tags ) );
				$content_type= Post::type( $post->content_type );
		?>
		<a href="<?php echo $post->permalink; ?>" class="viewpost">View Post</a>
		<?php
			} else {
				$post= new Post();
				$tags= '';
				$content_type= Post::type( ( isset( $content_type ) ) ? $content_type : 'entry' );
			}
		?>

		<p><label for="title" class="incontent"><?php _e('Title'); ?></label><input type="text" name="title" id="title" class="styledformelement" size="100%" value="<?php if ( !empty($post->title) ) { echo $post->title; } ?>" tabindex='1'></p>
	</div>

	<?php if (isset($silos) && count($silos)) : ?>
	<div class="container pagesplitter">
		<ul class="tabs">
			<?php
			$first = 'first';
			$ct = 0;
			$last = '';
			foreach($silos as $silodir):
				$ct++;
				if($ct == count($silos)) {
					$last = 'last';
				}
				$class = "{$first} {$last}";
				$first = '';
			?><li class="<?php echo $class; ?>"><a href="#silo_<?php echo $ct; ?>"><?php echo $silodir->path; ?></a></li><?php endforeach; ?>
		</ul>

		<?php
		$ct = 0;
		foreach($silos as $silodir):
			$ct++;
		?>
			<div id="silo_<?php echo $ct; ?>" class="splitter">
				<div class="splitterinside" style="overflow-x:scroll;">
					<div style="white-space:nowrap;" class="media_browser">
				<?php
				$assets = Media::highlights($silodir->path);

				foreach((array)$assets as $asset) {
					echo "<div class=\"media\"><img src=\"{$asset->thumbnail_url}\"><div class=\"foroutput\"><img src=\"{$asset->url}\"></div></div>";
				}
				?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<div class="container">
		<p><label for="content" class="incontent"><?php _e('Content'); ?></label><textarea name="content" id="content" class="styledformelement resizable" rows="20" cols="114" tabindex='2'><?php if ( !empty($post->content) ) { echo htmlspecialchars($post->content); } ?></textarea></p>

		<p><label for="tags" class="incontent"><?php _e('Tags, separated by, commas')?></label><input type="text" name="tags" id="tags" class="styledformelement" value="<?php if ( !empty( $tags ) ) { echo $tags; } ?>" tabindex='3'></p>
	</div>


	<div class="container pagesplitter">
		<ul class="tabs">
			<li class="publishsettings first"><a href="#publishsettings">Settings</a></li><li class="tagsettings last"><a href="#tagsettings">Tags</a></li><!--li class="preview last"><a href="#preview">Preview</a></li-->
		</ul>

		<div id="publishsettings" class="splitter">
			<div class="splitterinside">
				<div class="container"><p class="column span-5"><?php _e('Content Type'); ?></p> 		<p class="column span-14 last"><input type="text" name="content_type" class="styledformelement" value="<?php echo $content_type; ?>"></p></div>
				<hr>
				<div class="container">
					<p class="column span-5"><?php _e('Content State'); ?></p>
					<p class="column span-14 last">
						<?php
						// pass "false" to list_post_statuses() so that we don't
						// include internal post statuses
						$statuses= Post::list_post_statuses( false );
						unset( $statuses[array_search( 'any', $statuses )] );
						$statuses= Plugins::filter('admin_publish_list_post_statuses', $statuses);
						?>

					 	<label><?php echo Utils::html_select( 'status', array_flip($statuses), $post->status, array( 'class'=>'longselect') ); ?></label>
					</p>
				</div>
				<hr>
				<div class="container"><p class="column span-5"><?php _e('Comments Allowed'); ?></p> 	<p class="column span-14 last"><input type="checkbox" name="comments_disabled" class="styledformelement" value="0" <?php echo ( $post->info->comments_disabled == 0 ) ? 'checked' : ''; ?>></p></div>
				<hr>
				<div class="container"><p class="column span-5"><?php _e('Publication Time'); ?></p>	<p class="column span-14 last"><input type="text" name="pubdate" id="pubdate" class="styledformelement" value="<?php echo $post->pubdate; ?>"></p></div>
				<hr>
				<div class="container"><p class="column span-5"><?php _e('Content Address'); ?></p>		<p class="column span-14 last"><input type="text" name="newslug" id="newslug" class="styledformelement" value="<?php echo $post->slug; ?>"></p></div>

				<?php if ( $post->slug != '' ) { ?>
				<p><input type="hidden" name="slug" id="slug" value="<?php echo $post->slug; ?>"></p>
				<?php } ?>
			</div>
		</div>
		<div id="tagsettings" class="splitter">
			<div class="splitterinside">
				<div class="container">
					<p class="column span-5"><input type="button" value="Clear" id="clear"></p>
				</div>
				<hr>
				<div class="container">
					<ul id="tag-list" class="column span-19">
					<?php foreach( Tags::get() as $taglist ) { ?>
						<li id="<?php echo $taglist->tag; ?>"><?php echo $taglist->tag; ?></li>
					<?php } ?>
					</ul>
				</div>
			</div>
		</div>
	</div>


	<div id="formbuttons" class="container">
		<p class="column span-13" id="left_control_set">
			<input type="submit" name="submit" id="save" class="save" value="<?php _e('Save'); ?>">
		</p>
		<p class="column span-3 last" id="right_control_set"></p>
	</div>


</div>

</form>

<script type="text/javascript">
$(document).ready(function(){
	<?php if(isset($statuses['published']) && $post->status != $statuses['published']) : ?>
	$('#left_control_set').append($('<input type="submit" name="submit" id="publish" class="publish" value="<?php _e('Publish'); ?>">'));
	$('#publish').click(function(){
		$('#status').val(<?php echo $statuses['published']; ?>);
	});
	<?php endif; ?>
	<?php if(isset($post->slug) && ($post->slug != '')) : ?>
	$('#right_control_set').append($('<input type="submit" name="submit" id="delete" class="delete" value="<?php _e('Delete'); ?>">'));
	$('#delete').click(function(){
		$('#create-content').attr('action', '<?php URL::out( 'admin', array('page' => 'delete', 'slug' => $post->slug )); ?>');
	});
	<?php endif; ?>
	$('.media').dblclick(function(){
		$('#content').filter('.islabeled')
			.val('')
			.removeClass('islabeled');

		$("#content").val($("#content").val() + $('.foroutput', this).html());
	});
});
</script>

<?php include('footer.php'); ?>