<?php include('header.php'); ?>
<div class="container">
	<div class="column prepend-1 span-22 append-1">
		<h2>Comments Marked as Spam</h2>
		<?php if( Comments::count_total( Comment::STATUS_SPAM ) ) { ?>
		<p>Below you will find comments awaiting moderation.</p>
	<form method="post" name="spam" action="<?php URL::out( 'admin', array( 'page' => 'moderate', 'result' => 'success' ) ); ?>">
	<p class="submit"><input type="submit" name="submit" value="Update!"> <input type="checkbox" name="mass_spam_delete" id="mass_spam_delete" value="1">Delete 'em all</p>
	<ul id="waiting">
		<?php foreach( Comments::get( array( 'status' => Comment::STATUS_SPAM, 'limit' => 30, 'orderby' => 'date DESC' ) ) as $comment ){ ?>
		<li>
			Comment by <?php echo $comment->name;?> on <a href="<?php URL::out( 'post', array( 'slug' => $comment->post->slug ) ); ?>"><?php echo $comment->post->slug; ?></a>
			<br><small>(Commented created on <?php echo $comment->date; ?>)</small>
			<p><?php echo $comment->content; ?></p>
			<span class="manage">
				<p>Action:
				<label>
					<input type="radio" name="moderate[<?php echo $comment->id; ?>]" id="approve-<?php echo $comment->id; ?>" value="approve">Approve
				</label>
				<label>
					<input type="radio" name="moderate[<?php echo $comment->id; ?>]" id="delete-<?php echo $comment->id; ?>" value="delete">Delete
				</label>
				<label>
					<input type="radio" name="moderate[<?php echo $comment->id; ?>]" id="ignore-<?php echo $comment->id; ?>" value="spam" checked>Leave as Spam
				</label>
				</p>
			</span><br>
		</li>
		<?php }	?>
	</ul>
	<input type="hidden" name="returnpage" value="spam" >
	<p class="submit"><input type="submit" name="submit" value="Update!" > <input type="checkbox" name="mass_spam_delete" id="mass_spam_delete2" value="1">Delete 'em all</p>
	</form>
	<?php } else { ?>
		<p>You are currently spam free!</p>
	<?php } ?>
	</div>
</div>
<?php include('footer.php'); ?>
