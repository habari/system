<div id="footer">
	<p class="left"><a href="<?php Site::out_url( 'habari' ); ?>/doc/manual/index.html" onclick="popUp(this.href); return false;" title="<?php _e('Read the user manual'); ?>"><?php _e('Manual'); ?></a> - 
		<a href="http://wiki.habariproject.org/" title="<?php _e('Read the Habari wiki'); ?>"><?php _e('Wiki'); ?></a> - 
		<a href="http://groups.google.com/group/habari-users" title="<?php _e('Ask the community'); ?>"><?php _e('Mailing List'); ?></a>
	</p>
	
</div>

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
