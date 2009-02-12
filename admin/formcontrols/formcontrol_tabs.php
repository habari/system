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
<div class="container pagesplitter" id="<?php echo $id; ?>">
	<ul class="tabcontrol tabs">
		<?php $ct =0;foreach($controls as $title => $tabcontent):$ct++;?><li><a href="#tab_<?php echo $id; ?>_<?php echo $ct; ?>"><?php echo $title; ?></a></li><?php endforeach; ?>
	</ul>

	<?php $ct =0;foreach($controls as $title => $tabcontent):$ct++;?>
		<div id="tab_<?php echo $id; ?>_<?php echo $ct; ?>" class="splitter">
			<div class="splitterinside"><?php echo $tabcontent; ?></div>
		</div>
	<?php endforeach; ?>
</div>
