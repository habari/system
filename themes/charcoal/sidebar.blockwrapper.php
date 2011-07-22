<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } ?>
<div class="block<?php if( $block->_first ): ?> first<?php endif; if( $block->_last ):?> last<?php endif; echo ' index_' . $block->_area_index; ?>">
<?php if ( $block->_show_title ) :?>
	<h2><?php echo $block->title; ?></h2>
<?php endif; ?>
<?php echo $content; ?>
</div>
