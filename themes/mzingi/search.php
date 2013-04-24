{hi:display:header}
<!--begin content-->
<div id="content">
	<!--begin primary content-->
	<div id="primaryContent">
		<!--begin loop-->
		<h2>{hi:"Results for search of"} "{hi:escape:criteria}"</h2>
		{hi:?isset(post)}
		{hi:posts}
			<div id="post-{hi:id}" class="{hi:statusname}">
					<h2><a href="{hi:permalink}" title="{hi:title}">{hi:title_out}</a></h2>
				<div class="entry">
					{hi:pubdate_nice} -	{hi:content_excerpt}
				</div>
				<div class="entryMeta">
					{hi:?count(tags)}
					<div class="tags">{hi:"Tagged:"} {hi:tags_out}</div>
					{/hi:?}
					<div class="commentCount"><a href="{hi:permalink}#comments" title="{hi:"Comments on this post"}">{hi:"{hi:comments.approved.count} Comment" "{hi:comments.approved.count} Comments" comments.approved.count}</a></div>
				</div><br>
				{hi:?loggedin}
				<a href="{hi:editlink}" title="{hi:"Edit post"}">{hi:"Edit"}</a>
				{/hi:?}
			</div>
		{/hi:posts}
		<!--end loop-->
		<div id="pagenav">
			<?php //echo $theme->prev_page_link('&laquo; ' . _t('Newer Results')); ?> <?php //echo $theme->page_selector( null, array( 'leftSide' => 2, 'rightSide' => 2 ) ); ?> <?php //echo $theme->next_page_link('&raquo; ' . _t('Older Results')); ?>
			{hi:@prevpage_results_out} {hi:@pageselector_out} {hi:@nextpage_results_out}
		{hi:?else?}
			<p><em>{hi:"No results for"} {hi:escape:criteria}</em></p>
		{/hi:?}
		</div>
	</div>

	<!--end primary content-->
	{hi:display:sidebar}
</div>
<!--end content-->
{hi:display:footer}
