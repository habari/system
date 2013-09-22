<?php namespace Habari; ?>
<article id="post-<?php echo $content->id; ?>" class="post" itemscope itemtype="http://schema.org/BlogPosting">

	<header class="metadata">
		<h1 itemprop="name"><a href="<?php echo $content->permalink; ?>" itemprop="url"><?php echo $content->title_out; ?></a></h1>
		<div class="pubdata">
			<span itemprop="author" itemscope itemtype="http://schema.org/Person"><span itemprop="name"><?php echo $content->author->displayname; ?></span></span>
			<time datetime="<?php echo $content->pubdate->format('Y-m-d\TH:i:s\Z'); ?>" itemprop="datePublished"><?php echo $content->pubdate->format(Options::get('dateformat') . ' ' . Options::get('timeformat')); ?></time>
		</div>
		<?php if ( $content->tags->count() ): ?>
		<div itemprop="keywords" class="tags">
			<?php echo Format::tag_and_list($content->tags, ', ', ', and '); ?>
		</div>
		<?php endif; ?>
	</header>

	<div class="content" itemprop="articleBody">
		<?php if($request->display_entry): ?>
			<?php echo $content->content_out; ?>
		<?php else: ?>
			<?php echo $content->content_excerpt; ?>
		<?php endif; ?>
	</div>

	<?php if($request->display_entry): ?>
	<section class="comments">
		<h1 id="comments">Comments</h1>
		<?php if($content->comments->moderated->count == 0): ?>
			<p><?php _e('There are no comments on this post.'); ?>
		<?php else: ?>
			<?php foreach($content->comments->moderated->comments as $comment): ?>
				<?php echo $theme->content($comment); ?>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php if($content->info->comments_disabled): ?>
			<p><?php _e('Sorry, commenting on this post is disabled.'); ?>
		<?php else: ?>
		<?php $content->comment_form()->out(); ?>
		<?php endif; ?>
	</section>
	<?php endif; ?>

</article>

