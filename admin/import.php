<div id="content-area">
	<div id="left-column">

<?php
$db_connection = array(
'connection_string' => 'mysql:host=localhost;dbname=asymptomatic',  // MySQL Connection string
'username' => 'root',  // MySQL username
'password' => '',  // MySQL password
'prefix'	=>	'wp_', // Prefix for your WP tables
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
		ID as id,
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

	$tags = $wpdb->get_column( 
		"SELECT category_nicename
		FROM {$db_connection['prefix']}post2cat
		INNER JOIN {$db_connection['prefix']}categories 
		ON ({$db_connection['prefix']}categories.cat_ID = {$db_connection['prefix']}post2cat.category_id)
		WHERE post_id = {$post->id}" 
	);
	$post->tags = $tags;

	$p = new Post( $post->to_array() );
	
	$p->insert();

}

$comments = $wpdb->get_results("SELECT 
								comment_content as content,
								comment_author as name,
								comment_author_email as email,
								comment_author_url as url,
								comment_author_IP as ip,
							 	comment_approved as status,
								comment_date as date,
								comment_type as type,
								post_name as post_slug 
								FROM {$db_connection['prefix']}comments
								INNER JOIN
								{$db_connection['prefix']}posts on ({$db_connection['prefix']}posts.ID = {$db_connection['prefix']}comments.comment_post_ID)
								", 
								array(), 'Comment');

foreach( $comments as $comment ) {
	switch( $comment->type ) {
		case 'pingback': $comment->type = Comment::PINGBACK; break;
		case 'trackback': $comment->type = Comment::TRACKBACK; break;
		default: $comment->type = Comment::COMMENT;
	}
		
	$c = new Comment( $comment->to_array() );
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
