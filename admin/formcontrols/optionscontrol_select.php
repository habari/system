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
	<span class="pct25"><select name="<?php echo $field . ( $multiple ? '[]' : '' ); ?>"<?php echo ( $multiple ? ' multiple="multiple" size="' . intval($size) . '"' : '' ) ?> <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>>
	<?php foreach($options as $opts_key => $opts_val) : ?>
		<?php if (is_array($opts_val)) : ?>
			<optgroup label="<?php echo $opts_key; ?>">
			<?php foreach($opts_val as $opt_key => $opt_val) : ?>
				<option value="<?php echo $opt_key; ?>"<?php echo ( in_array( $opt_key, (array) $value ) ? ' selected' : '' ); ?>><?php echo htmlspecialchars($opt_val); ?></option>
			<?php endforeach; ?>
			</optgroup>
		<?php else : ?>
			<option value="<?php echo $opts_key; ?>"<?php echo ( in_array( $opts_key, (array) $value ) ? ' selected' : '' ); ?>><?php echo htmlspecialchars($opts_val); ?></option>
		<?php endif; ?>
	<?php endforeach; ?>
	</select></span>
	<?php if(!empty($helptext)) : ?>
	<span class="pct40 helptext"><?php echo $helptext; ?></span>
	<?php endif; ?>
	<?php if($message != '') : ?>
	<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>
