<?php include('header.php'); ?>

<div class="container navigator">
	<span class="older pct10"><a href="#">&laquo; Older</a></span>
	<span class="currentposition pct15 minor">0-0 of 0</span>
	<span class="search pct50"><input type="search" placeholder="Type and wait to search for any entry component" autosave="habaricontent" results="10"></span>
	<span class="nothing pct15">&nbsp;</span>
	<span class="newer pct10"><a href="#">Newer &raquo;</a></span>

	<div class="timeline">
		<div class="years">
			<div class="months">
				<?php foreach($monthcomments as $cdata): ?>
				<div><span style="width: <?php echo $cdata->ct; ?>px"><?php echo date('M', mktime(0, 0, 0, $cdata->month)) ?></span></div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="track">
			<div class="handle">
				<span class="resizehandleleft"></span>
				<span class="resizehandleright"></span>
			</div>
		</div>

	</div>

</div>

<form method="post" name="moderation" action="<?php URL::out( 'admin', array( 'page' => 'comments', 'search_status' => $search_status ) ); ?>">
	<input type="hidden" name="search" value="<?php echo $search; ?>">
	<input type="hidden" name="limit" value="<?php echo $limit; ?>">
	<input type="hidden" name="index" value="<?php echo $index; ?>">
	<input type="hidden" name="search_status" value="<?php echo $search_status; ?>">
	<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>">

	<div class="container transparent">

		<div class="item controls">
			<span class="checkboxandselected pct25">
				<input type="checkbox"></input>
				<span class="selectedtext minor none">None selected</span>
			</span>
			<span class="buttons">
				<input type="submit" name="do_delete" value="Delete" class="deletebutton"></input>
				<input type="submit" name="do_spam" value="Spam" class="spambutton"></input>
				<input type="submit" name="do_unapprove" value="Unapprove" class="spambutton"></input>
				<input type="submit" name="do_approve" value="Approve" class="approvebutton"></input>
			</span>
		</div>
	</div>

<div class="container manage comments">

	<?php foreach( $comments as $comment ) : ?>

	<div class="item clear" id="comment_<?php echo $comment->id; ?>">
		<div class="head clear">
			<span class="checkboxandtitle pct25">
				<input type="checkbox" class="checkbox" name="comment_ids[<?php echo $comment->id; ?>]" id="comments_ids[<?php echo $comment->id; ?>]" value="1"></input>
				<?php if($comment->url != ''): ?>
				<a href="#" class="author"><?php echo $comment->name; ?></a>
				<?php else: ?>
				<?php echo $comment->name; ?>
				<?php endif; ?>
			</span>
			<span class="entry pct30"><a href="<?php echo $comment->post->permalink ?>#comment-<?php echo $comment->id; ?>"><?php echo $comment->post->title; ?></a></span>
      <span class="time pct10"><a href="#"><span class="dim">at</span> <?php echo date('H.i', strtotime($comment->date));?></a></span>
      <span class="date pct15"><a href="#"><span class="dim">on</span> <?php echo date('M d, Y', strtotime($comment->date));?></a></span>
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
			</span>
			<span class="content pct75"><?php echo $comment->content ?></span>
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
			<input type="submit" name="do_delete" value="Delete" class="deletebutton"></input>
			<input type="submit" name="do_spam" value="Spam" class="spambutton"></input>
			<input type="submit" name="do_unapprove" value="Unapprove" class="spambutton"></input>
			<input type="submit" name="do_approve" value="Approve" class="approvebutton"></input>
		</span>
	</div>
</div>

</form>


<?php include('footer.php'); ?>
