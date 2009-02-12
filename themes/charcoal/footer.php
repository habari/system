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
	</div>
	<div id="bottom-secondary">
		<div id="tags"><?php if (Plugins::is_loaded('tagcloud')) $theme->tag_cloud(); else $theme->show_tags();?></div>
	</div>
	<div id="footer">
		<p>
			<?php printf( _t('%1$s is powered by %2$s'), Options::get('title'),' <a
			href="http://www.habariproject.org/" title="Habari">Habari ' . Version::HABARI_VERSION  . '</a>' ); ?> - 
			<a href="<?php URL::out( 'atom_feed', array( 'index' => '1' ) ); ?>"><?php _e( 'Atom Entries' ); ?></a><?php _e( ' and ' ); ?>
			<a href="<?php URL::out( 'atom_feed_comments' ); ?>"><?php _e( 'Atom Comments' ); ?></a>
		</p>
	</div>
	<div class="clear"></div>
</div>
</div>
<?php $theme->footer(); ?>
</body>
</html>
