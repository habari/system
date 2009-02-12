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
<?php $theme->display ( 'header' ); ?>
<!-- error -->
  <div class="content">
   <div id="primary">
    <div id="post" class="error">

     <div class="entry-head">
      <h3 class="entry-title"><?php _e('Error!'); ?></h3>
     </div>

     <div class="entry-content">
      <p><?php _e('The requested post was not found.'); ?></p>
     </div>

    </div>
   </div>

   <hr>

   <div class="secondary">

<?php $theme->display ( 'sidebar' ); ?>

   </div>

   <div class="clear"></div>
  </div>
<!-- /error -->
<?php $theme->display ('footer'); ?>
