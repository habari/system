<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>

<div class="container<?php echo ($class) ? ' ' . $class : ''?>">
	<p class="pct25"><label for="<?php echo $id; ?>"><?php echo $this->caption; ?></label></p>
	<p class="pct75"><textarea name="<?php echo $field; ?>" id="<?php echo $id; ?>" class="styledformelement" rows="<?php echo isset( $rows ) ? $rows : 10; ?>" cols="<?php echo isset( $cols ) ? $cols : 30; ?>"><?php echo Utils::htmlspecialchars( $value ); ?></textarea></p>
</div>
