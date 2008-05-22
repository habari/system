<?php include('header.php'); ?>
<div class="container">
<hr>
	<div class="column prepend-1 span-22 append-1">
		<h1><?php _e('Import'); ?></h1>
		<form method="post" action="">
			<?php
			if(empty($_POST['importer'])) :
				$import_names= array();
				$import_names= Plugins::filter('import_names', $import_names);
				if(count($import_names) == 0):
				?>
				<p><?php _e('You do not currently have any import plugins installed.'); ?></p>
				<p><?php _e('Please '); ?><a href="<?php URL::out('admin', 'page=plugins'); ?>"><?php _e('activate an import plugin</a> to enable importing.'); ?></p>
				<?php else: ?>
				<p><?php _e('Please choose the type of import to perform:'); ?></p>
				<select name="importer">
					<option></option>
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
