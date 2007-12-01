<?php include('header.php'); ?>

<div class="container">
	<hr>
	<?php if(Session::has_messages()) {Session::messages_out();} ?>
	<div class="column span-24 last">
		<h1><?php _e('Comments'); ?></h1>
		<p><?php _e('Here you will find all the comments, including those deleted. You can also manage  pingbacks.'); ?></p>

		<div class="column span-7 first" id="stats">
			<h3><?php _e('Comment Statistics'); ?></h3>
			<table width="100%" cellspacing="0">
				<tr><td><?php _e('Total Approved Comments'); ?></td><td><?php echo Comments::count_total( Comment::STATUS_APPROVED ); ?></td></tr>
			<tr><td><?php _e('Total Unapproved Comments'); ?></td><td><?php echo Comments::count_total( Comment::STATUS_UNAPPROVED ); ?></td></tr>
			<tr><td><?php _e('Total Spam Comments'); ?></td><td><?php echo Comments::count_total( Comment::STATUS_SPAM ); ?></td></tr>
			</table>
		</div>

		<div class="column span-17 last push-1">
			<form method="post" action="<?php URL::out('admin', 'page=moderate'); ?>" class="buttonform">
			<p>
				<label>Search comments: <input type="textbox" size="22" name="search" value="<?php echo $search; ?>"></label> <input type="submit" name="do_search" value="<?php _e('Search'); ?>">
				<label><?php printf( _t('Limit: %s'), Utils::html_select('limit', $limits, $limit)); ?></label>
				<label><?php printf( _t('Page: %s'), Utils::html_select('index', $pages, $index)); ?></label>
				<a href="<?php URL::out('admin', 'page=moderate'); ?>">Reset</a>
			</p>
			<p>
				<label>Content <input type="checkbox" name="search_fields[]" class="search_field" value="content"<?php echo in_array('content', $search_fields) ? ' checked' : ''; ?>></label>
				<label>Author <input type="checkbox" name="search_fields[]" class="search_field" value="name"<?php echo in_array('name', $search_fields) ? ' checked' : ''; ?>></label>
				<label>IP Address<input type="checkbox" name="search_fields[]" class="search_field" value="ip"<?php echo in_array('ip', $search_fields) ? ' checked' : ''; ?>></label>
				<label>E-mail <input type="checkbox" name="search_fields[]" class="search_field" value="email"<?php echo in_array('email', $search_fields) ? ' checked' : ''; ?>></label>
				<label>URL <input type="checkbox" name="search_fields[]" class="search_field" value="url"<?php echo in_array('url', $search_fields) ? ' checked' : ''; ?>></label>
				<?php echo Utils::html_select('search_status', $statuses, $search_status, array( 'class'=>'longselect')); ?>
				<?php echo Utils::html_select('search_type', $types, $type, array( 'class'=>'longselect')); ?>
			</p>
			</form>
		</div>

	</div>

	<hr>

	<div class="column span-24 last">
