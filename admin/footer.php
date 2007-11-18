
<div id="footer">
	<p class="left"><a href="<?php Site::out_url( 'habari' ); ?>/manual/index.html" onclick="popUp(this.href); return false;" title="Read the user manual">Manual</a> - 
		<a href="http://wiki.habariproject.org/" title="Read the Habari wiki">Wiki</a> - 
		<a href="http://groups.google.com/group/habari-users" title="Ask the community">Mailing List</a>
	</p>
	
	<p class="right"><a href="<?php URL::out( 'user', 'page=logout' ); ?>" title="Logout of Habari">Logout &rarr;</a></p>
</div>

<?php
	Plugins::act( 'admin_footer', $this );
	Stack::out( 'admin_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
	include ('db_profiling.php');
?>
</div>
</body>
</html>
