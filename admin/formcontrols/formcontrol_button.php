<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php
		echo $control->parameter_map(
			array(
				'class', 'id' => 'name'
			)
		); ?>>
		<input type="button" <?php
		echo $control->parameter_map(
			array(
				'title' => array( 'control_title', 'title' ),
				'tabindex', 'disabled',
				'id' => 'field',
				'name' => 'field',
			),
			array(
				'value' => Utils::htmlspecialchars( $caption ),
				'onclick' => 'onclick_'.Utils::slugify( $id, '_' ).'()',
			)
		);
		?>>
		<?php if ( isset( $onclick ) && $onclick != '' ): ?>
		<script type="text/javascript">function onclick_<?php echo Utils::slugify( $id, '_' ) ?>() {
		<?php echo $onclick; ?>
		}</script>
		<?php endif; ?>
		<?php 
			if ( isset( $helptext ) && !empty( $helptext ) ) { ?>
				<span class="helptext"><?php echo $helptext; ?></span>
				<?php
			}
		?>
</div>
