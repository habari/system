<ul class="modules">
	<?php foreach($modules as $moduleid => $module): ?>
	<li class="module <?php echo Utils::slugify( $module['name'] ); ?>-module" id="<?php echo $moduleid . ':' . $module['name']; ?>">
		<?php echo $module['content']; ?>
	</li>
	<?php endforeach; ?>
</ul>
