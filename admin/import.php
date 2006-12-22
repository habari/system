<div id="content-area">
	<div id="left-column">

<?php
$db_connection = array(
'connection_string' => 'mysql:host=mysql.chrisjdavis.org;dbname=chrispress',  // MySQL Connection string
'username' => 'chrisjdavis',  // MySQL username
'password' => 'walker',  // MySQL password
'prefix'	=>	'b2', // Prefix for your WP tables
);

// Connect to the database or fail informatively
try {
	$wpdb = new DB( $db_connection['connection_string'], $db_connection['username'], $db_connection['password'], $db_connection['prefix'] );
}
catch( Exception $e) {
	die( 'Could not connect to database using the supplied credentials.  Please check config.php for the correct values. Further information follows: ' .  $e->getMessage() );		
}

echo '<h1>Import your contnt into ' . Options::get('title') . '</h1>';

$posts = $wpdb->get_results("
	SELECT
		post_content as content,
		post_title as title,
		post_name as slug,
		post_author as user_id,
		guid as guid,
		post_date as pubdate,
		post_modified as updated,
		(post_status = 'publish') as status 
	FROM {$db_connection['prefix']}posts 
	", array(), 'Post');

foreach( $posts as $post ) {
	$post->insert();
}

$comments = $wpdb->get_results("SELECT 
								comment_content as content,
								comment_post_ID as slug_id,
								comment_author as name,
								comment_author_email as email,
								comment_author_url as url,
								comment_author_IP as ip,
							 	comment_approved as status,
								comment_date as date,
								comment_type as type
								FROM {$db_connection['prefix']}comments", 
								array(), 'Comment');

foreach( $comments as $comment ) {
	$post_slug = $wpdb->get_value( "SELECT post_name FROM {$db_connection['prefix']}posts WHERE ID = $comment->slug_id");
	if ( $comment->type = '' ) {
		$comment->type = Comment::COMMENT;
	} elseif( $comment->type = 'pingback' ) {
		$comment->type = Comment::PINGBACK;
	} elseif( $comment->type = 'trackback' ) {
		$comment->type = Comment::TRACKBACK;
	}
	
	$c = new Comment( array( 
							'content' => $comment->content,
							'name' => $comment->name,
							'email' => $comment->email,
							'url'	=> $comment->url,
							'ip'	=> $comment->ip,
							'status' => $comment->status,
							'date'	=> $comment->date,
							'type' => $comment->type,
							'post_slug' => $post_slug
							) );
	//Utils::debug( $c );
	$c->insert();
}
	echo '<p>All done, your content has been imported.</p>';
?>
	</div>
</div>
<?php 
// unset the $db_connection variable, since we don't need it any more
unset( $db_connection );

?>