<?php if( count($comments) ) { ?>
		<form method="post" name="moderation" action="<?php URL::out( 'admin', array( 'page' => 'moderate', 'search_status' => $search_status ) ); ?>">
			<input type="hidden" name="search" value="<?php echo $search; ?>">
			<input type="hidden" name="limit" value="<?php echo $limit; ?>">
			<input type="hidden" name="index" value="<?php echo $index; ?>">
			<?php foreach($search_fields as $field): ?>
			<input type="hidden" name="search_fields[]" value="<?php echo $field; ?>">
			<?php endforeach; ?>
			<input type="hidden" name="search_status" value="<?php echo $search_status; ?>">
			<input type="hidden" name="search_type" value="<?php echo $type; ?>">

			<div>
				<p class="submit">
					<input type="submit" name="do_update" value="<?php _e('Moderate!'); ?>">
<?php if ($mass_delete != '') : ?>
					<label><input type="checkbox" name="<?php echo $mass_delete; ?>" id="mass_delete" value="1"><?php _e("Delete 'em all"); ?></label>
<?php endif; ?>
				</p>
			</div>

			<div>
				Mark All For:
				<a href="#" onclick="$('.radio_approve').attr('checked', 'checked');return false;"><?php _e('Approval'); ?></a> &bull;
				<a href="#" onclick="$('.radio_delete').attr('checked', 'checked');return false;"><?php _e('Deletion'); ?></a> &bull;
				<a href="#" onclick="$('.radio_spam').attr('checked', 'checked');return false;"><?php _e('Spam'); ?></a> &bull;
				<a href="#" onclick="$('.radio_unapprove').attr('checked', 'checked');return false;"><?php _e('Unapproval'); ?></a>
			</div>

			<div id="waiting">
<?php foreach( $comments as $comment ) : ?>
			<hr>
			<div class="comment">
				<div class="comment_header">
				<strong>Author:</strong> <?php echo $comment->name."\r\n";?>
				<?php
				if ($comment->url != '')
					echo '&bull; <strong>Site:</strong> <a href="' . $comment->url . '">' . $comment->url . '</a>'."\r\n";
				?>
				<?php
				if ( $comment->email != '' )
					echo '&bull; <strong>E-mail:</strong> <a href="mailto:' . $comment->email . '">' . $comment->email . '</a>'."\r\n";
				?>
				&bull; <strong>Post:</strong> <a href="<?php echo $comment->post->permalink ?>"><?php echo $comment->post->title; ?></a>
				&bull; <?php echo $comment->date."\r\n"; ?>
				</div>
				<div class="comment_content" id="comment_content_<?php echo $comment->id; ?>"<?php echo ($comment->status == COMMENT::STATUS_SPAM) ? ' style="display:none;"' : '' ?>>
					<?php echo htmlentities($comment->content_out, ENT_COMPAT, 'UTF-8'); ?>
				</div>
<?php if ($comment->status == COMMENT::STATUS_SPAM) : ?>
					<a href="" onclick="$(this).hide();$('#comment_content_<?php echo $comment->id; ?>').show();return false;">[Show Comment]</a>
<?php endif; ?>
<?php if ($comment->info->spamcheck) { ?>
				<ul style="list-style:disc;margin-top:10px;">
<?php
$reasons = (array)$comment->info->spamcheck;
$reasons = array_unique($reasons);
foreach($reasons as $reason):
?>
					<li><?php echo $reason; ?></li>
				<?php endforeach; ?>
				</ul>
<?php } ?>
				<div class="comment_footer">
					<p><?php _e('Action:'); ?>
					<label><input type="radio" class="radio_approve" name="comment_ids[<?php echo $comment->id; ?>]" id="approve-<?php echo $comment->id; ?>" value="approve" <?php echo $default_radio['approve']; ?>><?php _e('Approve'); ?></label>
					<label><input type="radio" class="radio_delete" name="comment_ids[<?php echo $comment->id; ?>]" id="delete-<?php echo $comment->id; ?>" value="delete" <?php echo $default_radio['delete']; ?>><?php _e('Delete'); ?></label>
					<label><input type="radio" class="radio_spam" name="comment_ids[<?php echo $comment->id; ?>]" id="spam-<?php echo $comment->id; ?>" value="spam" <?php echo $default_radio['spam']; ?>><?php _e('Mark as Spam'); ?></label>
					<label><input type="radio" class="radio_unapprove" name="comment_ids[<?php echo $comment->id; ?>]" id="unapprove-<?php echo $comment->id; ?>" value="unapprove" <?php echo $default_radio['unapprove']; ?>><?php _e('Unapprove'); ?></label>
					<label><input type="radio" class="radio_edit" name="comment_ids[<?php echo $comment->id; ?>]" id="edit-<?php echo $comment->id; ?>" onclick="$('#edit_comment_<?php echo $comment->id; ?>').show();" value="edit" <?php echo $default_radio['edit']; ?>><?php _e('Edit'); ?></label>
					</p>
					<div id="edit_comment_<?php echo $comment->id; ?>" style="display:none;">
					<h2>Edit this comment</h2>
						<label>
						<p>Name: 
							<input type="text" name="name" id="name" value="<?php echo $comment->name; ?>">
						</p>
						</label>
						<label>
						<p>Email: 
							<input type="text" name="email" id="email" value="<?php echo $comment->email; ?>">
						</p>
						</label>
						<label>
						<p>Website: 
							<input type="text" name="url" id="url" value="<?php echo $comment->url; ?>">
						</p>
						</label>
						<label>
							<textarea name="content" id="content"><?php echo $comment->content; ?></textarea>
						</label>
					</div>
				</div>
			</div>
<?php endforeach; ?>
			<hr>
			</div>
			<div>
				<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>">
				<input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
				<input type="hidden" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
				<input type="submit" name="do_update" value="<?php _e('Moderate!'); ?>">
<?php if ($mass_delete != '') : ?>
				<label><input type="checkbox" name="<?php echo $mass_delete; ?>" id="mass_delete1" value="1"><?php _e("Delete 'em all"); ?></label>
<?php endif; ?>
			</div>
		</form>
<?php } else { ?>
			<p><?php _e('You currently have no comments to moderate.'); ?></p>
<?php } ?>
	</div>
</div>
<script type="text/javascript">
$('.comment:even').css('background-color', '#EEE');
$('.comment_header:even').css('border-color', '#DDD');
$('.comment_footer:even').css('border-color', '#DDD');
</script>

<?php include('footer.php'); ?>
