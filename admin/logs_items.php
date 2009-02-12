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
<?php foreach( $logs as $log ): ?>
<div class="item clear">
		<span class="checkbox pct5"><span><input type="checkbox" class="checkbox" name="checkbox_ids[<?php echo $log->id; ?>]" id="checkbox_ids[<?php echo $log->id; ?>]"></span></span>
		<span class="time pct15 minor"><span><?php $log->timestamp->out( "M j, Y" ); ?> &middot; <?php $log->timestamp->out( "H:i" ); ?></span></span>
		<span class="user pct15 minor"><span>
			<?php if ( $log->user_id ) { 
				if ( $user = User::get_by_id( $log->user_id ) ) {
					 echo $user->displayname;
				} else {
					echo $log->user_id;
				}
			} ?>&nbsp;
		</span></span>
		<span class="ip pct10 minor"><span><?php echo long2ip($log->ip); ?></span></span>
		<span class="module pct10 minor"><span><?php echo $log->module; ?></span></span>
		<span class="type pct10 minor"><span><?php echo $log->type; ?></span></span>
		<span class="severity pct5 minor"><span><?php echo $log->severity; ?></span></span>
		<span class="message pct30 minor less"><span><?php echo Utils::truncate($log->message, 40, false); ?></span></span>
		<span class="message pct30 minor more"><span><?php echo $log->message; ?></span></span>
</div>
<?php endforeach; ?>
