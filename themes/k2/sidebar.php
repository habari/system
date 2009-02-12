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
<!-- sidebar -->
<?php Plugins::act( 'theme_sidebar_top' ); ?>

    <div id="search">
     <h2><?php _e('Search'); ?></h2>
<?php $theme->display ('searchform' ); ?>
    </div>

    <div class="sb-about">
     <h2><?php _e('About'); ?></h2>
     <p><?php Options::out('about'); ?></p>
    </div>

    <div class="sb-user">
     <h2><?php _e('User'); ?></h2>
<?php $theme->display ( 'loginform' ); ?>
    </div>

<?php Plugins::act( 'theme_sidebar_bottom' ); ?>
<!-- /sidebar -->
