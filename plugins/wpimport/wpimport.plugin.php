<?php

define( 'IMPORT_BATCH', 100 );

/**
 * WordPress Importer - Imports data from WordPress into Habari
 *
 * @package Habari
 */
class WPImport extends Plugin implements Importer
{
	private $supported_importers = array();

	/**
	 * Initialize plugin.
	 * Set the supported importers.
	 **/
	public function action_init()
	{
		$this->supported_importers = array( _t( 'WordPress Database' ) );
	}

	/**
	* Return plugin metadata for this plugin
	*
	* @return array Plugin metadata
	*/
	public function info()
	{
		return array(
			'name' => 'WordPress Importer',
			'version' => '1.1',
			'url' => 'http://habariproject.org/',
			'author' =>	'Habari Community',
			'authorurl' => 'http://habariproject.org/',
			'license' => 'Apache License 2.0',
			'description' => 'Import database from WordPress 2.3 and higher. Includes Ultimate Tag Warrior integration.',
			'copyright' => '2008'
		 );
	}

	/**
	 * Return a list of names of things that this importer imports
	 *
	 * @return array List of importables.
	 */
	public function filter_import_names( $import_names )
	{
		return array_merge( $import_names, $this->supported_importers );
	}

	/**
	 * Plugin filter that supplies the UI for the WP importer
	 *
	 * @param string $stageoutput The output stage UI
	 * @param string $import_name The name of the selected importer
	 * @param string $stage The stage of the import in progress
	 * @param string $step The step of the stage in progress
	 * @return output for this stage of the import
	 */
	public function filter_import_stage( $stageoutput, $import_name, $stage, $step )
	{
		// Only act on this filter if the import_name is one we handle...
		if( !in_array( $import_name, $this->supported_importers ) ) {
			// Must return $stageoutput as it may contain the stage HTML of another importer
			return $stageoutput;
		}

		$inputs = array();

		// Validate input from various stages...
		switch( $stage ) {
		case 1:
			if( count( $_POST ) ) {
				$valid_fields = array( 'db_name','db_host','db_user','db_pass','db_prefix', 'category_import', 'utw_import' );
				$inputs = array_intersect_key( $_POST->getArrayCopy(), array_flip( $valid_fields ) );
				if( $this->wp_connect( $inputs['db_host'], $inputs['db_name'], $inputs['db_user'], $inputs['db_pass'], $inputs['db_prefix'] ) ) {
					$stage = 2;
				}
				else {
					$inputs['warning']= _t( 'Could not connect to the WordPress database using the values supplied. Please correct them and try again.' );
				}
			}
			break;
		}

		// Based on the stage of the import we're on, do different things...
		switch( $stage ) {
		case 1:
		default:
			$output = $this->stage1( $inputs );
			break;
		case 2:
			$output = $this->stage2( $inputs );
		}

		return $output;
	}

	/**
	 * Create the UI for stage one of the WP import process
	 *
	 * @param array $inputs Inputs received via $_POST to the importer
	 * @return string The UI for the first stage of the import process
	 */
	private function stage1( $inputs )
	{
		$default_values = array(
			'db_name' => '',
			'db_host' => 'localhost',
			'db_user' => '',
			'db_pass' => '',
			'db_prefix' => 'wp_',
			'warning' => '',
			'category_import' => 1,
			'utw_import' => 0,
		 );
		$inputs = array_merge( $default_values, $inputs );
		extract( $inputs );
		if( $warning != '' ) {
			$warning = "<p class=\"warning\">{$warning}</p>";
		}
		$output = <<< WP_IMPORT_STAGE1
			<p>Habari will attempt to import from a WordPress Database.</p>
			{$warning}
			<p>Please provide the connection details for an existing WordPress database:</p>
			<table>
				<tr><td>Database Name</td><td><input type="text" name="db_name" value="{$db_name}"></td></tr>
				<tr><td>Database Host</td><td><input type="text" name="db_host" value="{$db_host}"></td></tr>
				<tr><td>Database User</td><td><input type="text" name="db_user" value="{$db_user}"></td></tr>
				<tr><td>Database Password</td><td><input type="password" name="db_pass" value="{$db_pass}"></td></tr>
				<tr><td>Table Prefix</td><td><input type="text" name="db_prefix" value="{$db_prefix}"></td></tr>
				<tr><td>Import Category as Tag</td><td><input type="checkbox" name="category_import" value="1" checked></td></tr>
			</table>
			<input type="hidden" name="stage" value="1">
			<p class="extras" style="border: solid 1px #ccc; padding: 5px;">
				Extras - additional data from WordPress plugins
				<table>
				<tr><td>Import tags from Ultimate Tag Warrior</td>
				<td><input type="checkbox" name="utw_import" value="1"></td>
				</tr>
				</table>
			</p>
			<p class="submit"><input type="submit" name="import" value="Import" /></p>

WP_IMPORT_STAGE1;
		return $output;
	}

