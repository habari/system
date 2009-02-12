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
 
<!-- footer -->
 <div class="clear"></div>
</div>

<hr>

<p id="footer">
 <small><?php Options::out('title'); _e(' is powered by'); ?> <a href="http://www.habariproject.org/" title="Habari">Habari</a> <?php _e('and a huge amount of'); ?>
 <a href="http://en.wikipedia.org/wiki/Caffeine" title="<?php _e('Caffeine'); ?>" rel="nofollow">C<sub>8</sub>H<sub>10</sub>N<sub>4</sub>O<sub>2</sub></a></small><br>
 <small><a href="<?php URL::out( 'atom_feed', array( 'index' => '1' ) ); ?>"><?php _e('Atom Entries'); ?></a> <?php _e('and'); ?> <a href="<?php URL::out( 'atom_feed_comments' ); ?>"><?php _e('Atom Comments'); ?></a></small>
</p>

<?php $theme->footer(); ?>

<?php
/* In order to see DB profiling information:
 1. Insert this line in your config file: define( 'DEBUG', TRUE );
 2.Uncomment the followng line
 */
// include 'db_profiling.php';
?>
</body>
</html>
<!-- /footer -->
