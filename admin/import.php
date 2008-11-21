<?php include('header.php'); ?>
<div class="container">
	<div class="">
		<h2><?php _e('Import'); ?></h2>
		<form method="post" action="">
			<?php
			if(empty($_POST['importer'])) :
				$import_names = array();
				$import_names = Plugins::filter('import_names', $import_names);
				if(count($import_names) == 0):
				?>
				<p><?php _e('You do not currently have any import plugins installed.'); ?></p>
				<p><?php printf( _t('Please <a href="%1$s">activate an import plugin</a> to enable importing.'), URL::get('admin', 'page=plugins') ); ?></p>
				<?php else: ?>
				<p><?php _e('Please choose the type of import to perform:'); ?></p>
				<select name="importer">
					<?php
					foreach($import_names as $name) {
						echo "<option>{$name}</option>";
					}
					?>
				</select>
				<p class="submit"><input type="submit" name="import" value="<?php _e('Select'); ?>"></p>
			<?php
				endif;
			else:
				echo Plugins::filter('import_stage', '', @$_POST['importer'], @$_POST['stage'], @$_POST['step']);
			?>
			<?php
			endif;
			if(isset($_POST['importer'])) {
				echo '<input type="hidden" name="importer" value="' . $_POST['importer'] . '">';
			}
			?>
		</form>

	</div>
</div>
<?php
// unset the $db_connection variable, since we don't need it any more
unset( $db_connection );
?>
<?php include('footer.php'); ?>
