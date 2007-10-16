<hr>
<div class="container" id="footer">
	<div class="column span-24">
		<div class="column span-8 first"><a href="http://habariproject.org/" title="Habari Official Website">Habari</a> - Spread the News</div>
		
		<div class="column prepend-11 span-5 last">
			<a href="<?php Site::out_url('habari')?>/manual/index.html" onclick="popUp(this.href);return false;" title="Read this first">User Manual</a> |
			<a href="http://groups.google.com/group/habari-users" title="Ask questions to the community">Support</a> |
			<a href="http://wiki.habariproject.org/" title="Various documentation">Wiki</a>
		</div>
		
	</div>
</div>
<?php
	Plugins::act( 'admin_footer', $this );
	Stack::out( 'admin_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
	include ('db_profiling.php');
?>
</body>
</html>
