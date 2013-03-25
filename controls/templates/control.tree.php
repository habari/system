<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div <?= $_attributes ?>><?php echo \Habari\Format::term_tree( $terms, $_name, $_settings ); ?></div>