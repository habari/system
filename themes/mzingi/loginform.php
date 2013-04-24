{hi:?loggedin}
	<p>{hi:"You are logged in as {hi:user.username}" user.username}</p>
	<p>{hi:"Want to "}<a href="{hi:siteurl:habari}/auth/logout">{hi:"log out"}</a></p>
{hi:?else?}
	{hi:session:messages}
	{hi:form}
{/hi:?}
