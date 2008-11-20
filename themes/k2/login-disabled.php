<?php include 'header.php'; ?>
<!-- login -->
  <div class="content">
   <div id="primary">
    <div id="primarycontent" class="hfeed">
<?php include 'loginform.php'; ?>
<?php Plugins::act( 'theme_login' ); ?>
    </div>

   </div>

   <hr>

   <div class="secondary">

<?php include 'sidebar.php'; ?>

   </div>

   <div class="clear"></div>
  </div>
<!-- /login -->
<?php include 'footer.php'; ?>
