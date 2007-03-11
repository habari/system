<?php include_once( 'header.php' ); ?>
<script type="text/javascript" src="/scripts/restrict.jquery.js"></script>
<script type="text/javascript">
$(document).ready(function () {
/*
$('ul#waiting li').quicksearch({
    position: 'before',
    attached: 'ul#waiting',
    loaderImg: '/system/admin/images/spinner.gif',
    focusOnLoad: true
   });
	*/
	$('#remove').click(function() {
		$("input:radio[@id=^'unapprove']").checked;
	});

});
</script>
<div id="content-area">
<div class="dashbox c3" id="welcome">
		<h1>Comments on <?php Options::out( 'title' ); ?></h1>
		<?php if( Comments::count_total( Comment::STATUS_APPROVED ) ) { ?>
		<p>Below you will find comments awaiting moderation.</p>
	<form method="post" name="moderation" action="<?php URL::out( 'admin', array( 'page' => 'moderate', 'result' => 'success' ) ); ?>">
	<p class="submit"><input type="button" id="remove" value="Mark all for Deletion" /> or <input type="button" id="demote" value="Mark all for Unapproval" /> or <input type="button" id="spamify" value="Mark all as Spam" /> then <input type="submit" value="Execute!" /></p>
	<ul id="waiting">
		<?php foreach( Comments::get( array( 'status' => Comment::STATUS_APPROVED, 'limit' => 30, 'orderby' => 'date DESC' ) ) as $comment ) {
		$post= Post::get( array( 'id' => $comment->post_id, ) );
		?>
		<li>
			Comment by <?php echo $comment->name;?> on
			<a href="<?php URL::out('display_posts_by_slug', array('slug'=>$post->slug) ); ?>#comment-<?php echo $comment->id; ?>" title="View this post"><?php echo $post->title; ?></a>
			<br /><small>(Comment created on <?php echo $comment->date; ?>)</small>
			<p><?php echo $comment->content; ?></p>
			<span class="manage">
				<p>Action:
				<label>
					<input type="radio" name="moderate[<?php echo $comment->id; ?>]" id="unapprove" value="unapprove">Unapprove
				</label>
				<label>
					<input type="radio" name="moderate[<?php echo $comment->id; ?>]" id="delete-<?php echo $comment->id; ?>" value="delete">Delete
				</label>
				<label>
					<input type="radio" name="moderate[<?php echo $comment->id; ?>]" id="spam-<?php echo $comment->id; ?>" value="spam">Mark as Spam
				</label>
				</p>
			</span><br />
		</li>
		<?php }	?>
	</ul>
	<input type="hidden" name="returnpage" value="comments" />
	<p class="submit"><input type="submit" value="Moderate!" /></p>
	</form>
	<?php } else { ?>
		<p>There are currently no comments on this blog.</p>
	<?php } ?>
	</div>
</div>
<?php include_once( 'footer.php' ); ?>