<div id="content-area">
<div class="dashbox c3" id="welcome">
		<h1>Unapproved Comments</h1>
		<?php if( Comments::count_total( Comment::STATUS_UNAPPROVED ) ) { ?>
		<p>Below you will find comments awaiting moderation.</p>
	<form method="post" name="moderation">
	<p class="submit"><input type="submit" value="Moderate!" /> <label><input type="checkbox" name="mass_delete" id="mass_delete" value="mass_delete">Delete 'em all</label></p>
	<ul id="waiting">
		<?php foreach( Comments::get( array( 'status' => Comment::STATUS_UNAPPROVED, 'limit' => 30, 'orderby' => 'date DESC' ) ) as $comment ){ ?>
		<li>
			Comment by <?php echo $comment->name;?> on <a href="<?php URL::get( 'post', array( 'slug' => $comment->post_slug ) ); ?>"><?php echo $comment->post_slug; ?></a>
			<br /><small>(Commented created on <?php echo $comment->date; ?>)</small>
			<p><?php echo $comment->content; ?></p>
			<span class="manage">
				<p>Action: 
				<label>
					<input type="radio" name="moderate[<?php echo $comment->id; ?>]" id="approve-<?php echo $comment->id; ?>" value="<?php echo Comment::STATUS_APPROVED; ?>">Approve
				</label>
				<label>
					<input type="radio" name="moderate[<?php echo $comment->id; ?>]" id="delete-<?php echo $comment->id; ?>" value="<?php echo Comment::STATUS_DELETED; ?>">Delete
				</label>
				<label>
					<input type="radio" name="moderate[<?php echo $comment->id; ?>]" id="spam-<?php echo $comment->id; ?>" value="" checked="checked">Ignore
				</label>
				</p>
			</span><br />
		</li>
		<?php }	?>
	</ul>
	<p class="submit"><input type="submit" value="Moderate!" /> <label><input type="checkbox" name="mass_delete" id="mass_delete" value="mass_delete">Delete 'em all</label></p>
	</form>
	<?php } else { ?>
		<p>You currently have no comments to moderate</p>
	<?php } ?>
	</div>
</div>
