<?php include('header.php'); ?>


<div class="container timeline">
	<span class="older pct10"><a href="#">&laquo; Older</a></span>
	<span class="currentposition pct15 minor">0-20 of 480</span>
	<span class="search pct50"><input type="search" placeholder="Type and wait to search for any comments component" autosave="habaricontent" results="10"></input></span>
	<span class="nothing pct15">&nbsp;</span>
	<span class="newer pct10"><a href="#">Newer &raquo;</a></span>
</div>


<div class="container manage comments">

	<?php foreach( $comments as $comment ) : ?>

	<div class="item clear" id="uniquecommentid">
		<div class="head clear">
			<span class="checkboxandtitle pct25">
				<input type="checkbox" class="checkbox"></input>
				<a href="#" class="author"><?php echo $comment->name."\r\n";?></a>
			</span>
			<span class="entry pct30"><a href="<?php echo $comment->post->permalink ?>"><?php echo $comment->post->title; ?></a><a href="#"><span class="dim">in</span> 'Why Habari Rocks'</a></span>
			<span class="time pct10"><a href="#"><span class="dim">at</span> 19.18</a></span>
			<span class="date pct15"><a href="#"><span class="dim">on</span> Jan 20, 2007</a></span>
			<ul class="dropbutton">
				<li><a href="#">Delete</a></li>
				<li><a href="#">Spam</a></li>
				<li><a href="#">Approve</a></li>
				<li><a href="#">Unapprove</a></li>
				<li><a href="#">Edit</a></li>
			</ul>
		</div>
		
		<div class="infoandcontent clear">
			<span class="authorinfo pct25 minor">
				<?php if ($comment->url != '')
					echo '<a href="' . $comment->url . '">' . $comment->url . '</a>'."\r\n"; ?>
				<?php if ( $comment->email != '' )
					echo '<a href="mailto:' . $comment->email . '">' . $comment->email . '</a>'."\r\n"; ?>
				<!--<a href="#">86.73.54.12</a><br>-->
			</span>
			<span class="content pct75">Morbi posuere lacinia sapien. Vestibulum leo. Sed ac lacus ut lorem ultrices fermentum. Sed eget massa quis mauris dapibus aliquet. Sed fermentum, ipsum a egestas porttitor, tellus dolor scelerisque nibh, at sodales lorem mi vitae risus. Integer elementum eros vitae ante. Pellentesque posuere purus in orci. Ut eget urna id enim venenatis ullamcorper. Pellentesque eget lorem ac felis sollicitudin sagittis. Sed nisi.</span>
		</div>
	</div>

	<?php endforeach; ?>

</div>


<div class="container transparent">

	<div class="item controls">
		<span class="checkboxandselected pct25">
			<input type="checkbox"></input>
			<span class="selectedtext minor none">None selected</span>
		</span>
		<span class="buttons">
			<input type="button" value="Delete" class="deletebutton"></input>
			<input type="button" value="Spam" class="spambutton"></input>
			<input type="button" value="Unapprove" class="spambutton"></input>
			<input type="button" value="Approve" class="approvebutton"></input>
		</span>
	</div>
</div>


<?php include('footer.php'); ?>