{hi:display:header}
	<div id="content">
		<!--begin primary content-->
		<div id="primaryContent">
			<!--begin loop-->
			{hi:posts}
				<div id="post-{hi:id}" class="{hi:statusname}">
					<h2><a href="{hi:permalink}" title="{hi:title}">{hi:title_out}</a></h2>
					<div class="cal">{hi:pubdate_out}</div>
					<div class="entry">{hi:content_out}</div>
					<div class="entryMeta">
						{hi:?count(tags)}
						<div class="tags">{hi:"Tagged"} {hi:tags_out}</div>
						{/hi:?}
						<div class="commentCount"><a href="{hi:permalink}#comments" title="{hi:"Comments on this post"}">{hi:"{hi:comments.approved.count} Comment" "{hi:comments.approved.count} Comments" comments.approved.count}</a></div>
					</div><br>
					{hi:?loggedin}
					<a href="{hi:editlink}" title="{hi:"Edit post"}">{hi:"Edit"}</a>
					{/hi:?}
				</div>
			{/hi:posts}
			<!--end loop-->
			<div id="pagenav">{hi:@prevpage_link_out} {hi:@pageselector_out} {hi:@nextpage_link_out}</div>
		</div> <!--end primary content-->
		{hi:display:sidebar}
	</div>
	<!--end content-->
{hi:display:footer}
	