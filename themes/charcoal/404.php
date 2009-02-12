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
<?php include 'header.php'; ?>

			<div id="main-posts">
				<div class="post alt">
					<div class="post-title">
						<h3><?php _e( "Whoops! 404" ); ?></h3>
					</div>
					<div class="post-entry">
						<p><?php _e( "The page you were trying to access is not really there. Please try again." ); ?><p>
					</div>
				</div>
			</div>
		</div>
		<div id="top-secondary">
			<?php include'sidebar.php' ?>
		</div>
		<div class="clear"></div>
	</div>
</div>
<div id="page-bottom">
	<div id="wrapper-bottom">
		<div id="bottom-primary">
			<?php $theme->display_archives() ;?>
			
<?php include 'footer.php'; ?>
