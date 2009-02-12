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
	<div class="container">
		<p>
			<label for="<?php echo $id; ?>" class="incontent textarea"><?php echo $caption; ?></label>
			<textarea name="<?php echo $field; ?>" id="<?php echo $id; ?>" class="styledformelement <?php echo $class; ?>" rows="20" cols="114" <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>><?php echo htmlspecialchars($value); ?></textarea>
		</p>
	<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
	</div>