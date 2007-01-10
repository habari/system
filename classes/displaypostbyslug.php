<?php
class DisplayPostBySlug extends ThemeAction {
  public function act() {
    $page= min( (int) $this->settings['page'], 1); // pager index
    $slug= $this->settings['slug'];
    $filters= array('where'=>array(
              'status'=>Post::STATUS_PUBLISHED
            , 'page'=>$page
            , 'slug'=>$slug));
    $post= Post::get($filters);
    $this->theme->template_engine->assign('post', $post);
    $this->theme->template_engine->display('post');
  }
}
?>


