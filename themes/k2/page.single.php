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
<?php $theme->display ('header'); ?>
<!-- page.single -->
  <div class="page">
   <div id="primary">
    <div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">

     <div class="entry-head">
      <h3 class="entry-title"><?php _e('Page:'); ?> <a href="<?php echo $post->permalink; ?>" title="<?php echo $post->title; ?>"><?php echo $post->title_out; ?></a></h3>
      <small class="entry-meta">
       <span class="chronodata"><abbr class="published"><?php $post->pubdate->out(); ?></abbr></span> <?php if ( $show_author ) { _e('by %s', array( $post->author->displayname ) ); } ?>
       <span class="commentslink"><a href="<?php echo $post->permalink; ?>#comments" title="<?php _e('Comments to this post'); ?>"><?php echo $post->comments->approved->count; ?>
	<?php echo _n( 'Comment', 'Comments', $post->comments->approved->count ); ?></a></span>
<?php if ( $loggedin ) { ?>
       <span class="entry-edit"><a href="<?php echo $post->editlink; ?>" title="<?php _e('Edit post'); ?>"><?php _e('Edit'); ?></a></span>
<?php } ?>
<?php if ( is_array( $post->tags ) ) { ?>
       <span class="entry-tags"><?php echo $post->tags_out; ?></span>
<?php } ?>
      </small>
     </div>

     <div class="entry-content">
      <?php echo $post->content_out; ?>

     </div>

    </div>
<?php $theme->display ( 'comments' ); ?>
   </div>

   <hr>

   <div class="secondary">

<?php $theme->display ('sidebar'); ?>

   </div>

   <div class="clear"></div>
  </div>
<!-- /page.single -->
<?php $theme->display ( 'footer' ); ?>
