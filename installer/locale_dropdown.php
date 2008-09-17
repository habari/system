	<div class="locale-dropdown ">
		<form method="post" action="" id="locale-form">
		
			<label for="locale"><?php _e('Language'); ?> </label>
			
			<select name="locale" id="locale" onchange="$('#locale-form').submit();">
				<?php foreach($locales as $loc): ?>
				<option value="<?php echo  htmlspecialchars($loc); ?>"
					<?php echo (isset($locale) && $loc == $locale) ? 'selected' : ''; ?> >
					<?php echo htmlspecialchars($loc); ?>
				</option>
				<?php endforeach; ?>
			</select>
		</form>
	</div>
