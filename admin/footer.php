
<div class="container" id="footer">
		
		<div id="footer-main">
			<p><a href="<?php URL::out('user', 'page=logout'); ?>" title="logout of Habari">Logout</a> |<a href="<?php Site::out_url('habari')?>/manual/index.html" onclick="popUp(this.href);return false;" title="Read this first">User Manual</a>  | 
			<a href="http://groups.google.com/group/habari-users" title="Ask questions to the community">Support</a>  | 
			<a href="http://wiki.habariproject.org/" title="Various documentation">Wiki</a></p>
		</div>
</div>
<?php
	Plugins::act( 'admin_footer', $this );
	Stack::out( 'admin_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
	include ('db_profiling.php');
?>
</div>
</body>
</html>