	/**
	 * Create the UI for stage two of the WP import process
	 * This stage kicks off the ajax import.
	 *
	 * @param array $inputs Inputs received via $_POST to the importer
	 * @return string The UI for the second stage of the import process
	 */
	private function stage2( $inputs )
	{
		extract( $inputs );

		if ( ! isset( $category_import ) ) {
			$category_import = 0;
		}
		if ( ! isset( $utw_import ) ) {
			$utw_import = 0;
		}
		$ajax_url = URL::get( 'auth_ajax', array( 'context' => 'wp_import_users' ) );
		EventLog::log(sprintf(_t('Starting import from "%s"'), $db_name));
		Options::set('import_errors', array());

		$vars = Utils::addslashes( array( 'host' => $db_host, 'name' => $db_name, 'user' => $db_user, 'pass' => $db_pass, 'prefix' => $db_prefix ) );

		$output = <<< WP_IMPORT_STAGE2
		<p>Import In Progress</p>
		<div id="import_progress">Starting Import...</div>
		<script type="text/javascript">
		// A lot of ajax stuff goes here.
		$( document ).ready( function(){
			$( '#import_progress' ).load(
				"{$ajax_url}",
				{
					db_host: "{$vars['host']}",
					db_name: "{$vars['name']}",
					db_user: "{$vars['user']}",
					db_pass: "{$vars['pass']}",
					db_prefix: "{$vars['prefix']}",
					category_import: "{$category_import}",
					utw_import: "{$utw_import}",
					postindex: 0
				}
			 );
		} );
		</script>
WP_IMPORT_STAGE2;
		return $output;
	}

