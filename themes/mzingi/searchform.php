<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<!-- searchform -->
<?php Plugins::act( 'theme_searchform_before' ); ?>
     <form method="get" id="searchform" action="<?php URL::out('display_search'); ?>">
      <p><input type="text" id="s" name="criteria" value="<?php if ( isset( $criteria ) ) { echo htmlentities($criteria, ENT_COMPAT, 'UTF-8'); } else { _e('Search'); } ?>"><br> <input type="submit" id="searchsubmit" value="<?php _e('Go!'); ?>"></p>
     </form>
<?php Plugins::act( 'theme_searchform_after' ); ?>
<!-- searchform -->
