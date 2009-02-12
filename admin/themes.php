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
<?php include('header.php');?>


<div class="container currenttheme">
	<h2><?php _e('Current Theme'); ?></h2>
	<div class="item clear">
		<div class="head">
			<a href="<?php echo $active_theme['info']->url; ?>" class="plugin"><?php echo $active_theme['info']->name; ?></a> <span class="version dim"><?php echo $active_theme['info']->version; ?></span> <span class="dim"><?php _e('by'); ?></span> <a href="<?php echo $active_theme['info']->url; ?>"><?php echo $active_theme['info']->author; ?></a></span>

			<?php if($configurable): ?>
			<ul class="dropbutton">
				<li><a href="<?php URL::out( 'admin', 'page=themes&configure=' . $active_theme['dir'] ); ?>"><?php _e('Settings'); ?></a></li>
			</ul>
			<?php endif; ?>

			<?php if($active_theme['info']->update != ''): ?>
			<ul class="dropbutton alert">
				<li><a href="#"><?php _e('v'); ?><?php echo $active_theme['info']->update; ?> <?php _e('Update Available'); ?></a></li>
			</ul>
			<?php endif; ?>
		</div>

		<div>
			<div class="thumb pct30"><span><img src="<?php echo $active_theme['screenshot']; ?>"></span></div>

			<p class="description pct70"><?php echo $active_theme['info']->description; ?></p>
			<?php if($active_theme['info']->license != ''): ?>
			<p class="description pct70"><?php printf( _t('%1$s is licensed under the %2$s'), $active_theme['info']->name, '<a href="' . $active_theme['info']->license['url'] . '">' . $active_theme['info']->license . '</a>' ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( isset( $this->engine_vars['configure'] ) ): ?>
		<div id="themeconfigure">
			<?php Plugins::act( 'theme_ui', $active_theme ); ?>
			<a class="link_as_button" href="<?php URL::out( 'admin', 'page=themes' ); ?>"><?php _e('close'); ?></a>
		</div>
		<?php endif; ?>
	</div>

</div>



<div class="container availablethemes">

	<h2><?php _e('Available Themes'); ?></h2>
<?php
foreach($all_themes as $inactive_theme):
	if ( $inactive_theme['path'] != $active_theme_dir ) : ?>
	<div class="item clear">
		<div class="head">
			<a href="<?php echo $inactive_theme['info']->url; ?>"><?php echo $inactive_theme['info']->name; ?> <span class="version dim"><?php echo $inactive_theme['info']->version; ?></span></a> <span class="dim"><?php _e('by'); ?></span> <a href="<?php echo $inactive_theme['info']->url; ?>" class="author"><?php echo $inactive_theme['info']->author; ?></a></span>

			<ul class="dropbutton">
				<li><a href="<?php URL::out( 'admin', 'page=activate_theme&theme_dir=' . $inactive_theme['dir'] . '&theme_name=' . $inactive_theme['info']->name ); ?>"><?php _e('Activate'); ?></a></li>
			</ul>
		</div>

		<div>
			<div class="thumb pct30"><span><img src="<?php echo $inactive_theme['screenshot']; ?>"></span></div>

			<p class="description pct70"><?php echo $inactive_theme['info']->description; ?></p>
			<?php if($inactive_theme['info']->license != ''): ?>
			<p class="description pct70"><?php printf( _t('%1$s is licensed under the %2$s'), $inactive_theme['info']->name, '<a href="' . $inactive_theme['info']->license['url'] . '">' . $inactive_theme['info']->license . '</a>' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

<?php
	endif;
endforeach;
?>
</div>





<?php include('footer.php');?>
