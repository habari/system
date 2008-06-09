<ul class="modules">
		<?php foreach($modules as $module_name => $module): ?>
		<li class="module <?php echo Utils::slugify( $module_name ); ?>-module" id="<?php echo $module_name; ?>">
			<?php echo $module['content']; ?>
		</li>
		<?php endforeach; ?>
	</ul>
