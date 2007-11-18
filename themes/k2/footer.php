 
<!-- footer -->
 <div class="clear"></div>
</div>

<hr>

<p id="footer">
 <small><?php Options::out('title'); _e(' is powered by'); ?> <a href="http://www.habariproject.org/" title="Habari">Habari</a> <?php _e('and'); ?> <a href="http://i-want-a-pony.com/" title="A Pony" rel="nofollow">A Pony</a>.</small><br>
 <small><a href="<?php URL::out( 'atom_feed', array( 'index' => '1' ) ); ?>">Atom Entries</a> and <a href="<?php URL::out( 'atom_feed_comments' ); ?>">Atom Comments</a></small>
</p>

<?php Theme::footer(); ?>

<?php
// Uncomment this to view your DB profiling info
// include 'db_profiling.php';
?>
</body>
</html> 
<!-- /footer -->