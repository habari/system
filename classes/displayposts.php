<?php
class DisplayPosts extends ThemeAction {
  public function act() {
    $page= min( (int) $this->settings['page'], 1); // pager index
    $filters= array('where'=>array(
              'status'=>Post::STATUS_PUBLISHED
            , 'page'=>$page
            //, 'content_type'=>Post::TYPE_ENTRY
            ));
    $posts= Posts::get($filters);
    $this->theme->template_engine->assign('posts', $posts);
    $this->theme->template_engine->display('posts');
  }
}
?>

