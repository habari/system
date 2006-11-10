<div id="content-area">
	<h1>Habari Content</h1>
	<?php 
	if ( isset( $settings['result'] ) )
	{
		switch( URL::o()->settings['result'] ) {
		case 'success':
			_e('<p>Your options have been updated.</p>');
			break;
		}
	}
	?>
<div style="width: 45%; float: left; border-right: 1px solid #000;">
<strong>Posts</strong>
<ul>
<?php
	foreach (Posts::get( array('limit' => '10') ) as $post)
	{
		echo '<li>';
		echo '<a href="' . $post->permalink . '">' . $post->title . '</a><br />';
		echo 'By ' . $post->author->username . ' on ' . $post->pubdate;
		if ($post->updated > $post->pubdate)
		{
			echo ' (updated: ' . $post->updated . ')';
		}
		echo '<br />';
		echo $post->content;
		echo '</li>';
	}
?>
</ul>
</div>
<div style="width: 45%; float: left; margin-left: 2px;">
<strong>Comments</strong>
<ul>
<?php
	foreach (Comments::get() as $comment)
	{
		echo '<li>';
		echo '<a href="mailto:' . $comment->email . '">' . $comment->name . '</a> on <a href="' . URL::get( 'post', array( 'slug' => $comment->post_slug ) ) . '">' . $comment->post_slug . '</a><br />';
		echo $comment->date . ' from <a href="http://ws.arin.net/cgi-bin/whois.pl?queryinput=' . $comment->ip . '">' . $comment->ip . '</a><br />';
		echo $comment->content;
		echo '</li>';
	}
?>
</ul>
</div>
<div style="clear: both;"></div>
<p> </p>

</div>
