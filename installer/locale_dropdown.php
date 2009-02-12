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
<div class="installstep locale-dropdown ready done" id="locale">
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
