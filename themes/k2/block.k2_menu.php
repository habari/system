<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } ?>
<ul class="menu">
<?php foreach($content->menus as $menu) : ?>
<li class="<?php echo $menu['cssclass']; ?> block-<?php echo Utils::slugify($menu['caption']); ?>"><a href="<?php echo $menu['link']; ?>"><?php echo $menu['caption']; ?></a></li>
<?php endforeach; ?>
</ul>
