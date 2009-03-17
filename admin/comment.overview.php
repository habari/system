<div class="textual item"><?php
$view_url = $comment->post->permalink . '#comment-' . $comment->id;

if($comment->url != ''): 
	$string = '<a href="%s">Comment</a> by ';
	$string.= '<a href="%s" title="Visit %s">%s</a> ';
	$string .= 'posted on <a href="%s" title="View post">%s</a> ';
	$string .= 'at <strong>%s</strong> ';
	$string .= 'on <strong>%s</strong>';
	printf(_t($string),
		$view_url,
		$comment->url,
		$comment->name,
		$comment->name,
		$comment->post->permalink,
		$comment->post->title,
		$comment->date->get('H:i'),
		$comment->date->get('F d, Y')
	);
else:
	$string = '<a href="%s">Comment</a> by ';
	$string.= '<strong>%s</strong> ';
	$string .= 'posted on <a href="%s" title="View post">%s</a> ';
	$string .= 'at <strong>%s</strong> ';
	$string .= 'on <strong>%s</strong>';
	printf(_t($string),
		$view_url,
		$comment->name,
		$comment->post->permalink,
		$comment->post->title,
		$comment->date->get('H:i'),
		$comment->date->get('F d, Y')
	);
endif;
?></div>