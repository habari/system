{hi:@sidebar_top_out}
	<!--begin secondary content-->
	<div id="secondaryContent">
		<h3><a id="rss" href="{hi:@feed_alternate_out}" class="block">{hi:"Subscribe to Feed"}</a></h3>
		<h2 id="site">{hi:"Navigation"}</h2>
		<ul id="nav">
			<li><a href="{hi:siteurl:habari}">{hi:"Home"}</a></li>
			{hi:?isset(pages) && !empty(pages)}
				{hi:pages}
					<li><a href="{hi:permalink}" title="{hi:title}">{hi:title}</a></li>
				{/hi:pages}
			{/hi:?}
		</ul>

		<h2 id="aside">{hi:"Asides"}</h2>
		<ul id="asides">
			{hi:?isset(asides) && !empty(asides)}
				{hi:asides}
					<li><span class="date">{hi:pubdate_nice} - </span><a href="{hi:permalink}">{hi:title_out}</a> {hi:content_out}</li>
				{/hi:asides}
			{/hi:?}
		</ul>
		{hi:area:sidebar}
	</div>
	<!--end secondary content-->
{hi:@sidebar_bottom_out}
