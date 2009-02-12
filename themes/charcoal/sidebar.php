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

<div id="search">
	<?php $theme->search_form() ?>
</div>
<div id="feeds">
	<div class="feedlink"><a href="<?php URL::out( 'atom_feed', array( 'index' => '1' ) ); ?>"><?php _e( "{blog entries}" ); ?></a></div>
	<div class="feedlink"><a href="<?php URL::out( 'atom_feed_comments' ); ?>"><?php _e( "{comments}" ); ?></a></div>
</div>
<div id="habari-link">
<?php if ($show_powered) : ?>
	<a href="http://www.habariproject.org" title="<?php _e( "Powered by Habari" ); ?>"><img src="<?php Site::out_url('theme'); ?>/images/pwrd_habari.png" alt="<?php _e( "Powered by Habari" ); ?>"></a>
<?php  endif; ?>
</div>
<div id="sidebar">
<!-- call your plugins theme methods here-->
</div>
