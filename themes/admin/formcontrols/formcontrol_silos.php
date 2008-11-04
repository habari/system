<div class="container pagesplitter">
	<ul id="mediatabs" class="tabs">
		<?php foreach($silos as $ct => $silodir): ?><li><a href="#silo_<?php echo $ct; ?>"<?php if($silodir->icon != NULL): ?> style="background-image: url(<?php echo $silodir->icon; ?>)"<?php endif; ?>><?php echo $silodir->path; ?></a></li><?php endforeach; ?>
	</ul>

	<?php foreach($silos as $ct => $silodir): ?>
		<div id="silo_<?php echo $ct; ?>" class="splitter mediasplitter">
			<div class="toload pathstore" style="display:none;"><?php echo $silodir->path; ?></div>
			<div class="splitterinside">
				<div id="mediaspinner"></div>
				<div class="media_controls">
					<input type="search" placeholder="<?php _e('Search descriptions, names and tags'); ?>" autosave="habarisettings" results="10">
					<ul>
						<li><a href="#" onclick="habari.media.showdir('<?php echo $silodir->path; ?>');return false;"><?php _e('Root'); ?></a></li>
					</ul>
					<?php /*<div class="upload"><input type="file"><input type="submit" value="<?php _e('Upload'); ?>"></div>*/ ?>
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
