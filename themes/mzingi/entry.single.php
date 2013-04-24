{hi:display:header}
<!--begin content-->
	<div id="content">
		<!--begin primary content-->
		<div id="primaryContent">
			<!--begin single post navigation-->
			<div id="post-nav">
				<span class="left">{hi:@next_post_link_out}</span>
				<span class="right">{hi:@prev_post_link_out}</span>
			</div>
				<div id="post-{hi:post.id}" class="{hi:post.statusname}">
						<h2><a href="{hi:post.permalink}" title="{hi:post.title}">{hi:post.title_out}</a></h2>
						<div class="cal">{hi:post.pubdate_out}</div>
						<div class="entry">{hi:post.content_out}</div>
					<div class="entryMeta">
						{hi:?count(post.tags)}
						<div class="tags">{hi:"Tagged"} {hi:post.tags_out}</div>
						{/hi:?}
					</div><br>
						{hi:?loggedin}
						<a href="{hi:post.editlink}" title="{hi:"Edit Post"}">{hi:"Edit"}</a>
						{/hi:?}

				</div>

			{hi:display:commentform}
			</div>
		<!--end primary content-->
		{hi:display:sidebar}
	</div>
	<!--end content-->
{hi:display:footer}
