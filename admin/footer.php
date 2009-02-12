<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
<div id="footer">
	<p>
		<span><a href="http://habariproject.org/" title="<?php _e('Go to the Habari site'); ?>">Habari
<?php
echo Version::get_habariversion();
if(Version::is_devel()) {
	echo ' r' . Version::get_svn_revision();
}
?> </a></span> <span class="middot">&middot;</span>
		<span><?php _e('Logged in as'); ?></span> <a href="<?php Site::out_url( 'habari' ); ?>/admin/user" title="<?php _e('Go to your user page'); ?>"><?php echo User::identify()->displayname ?></a> <span class="middot">&middot;</span>
		<span><a href="<?php Site::out_url( 'habari' ); ?>/doc/manual/index.html" onclick="popUp(this.href); return false;" title="<?php _e('Open the Habari manual in a new window'); ?>"><?php _e('Manual'); ?></a></span> <span class="middot">&middot;</span>
		<span><a href="<?php Site::out_url( 'habari' ); ?>/admin/sysinfo"> <?php _e( 'System Information'); ?></a>
 
	</p>
</div>

<?php
	Plugins::act( 'admin_footer', $this );
	Stack::out( 'admin_footer_javascript', array('Stack', 'scripts') );
	include ('db_profiling.php');
?>

</div>

<?php if(Session::has_messages()): ?>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		<?php Session::messages_out( true, array( 'Format', 'humane_messages' ) ); ?>
	})
  </script>
<?php endif; ?>

</body>
</html>
