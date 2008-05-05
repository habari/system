
<?php
	Plugins::act( 'admin_footer', $this );
	Stack::out( 'admin_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
	include ('db_profiling.php');
?>
</div>
<?php if(Session::has_messages()): ?>
  <?php $messages= preg_replace("/'/", "\'", Session::messages_get()); ?>

	<script>
	jQuery(document).ready(function() {
		humanMsg.displayMsg('<?php echo $messages; ?>');
	})
  </script>
<?php endif; ?>
</body>
</html>
