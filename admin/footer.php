
<?php
	Plugins::act( 'admin_footer', $this );
	Stack::out( 'admin_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
	include ('db_profiling.php');
?>
</div>
<?php if(Session::has_messages()): ?>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		<?php echo Session::messages_out(); ?>
	})
  </script>
<?php endif; ?>
</body>
</html>
