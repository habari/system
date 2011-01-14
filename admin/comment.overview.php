<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div class="textual item"><?php
$view_url = $comment->post->permalink . '#comment-' . $comment->id;

if ( $comment->url != '' ):
	printf( _t( '<a href="%1$s">Comment</a> by ' .
						'<a href="%2$s" title="Visit %3$s">%3$s</a> ' .
						'posted on <a href="%4$s" title="View post">%5$s</a> ' .
						'at <strong>%6$s</strong> ' .
						'on <strong>%7$s</strong>' ),
		$view_url,
		$comment->url,
		$comment->name,
		$comment->post->permalink,
		$comment->post->title,
		$comment->date->get( HabariDateTime::get_default_time_format() ),
		$comment->date->get( HabariDateTime::get_default_date_format() )
	);
else:
	printf( _t( '<a href="%1$s">Comment</a> by ' .
						'<strong>%2$s</strong> ' .
						'posted on <a href="%3$s" title="View post">%4$s</a> ' .
						'at <strong>%5$s</strong> ' .
						'on <strong>%6$s</strong>' ),
		$view_url,
		$comment->name,
		$comment->post->permalink,
		$comment->post->title,
		$comment->date->get( HabariDateTime::get_default_time_format() ),
		$comment->date->get( HabariDateTime::get_default_date_format() )
	);
endif;
?></div>
