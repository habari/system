<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
<?php include('header.php'); ?>

<div class="container">
	<div class="">
		<h2><?php _e('Import'); ?></h2>
		<form method="post" action="" enctype="<?php echo $enctype; ?>">
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
