<?php include('header.php'); ?>
<div id="content-area">
	<div class="dashboard-block c3" id="welcome">
		<h1>Unapproved Comments</h1>
		<?php if( Comments::count_total( Comment::STATUS_UNAPPROVED ) ) { ?>
		<p>Below you will find comments awaiting moderation.</p>
		<form method="post" name="moderation" action="<?php URL::out( 'admin', array( 'page' => 'moderate', 'result' => 'success' ) ); ?>">
		<p class="submit"><input type="submit" name="moderate" value="Moderate!" /> <label><input type="checkbox" name="mass_delete" id="mass_delete" value="1">Delete 'em all</label></p>
		<ul id="waiting">
		<?php foreach( Comments::get( array( 'status' => Comment::STATUS_UNAPPROVED, 'limit' => 30, 'orderby' => 'date DESC' ) ) as $comment ){ ?>
			<li>
				Comment by <?php echo $comment->name;?>
				<a href="<?php URL::get('post', array( 'post_id' => $comment->post_id ) ); ?>"><?php echo $comment->post->slug; ?></a>
				<br /><small>(Commented created on <?php echo $comment->date; ?>)</small>
				<p><?php echo $comment->content; ?></p>
				<span class="manage">
				<p>
					Action:
					<label>
						<input type="radio" name="moderate[<?php echo $comment->id; ?>]" id="approve-<?php echo $comment->id; ?>" value="approve">Approve
					</label>
					<label>
						<input type="radio" name="moderate[<?php echo $comment->id; ?>]" id="delete-<?php echo $comment->id; ?>" value="delete">Delete
					</label>
					<label>
						<input type="radio" name="moderate[<?php echo $comment->id; ?>]" id="spam-<?php echo $comment->id; ?>" value="spam">Mark as Spam
					</label>
					<label>
						<input type="radio" name="moderate[<?php echo $comment->id; ?>]" id="spam-<?php echo $comment->id; ?>" value="unapprove" checked="checked">Leave Unapproved
					</label>
				</p>
			</li>
		<?php }	?>
		</ul>
		<p class="submit"><input type="submit" name="submit" value="Moderate!" /> <label><input type="checkbox" name="mass_delete" id="mass_delete1" value="1" />Delete 'em all</label></p>
		</form>
	<?php } else { ?>
		<p>You currently have no comments to moderate</p>
	<?php } ?>
	</div>
</div>
<?php include('footer.php'); ?>
