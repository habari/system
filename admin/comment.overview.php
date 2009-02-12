<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
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