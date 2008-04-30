<?php // Do not delete these lines
if ( ! defined('HABARI_PATH' ) ) { die( _t('Please do not load this page directly. Thanks!') ); }
?>
<?php if ( Session::has_errors() ) {
		Session::messages_out();
	}
?>

<div id="comments">
<a href="<?php echo $post->comment_feed_link; ?>">Feed for this Entry</a>
	<?php if( $post->comments->pingbacks->count ) : ?>
			<div id="pings">
			<h4><?php echo $post->comments->pingbacks->count; ?> <?php echo _n( 'Pingback', 'Pingbacks', $post->comments->pingbacks->count ); ?> to <?php echo $post->title; ?></h4>
				<ul id="pings-list">
					<?php foreach ( $post->comments->pingbacks->approved as $pingback ) : ?>
						<li id="ping-<?php echo $pingback->id; ?>">
								
								<div class="comment-content">
								<?php echo $pingback->content; ?>
								</div>
								<div class="ping-meta"><a href="<?php echo $pingback->url; ?>" title=""><?php echo $pingback->name; ?></a></div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		

	<h4 class="commentheading"><?php echo $post->comments->comments->approved->count; ?> <?php echo _n( 'Response', 'Responses', $post->comments->comments->approved->count ); ?> to <?php echo $post->title; ?></h4>
	<ul id="commentlist">
	
		<?php 
		if ( $post->comments->moderated->count ) {
			foreach ( $post->comments->moderated as $comment ) {
			$class= 'class="comment';
			if ( $comment->status == Comment::STATUS_UNAPPROVED ) {
				$class.= '-unapproved';
			}
			$class.= '"';
		?>
		
			<li id="comment-<?php echo $comment->id; ?>" <?php echo $class; ?>>
 				<div class="comment-content">
		        <?php echo $comment->content_out; ?>
		       </div>
			<div class="comment-meta">#<a href="#comment-<?php echo $comment->id; ?>" class="counter" title="Permanent Link to this Comment"><?php echo $comment->id; ?></a> | 
		       <span class="commentauthor">Comment by <a href="<?php echo $comment->url; ?>"><?php echo $comment->name; ?></a></span>
		       <span class="commentdate"> on <a href="#comment-<?php echo $comment->id; ?>" title="Time of this comment"><?php echo $comment->date_out; ?></a></span><h5><?php if ( $comment->status == Comment::STATUS_UNAPPROVED ) : ?> <em>In moderation</em><?php endif; ?></h5></div>
		      </li>
	
		


		<?php 
			}
		}
		else {
			_e('There are currently no comments.');
		}
		?>
	</ul>
	<div class="comments">
		
		<br>
		<form id="comments_form" action="<?php URL::out( 'submit_feedback', array( 'id' => $post->id ) ); ?>" method="post">

		<fieldset>
			<legend>About You</legend>
			<p>
			<label for="name">Name: <span class="required">(Required)</span></label>
			<input name="name" id="name" value="<?php echo $commenter_name; ?>" type="text" >
			</p>

			<p>
			<label for="email">Email Address: <span class="required">(Required)</span></label>
			<input name="email" id="email" value="<?php echo $commenter_email; ?>" type="text" >
			</p>

			<p>
			<label for="url">Web Address:</label>
			<input name="url" id="url" value="<?php echo $commenter_url; ?>" type="text" >
			</p>
			<p><em><small>Email address is not published</small></em></p>
		</fieldset>
		
		<fieldset>
			<legend>Add to the Discussion</legend>
			<p>
			<label for="content">Message: (Required)</label>
			<textarea name="content" id="commentContent" cols="20" rows="10"></textarea>
			</p>
		</fieldset>
		
		<p><input id="submit" class="submit" name="submit" value="Submit" type="submit"></p>
		</form>
	</div>
	 

</div>
