<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div class="installstep locale-dropdown ready done">
	<h2><?php _e('Locale'); ?></h2>
	<div class="options">
		<form method="post" action="" id="locale-form">
		<div class="inputfield">
			<label for="locale"><?php _e('Language'); ?></label>
			<?php
			$locs= array();
			foreach($locales as $loc):
				$locs[$loc]= $loc;
			endforeach;
			echo Utils::html_select( 'locale', $locs, $locale, array( 'tabindex' => $tab++ ) ); ?>
		</div>
		</form>
	</div>
	<div class="bottom"></div>
</div>
<div class="next-section"></div>
