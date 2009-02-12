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
<div class="container pagesplitter">
	<ul id="mediatabs" class="tabs">
		<?php foreach($silos as $ct => $silodir): ?><li><a href="#silo_<?php echo $ct; ?>"<?php if($silodir->icon != NULL): ?> style="background-image: url(<?php echo $silodir->icon; ?>)"<?php endif; ?>><?php echo $silodir->path; ?></a></li><?php endforeach; ?>
	</ul>

	<?php foreach($silos as $ct => $silodir): ?>
		<div id="silo_<?php echo $ct; ?>" class="splitter mediasplitter ui-tabs-hide">
			<div class="toload pathstore" style="display:none;"><?php echo $silodir->path; ?></div>
			<div class="splitterinside">
				<div id="mediaspinner"></div>
				<div class="media_controls">
					<ul>
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