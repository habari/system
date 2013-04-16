<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php');?>


<div class="container navigation">
	<span>
		<select name="navigationdropdown" onchange="navigationDropdown.filter();" tabindex="1">
			<option value="all"><?php _e('All options'); ?></option>
		</select>
	</span>
	<span class="or">
		<?php _e('or'); ?>
	</span>
	<span>
		<input type="search" id="search" placeholder="<?php _e('search settings'); ?>" tabindex="2" autofocus="autofocus">
	</span>
</div>

<?php echo $form; ?>

<?php include('footer.php');?>
