<?php

function table($headers, $data, $sort = null){

	$html .= '<table><thead><tr>';
	foreach($headers as $headercaption => $headerfield) {
		$html .= '<th>' . $header . '</th>';
	}
	$html .= '</tr></thead><tbody>';

	foreach($data as $row) {
		$html .= '<tr>';
		foreach($headers as $field) {
			$html .= '<td>' . htmlspecialchars($row->$field) . '</td>';
		}
		$html .= '</tr>';
	}
	
	$html .= '</tbody></table>';
	return $html;
}

$SCoPH = DB::get_value('select round(360000.0 / (UNIX_TIMESTAMP(max(date)) - UNIX_TIMESTAMP(min(date))), 2) from (select date from ' . DB::table('comments') . ' WHERE status = ' . Comment::STATUS_SPAM . ' order by date desc limit 10) as c3');

?>
<?php include('header.php'); ?>
<div id="content-area">
	<div class="dashboard-block" id="stats">
		<h4><?php _e('Comment Statistics'); ?></h4>
		<ul>
			<li><span class="right"><?php echo Comments::count_total( Comment::STATUS_APPROVED ); ?></span>
			<?php _e('Total Approved Comments'); ?></li>
			<li><span class="right"><?php echo Comments::count_total( Comment::STATUS_UNAPPROVED ); ?></span>
			<?php _e('Total Unapproved Comments'); ?></li>
			<li><span class="right"><?php echo Comments::count_total( Comment::STATUS_SPAM ); ?></span>
			<?php _e('Total Spam Comments'); ?></li>
			<li><span class="right"><?php echo $SCoPH; ?></span>
			<?php _e('Spam Comments Per Hour'); ?></li>
		</ul>
	</div>
	<div class="dashboard-block c2">
		<?php
		
		// Decide what to display
		
		if( empty($show) ) {
			$show = 'unapproved';
		}
		$default_radio = array(
			'approve'=>'',
			'delete'=>'',
			'spam'=>'',
			'unapprove'=>'',
		);
		switch($show) {
			case 'spam':
				$comments = Comments::get( array( 'status' => Comment::STATUS_SPAM, 'limit' => 30, 'orderby' => 'date DESC' ) );
				$mass_delete = 'mass_spam_delete';
				$default_radio['spam']= ' checked="checked"';
				break;
			case 'approved':
				$comments = Comments::get( array( 'status' => Comment::STATUS_APPROVED, 'limit' => 30, 'orderby' => 'date DESC' ) );
				$mass_delete = '';
				$default_radio['approve']= ' checked="checked"';
				break;
			case 'unapproved':
			default:
				$comments = Comments::get( array( 'status' => Comment::STATUS_UNAPPROVED, 'limit' => 30, 'orderby' => 'date DESC' ) );
				$mass_delete = 'mass_delete';
				$default_radio['unapprove']= ' checked="checked"';
				break;			
		}
		
		?>
		<?php if( count($comments) ) { ?>
		<p><?php _e('Below you will find comments awaiting moderation.'); ?></p>
		<form method="post" name="moderation" action="<?php URL::out( 'admin', array( 'page' => 'moderate', 'result' => 'success' ) ); ?>">

		<p class="submit"><input type="submit" name="moderate" value="<?php _e('Moderate!'); ?>" />
		<?php if ($mass_delete != '') : ?>
		<label><input type="checkbox" name="<?php echo $mass_delete; ?>" id="mass_delete" value="1"><?php _e("Delete 'em all"); ?></label></p>
		<?php endif; ?>

		<p class="manage">
			Mark All For:
			<a href="" onclick="$('.radio_approve').attr('checked', 'checked');return false;"><?php _e('Approval'); ?></a> &middot;
			<a href="" onclick="$('.radio_delete').attr('checked', 'checked');return false;"><?php _e('Deletion'); ?></a> &middot;
			<a href="" onclick="$('.radio_spam').attr('checked', 'checked');return false;"><?php _e('Spam'); ?></a> &middot;
			<a href="" onclick="$('.radio_unapprove').attr('checked', 'checked');return false;"><?php _e('Unapproval'); ?></a>
		</p>
		
		<ul id="waiting">
		<?php foreach( $comments as $comment ){ ?>
			<li class="moderated_comment">
				Comment by <?php echo $comment->name;?> 
				<?php 
				$metadata = array();
				if ($comment->url != '') {
					$metadata[] = '<a href="' . $comment->url . '">' . $comment->url . '</a>';
				}
				if ( $comment->email != '' ) {
					$metadata[] = '<a href="mailto:' . $comment->email . '">' . $comment->email . '</a>';
				}
				if ( count($metadata) > 0 ) {
					echo '<small>(' . implode(' &middot; ', $metadata) . ')</small>';
				}
				?>
				On <a href="<?php URL::out('display_posts_by_slug', array( 'slug' => $comment->post->slug ) ); ?>"><?php echo $comment->post->title; ?></a>
				<br /><small>(Commented created on <?php echo $comment->date; ?>)</small>
				<div class="comment_content" id="comment_content_<?php echo $comment->id; ?>"
				<?php if ($comment->status == COMMENT::STATUS_SPAM) {
					echo 'style="display:none;"';
				}?>
				>
				<?php echo $comment->content_out; ?>
				</div>
				<?php if ($comment->status == COMMENT::STATUS_SPAM) : ?>
					<a href="" onclick="$(this).hide();$('#comment_content_<?php echo $comment->id; ?>').show();return false;">[Show Comment]</a>
				<?php endif; ?>
				<?php if ($comment->info->spamcheck) : ?>
				<ul style="list-style:disc;margin-top:10px;font-size:xx-small;">
				<?php
				$reasons = (array)$comment->info->spamcheck;
				$reasons = array_unique($reasons);	
				foreach($reasons as $reason): 
				?>
					<li><?php echo $reason; ?></li>
				<?php	endforeach; ?>
				</ul>
				<?php endif; ?>
				<p class="manage">
					<?php _e('Action:'); ?>
					<label>
						<input type="radio" class="radio_approve" name="moderate[<?php echo $comment->id; ?>]" id="approve-<?php echo $comment->id; ?>" value="approve" <?php echo $default_radio['approve']; ?> ><?php _e('Approve'); ?>
					</label>
					<label>
						<input type="radio" class="radio_delete" name="moderate[<?php echo $comment->id; ?>]" id="delete-<?php echo $comment->id; ?>" value="delete" <?php echo $default_radio['delete']; ?> ><?php _e('Delete'); ?>
					</label>
					<label>
						<input type="radio" class="radio_spam" name="moderate[<?php echo $comment->id; ?>]" id="spam-<?php echo $comment->id; ?>" value="spam" <?php echo $default_radio['spam']; ?> ><?php _e('Mark as Spam'); ?>
					</label>
					<label>
						<input type="radio" class="radio_unapprove" name="moderate[<?php echo $comment->id; ?>]" id="spam-<?php echo $comment->id; ?>" value="unapprove" <?php echo $default_radio['unapprove']; ?> ><?php _e('Unapprove'); ?>
					</label>
				</p>
				&nbsp;
			</li>
		<?php }	?>
		</ul>
		<p class="submit"><input type="submit" name="submit" value="<?php _e('Moderate!'); ?>" /> 
		<?php if ($mass_delete != '') : ?>
		<label><input type="checkbox" name="<?php echo $mass_delete; ?>" id="mass_delete1" value="1"><?php _e("Delete 'em all"); ?></label></p>
		<?php endif; ?>
		</form>
	<?php } else { ?>
		<p><?php _e('You currently have no comments to moderate.'); ?></p>
	<?php } ?>
	</div>
</div>
<script type="text/javascript">
$('.moderated_comment:even').css('background', '#f8f8f8');
</script>
<?php include('footer.php'); ?>
