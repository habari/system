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
<?php foreach( $years as $year => $year_array ): ?> 
<div class="year">
	<span><?php echo $year; ?></span>
	<div class="months">
		<?php foreach($year_array as $pdata): ?>
		<div><span style="width: <?php echo $pdata->ct; ?>px"><?php echo date('M', mktime(0, 0, 0, $pdata->month)) ?></span></div>
		<?php endforeach; ?>
	</div>
</div>
<?php endforeach; ?>
