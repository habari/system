<article class="comment<?php if ( $content->status != Comment::STATUS_APPROVED ) : ?> moderated<?php endif; ?>" id="comment-<?php echo $content->id; ?>" itemscope itemtype="http://schema.org/UserComments">
	<header class="comment-meta">
		<h1 itemprop="description">
			<span class="comment-author" itemprop="author" itemscope itemtype="http://schema.org/Person">
				<img class="comment-avatar" itemprop="image" src="http://www.gravatar.com/avatar/<?php echo md5($content->email); ?>?size=50&d=identicon">
				<span itemprop="name">
				<?php
				$author = '%1$s';
				if(!empty($content->url)) {
					$author = '<a href="%2$s" itemprop="url">%1$s</a>';
				}
				printf($author, $content->name_out, $content->url_out);
				?>
				</span>
			</span>
			<a href="<?php echo $content->post->permalink; ?>#comment-<?php echo $content->id; ?>" title="<?php _e('Link to this comment'); ?>" itemprop="url">
				<time class="comment-date" datetime="<?php echo $content->date->format('Y-m-d H:i:s'); ?>" itemprop="commentTime">
					<?php $content->date->out(Options::get('dateformat') . ' ' . Options::get('timeformat')); ?>
				</time>
			</a>
		</h1>
	</header>
	<div class="comment-content" itemprop="commentText"><?php echo $content->content_out; ?></div>
</article>