	/**
	 * Attempt to connect to the WordPress database
	 *
	 * @param string $db_host The hostname of the WP database
	 * @param string $db_name The name of the WP database
	 * @param string $db_user The user of the WP database
	 * @param string $db_pass The user's password for the WP database
	 * @param string $db_prefix The table prefix for the WP instance in the database
	 * @return mixed false on failure, DatabseConnection on success
	 */
	private function wp_connect( $db_host, $db_name, $db_user, $db_pass, $db_prefix )
	{
		// Connect to the database or return false
		try {
			$wpdb = DatabaseConnection::ConnectionFactory( "mysql:host={$db_host};dbname={$db_name}" );;
			$wpdb->connect( "mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass );
			return $wpdb;
		}
		catch( Exception $e ) {
			return false;
		}
	}

	/**
	 * The plugin sink for the auth_ajax_wp_import_posts hook.
	 * Responds via authenticated ajax to requests for post importing.
	 *
	 * @param AjaxHandler $handler The handler that handled the request, contains $_POST info
	 */
	public function action_auth_ajax_wp_import_posts( $handler )
	{
		$valid_fields = array( 'db_name','db_host','db_user','db_pass','db_prefix','postindex', 'category_import', 'utw_import' );
		$inputs = array_intersect_key( $_POST->getArrayCopy(), array_flip( $valid_fields ) );
		extract( $inputs );
		if ( ! isset( $inputs['category_import'] ) ) {
			$inputs['category_import']= 0;
		}
		if ( ! isset( $inputs['utw_import'] ) ) {
			$inputs['utw_import']= 0;
		}

		$wpdb = $this->wp_connect( $db_host, $db_name, $db_user, $db_pass, $db_prefix );
		if( $wpdb ) {
			if( !DB::in_transaction() ) DB::begin_transaction();

			$has_taxonomy = count($wpdb->get_column( "SHOW TABLES LIKE '{$db_prefix}term_taxonomy';" ));

			$postcount = $wpdb->get_value( "SELECT count( id ) FROM {$db_prefix}posts;" );
			$min = $postindex * IMPORT_BATCH + ( $postindex == 0 ? 0 : 1 );
			$max = min( ( $postindex + 1 ) * IMPORT_BATCH, $postcount );

			$user_map = array();
			$userinfo = DB::table( 'userinfo' );
			$user_info = DB::get_results( "SELECT user_id, value FROM {$userinfo} WHERE name= 'wp_id';" );
			foreach( $user_info as $info ) {
				$user_map[$info->value]= $info->user_id;
			}
			echo "<p>Importing posts {$min}-{$max} of {$postcount}.</p>";
			$posts = $wpdb->get_results( "
				SELECT
					post_content as content,
					ID as id,
					post_title as title,
					post_name as slug,
					post_author as user_id,
					guid as guid,
					post_date as pubdate,
					post_modified as updated,
					post_status,
					post_type
				FROM {$db_prefix}posts
				WHERE post_type != 'revision' AND post_type != 'attachment'
				ORDER BY ID DESC
				LIMIT {$min}, " . IMPORT_BATCH
				, array(), 'Post' );

			$post_map = DB::get_column( "SELECT value FROM {postinfo} WHERE name='wp_id';");
			foreach( $posts as $post ) {
				if(in_array($post->id, $post_map)) {
					continue;
				}

				if ( $has_taxonomy ) {
					// Importing from >= WP2.3
					if ( $category_import == 1 ) {
						// Import WP category and tags as tags
						$taxonomies = "({$db_prefix}term_taxonomy.taxonomy= 'category' OR {$db_prefix}term_taxonomy.taxonomy= 'post_tag')";
					}
					else {
						// Import WP tags as tags
						$taxonomies = "{$db_prefix}term_taxonomy.taxonomy= 'post_tag'";
					}
					$tags = $wpdb->get_column(
						"SELECT DISTINCT name
						FROM {$db_prefix}terms
						INNER JOIN {$db_prefix}term_taxonomy
						ON ( {$db_prefix}terms.term_id= {$db_prefix}term_taxonomy.term_id AND {$taxonomies} )
						INNER JOIN {$db_prefix}term_relationships
						ON ({$db_prefix}term_taxonomy.term_taxonomy_id= {$db_prefix}term_relationships.term_taxonomy_id)
						WHERE {$db_prefix}term_relationships.object_id= {$post->id}"
						);
				}
				else {
					// Importing from < WP2.3
					if ( $category_import == 1 ) {
						// Import WP category as tags
						$tags = $wpdb->get_column(
							"SELECT category_nicename
							FROM {$db_prefix}post2cat
							INNER JOIN {$db_prefix}categories
							ON ( {$db_prefix}categories.cat_ID= {$db_prefix}post2cat.category_id )
							WHERE post_id= {$post->id}"
						 );
					} else {
						$tags = array();
					}
				}

				// we want to include the Ultimate Tag Warrior in that list of tags
				if ( $utw_import == 1 && count( DB::get_results( "show tables like 'post2tag'" ) ) ) {
					$utw_tags = $wpdb->get_column(
					"SELECT tag
					FROM {$db_prefix}post2tag
					INNER JOIN {$db_prefix}tags
					ON ( {$db_prefix}tags.tag_ID= {$db_prefix}post2tag.tag_id )
					WHERE post_id= {$post->id}"
					 );
					// UTW substitutes underscores and hyphens for spaces, so let's do the same
					$utw_tag_formatter = create_function( '$a', 'return preg_replace( "/_|-/", " ", $a );' );

					// can this be done in just two calls instead of three? I think so.
					$tags = array_unique( array_merge( $tags, array_map( $utw_tag_formatter, $utw_tags ) ) );
				}

				$post->content = MultiByte::convert_encoding( $post->content );
				$post->title = MultiByte::convert_encoding( $post->title );
				$tags = implode( ',', $tags );
				$tags = MultiByte::convert_encoding( $tags );

				$post_array = $post->to_array();
				switch( $post_array['post_status'] ) {
				case 'publish':
					$post_array['status']= Post::status( 'published' );
					break;
				default:
					$post_array['status']= Post::status( $post_array['post_status'] );
					break;
				}
				unset( $post_array['post_status'] );

				switch( $post_array['post_type'] ) {
				case 'post':
					$post_array['content_type']= Post::type( 'entry' );
					break;
				case 'page':
					$post_array['content_type']= Post::type( 'page' );
					break;
				default:
					// We're not inserting WP's media records.  That would be silly.
					continue;
				}
				unset( $post_array['post_type'] );

				$p = new Post( $post_array );
				$p->slug = $post->slug;
				if(isset($user_map[$p->user_id])) {
					$p->user_id = $user_map[$p->user_id];
				}
				else {
					$errors = Options::get('import_errors');
					$errors[] = _t('Post author id %s was not found in WP database, assigning post "%s" (WP post id #%d) to current user.', array($p->user_id, $p->title,$post_array['id']) );
					Options::set('import_errors', $errors);
					$p->user_id = User::identify()->id;
				}

				$p->guid = $p->guid; // Looks fishy, but actually causes the guid to be set.
				$p->tags = $tags;

				$p->info->wp_id = $post_array['id'];  // Store the WP post id in the post_info table for later

				try {
					$p->insert();
				}
				catch( Exception $e ) {
					EventLog::log($e->getMessage(), 'err', null, null, print_r(array($p, $e), 1));
					Session::error( $e->getMessage() );
					$errors = Options::get('import_errors');
					$errors[] = $p->title . ' : ' . $e->getMessage();
					Options::set('import_errors', $errors);
				}
			}

			if( DB::in_transaction() ) DB::commit();

			if( $max < $postcount ) {
				$ajax_url = URL::get( 'auth_ajax', array( 'context' => 'wp_import_posts' ) );
				$postindex++;

				$vars = Utils::addslashes( array( 'host' => $db_host, 'name' => $db_name, 'user' => $db_user, 'pass' => $db_pass, 'prefix' => $db_prefix ) );

				echo <<< WP_IMPORT_AJAX1
					<script type="text/javascript">
					$( '#import_progress' ).load(
						"{$ajax_url}",
						{
							db_host: "{$vars['host']}",
							db_name: "{$vars['name']}",
							db_user: "{$vars['user']}",
							db_pass: "{$vars['pass']}",
							db_prefix: "{$vars['prefix']}",
							category_import: "{$category_import}",
							utw_import: "{$utw_import}",
							postindex: {$postindex}
						}
					 );

				</script>
WP_IMPORT_AJAX1;
			}
			else {
				$ajax_url = URL::get( 'auth_ajax', array( 'context' => 'wp_import_comments' ) );

				$vars = Utils::addslashes( array( 'host' => $db_host, 'name' => $db_name, 'user' => $db_user, 'pass' => $db_pass, 'prefix' => $db_prefix ) );

				echo <<< WP_IMPORT_AJAX2
					<script type="text/javascript">
					$( '#import_progress' ).load(
						"{$ajax_url}",
						{
							db_host: "{$vars['host']}",
							db_name: "{$vars['name']}",
							db_user: "{$vars['user']}",
							db_pass: "{$vars['pass']}",
							db_prefix: "{$vars['prefix']}",
							category_import: "{$category_import}",
							utw_import: "{$utw_import}",
							commentindex: 0
						}
					 );

				</script>
WP_IMPORT_AJAX2;

			}
		}
		else {
			EventLog::log(sprintf(_t('Failed to import from "%s"'), $db_name), 'crit');
			Session::error( $e->getMessage() );
			echo '<p>'._t( 'The database connection details have failed to connect.' ).'</p>';
		}
	}

	/**
	 * The plugin sink for the auth_ajax_wp_import_posts hook.
	 * Responds via authenticated ajax to requests for post importing.
	 *
	 * @param mixed $handler
	 * @return
	 */
	public function action_auth_ajax_wp_import_users( $handler )
	{
		$valid_fields = array( 'db_name','db_host','db_user','db_pass','db_prefix','userindex', 'category_import', 'utw_import' );
		$inputs = array_intersect_key( $_POST->getArrayCopy(), array_flip( $valid_fields ) );
		extract( $inputs );
		$wpdb = $this->wp_connect( $db_host, $db_name, $db_user, $db_pass, $db_prefix );
		if( $wpdb ) {
			if( !DB::in_transaction() ) DB::begin_transaction();
			$wp_users = $wpdb->get_results(
				"
					SELECT
						user_login as username,
						user_pass as password,
						user_email as email,
						user_url as wp_url,
						{$db_prefix}users.id as wp_id
					FROM {$db_prefix}users
					INNER JOIN {$db_prefix}posts ON {$db_prefix}posts.post_author = {$db_prefix}users.id
					GROUP BY {$db_prefix}users.id
				",
				array(),
				'User'
			);
			$usercount = 0;
			_e('<p>Importing users...</p>');

			foreach($wp_users as $user) {
				$habari_user = User::get_by_name($user->username);
				// If username exists
				if($habari_user instanceof User) {
					$habari_user->info->wp_id = $user->wp_id;
					$habari_user->info->url = $user->wp_url;
					$habari_user->update();
				}
				else {
					try {
						$user->info->wp_id = $user->wp_id;
						if($user->wp_url != '') {
							$user->info->url = $user->wp_url;
						}
						// This should probably remain commented until we implement ACL more,
						// or any imported user will be able to log in and edit stuff
						//$user->password = '{MD5}' . $user->password;
						$user->exclude_fields(array('wp_id', 'wp_url'));
						$user->insert();
						$usercount++;
					}
					catch( Exception $e ) {
						EventLog::log($e->getMessage(), 'err', null, null, print_r(array($user, $e), 1));
						Session::error( $e->getMessage() );
						$errors = Options::get('import_errors');
						$errors[] = $user->username . ' : ' . $e->getMessage();
						Options::set('import_errors', $errors);
					}
				}
			}
			if( DB::in_transaction()) DB::commit();

			$ajax_url = URL::get( 'auth_ajax', array( 'context' => 'wp_import_posts' ) );

			$vars = Utils::addslashes( array( 'host' => $db_host, 'name' => $db_name, 'user' => $db_user, 'pass' => $db_pass, 'prefix' => $db_prefix ) );

			echo <<< WP_IMPORT_USERS1
			<script type="text/javascript">
			// A lot of ajax stuff goes here.
			$( document ).ready( function(){
				$( '#import_progress' ).load(
					"{$ajax_url}",
					{
						db_host: "{$vars['host']}",
						db_name: "{$vars['name']}",
						db_user: "{$vars['user']}",
						db_pass: "{$vars['pass']}",
						db_prefix: "{$vars['prefix']}",
						category_import: "{$category_import}",
						utw_import: "{$utw_import}",
						postindex: 0
					}
				 );
			} );
			</script>
WP_IMPORT_USERS1;
		}
		else {
			EventLog::log(sprintf(_t('Failed to import from "%s"'), $db_name), 'crit');
			Session::error( $e->getMessage() );
			echo '<p>'._t( 'Failed to connect using the given database connection details.' ).'</p>';
		}
	}

	/**
	 * The plugin sink for the auth_ajax_wp_import_comments hook.
	 * Responds via authenticated ajax to requests for comment importing.
	 *
	 * @param AjaxHandler $handler The handler that handled the request, contains $_POST info
	 */
	public function action_auth_ajax_wp_import_comments( $handler )
	{
		$valid_fields = array( 'db_name','db_host','db_user','db_pass','db_prefix','commentindex', 'category_import', 'utw_import' );
		$inputs = array_intersect_key( $_POST->getArrayCopy(), array_flip( $valid_fields ) );
		extract( $inputs );

		$wpdb = $this->wp_connect( $db_host, $db_name, $db_user, $db_pass, $db_prefix );
		if( $wpdb ) {
			if( !DB::in_transaction() ) DB::begin_transaction();

			$commentcount = $wpdb->get_value( "SELECT count( comment_ID ) FROM {$db_prefix}comments;" );
			$min = $commentindex * IMPORT_BATCH + 1;
			$max = min( ( $commentindex + 1 ) * IMPORT_BATCH, $commentcount );

			echo "<p>Importing comments {$min}-{$max} of {$commentcount}.</p>";

			$postinfo = DB::table( 'postinfo' );
			$post_info = DB::get_results( "SELECT post_id, value FROM {$postinfo} WHERE name= 'wp_id';" );
			foreach( $post_info as $info ) {
				$post_map[$info->value]= $info->post_id;
			}

			$comments = $wpdb->get_results( "
				SELECT
				comment_content as content,
				comment_author as name,
				comment_author_email as email,
				comment_author_url as url,
				INET_ATON( comment_author_IP ) as ip,
			 	comment_approved as status,
				comment_date as date,
				comment_type as type,
				ID as wp_post_id
				FROM {$db_prefix}comments
				INNER JOIN
				{$db_prefix}posts on ( {$db_prefix}posts.ID= {$db_prefix}comments.comment_post_ID )
				LIMIT {$min}, " . IMPORT_BATCH
				, array(), 'Comment' );

			foreach( $comments as $comment ) {
				switch( $comment->type ) {
					case 'pingback': $comment->type = Comment::PINGBACK; break;
					case 'trackback': $comment->type = Comment::TRACKBACK; break;
					default: $comment->type = Comment::COMMENT;
				}

				$comment->content = MultiByte::convert_encoding( $comment->content );
				$comment->name = MultiByte::convert_encoding( $comment->name );

				$carray = $comment->to_array();
				if ( $carray['ip'] == '' ) {
					$carray['ip']= 0;
				}
				switch( $carray['status'] ) {
				case '0':
					$carray['status']= Comment::STATUS_UNAPPROVED;
					break;
				case '1':
					$carray['status']= Comment::STATUS_APPROVED;
					break;
				case 'spam':
					$carray['status']= Comment::STATUS_SPAM;
					break;
				}

				if( isset( $post_map[$carray['wp_post_id']] ) ) {
					$carray['post_id']= $post_map[$carray['wp_post_id']];
					unset( $carray['wp_post_id'] );

					$c = new Comment( $carray );
					//Utils::debug( $c );
					try{
						$c->insert();
					}
					catch( Exception $e ) {
						EventLog::log($e->getMessage(), 'err', null, null, print_r(array($c, $e), 1));
						Session::error( $e->getMessage() );
						$errors = Options::get('import_errors');
						$errors[] = $e->getMessage();
						Options::set('import_errors', $errors);
					}
				}
			}
			if( DB::in_transaction() ) DB::commit();

			if( $max < $commentcount ) {
				$ajax_url = URL::get( 'auth_ajax', array( 'context' => 'wp_import_comments' ) );
				$commentindex++;

				$vars = Utils::addslashes( array( 'host' => $db_host, 'name' => $db_name, 'user' => $db_user, 'pass' => $db_pass, 'prefix' => $db_prefix ) );

				echo <<< WP_IMPORT_AJAX1
					<script type="text/javascript">
					$( '#import_progress' ).load(
						"{$ajax_url}",
						{
							db_host: "{$vars['host']}",
							db_name: "{$vars['name']}",
							db_user: "{$vars['user']}",
							db_pass: "{$vars['pass']}",
							db_prefix: "{$vars['prefix']}",
							category_import: "{$category_import}",
							utw_import: "{$utw_import}",
							commentindex: {$commentindex}
						}
					 );

				</script>
WP_IMPORT_AJAX1;
			}
			else {
				EventLog::log('Import complete from "'. $db_name .'"');
				echo '<p>' . _t( 'Import is complete.' ) . '</p>';

				$errors = Options::get('import_errors');
				if(count($errors) > 0 ) {
					echo '<p>' . _t( 'There were errors during import:' ) . '</p>';

					echo '<ul>';
					foreach($errors as $error) {
						echo '<li>' . $error . '</li>';
					}
					echo '</ul>';
				}

			}
		}
		else {
			EventLog::log(sprintf(_t('Failed to import from "%s"'), $db_name), 'crit');
			Session::error( $e->getMessage() );
			echo '<p>'._t( 'Failed to connect using the given database connection details.' ).'</p>';
		}
	}

}

?>
