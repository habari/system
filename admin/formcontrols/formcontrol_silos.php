<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div class="container pagesplitter">
	<ul id="mediatabs" class="tabs">
		<?php foreach($silos as $ct => $silodir): ?><li><a href="#silo_<?php echo $ct; ?>"<?php if ($silodir->icon != NULL): ?> style="background-image: url(<?php echo $silodir->icon; ?>)"<?php endif; ?>><?php echo $silodir->path; ?></a></li><?php endforeach; ?>
	</ul>

	<?php foreach($silos as $ct => $silodir): ?>
		<div id="silo_<?php echo $ct; ?>" class="splitter mediasplitter ui-tabs-hide">
			<div class="toload pathstore" style="display:none;"><?php echo $silodir->path; ?></div>
			<div id="silo_<?php echo Utils::slugify($silodir->path); ?>" class="splitterinside">
				<div class="media_controls">
					<ul>
					</ul>
				</div>
				<div class="media_browser">
					<div class="media_row">
						<ul class="mediadir"></ul>
						<div class="mediaphotos"></div>
					</div>
				</div>
				<div class="media_panel"></div>
			</div>
		</div>
	<?php endforeach; ?>
</div>
