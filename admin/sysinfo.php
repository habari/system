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
<?php $admin_title = _t( 'System Information' ); ?>

<?php include_once( 'header.php' ); ?>

<div class="container">
	<h2><?php _e( "System Information" ); ?></h2>
	<?php $plaintext_output = _t( "SYSTEM INFORMATION\n" ); ?>

	<div class="manage">
	<?php foreach( $sysinfo as $key => $value ) : ?>
		<div class="item clear">
			<span class="pct25"><?php echo $key; 
				$plaintext_output .= $key; ?></span>
			<span class="message pct75 minor"><span><?php echo $value; 
				$plaintext_output .= ": $value\n"; ?></span></span>
		</div>
	<?php endforeach; ?>
	</div>

</div>

<div class="container">
	<h2><?php _e( "Site Information" ); ?></h2>
	<?php $plaintext_output .= _t( "\nSITE INFORMATION\n" ); ?>

	<div class="manage">
	<?php foreach( $siteinfo as $key => $value ) : ?>
		<div class="item clear">
			<span class="pct25"><?php echo $key; 
				$plaintext_output .= $key; ?></span>
			<span class="message pct75 minor"><span><?php echo $value; 
				$plaintext_output .= ": $value\n"; ?></span></span>
		</div>
	<?php endforeach; ?>
	</div>

</div>

<div class="container">
	<h2><?php _e( "User Classes" ); ?></h2>
	<?php $plaintext_output .= _t( "\nUSER CLASSES\n" ); ?>

	<div class="manage">
	<?php foreach( $classinfo as $fullpath ) : ?>
		<div class="item clear">
			<span class="pct100"><?php echo $fullpath; 
				$plaintext_output .= "$fullpath\n"; ?></span>
		</div>

	<?php endforeach; ?>
	<?php if ( empty( $fullpath ) ) : ?>
		<div class="item clear"><span class="pct100"><?php _e( "None found" ); 
			$plaintext_output .= _t( "None found\n" ); ?></span></div>
	<?php endif; ?>
	</div>
</div>

<div class="container">
	<h2><?php _e( "Plugin Information" ); ?></h2>
	<?php $plaintext_output .= _t( "\nPLUGIN INFORMATION" ); ?>

	<?php foreach($plugins as $section => $sec_plugins): ?>

	<h3><?php echo $section; 
		$plaintext_output .= "\n/$section/plugins:\n"; ?></h3>
	<div class="manage">
	<?php foreach( $sec_plugins as $name => $pluginfile ) : ?>
		<div class="item clear">
			<span class="pct25"><?php echo $name; 
				$plaintext_output .= $name; ?></span>
			<span class="message pct75 minor"><span><?php echo $pluginfile; 
				$plaintext_output .= ": $pluginfile\n"; ?></span></span>
		</div>

	<?php endforeach; ?>
	<?php if ( count($sec_plugins) == 0 ) : ?>
		<div class="item clear"><span class="pct100"><?php _e( "None found" ); 
			$plaintext_output .= _t( "None found\n" ); ?></span></div>
	<?php endif; ?>
	</div>
	<?php endforeach; ?>
</div>

<div class="container">
	<h2><?php _e( "All Results" ); ?></h2>
	<textarea rows = "<?php echo substr_count( $plaintext_output, "\n" ); ?>"><?php echo $plaintext_output; ?></textarea>
</div>

<?php include('footer.php'); ?>
