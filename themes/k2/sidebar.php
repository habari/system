<!-- sidebar -->
<?php Plugins::act( 'theme_sidebar_top' ); ?>

    <div id="search">
     <h2>Search</h2>
<?php include 'searchform.php'; ?>
    </div>	
 
    <div class="sb-about">
     <h2>About</h2>
     <p><?php Options::out('about'); ?></p>
    </div>
 
    <div class="sb-user">
     <h2>User</h2>
<?php include 'loginform.php'; ?>
    </div>	
    
<?php Plugins::act( 'theme_sidebar_bottom' ); ?>
<!-- /sidebar -->