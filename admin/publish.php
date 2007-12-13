<?php include('header.php'); ?>
<form name="create-content" id="create-content" method="post" action="<?php URL::out( 'admin', 'page=publish' ); ?>">

<div class="publish">
	<?php if(Session::has_messages()) {Session::messages_out();} ?>

	<div class="container">

		<?php if(!$newpost): ?>
		<a href="<?php echo $post->permalink; ?>" class="viewpost">View Post</a>
		<?php endif; ?>

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
			<?php $this->display('publish_settings'); ?>
			</div>
		</div>
		<div id="tagsettings" class="splitter">
			<div class="splitterinside">
			<?php $this->display('publish_tags'); ?>
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
