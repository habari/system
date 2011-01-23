<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>

<div class="container<?php echo ($class) ? ' ' . $class : ''?>">
	<p class="pct25"><label for="<?php echo $id; ?>"><?php echo $this->caption; ?></label></p>
	<p class="pct75">
		<input type="text" name="<?php echo $field; ?>" id="<?php echo $id; ?>" class="styledformelement" value="<?php echo Utils::htmlspecialchars( $value ); ?>">
		<?php 
		
			if ( isset( $helptext ) && !empty( $helptext ) ) {
				?>
					<span class="helptext"><?php echo $helptext; ?></span>
				<?php
			}
		
		?>
	</p>
</div>
