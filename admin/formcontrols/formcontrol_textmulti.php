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
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
<p><?php echo $this->caption; ?></p>
<?php
if(!is_array($value)) {
	$value = array($value);
}
foreach($value as $value_1) :
?>
	<input type="text" name="<?php echo $field; ?>[]" value="<?php echo htmlspecialchars($value_1); ?>"> <label><a href="#" onclick="return controls.textmulti.remove(this);">[<?php _e('remove'); ?>]</a></label>
<?php
endforeach;
?>
<a href="#" onclick="return controls.textmulti.add(this, '<?php echo $field; ?>');">[<?php _e('add'); ?>]</a>
</div>
