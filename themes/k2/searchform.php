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
<!-- searchform -->
<?php Plugins::act( 'theme_searchform_before' ); ?>
     <form method="get" id="searchform" action="<?php URL::out('display_search'); ?>">
      <p><input type="text" id="s" name="criteria" value="<?php if ( isset( $criteria ) ) { echo htmlentities($criteria, ENT_COMPAT, 'UTF-8'); } ?>"> <input type="submit" id="searchsubmit" value="<?php _e('Go!'); ?>"></p>
     </form>
<?php Plugins::act( 'theme_searchform_after' ); ?>
<!-- /searchform -->
