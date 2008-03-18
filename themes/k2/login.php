<?php include 'header.php'; ?>
<!-- login -->
  <div class="content">
   <div id="primary">
    <div id="primarycontent" class="hfeed">
<?php
if ( Session::has_errors( 'expired_session' ) ) {
	echo '<div class="alert">' . Session::get_error( 'expired_session', false ) . '</div>';
}
if ( Session::has_errors( 'expired_form_submission' ) ) {
	echo '<div class="alert">' . Session::get_error( 'expired_form_submission', false ) . '</div>';
}
?>
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
