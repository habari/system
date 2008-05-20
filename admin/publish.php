<?php include('header.php'); ?>


<form name="create-content" id="create-content" method="post" action="<?php URL::out( 'admin', 'page=publish' ); ?>">

<div class="create">

	<?php if(!$newpost): ?>
	<div class="container">
		<a href="<?php echo $post->permalink; ?>" class="viewpost">View Post</a>
	</div>
	<?php endif; ?>

	<div class="container">
		<p>
			<label for="title" class="incontent"><?php _e('Title'); ?></label>
			<input type="text" name="title" id="title" class="styledformelement" size="100%" value="<?php if ( !empty($post->title) ) { echo $post->title; } ?>" tabindex='1'>
		</p>
	</div>
	
	<?php if (isset($silos) && count($silos)) : ?>
	<div class="container pagesplitter">
		<ul id="mediatabs" class="tabs">
			<?php
			$ct = 0;
			foreach($silos as $silodir):
				$ct++;
			?><li><a href="#silo_<?php echo $ct; ?>"><?php echo $silodir->path; ?></a></li><?php endforeach; ?>
		</ul>

		<?php
		$ct = 0;
		foreach($silos as $silodir):
			$ct++;
		?>
			<div id="silo_<?php echo $ct; ?>" class="splitter mediasplitter">
				<div class="toload pathstore" style="display:none;"><?php echo $silodir->path; ?></div>
				<div class="splitterinside">
					<div id="mediaspinner"></div>
					<div class="media_controls">
						<input type="search" placeholder="Search descriptions, names and tags" autosave="habarisettings" results="10"></input>
						<ul>
							<li><a href="#" onclick="habari.media.showdir('<?php echo $silodir->path; ?>');return false;">Root</a></li>
						</ul>
						<div class="upload"><input type="file"></input><input type="submit" value="Upload"></input></div>
					</div>
					<div class="media_browser">
						<div class="media_row">
							<ul class="mediadir"></ul>
							<div class="mediaphotos"></div>
						</div>
					</div>
					<div class="media_panel"></div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<div class="container">
		<p>
			<label for="content" class="incontent"><?php _e('Content'); ?></label>
			<textarea name="content" id="content" class="styledformelement resizable" rows="20" cols="114" tabindex='2'><?php if ( !empty($post->content) ) { echo htmlspecialchars($post->content); } ?></textarea>
		</p>
	</div>

	<div class="container">
		<p>
			<label for="tags" class="incontent"><?php _e('Tags, separated by, commas')?></label>
			<input type="text" name="tags" id="tags" class="styledformelement" <?php if ( !empty( $tags ) ) { echo 'value="'.$tags.'"'; } ?> tabindex="3">
		</p>
	</div>


	<div class="container pagesplitter">
		<ul class="tabcontrol tabs">
			<?php
			$first = 'first';
			$ct = 0;
			$last = '';
			foreach($controls as $controlsetname => $controlset) :
				$ct++;
				if($ct == count($controls)) {
					$last = 'last';
				}
				$class = "{$first} {$last}";
				$first = '';
				$cname = preg_replace('%[^a-z]%', '', strtolower($controlsetname)) . '_settings';
				echo <<< EO_CONTROLS
<li class="{$cname} {$class}"><a href="#{$cname}">{$controlsetname}</a></li>
EO_CONTROLS;
			endforeach;
			?>
		</ul>

		<?php foreach($controls as $controlsetname => $controlset) {
			$cname = preg_replace('%[^a-z]%', '', strtolower($controlsetname)) . '_settings'; ?>

			<div id="<?php echo $cname; ?>" class="splitter">
				<div class="splitterinside">
				<?php echo $controlset; ?>
				</div>
			</div>

		<?php } ?>

	</div>

	<div style="display:none;" id="hiddenfields">
		<input type="hidden" name="content_type" value="<?php echo $content_type; ?>">
		<?php if ( $post->slug != '' ) { ?>
		<input type="hidden" name="slug" id="slug" value="<?php echo $post->slug; ?>">
		<?php } ?>
	</div>

	<div class="container buttons">
		<p id="right_control_set">
			<input type="submit" name="submit" id="save" class="save" value="<?php _e('Save'); ?>">
		</p>
		<p id="left_control_set"></p>
	</div>


</div>

</form>

<script type="text/javascript">
$(document).ready(function(){
	<?php if(isset($statuses['published']) && $post->status != $statuses['published']) : ?>
	$('#right_control_set').append($('<input type="submit" name="submit" id="publish" class="publish" value="<?php _e('Publish'); ?>">'));
	$('#publish').click(function(){
		$('#status').val(<?php echo $statuses['published']; ?>);
	});
	<?php endif; ?>
	<?php if(isset($post->slug) && ($post->slug != '')) : ?>
	$('#left_control_set').append($('<input type="submit" name="submit" id="delete" class="delete" value="<?php _e('Delete'); ?>">'));
	$('#delete').click(function(){
		$('#create-content')
			.append($('<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>"><input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>"><input type="hidden" name="digest" value="<?php echo $wsse['digest']; ?>">'))
			.attr('action', '<?php URL::out( 'admin', array('page' => 'delete_post', 'slug' => $post->slug )); ?>');
	});
	<?php endif; ?>
});
</script>

<?php include('footer.php'); ?>
