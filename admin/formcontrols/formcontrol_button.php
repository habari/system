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
				'value' => Utils::htmlspecialchars( $control->caption ),
				'onclick' => 'onclick_'.Utils::slugify( $control->name, '_' ).'()',
			)
		);
		?>>
		<?php if ( isset( $control->onclick ) && $control->onclick != '' ): ?>
		<script type="text/javascript">function onclick_<?php echo Utils::slugify( $control->name, '_' ) ?>() {
		<?php echo $control->onclick; ?>
		}</script>
		<?php endif; ?>
		<?php 
			if ( isset( $control->helptext ) && !empty( $control->helptext ) ) { ?>
				<span class="helptext"><?php echo $control->helptext; ?></span>
				<?php
			}
		?>
</div>