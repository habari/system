<?php include('header.php'); ?>

<div class="container">
	<hr>
	<div class="column prepend-1 span-22 append-1">
		<?php
		if ( isset( $result ) ) {
			switch( $result ) {
				case 'success':
					echo '<p class="update">' . _t('Comments moderated successfully.') . '</p>';
					break;
			}
		}
		?>
		<h1><?php _e('Habari Comments'); ?></h1>
		<div id="<?php echo $active_tab; ?>">
			<ul id="tabnav">
				<li class="tab1"><a href="<?php URL::out('admin', 'page=moderate&show=approved'); ?>"><?php _e('Approved'); ?> (<?php echo Comments::count_total( Comment::STATUS_APPROVED ); ?>)</a></li>
				<li class="tab2"><a href="<?php URL::out('admin', 'page=moderate&show=unapproved'); ?>"><?php _e('Unapproved'); ?> (<?php echo Comments::count_total( Comment::STATUS_UNAPPROVED ); ?>)</a></li>
				<li class="tab3"><a href="<?php URL::out('admin', 'page=moderate&show=spam'); ?>"><?php _e('Spam'); ?> (<?php echo Comments::count_total( Comment::STATUS_SPAM ); ?>)</a></li>
			</ul>
		</div>
		<div id="searchform">
			<form method="post" action="<?php URL::out('admin', 'page=moderate'); ?>" class="buttonform">
			<p>
				<label>Search comments: <input type="textbox" size="22" name="search" value="<?php echo $search; ?>"></label>
				<label><?php _e('Limit'); ?>: <?php echo Utils::html_select('limit', $limits, $limit); ?></label>
				<label><?php _e('Page'); ?>: <?php echo Utils::html_select('index', $pages, $index); ?></label>
				<input type="submit" name="do_search" value="<?php _e('Search'); ?>">
				<input type="reset" name="do_reset" value="Reset">
				<a href="#" onclick="$('.searchoptions').toggle()">Advanced</a>
			</p>
			<p class="searchoptions" style="display:none;">
				<label>Content <input type="checkbox" name="search_fields[]" value="content"<?php echo in_array('content', $search_fields) ? ' checked' : ''; ?>></label>
				<label>Author <input type="checkbox" name="search_fields[]" value="name"<?php echo in_array('name', $search_fields) ? ' checked' : ''; ?>></label>
				<label>IP Address<input type="checkbox" name="search_fields[]" value="ip"<?php echo in_array('ip', $search_fields) ? ' checked' : ''; ?>></label>
				<label>E-mail <input type="checkbox" name="search_fields[]" value="email"<?php echo in_array('email', $search_fields) ? ' checked' : ''; ?>></label>
				<label>URL <input type="checkbox" name="search_fields[]" value="url"<?php echo in_array('url', $search_fields) ? ' checked' : ''; ?>></label>
				<?php echo Utils::html_select('search_status', $statuses, $status, array( 'class'=>'longselect')); ?>
				<?php echo Utils::html_select('search_type', $types, $type, array( 'class'=>'longselect')); ?>
			</p>
			</form>
		</div>
	</div>
	
	<hr class="space">
	
	<div class="column prepend-1 span-22 append-1 last">
<?php if( count($comments) ) { ?>
		<form method="post" name="moderation" action="<?php URL::out( 'admin', array( 'page' => 'moderate', 'result' => 'success' ) ); ?>">
			
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
					<?php _e('Action:'); ?>
					
					<label><input type="radio" class="radio_approve" name="comment_ids[<?php echo $comment->id; ?>]" id="approve-<?php echo $comment->id; ?>" value="approve" <?php echo $default_radio['approve']; ?>><?php _e('Approve'); ?></label>
					<label><input type="radio" class="radio_delete" name="comment_ids[<?php echo $comment->id; ?>]" id="delete-<?php echo $comment->id; ?>" value="delete" <?php echo $default_radio['delete']; ?>><?php _e('Delete'); ?></label>
					<label><input type="radio" class="radio_spam" name="comment_ids[<?php echo $comment->id; ?>]" id="spam-<?php echo $comment->id; ?>" value="spam" <?php echo $default_radio['spam']; ?>><?php _e('Mark as Spam'); ?></label>
					<label><input type="radio" class="radio_unapprove" name="comment_ids[<?php echo $comment->id; ?>]" id="unapprove-<?php echo $comment->id; ?>" value="unapprove" <?php echo $default_radio['unapprove']; ?>><?php _e('Unapprove'); ?></label>
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

<hr class="space">

<?php include('footer.php'); ?>
