<?php include('header.php');?>


<div class="container navigation">
	<span class="pct40">
		<select name="navigationdropdown" onchange="navigationDropdown.filter();">
			<option value="all"><?php _e('All options'); ?></option>
			<?php foreach ( $option_names as $name ): ?>
			<option value="<?php echo Utils::slugify( $name ); ?>"><?php echo $name; ?></option>
			<?php endforeach; ?>
		</select>
	</span>
	<span class="or pct20">
		<?php _e('or'); ?>
	</span>
	<span class="pct40">
		<input type="search" id="search" placeholder="<?php _e('search settings'); ?>" autosave="habarisettings" results="10">
	</span>
</div>

<?php echo $form; ?>

<?php include('footer.php');?>
