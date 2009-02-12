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
<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<span class="pct25"><label for="<?php echo $field ?>"><?php echo $this->caption; ?></label></span>
	<span class="pct5"><input type="checkbox" name="<?php echo $field; ?>" value="1" <?php echo $value ? 'checked' : ''; ?> <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>></span>
	<?php if(!empty($helptext)) : ?>
	<span class="pct60 helptext"><?php echo $helptext; ?></span>
	<?php endif; ?>
	<input type="hidden" name="<?php echo $field; ?>_submitted" value="1" >
	<?php if($message != '') : ?>
	<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>
