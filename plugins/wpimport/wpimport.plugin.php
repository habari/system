<?php
	
	// define IMPORT_BATCH in your config.php to limit each batch of DB results
	if ( !defined('IMPORT_BATCH') ) {
		define('IMPORT_BATCH', 100);
	}
	
	class WPImport extends Plugin implements Importer {
		
		private $supported_importers = array();
		
		private $default_values = array(
			'db_name' => '',
			'db_host' => 'localhost',
			'db_user' => '',
			'db_pass' => '',
			'db_prefix' => 'wp_',
			'category_import' => true,
			'import_index' => 0,
			'error' => '',
		);
		
		public function action_init ( ) {
			
			$this->supported_importers[] = _t('WordPress Database');
			
		}
		
		public function filter_import_names ( $import_names ) {
			
			return array_merge( $import_names, $this->supported_importers );
			
		}
		
		public function filter_import_stage ( $stage_output, $import_name, $stage, $step ) {
			
			// only act on this filter if the import_name is one we handle
			if ( !in_array( $import_name, $this->supported_importers ) ) {
				// it's a filter, always return the output another plugin might have generated
				return $stage_output;
			}
			
			// the values we'll hand to each stage for processing
			$inputs = array();
			
			// validate input and figure out which stage we're at
			switch ( $stage ) {
				
				case 1:
					
					if ( isset( $_POST['wpimport'] ) ) {
						
						$inputs = $_POST->filter_keys( array( 'db_name', 'db_user', 'db_host', 'db_pass', 'db_prefix', 'category_import', 'import_index' ) );
						$inputs = $inputs->getArrayCopy();
						
						// try to connect to the db with the given values
						if ( $this->wp_connect( $inputs['db_host'], $inputs['db_name'], $inputs['db_user'], $inputs['db_pass'] ) ) {
							
							// we've got good connection info, bump to stage 2
							$stage = 2;
							
						}
						else {
							
							// add a warning to the stack
							$inputs['error'] = _t('Could not connect to the WordPress database using the values supplied. Please correct them and try again.');
							
						}
						
					}
					
					break;
				
			}
			
			
			// now dispatch the right stage
			switch ( $stage ) {
				
				case 1:
				default:
					$output = $this->stage1( $inputs );
					break;
					
				case 2:
					$output = $this->stage2( $inputs );
					break;
				
			}
			
			// return the output for the importer to display
			return $output;
			
		}
		
		private function stage1 ( $inputs ) {
			
			$inputs = array_merge( $this->default_values, $inputs );
			
			// if there is a error, display it
			if ( $inputs['error'] != '' ) {
				$error = '<p class="error">' . $inputs['error'] . '</p>';
			}
			else {
				// blank it out just so we can use the value in output
				$error = '';
			}
			
			$output = '<p>' . _t( 'Habari will attempt to import from a WordPress database.') . '</p>';
			$output .= $error;
			
			// get the FormUI form
			//$form = $this->get_form( $inputs );
			
			// append the output of the form
			//$output .= $form->get();
			
			$output .= '<div class="item clear">';
			$output .= 	'<span class="pct25"><label for="db_name">' . _t( 'Database Name' ) . '</label></span><span class="pct40"><input type="text" name="db_name" id="db_name" value="' . $inputs['db_name'] . '"></span>';
			$output .= '</div>';
			
			$output .= '<div class="item clear">';
			$output .= 	'<span class="pct25"><label for="db_host">' . _t( 'Database Host' ) . '</label></span><span class="pct40"><input type="text" name="db_host" id="db_host" value="' . $inputs['db_host'] . '"></span>';
			$output .= '</div>';
			
			$output .= '<div class="item clear">';
			$output .= 	'<span class="pct25"><label for="db_user">' . _t( 'Database User' ) . '</label></span><span class="pct40"><input type="text" name="db_user" id="db_user" value="' . $inputs['db_user'] . '"></span>';
			$output .= '</div>';
			
			$output .= '<div class="item clear">';
			$output .= 	'<span class="pct25"><label for="db_pass">' . _t( 'Database Password' ) . '</label></span><span class="pct40"><input type="text" name="db_pass" id="db_pass" value="' . $inputs['db_pass'] . '"></span>';
			$output .= '</div>';
			
			$output .= '<div class="item clear">';
			$output .= 	'<span class="pct25"><label for="db_prefix">' . _t( 'Table Prefix' ) . '</label></span><span class="pct40"><input type="text" name="db_prefix" id="db_prefix" value="' . $inputs['db_prefix'] . '"></span>';
			$output .= '</div>';
			
			$output .= '<div class="item clear">';
			$output .= 	'<span class="pct25"><label for="category_import">' . _t( 'Import Categories as Tags' ) . '</label></span><span class="pct40"><input type="checkbox" name="category_import" id="category_import" value="true" ' . ( ( $inputs['category_import'] == true ) ? 'checked="checked"' : '' ) . '></span>';
			$output .= '</div>';
			
			$output .= '<div class="item clear transparent">';
			$output .=	'<input type="submit" class="button" name="wpimport" value="' . _t( 'Import' ) . '">';
			$output .= '</div>';
			
			return $output;
			
		}
		
		private function stage2 ( $inputs ) {
			
			// make sure we have all our default values
			$inputs = array_merge( $this->default_values, $inputs );
			
			// the first thing we import are users, so get that URL to kick off the ajax process
			$ajax_url = URL::get( 'auth_ajax', array( 'context' => 'wp_import_users' ) );
			
			// the variables we'll hand to the ajax call are all the input values
			$vars = $inputs;
			
			EventLog::log( _t('Starting import from "%s"', array( $inputs['db_name'] ) ) );
			
			$output = '<p>' . _t('Import in Progress') . '</p>';
			$output .= '<div id="import_progress">' . _t( 'Starting Import&hellip;' ) . '</div>';
			$output .= $this->get_ajax( $ajax_url, $vars );
			
			return $output;
			
		}
		
		private function get_ajax ( $url, $vars = array() ) {
			
			// generate the vars we'll use
			$ajax_vars = array();
			foreach ( $vars as $k => $v ) {
				$ajax_vars[] = $k . ': "' . $v . '"';
			}
			$ajax_vars = implode( ',', $ajax_vars );
			
			$output = <<<WP_IMPORT_AJAX
				<script type="text/javascript">
					$(document).ready( function() {
						$('#import_progress').load(
							"{$url}",
							{
								{$ajax_vars}
							}
						);
					} );
				</script>
WP_IMPORT_AJAX;

			return $output;
			
		}
		
		private function get_form ( $inputs ) {
			// this isn't used right now because we can't use formui in an importer, there's already a form
			$form = new FormUI('wp_importer');
			
			$db_name = $form->append( 'text', 'db_name', 'null:null', _t( 'Database Name') );
			$db_name->value = $inputs['db_name'];
			
			$db_host = $form->append( 'text', 'db_host', 'null:null', _t( 'Database Host' ) );
			$db_host->value = $inputs['db_host'];
			
			$db_user = $form->append( 'text', 'db_user', 'null:null', _t( 'Database User' ) );
			$db_user->value = $inputs['db_user'];
			
			$db_pass = $form->append( 'text', 'db_pass', 'null:null', _t( 'Database Password' ) );
			$db_pass->value = $inputs['db_pass'];
			
			$db_prefix = $form->append( 'text', 'db_prefix', 'null:null', _t( 'Table Prefix' ) );
			$db_prefix->value = $inputs['db_prefix'];
			
			$category_import = $form->append( 'checkbox', 'category_import', 'null:null', _t( 'Import Categories as Tags' ) );
			$category_import->value = ( $inputs['category_import'] ) ? true : false;
			
			$submit = $form->append( 'submit', 'submit', _t( 'Import' ) );
			
			
			return $form;
			
		}
		
		private function wp_connect ( $db_host, $db_name, $db_user, $db_pass ) {
			
			// build the connection string, since we stupidly have to use it twice
			$connection_string = 'mysql:host=' . $db_host . ';dbname=' . $db_name;
			
			try {
				$wpdb = DatabaseConnection::ConnectionFactory( $connection_string );
				$wpdb->connect( $connection_string, $db_user, $db_pass );
				
				// @todo make sure preifx_* tables exist?
				
				return $wpdb;
			}
			catch ( Exception $e ) {
				// just hide connection errors, it's enough that we errored out
				return false;
			}
			
		}
		
		public function action_auth_ajax_wp_import_users ( ) {
			
			// get the values post'd in
			$inputs = $_POST->filter_keys( array( 'db_name', 'db_host', 'db_user', 'db_pass', 'db_prefix', 'category_import', 'import_index' ) );
			$inputs = $inputs->getArrayCopy();
			
			// make sure we have all our default values
			$inputs = array_merge( $this->default_values, $inputs );
			
			// get the wpdb
			$wpdb = $this->wp_connect( $inputs['db_host'], $inputs['db_name'], $inputs['db_user'], $inputs['db_pass'] );
			
			// if we couldn't connect, error out
			if ( !$wpdb ) {
				EventLog::log( _t( 'Failed to import from "%s"', array( $inputs['db_name'] ) ) );
				Session::error( _t( 'Failed to import from "%s"', array( $inputs['db_name'] ) ) );
				echo '<p>' . _t( 'Failed to connect using the given database connection details.' ) . '</p>';
			}
			
			// we connected just fine, let's get moving!
			
			// begin a transaction. if we error out at any point, we want to roll back to before import began
			DB::begin_transaction();
			
			// fetch all the users from the wordpress database
			$wp_users = $wpdb->get_results( 'select id, user_login, user_pass, user_email, user_url, display_name from ' . $inputs['db_prefix'] . 'users' );
			
			echo '<p>' . _t( 'Importing Users&hellip;' ) . '</p>';
			
			foreach ( $wp_users as $wp_user ) {
				
				// see if a user with this username already exists
				$user = User::get_by_name( $wp_user->user_login );
				
				if ( $user !== false ) {
					
					// if the user exists, save their old ID into an info attribute
					$user->info->wp_id = intval( $wp_user->id );
					
					// and update
					$user->update();
					
					echo '<p>' . _t( 'Associated imported user %1$s with existing user %2$s', array( $wp_user->user_login, $user->username ) ) . '</p>';
					
					EventLog::log( _t( 'Associated imported user %1$s with existing user %2$s', array( $wp_user->user_login, $user->username ) ) );
					
				}
				else {
					
					// no user exists, we need to create one
					try {
						
						$u = new User();
						$u->username = $wp_user->user_login;
						$u->email = $wp_user->user_email;
						
						// set their password so the user will be able to login. they're auto-added to the 'authenticated' ACL group
						$u->password = Utils::crypt( $wp_user->user_pass );
						
						$u->info->wp_id = intval( $wp_user->id );
						$u->info->displayname = $wp_user->display_name;
						
						if ( $wp_user->user_url != '' ) {
							$u->info->url = $wp_user->user_url;
						}
						
						// and save it
						$u->insert();
						
						echo '<p>' . _t( 'Created new user %1$s. Their old ID was %2$d.', array( $u->username, $wp_user->id ) ) . '</p>';
						
						EventLog::log( _t( 'Created new user %1$s. Their old ID was %2$d.', array( $u->username, $wp_user->id ) ) );
						
					}
					catch ( Exception $e ) {
						
						// no idea why we might error out, but catch it if we do
						EventLog::log( $e->getMessage, 'err' );
						
						echo '<p class="error">' . _t( 'There was an error importing user %s. See the EventLog for the error message. ', array( $wp_user->user_login ) ) . '</p>';
						
						echo '<p>' . _t( 'Rolling back changes&hellip;' ) . '</p>';
						
						// rollback all changes before we return so the import hasn't changed anything yet
						DB::rollback();
						
						// and return so they don't get AJAX to send them on to the next step
						return false;
						
					}
					
				}
				
			}
			
			
			// if we've finished without an error, commit the import
			DB::commit();
			
			// get the next ajax url
			$ajax_url = URL::get( 'auth_ajax', array( 'context' => 'wp_import_posts' ) );
			
			// and spit out ajax to send them to the next step - posts!
			echo $this->get_ajax( $ajax_url, $inputs );
			
		}

		public function action_auth_ajax_wp_import_posts ( ) {
			
			// get the values post'd in
			$inputs = $_POST->filter_keys( array( 'db_name', 'db_host', 'db_user', 'db_pass', 'db_prefix', 'category_import', 'import_index' ) );
			$inputs = $inputs->getArrayCopy();
			
			// make sure we have all our default values
			$inputs = array_merge( $this->default_values, $inputs );
			
			// get the wpdb
			$wpdb = $this->wp_connect( $inputs['db_host'], $inputs['db_name'], $inputs['db_user'], $inputs['db_pass'] );
			
			// if we couldn't connect, error out
			if ( !$wpdb ) {
				EventLog::log( _t( 'Failed to import from "%s"', array( $inputs['db_name'] ) ) );
				Session::error( _t( 'Failed to import from "%s"', array( $inputs['db_name'] ) ) );
				echo '<p>' . _t( 'Failed to connect using the given database connection details.' ) . '</p>';
			}
			
			// we connected just fine, let's get moving!
			
			// begin a transaction. if we error out at any point, we want to roll back to before import began
			DB::begin_transaction();
			
			// fetch the number of posts from the wordpress database so we can batch things up
			$num_posts = $wpdb->get_value( 'select count(id) from ' . $inputs['db_prefix'] . 'posts' );
			
			// figure out the LIMIT we're at
			$min = $inputs['import_index'] * IMPORT_BATCH;
			$max = min( $min + IMPORT_BATCH, $num_posts );		// for display only
			
			
			echo '<p>' . _t( 'Importing posts %1$d - %2$d of %3$d.', array( $min, $max, $num_posts ) ) . '</p>';
			
			// get all the imported users so we can link old post authors to new post authors
			$users = DB::get_results( 'select user_id, value from {userinfo} where name = :name', array( ':name' => 'wp_id' ) );
			
			// create an easy user map of old ID -> new ID
			$user_map = array();
			foreach ( $users as $info ) {
				$user_map[ $info->value ] = $info->user_id;
			}
			
			// get all the post IDs we've imported so far to make sure we don't duplicate any
			$post_map = DB::get_column( 'select value from {postinfo} where name = :name', array( ':name' => 'wp_id' ) );
			
			
			
			// now we're ready to start importing posts
			$posts = $wpdb->get_results( 'select id, post_author, post_date, post_content, post_title, post_status, comment_status, post_name, post_modified, guid, post_type from ' . $inputs['db_prefix'] . 'posts order by id asc limit ' . $min . ', ' . IMPORT_BATCH );
			
			foreach ( $posts as $post ) {
				
				// if this post is already in the list we've imported, skip it
				if ( in_array( $post->id, $post_map ) ) {
					continue;
				}
				
				// set up the big taxonomy sql query
				// if this turns out to be incredibly slow we should refactor it into a big join, but they're all keys so it seems zippy enough for me
				$taxonomy_query = 'select name, slug from ' . $inputs['db_prefix'] . 'terms where term_id in ( select term_id from ' . $inputs['db_prefix'] . 'term_taxonomy where taxonomy = :taxonomy and term_taxonomy_id in ( select term_taxonomy_id from ' . $inputs['db_prefix'] . 'term_relationships where object_id = :object_id ) )';
				
				// get all the textual tag names for this post
				$tags = $wpdb->get_results( $taxonomy_query, array( ':taxonomy' => 'post_tag', ':object_id' => $post->id ) );
				
				// should we import categories as tags too?
				if ( $inputs['category_import'] ) {
					// then do the same as above for the category taxonomy
					$categories = $wpdb->get_results( $taxonomy_query, array( ':taxonomy' => 'category', ':object_id' => $post->id ) );
				}
				
				// create the new post
				$p = new Post( array(
					'title' => MultiByte::convert_encoding( $post->post_title ),
					'content' => MultiByte::convert_encoding( $post->post_content ),
					'user_id' => $user_map[ $post->post_author ],
					'pubdate' => HabariDateTime::date_create( $post->post_date ),
					'updated' => HabariDateTime::date_create( $post->post_modified ),
					'slug' => MultiByte::convert_encoding( $post->post_name ),
				) );
				
				// figure out the post type
				switch ( $post->post_type ) {
					
					case 'post':
						$p->content_type = Post::type( 'entry' );
						break;
						
					case 'page':
						$p->content_type = Post::type( 'page' );
						break;
						
					default:
						// we're not importing other types - continue 2 to break out of the switch and the loop and continue to the next post
						continue 2;
					
				}
				
				// figure out the post status
				switch ( $post->post_status ) {
					
					case 'publish':
						$p->status = Post::status( 'published' );
						break;
						
					case 'future':
						$p->status = Post::status( 'scheduled' );
						break;
						
					case 'pending':		// means pending-review, not pending as in scheduled
					case 'draft':
						$p->status = Post::status( 'draft' );
						break;
						
					default:
						// Post::status() returns false if it doesn't recognize the status type
						$status = Post::status( $post->post_status );		// store in a temp value because if you try and set ->status to an invalid value the Post class freaks
						
						if ( $status == false ) {
							// we're not importing statuses we don't recognize - continue 2 to break out of the switch and the loop and continue to the next post
							continue 2;
						}
						else {
							$p->status = $status;
						}
						
						break;
					
				}
				
				// if comments are closed, disable them on the new post
				if ( $post->comment_status == 'closed' ) {
					$p->info->comments_disabled = true;
				}
				
				// save the old post ID in info
				$p->info->wp_id = $post->id;
				
				// since we're not using it, save the old GUID too
				$p->info->wp_guid = $post->guid;
				
				
				// now that we've got all the pieces in place, save the post
				try {
					$p->insert();
					
					// now that the post is in the db we can add tags to it
					
					// first, if we want to import categories as tags, add them to the array
					if ( $inputs['category_import'] ) {
						$tags = array_merge( $tags, $categories );
					}
					
					// now for the tags!
					foreach ( $tags as $tag ) {
						
						// try to get the tag by slug, which is the key and therefore the most unique
						$t = Tags::get_by_slug( $tag->slug );
						
						// if we didn't get back a tag, create a new one
						if ( $t == false ) {
							$t = Tag::create( array(
								'term' => $tag->slug,
								'term_display' => $tag->name
							) );
						}
					
						// now that we have a tag (one way or the other), associate this post with it
						$t->associate( 'post', $p->id );
						
					}
					
				}
				catch ( Exception $e ) {
					
					EventLog::log( $e->getMessage(), 'err' );
					
					echo '<p class="error">' . _t( 'There was an error importing post %s. See the EventLog for the error message.', array( $post->post_title ) );
					
					echo '<p>' . _t( 'Rolling back changes&hellip;' ) . '</p>';
					
					// rollback all changes before we return so the import hasn't changed anything yet
					DB::rollback();
					
					// and return so they don't get AJAX to send them on to the next step
					return false;
					
				}
				
			}
			
			
			// if we've finished without an error, commit the import
			DB::commit();
			
			if ( $max < $num_posts ) {
				
				// if there are more posts to import
				
				// get the next ajax url
				$ajax_url = URL::get( 'auth_ajax', array( 'context' => 'wp_import_posts' ) );
				
				// bump the import index by one so we get a new batch next time
				$inputs['import_index']++;
				
				
			}
			else {
				
				// move on to importing comments
				
				// get the next ajax url
				$ajax_url = URL::get( 'auth_ajax', array( 'context' => 'wp_import_comments' ) );
				
				// reset the import index so we start at the first comment
				$inputs['import_index'] = 0;
				
			}
			
			// and spit out ajax to send them to the next step - posts!
			echo $this->get_ajax( $ajax_url, $inputs );
			
		}
		
		public function action_auth_ajax_wp_import_comments ( ) {
			
			// get the values post'd in
			$inputs = $_POST->filter_keys( array( 'db_name', 'db_host', 'db_user', 'db_pass', 'db_prefix', 'category_import', 'import_index' ) );
			$inputs = $inputs->getArrayCopy();
			
			// make sure we have all our default values
			$inputs = array_merge( $this->default_values, $inputs );
			
			// get the wpdb
			$wpdb = $this->wp_connect( $inputs['db_host'], $inputs['db_name'], $inputs['db_user'], $inputs['db_pass'] );
			
			// if we couldn't connect, error out
			if ( !$wpdb ) {
				EventLog::log( _t( 'Failed to import from "%s"', array( $inputs['db_name'] ) ) );
				Session::error( _t( 'Failed to import from "%s"', array( $inputs['db_name'] ) ) );
				echo '<p>' . _t( 'Failed to connect using the given database connection details.' ) . '</p>';
			}
			
			// we connected just fine, let's get moving!
			
			// begin a transaction. if we error out at any point, we want to roll back to before import began
			DB::begin_transaction();
			
			// fetch the number of comments from the wordpress database so we can batch things up
			$num_comments = $wpdb->get_value( 'select count(comment_id) from ' . $inputs['db_prefix'] . 'comments' );
			
			// figure out the LIMIT we're at
			$min = $inputs['import_index'] * IMPORT_BATCH;
			$max = min( $min + IMPORT_BATCH, $num_comments );		// for display only
			
			
			echo '<p>' . _t( 'Importing comments %1$d - %2$d of %3$d.', array( $min, $max, $num_comments ) ) . '</p>';
			
			// get all the imported users so we can link old comment authors to new comment authors
			$users = DB::get_results( 'select user_id, value from {userinfo} where name = :name', array( ':name' => 'wp_id' ) );
			
			// create an easy user map of old ID -> new ID
			$user_map = array();
			foreach ( $users as $info ) {
				$user_map[ $info->value ] = $info->user_id;
			}
			
			// get all the imported posts so we can link old post IDs to new post IDs
			$posts = DB::get_results( 'select post_id, value from {postinfo} where name = :name', array( ':name' => 'wp_id' ) );
			
			// create an easy post map of old ID -> new ID
			$post_map = array();
			foreach ( $posts as $info ) {
				$post_map[ $info->value ] = $info->post_id;
			}
			
			// get all the comment IDs we've imported so far to make sure we don't duplicate any
			$comment_map = DB::get_column( 'select value from {commentinfo} where name = :name', array( ':name' => 'wp_id' ) );
			
			
			
			// now we're ready to start importing comments
			$comments = $wpdb->get_results( 'select comment_id, comment_post_id, comment_author, comment_author_email, comment_author_url, comment_author_ip, comment_date, comment_content, comment_karma, comment_approved, comment_agent, comment_type, comment_parent, user_id from ' . $inputs['db_prefix'] . 'comments order by comment_id asc limit ' . $min . ', ' . IMPORT_BATCH );
			
			foreach ( $comments as $comment ) {
				
				// if this post is already in the list we've imported, skip it
				if ( in_array( $comment->id, $comment_map ) ) {
					continue;
				}
				
				// if the post this comment belongs to is not in the list of imported posts, skip it
				if ( !isset( $post_map[ $comment->comment_post_id ] ) ) {
					continue;
				}
				
				// create the new comment
				$c = new Comment( array(
					'content' => MultiByte::convert_encoding( $comment->comment_content ),
					'name' => MultiByte::convert_encoding( $comment->comment_author ),
					'email' => MultiByte::convert_encoding( $comment->comment_author_email ),
					'url' => MultiByte::convert_encoding( $comment->comment_author_url ),
					'date' => HabariDateTime::date_create( $comment->comment_date ),
					'post_id' => $post_map[ $comment->comment_post_id ],
				) );
				
				// figure out the comment type
				switch ( $comment->comment_type ) {
					
					case 'pingback':
						$c->type = Comment::type( 'pingback' );
						break;
						
					case 'trackback':
						$c->type = Comment::type( 'trackback' );
						break;
						
					default:
					case 'comment':
						$c->type = Comment::type( 'comment' );
						break;
					
				}
				
				// figure out the comment status
				switch ( $comment->comment_approved ) {
					
					case '1':
						$c->status = Comment::status( 'approved' );
						break;
						
					case '':
					case '0':
						$c->status = Comment::status( 'unapproved' );
						break;
						
					case 'spam':
						$c->status = Comment::status( 'spam' );
						break;
						
					default:
						// Comment::status() returns false if it doesn't recognize the status type
						$status = Comment::status( $comment->comment_status );		// store in a temp value because if you try and set ->status to an invalid value the Comment class freaks
						
						if ( $status == false ) {
							// we're not importing statuses we don't recognize - continue 2 to break out of the switch and the loop and continue to the next comment
							continue 2;
						}
						else {
							$c->status = $status;
						}
						
						break;
					
				}
				
				// save the old comment ID in info
				$c->info->wp_id = $comment->comment_id;
				
				// save the old post ID in info
				$c->info->wp_post_id = $comment->comment_post_id;
				
				// save the old comment karma - but only if it is something
				if ( $comment->comment_karma != '0' ) {
					$c->info->wp_karma = $comment->comment_karma;
				}
				
				// save the old comment user agent - but only if it is something
				if ( $comment->comment_agent != '' ) {
					$c->info->wp_agent = $comment->comment_agent;
				}
				
				
				// now that we've got all the pieces in place, save the comment
				try {
					$c->insert();
				}
				catch ( Exception $e ) {
					
					EventLog::log( $e->getMessage(), 'err' );
					
					echo '<p class="error">' . _t( 'There was an error importing comment ID %d. See the EventLog for the error message.', array( $comment->comment_id ) );
					
					echo '<p>' . _t( 'Rolling back changes&hellip;' ) . '</p>';
					
					// rollback all changes before we return so the import hasn't changed anything yet
					DB::rollback();
					
					// and return so they don't get AJAX to send them on to the next step
					return false;
					
				}
				
			}
			
			
			// if we've finished without an error, commit the import
			DB::commit();
			
			if ( $max < $num_comments ) {
				
				// if there are more posts to import
				
				// get the next ajax url
				$ajax_url = URL::get( 'auth_ajax', array( 'context' => 'wp_import_comments' ) );
				
				// bump the import index by one so we get a new batch next time
				$inputs['import_index']++;
				
				
			}
			else {
				
				// display the completed message!
				
				EventLog::log( _t( 'Import completed from "%s"', array( $inputs['db_name'] ) ) );
				echo '<p>' . _t( 'Import is complete.' ) . '</p>';
				
				return;
				
			}
			
			// and spit out ajax to send them to the next step - posts!
			echo $this->get_ajax( $ajax_url, $inputs );
			
		}

		
	}

?>