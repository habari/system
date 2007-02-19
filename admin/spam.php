<?php include('header.php'); ?>
<div id="content-area">
	<div class="dashboard-block c3" id="welcome">
		<h1>Comments Marked as Spam</h1>
		<?php if( Comments::count_total( Comment::STATUS_SPAM ) ) { ?>
		<p>Below you will find comments awaiting moderation.</p>
	<form method="post" name="spam">
	<p class="submit"><input type="submit" name="remove_spam" value="Update!" /> <input type="checkbox" name="mass_spam_delete" id="mass_spam_delete" value="mass_spam_delete">Delete 'em all</p>
	<ul id="waiting">
		<?php foreach( Comments::get( array( 'status' => Comment::STATUS_SPAM, 'limit' => 30, 'orderby' => 'date DESC' ) ) as $comment ){ ?>
		<li>
			Comment by <?php echo $comment->name;?> on <a href="<?php URL::get( 'post', array( 'slug' => $comment->post->slug ) ); ?>"><?php echo $comment->post->slug; ?></a>
			<br /><small>(Commented created on <?php echo $comment->date; ?>)</small>
			<p><?php echo $comment->content; ?></p>
			<span class="manage">
				<p>Action: 
				<label>
					<input type="checkbox" name="spam_approve[<?php echo $comment->id; ?>]" id="spam_approve-<?php echo $comment->id; ?>" value="<?php echo $comment->id; ?>">Approve
				</label>
				<label>
				<input type="checkbox" name="spam_delete[<?php echo $comment->id; ?>]" id="spam_delete-<?php echo $comment->id; ?>" value="<?php echo $comment->id; ?>">Delete
				</label>
				<label>
				<input type="checkbox" name="spam_ignore" id="spam-<?php echo $comment->id; ?>" value="" checked="checked">Ignore
				</label>
				</p>
			</span><br />
		</li>
		<?php }	?>
	</ul>
	<p class="submit"><input type="submit" name="remove_spam" value="Update!" /> <input type="checkbox" name="mass_delete" id="mass_spam_delete" value="mass_spam_delete">Delete 'em all</p>
	</form>
	<?php } else { ?>
		<p>You are currently spam free!</p>
	<?php } ?>
	</div>
</div>
<?php include('footer.php'); ?>
