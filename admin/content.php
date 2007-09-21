<?php include('header.php');?>
<div class="container">
<hr />
<div class="column span-24 first" id="welcome">
	<h1><?php _e('Habari Content'); ?></h1>
	<p><?php _e('Here you will find all the content you have created, ready to be tweaked, edited or removed.'); ?></p>
	<?php 
	// what to show?
	$author= ( isset($author) ) ? $author : 'any';
	$type= ( isset($type) ) ? intval($type) : Post::type('entry');
	$status= ( isset($status) ) ? intval($status) : Post::status('published');
	$limit= ( isset($limit) ) ? intval($limit) : '20';
	$year_month= ( isset($year_month) ) ? $year_month : 'Any';
	$search= ( isset($search) ) ? $search : '';
	$do_search= ( isset($do_search) ) ? $do_search : false;
	$page= ( isset($page) ) ? intval($page) : 1;
	?>
	</div>
	<form method="post" action="<?php URL::out('admin', 'page=content'); ?>" class="buttonform">
	<div class="column span-24" id="content-published">
	<p>
	Search post titles and content: 
	<input type="textbox" size="50" name='search' value="<?php echo $search; ?>"> <input type="submit" name="do_search" value="<?php _e('Search'); ?>">
	</p>
		<table id="post-data-published" width="100%" cellspacing="0">
			<thead>
				<tr>
					<th align="left"><?php _e('Title'); ?></th>
					<th align="left"><?php _e('Author'); ?></th>
					<th align="left"><?php _e('Published'); ?></th>
					<th align="left"><?php _e('Type'); ?></th>
					<th align="left"><?php _e('Status'); ?></th>
					<th align="center"> </th>
					<th align="right"><a href="<?php URL::out('admin', 'page=content'); ?>">Reset</a> </th>
				</tr>
			</thead>
			<tr>
			<td></td>
			<td><?php
			$authors= DB::get_column( 'SELECT DISTINCT username FROM ' . DB::table('users') . ' JOIN ' . DB::table('posts') . ' ON ' . DB::table('users') . '.id=' . DB::table('posts') . '.user_id ORDER BY username ASC');
			echo '<select name="author">';
			echo '<option value="any"';
			if ( $author == strtolower('any') ) {
				echo ' selected';
			}
			echo '>' . _('Any') . '</option>';
			foreach ( $authors as $name ) {
				echo "<option value='$name'";
				if ( $author == $name ) {
					echo ' selected';
				}
				echo ">$name</option>";
			}
			?></select></td>
			<td><select name="year_month"><?php
				$dates= DB::get_column('SELECT pubdate FROM ' . DB::table('posts') . ' ORDER BY pubdate DESC');
				$done_dates= array();
				echo "<option value='any'";
				if ( $year_month == strtolower('any') ) {
					echo ' selected';
				}
				echo '>' . _('Any') . '</option>';
				foreach ($dates as $date) {
					$date= substr($date, 0, 7);
					if ( in_array( $date, $done_dates ) ) {
						continue;
					}
					echo "<option value='$date'";
					if ( $date == $year_month ) {
						echo ' selected';
					}
					echo ">$date</option>";
					$done_dates[]= $date;
				}
			?></select></td>
			<td><select name="type"><?php
			foreach (Post::list_post_types() as $name => $value) {
				echo "<option ";
				if ( $value == $type ) {
					echo 'selected ';
				}
				echo "value='" . _($value) . "'>$name</option>";
			} ?></select></td>
			<td><select name="status"><?php
			foreach (Post::list_post_statuses() as $name => $value) {
				echo "<option value='$value'";
				if ( $value == $status ) {
					echo ' selected';
				}
				echo ">" . _($name) . "</option>";
			} ?></select></td>
			<td><?php _e('Limit'); ?>: <select name="limit">
	                <?php
        	        foreach ( array( 5, 10, 20, 50, 100 ) as $qty ) {
                	        echo "<option value='$qty'";
	                        if ( $qty == $limit ) {
	                                echo ' selected';
	                        }
	                        echo ">$qty</option>";
	                }
        	        ?></select></td>
			<td><input type="submit" name="filter" value="<?php _e('Filter'); ?>"></td>
			</tr>
			<?php
			// we load the WSSE tokens here
			// for use in the delete button below
			$wsse= Utils::WSSE();
			$arguments= array( 'content_type' => $type, 'status' => $status, 'limit' => $limit ); 
			if ( 'any' != strtolower($year_month) ) {
				$arguments['year']= substr($year_month, 0, 4);
				$arguments['month']= substr($year_month, 5, 2);
			}
			if ( $do_search ) {
				$arguments= array( 'criteria' => $search, 'nolimit' => 1 );
			} elseif ( '' != $search ) {
				$arguments['search']= $search;
			}
			foreach ( Posts::get( $arguments ) as $post ) {
			?>
			<tr>
				<td><?php echo '<a href="' . $post->permalink . '">' . $post->title ?></a></td>
				<td><?php echo $post->author->username ?></td>
				<td><?php echo $post->pubdate ?></td>
				<td><?php _e( Post::type_name( $post->content_type ) ); ?></td>
				<td><?php _e( Post::status_name( $post->status ) ); ?></td>
				<td align="center">
					<a class="edit" href="<?php URL::out('admin', 'page=publish&slug=' . $post->slug); ?>" title="Edit this entry">
						Edit
					</a>
				</td>
				<td align="center">
				<input type="checkbox" name="post_ids[]" value="<?php echo $post->id; ?>">
				</td>
			</tr>
			<?php
			}
			?>
		<tr><td colspan="7" align="right" style="text-align:right">
		<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>">
		<input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
		<input type="hidden" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
		Selected posts: &nbsp;&nbsp;<select name="change">
		<option value="unpublish"><?php _e('Unpublish'); ?></option>
		<option value="publish"><?php _e('Publish'); ?></option>
		<option value="delete"><?php _e('Delete'); ?></option>
		</select>
		<input type="submit" name="do_update" value="<?php _e('Update'); ?>">
		</form>
		</td></tr>
		</table>
	</div>
</div>
<?php include('footer.php');?>
