{hi:display:header}
<!--begin content-->
<div id="page">
	<div id="content">
		<!--begin primary content-->
		<div id="primaryContent">
			<div id="post-{hi:post.id}" class="{hi:post.statusname}">
					<h2><a href="{hi:post.permalink}" title="{hi:post.title}">{hi:post.title_out}</a></h2>
				<div class="entry">{hi:post.content_out}</div>
				<div class="entryMeta">
					{hi:?loggedin}
						<a href="{hi:post.editlink}" title="{hi:"Edit post"}">{hi:"Edit"}</a>
					{/hi:?}
				</div>
			</div>
		</div>
	<!--end primary content-->
	{hi:display:sidebar}
	</div>
</div>
	<!--end content-->
{hi:display:footer}
