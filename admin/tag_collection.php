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
<?php
$max = intval( $max );
function tag_weight( $count, $max )
{
	return round( 10 * log($count + 1) / log($max + 1) );
}
?>
<?php foreach ($tags as $tag) : ?>
		<span id="<?php echo 'tag_' . $tag->id ?>" class="item tag wt<?php echo tag_weight($tag->count, $max); ?>"> 
		 	<span class="checkbox"><input type="checkbox" class="checkbox" name="checkbox_ids[<?php echo $tag->id; ?>]" id="checkbox_ids[<?php echo $tag->id; ?>]"></span><label for="checkbox_ids[<?php echo $tag->id; ?>]"><?php echo $tag->tag; ?></label><sup><?php echo $tag->count; ?></sup> 
		 </span>
<?php endforeach; ?>
