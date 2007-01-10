<?php
class DisplayPostsByDate extends ThemeAction {
  public function act() {
    $sql_date= $this->settings['year']
                . '-' . $this->settings['month']
                . '-' . $this->settings['day'];
    $filters= array(
              'status'=>Post::STATUS_PUBLISHED
            , 'pubdate'=>$sql_date
            , 'page'=>$this->settings['page']);
    $posts= Posts::get($filters);
    $this->theme->template_engine->assign('posts', $posts);
    $this->theme->template_engine->display('posts');
  }
}
?>
