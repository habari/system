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
<ul class="modules">
	<?php foreach($modules as $moduleid => $module): ?>
	<li class="module <?php echo Utils::slugify( $module['name'] ); ?>-module" id="<?php echo $moduleid . ':' . $module['name']; ?>">
	<?php if ( $module['name'] != _t('Add Item') ): ?>
		<div class="close">&nbsp;</div> 
	<?php endif; ?>
		<?php if ( $module['options'] ) : ?>
			<div class="options">&nbsp;</div>
		<?php endif; ?>
		
		<div class="modulecore">
			<h2><?php echo $module['title']; ?></h2>
			
			<?php if ( $moduleid != 'nosort' ) : ?>
				<div class="handle">&nbsp;</div>
			<?php endif; ?>
			
			<?php echo $module['content']; ?>
		</div>
		
		<?php if ( $module['options'] ) : ?>
		<div class="optionswindow">
			<h2><?php echo $module['title']; ?></h2>
			
			<?php if ( $moduleid != 'nosort' ) : ?>
				<div class="handle">&nbsp;</div>
			<?php endif; ?>
			
			<div class="optionscontent"> 
				<?php echo $module['options']; ?>
			</div>
		</div>
		<?php endif; ?>
	
	</li>
	<?php endforeach; ?>
</ul>
