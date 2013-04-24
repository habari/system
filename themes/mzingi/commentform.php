{hi:session:messages}

<div id="comments">
	<h3>{hi:"{hi:post.comments.approved.count} Response" "{hi:post.comments.approved.count} Responses" post.comments.approved.count} {hi:"to"} {hi:post.title}</h3>
	<a href="{hi:post.comment_feed_link}"> {hi:"Feed for this Entry"}</a>
	{hi:?post.comments.pingbacks.count}
		<div id="pings">
		<h4>{hi:"{hi:post.comments.pingbacks.approved.count} Pingback" "{hi:post.comments.pingbacks.approved.count} Pingbacks" post.comments.pingbacks.approved.count} {hi:"to"} {hi:post.title}</h4>
			<ul id="pings-list">
				{hi:post.comments.pingbacks.approved}
					<li id="ping-{hi:id}">
							<div class="comment-content">{hi:content}</div>
							<div class="ping-meta"><a href="{hi:url}" title="">{hi:name}</a></div>
					</li>
				{/hi:post.comments.pingbacks.approved}
			</ul>
		</div>
	{/hi:?}


	<h4 class="commentheading">{hi:"{hi:post.comments.comments.approved.count} Response" "{hi:post.comments.comments.approved.count} Responses" post.comments.comments.approved.count}</h4>
	<ul id="commentlist">

		{hi:?post.comments.moderated.count}
			{hi:post.comments.moderated}
				{hi:?is_unapproved}
					<li id="comment-{hi:id}" class="comment{hi:'-unapproved"}>
				{hi:?else?}
					<li id="comment-{hi:id}" class="comment">
				{/hi:?}

 			<div class="comment-content">{hi:content_out}</div>
			<div class="comment-meta">#<a href="#comment-{hi:id}" class="counter" title="{hi:"Permanent Link to this Comment"}">{hi:id}</a> |
			<span class="commentauthor">{hi:"Comment by"} <a href="{hi:url}">{hi:name}</a></span>
			<span class="commentdate"> {hi:"on"} <a href="#comment-{hi:id}" title="{hi:"Time of this comment"}">{hi:date_out}</a></span>
			{hi:?is_unapproved}<h5> <em>{hi:"In moderation"}</em></h5>{/hi:?}</div>
		      </li>
			{/hi:post.comments.moderated}
		{hi:?else?}
			<li>{hi:"There are currently no comments."}</li>
		{/hi:?}
	</ul>
	<div class="comments">

		<br>
{hi:@comment_form_out}
	</div>


</div>
