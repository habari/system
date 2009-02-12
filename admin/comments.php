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
<?php include('header.php'); ?>

<div class="container navigator">
	<span class="older pct10"><a href="#" onclick="timeline.skipLoupeLeft();return false">&laquo; <?php _e('Older'); ?></a></span>
	<span class="currentposition pct15 minor"><?php _e('0 of 0'); ?></span>
	<span class="search pct50">
		<input id="search" type="search" placeholder="<?php _e('Type and wait to search'); ?>" autosave="habaricontent" results="10" value="<?php echo $search_args ?>">
	</span>
	<span class="filters pct15">&nbsp;
		<ul class="dropbutton special_search">
			<?php foreach($special_searches as $text => $term): ?>
			<li><a href="#<?php echo $term; ?>" title="<?php printf( _t('Filter results for \'%s\''), $text ); ?>"><?php echo $text; ?></a></li>
			<?php endforeach; ?>
		</ul>
	</span>
	<span class="newer pct10"><a href="#" onclick="timeline.skipLoupeRight();return false"><?php _e('Newer'); ?> &raquo;</a></span>


	<div class="timeline">
		<div class="years">
			<?php $theme->display( 'timeline_items' )?>
		</div>

		<div class="track">
			<div class="handle">
				<span class="resizehandleleft"></span>
				<span class="resizehandleright"></span>
			</div>
		</div>

	</div>

</div>

<form method="post" name="moderation" action="<?php URL::out( 'admin', array( 'page' => 'comments', 'status' => $status ) ); ?>">
	<input type="hidden" name="search" value="<?php echo $search; ?>">
	<input type="hidden" name="status" value="<?php echo $status; ?>">
	<input type="hidden" id="nonce" name="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" id="timestamp" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" id="PasswordDigest" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>">

<div class="container transparent item comments controls">
	<span class="checkboxandselected pct30">
		<input type="checkbox" id="master_checkbox" name="master_checkbox" class="select_all">
		<label class="selectedtext minor none" for="master_checkbox"><?php _e('None selected'); ?></label>
	</span>
	<span class="buttons">
		<span class="approve"><input type="submit" name="do_approve" value="<?php _e('Approve'); ?>" class="approve button" onclick="itemManage.update( 'approve' ); return false;"></span>
		<span class="unapprove"><input type="submit" name="do_unapprove" value="<?php _e('Unapprove'); ?>" class="unapprove button" onclick="itemManage.update( 'unapprove' ); return false;"></span>
		<span class="spam"><input type="submit" name="do_spam" value="<?php _e('Spam'); ?>" class="spam button" onclick="itemManage.update( 'spam' ); return false;"></span>
		<span class="delete"><input type="submit" name="do_delete" value="<?php _e('Delete'); ?>" class="delete button" onclick="itemManage.update( 'delete' ); return false;"></span>
	</span>
</div>

<div id="comments" class="container manage comments">

<?php $theme->display('comments_items'); ?>

</div>


<div class="container transparent item comments controls">
	<span class="checkboxandselected pct30">
		<input type="checkbox" id="master_checkbox_2" name="master_checkbox_2" class="select_all">
		<label class="selectedtext minor none" for="master_checkbox_2"><?php _e('None selected'); ?></label>
	</span>
	<span class="buttons">
		<span class="approve"><input type="submit" name="do_approve" value="<?php _e('Approve'); ?>" class="approve button" onclick="itemManage.update( 'approve' ); return false;"></span>
		<span class="unapprove"><input type="submit" name="do_unapprove" value="<?php _e('Unapprove'); ?>" class="unapprove button" onclick="itemManage.update( 'unapprove' ); return false;"></span>
		<span class="spam"><input type="submit" name="do_spam" value="<?php _e('Spam'); ?>" class="spam button" onclick="itemManage.update( 'spam' ); return false;"></span>
		<span class="delete"><input type="submit" name="do_delete" value="<?php _e('Delete'); ?>" class="delete button" onclick="itemManage.update( 'delete' ); return false;"></span>
	</span>
</div>

</form>

<script type="text/javascript">

itemManage.updateURL = habari.url.ajaxUpdateComment;
itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'comments')) ?>";
itemManage.fetchReplace = $('#comments');
itemManage.inEdit = true;

</script>


<?php include('footer.php'); ?>