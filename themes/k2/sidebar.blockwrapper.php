<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } ?>
<div class="<?php echo $block->css_classes; ?>">
<?php if($block->_show_title):?><h2><?php echo $block->title; ?></h2><?php endif; ?>
<?php echo $content; ?>
</div>