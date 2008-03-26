 
<!-- footer -->
 <div class="clear"></div>
</div>

<hr>

<p id="footer">
 <small><?php Options::out('title'); _e(' is powered by'); ?> <a href="http://www.habariproject.org/" title="Habari">Habari</a> <?php _e('and'); ?> a huge amount of 
 <a href="http://en.wikipedia.org/wiki/Caffeine" title="Caffeine" rel="nofollow">C<sub>8</sub>H<sub>10</sub>N<sub>4</sub>O<sub>2</sub></a></small><br>
 <small><a href="<?php URL::out( 'atom_feed', array( 'index' => '1' ) ); ?>">Atom Entries</a> and <a href="<?php URL::out( 'atom_feed_comments' ); ?>">Atom Comments</a></small>
</p>

<?php $theme->footer(); ?>

<?php
// Uncomment this to view your DB profiling info
// include 'db_profiling.php';
?>
</body>
</html> 
<!-- /footer -->
