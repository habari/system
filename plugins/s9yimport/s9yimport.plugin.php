<?php
define( 'S9Y_IMPORT_BATCH', 100 );
define( 'S9Y_CONFIG_FILENAME', 'serendipity_config.inc.php');

/**
 * Serendipity Importer - Imports data from Serendipity into Habari
 *
 * @note	Currently, this imports data from s9y versions 0.9 and up.
 *
 * @package Habari
 */
class S9YImport extends Plugin implements Importer
{
	private $supported_importers = array();
	/** A string that is displayed on trigger of a warning */
	private $warning = null;
	/** HTML ID of the element to run AJAX import actions -- CURRENTLY NOT USED -- */
	private $ajax_html_id = 'import_progress';
	/** Connection to the s9y database to use during import */
	private $s9ydb = null;
	/** The table prefix for the s9y tables (only used in the private import_xxx() functions */
	private $s9y_db_prefix = 's9y_';
	/** Cache for imported categories uses as a map from s9y to habari during import. */
	private $imported_categories = array();

	/**
	 * Cache for imported category names
	 */
	protected $imported_category_names = array();

	/**
	 * List of already-rewritten categories (prevents duplicate rewrite rules)
	 */
	protected $rewritten_categories = array();

	/**
	 * Initialize plugin.
	 * Set the supported importers.
	 **/
	public function action_init()
	{
		$this->supported_importers = array( _t( 'Serendipity Database' ) );
	}

	/**
	* Return plugin metadata for this plugin
	*
	* @return array Plugin metadata
	*/
	public function info()
	{
		return array(
			'name' => 'Serendipity Importer',
			'version' => '1.0',
			'url' => 'http://habariproject.org/',
			'author' =>	'Habari Community',
			'authorurl' => 'http://habariproject.org/',
			'license' => 'Apache License 2.0',
			'description' => 'Import Serendipity 0.9+ database.',
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
		if( !in_array( $import_name, $this->supported_importers ) )
			// Must return $stageoutput as it may contain the stage HTML of another importer
			return $stageoutput;

		if ( isset( $_POST ) )
			$stage = (int) $stage + 1; /* Note that we do the error checking in the stageX() methods, not here... */

		$stage_method = 'stage' . $stage;
		if ( method_exists( $this, $stage_method ) )
			return $this->$stage_method();

		return FALSE;
	}

	/**
	 * Create the UI for stage one of the WP import process
	 *
	 * Stage 1 is responsible for gathering the required input
	 * from the importing user that will be used in the import
	 * process as well as displaying errors for invalid or missing
	 * required information.
	 *
	 * @return string The UI for the first stage of the import process
	 */
	private function stage1()
	{
		$valid_fields = array(
			'db_name'
			, 'db_host'
			, 'db_port'
			, 'db_user'
			, 'db_pass'
			, 'db_prefix'
			, 's9y_version'
			, 's9y_root_web'
			, 's9y_input_version'
			, 'category_import'
			, 'comments_ignore_unapproved'
			, 'rewrites_import'
		);
		$inputs = $this->get_valid_inputs( $valid_fields );

		$default_values = array(
			'db_name' => ''
			, 'db_host' => 'localhost'
			, 'db_port' => null
			, 'db_user' => ''
			, 'db_pass' => ''
			, 'db_prefix' => 's9y_'
			, 'warning' => ''
			, 'category_import' => 1
			, 'comments_ignore_unapproved' => 1
			, 'rewrites_import' => 1
			, 's9y_root_web' => ''
			, 's9y_input_version' =>''
		 ) ;
		$inputs = array_merge( $default_values, $inputs );

		extract( $inputs );

		$warning = '';
		if( ! empty( $this->warning ) )
			$warning = $this->get_warning_html( $this->warning );

		$output =<<<WP_IMPORT_STAGE1
			<h3>Habari will attempt to import from a Serendipity Database.</h3>
			{$warning}
			<p>
			Please specify the version of Serendipity to be imported <strong>OR</strong> specify the location of the
			root web directory where we can find the Serendipity configuration file to gather version information:
			</p>
			<table>
				<tr><td>Version:</td><td><input type="text" name="s9y_input_version" value="{$s9y_input_version}"></td></tr>
				<tr><td>&nbsp;<strong>OR</strong> Web directory root where s9y is installed:</td><td><input type="text" name="s9y_root_web" value="{$s9y_root_web}"></td></tr>
			</table>

			<p>Please provide the connection details for an existing Serendipity database:</p>
			<table>
				<tr><td>Database Name</td><td><input type="text" name="db_name" value="{$db_name}"></td></tr>
				<tr><td>Database Host</td><td><input type="text" name="db_host" value="{$db_host}"></td></tr>
				<tr><td>Database Port</td><td><input type="text" name="db_port" value="{$db_port}">(optional)</td></tr>
				<tr><td>Database User</td><td><input type="text" name="db_user" value="{$db_user}"></td></tr>
				<tr><td>Database Password</td><td><input type="password" name="db_pass" value="{$db_pass}"></td></tr>
				<tr><td>Table Prefix</td><td><input type="text" name="db_prefix" value="{$db_prefix}"></td></tr>
			</table>

			<p>
			Please specify any import options you would like:
			</p>
			<table>
				<tr><td>Import Serendipity Categories as Tags?</td><td><input type="checkbox" name="category_import" value="1" checked="true"></td></tr>
				<tr><td>Ignore Unapproved Comments?</td><td><input type="checkbox" name="comments_ignore_unapproved" value="1" checked="true"></td></tr>
				<tr><td>Port rewrites?</td><td><input type="checkbox" name="rewrites_import" value="1" checked="true"></td></tr>
			</table>
			<input type="hidden" name="stage" value="1">
			<p class="submit"><input type="submit" name="import" value="Proceed with Import" /></p>
WP_IMPORT_STAGE1;

		return $output;
	}

	/**
	 * Create the UI for stage two of the WP import process
	 *
	 * This stage verifies the initial configuration values and then
	 * proceeds to gather information about the potentially imported 
	 * data and display some confirmation information.  If missing or
	 * invalid information was given, this method returns the output
	 * from stage1()
	 *
	 * @return string The UI for the second stage of the import process
	 */
	private function stage2()
	{
		$valid_fields = array(
			'db_name'
			, 'db_host'
			, 'db_port'
			, 'db_user'
			, 'db_pass'
			, 'db_prefix'
			, 's9y_version'
			, 's9y_root_web'
			, 's9y_input_version'
			, 'category_import'
			, 'comments_ignore_unapproved'
			, 'rewrites_import'
		);
		$inputs = $this->get_valid_inputs( $valid_fields );

		extract( $inputs );

		/* Verify required and expected values from input */
		if ( isset( $comments_ignore_unapproved) )
			$comments_ignore_unapproved = 1;
		if ( isset( $category_import ) )
			$category_import = 1;
		if ( isset( $rewrites_import ) )
			$rewrites_import = 1;

		if ( empty( $s9y_input_version )
			&& empty( $s9y_root_web ) ) {
				/* 
				 * We need either the version or the config file 
				 * location to determine version of DB 
				 */
			$this->warning = 'Please enter either a location to find the s9y configuration file (root web directory for s9y) OR the version of s9y to import';
			return $this->stage1();
		}

		if ( empty( $db_host ) ) {
			$this->warning = 'Please enter a value for the database host.';
			return $this->stage1();
		}

		if ( empty( $db_name ) ) {
			$this->warning = 'Please enter a name for the database to import.';
			return $this->stage1();
		}

		if ( empty( $db_user ) ) {
			$this->warning = 'Please enter a database username.';
			return $this->stage1();
		}

		if ( FALSE == ($s9ydb = $this->s9y_connect( $db_host, $db_name, $db_user, $db_pass, $db_prefix, $db_port ) ) ) {
			$this->warning = 'A connection to the specified database could not be created.  Please check the values you provided.';
			return $this->stage1();
		}

		/*
		 * OK, now calculate some information about the imported data that
		 * we can show to the user in the confirmation screen
		 */
		$num_imported_posts = $s9ydb->get_value( "SELECT COUNT(*) FROM `{$db_prefix}entries`" );
		$comment_where = '';
		if ( $comments_ignore_unapproved == 1 )
			$comment_where = "WHERE status = 'Approved' ";
		$num_imported_comments = $s9ydb->get_value( "SELECT COUNT(*) FROM `{$db_prefix}comments`" . $comment_where);

		/* 
		 * Users are important during import.  We want to show the importer that
		 * we have identified X number of posting users (authors) and give the importer
		 * the ability to choose which authors they wish to import.  During this
		 * step, let's try to map an incoming (imported) author to an existing Habari
		 * user...
		 */
		$user_sql =<<<ENDOFSQL
SELECT e.authorid, e.author, a.realname, a.username, a.email,COUNT(*) as num_posts 
FROM `{$db_prefix}entries` e 
INNER JOIN `{$db_prefix}authors` a 
ON e.authorid = a.authorid 
GROUP BY e.authorid, e.author
ENDOFSQL;

		$imported_users = $s9ydb->get_results( $user_sql );
		$num_imported_users = count( $imported_users );
		
		/* Grab the categories from s9y to use as tags in Habari */
		$num_imported_tags = 0;
		if ( $category_import == 1 )
			$num_imported_tags = $s9ydb->get_value( "SELECT COUNT(*) FROM `{$db_prefix}category`" );

		$output =<<<WP_IMPORT_STAGE2
			<h3>To be imported</h3>
			<p>
			We have identified information that will be imported into Habari.  Please review
			the information below and continue with the import.
			</p>
			<div><strong>Found authors to be imported:</strong>&nbsp;{$num_imported_users}
WP_IMPORT_STAGE2;
		if ( $num_imported_users > 0 ) {
			$output.=<<<WP_IMPORT_STAGE2
				<div style="margin-left: 20px; padding 10px;">
				<p>Check which users (and their posts) you wish to import</p>
				<table>
				<tr><th>Import?</th><th>Author Name</th><th>Email</th><th>Num Posts</th><th>Match in Habari?</th></tr>
WP_IMPORT_STAGE2;
			foreach ($imported_users as $user) {
				$output.= "<tr><td><input type='checkbox' name='import_user[{$user->authorid}]' value='1' checked='true' /></td>" .
					"<td>{$user->realname}</td><td>{$user->email}</td><td>{$user->num_posts}</td><td>&nbsp;";

				$user_table_name = DB::table('users');
				$match_sql =<<<ENDOFSQL
SELECT id, username, email
FROM {$user_table_name}
WHERE email = ? OR username = ?
ENDOFSQL;
				$match_params = array( $user->email, $user->username );
				$habari_matched_users = DB::get_results( $match_sql, $match_params, 'User' );
				if ( count( $habari_matched_users ) > 0 ) {
					$output.= "<strong>Match found for habari user:</strong>&nbsp;";
					$matched_user = $habari_matched_users[0]; /* Just take the first match... */
					$output.= $matched_user->username . "<input type=\"hidden\" name=\"merge_user_matched[{$user->authorid}]\" value=\"{$matched_user->id}\" />";
				}
				else {
					/* No matches.  Allow importer to select a merged user account */
					$all_habari_users = Users::get_all();
					if ( isset( $all_habari_user_select ) )
						$output.= $all_habari_user_select;
					else {
						$all_habari_user_select = "<select name='merge_user[{$user->authorid}]'>";
						$all_habari_user_select.= "<option value='__new_user' selected='true'>Create a new user</option>";
						foreach ( $all_habari_users as $habari_user )
							$all_habari_user_select.= "<option value='{$habari_user->id}'>Merge with {$habari_user->username}</option>";
						$output.= $all_habari_user_select;
					}
				}
				$output.= "</td></tr>";
			}
			$output.= "</table></div>";
		} /* End num_imported_users > 0 */
		else {
			/* No authors found to import.  Display nothing to import and stop process */
			$output.= "</div>";
			return $output;
		}
		$output.=<<<WP_IMPORT_STAGE2
			</div>
			<div><strong>Found Blog Posts to be imported:</strong>&nbsp;{$num_imported_posts}</div>
			<div><strong>Comments to be imported:</strong>&nbsp;{$num_imported_comments}</div>
WP_IMPORT_STAGE2;
		if ( $category_import == 1 ) {
			$output.=<<<WP_IMPORT_STAGE2
			<div><strong>Tags to be imported:</strong>&nbsp;{$num_imported_tags}</div>
WP_IMPORT_STAGE2;
		}
		if ( $rewrites_import == 1 ) {
			$output.=<<<WP_IMPORT_STAGE2
			<div><strong>Porting Rewrites.</strong></div>
WP_IMPORT_STAGE2;
		}
		foreach ( $inputs as $key=>$value ) 
			$output.= "<input type='hidden' name='{$key}' value='" . htmlentities($value) . "' />";
		$output.=<<<WP_IMPORT_STAGE2
			<input type="hidden" name="stage" id="stage" value="2">
			<p class="submit">
			<input type="submit" name="import" value="Continue Import >>" /></p>
WP_IMPORT_STAGE2;
		return $output;
	}

	/**
	 * Create the UI for stage two of the WP import process
	 *
	 * This stage kicks off the actual import process.
	 *
	 * @return string The UI for the second stage of the import process
	 */
	private function stage3()
	{
		$valid_fields = array(
			'db_name'
			, 'db_host'
			, 'db_port'
			, 'db_user'
			, 'db_pass'
			, 'db_prefix'
			, 's9y_version'
			, 's9y_root_web'
			, 's9y_input_version'
			, 'category_import'
			, 'comments_ignore_unapproved'
			, 'rewrites_import'
			, 'merge_user'
			, 'merge_user_matched'
			, 'import_user'
		);
		$inputs = $this->get_valid_inputs( $valid_fields );

		extract( $inputs );

		/* 
		 * Cache some local private variables for use in 
		 * the import_xxx() private functions 
		 */
		$this->comments_ignore_unapproved = $comments_ignore_unapproved;
		$this->category_import = $category_import;
		$this->s9y_db_prefix = $db_prefix;
		$this->port_rewrites = $rewrites_import;
		
		if ( $rewrites_import ) {


			// atom feed link:
			$rew_url = URL::get( 'atom_feed', array( 'index' => 1 ), true, false, false );
			$rewrite = new RewriteRule( array(
				'name' => 'from_s9yimporter_atom_feed', 
				'parse_regex' => '%^feeds/atom(?P<r>.*)$%i', 
				'build_str' => $rew_url . '(/{$p})', 
				'handler' => 'actionHandler',
				'action' => 'redirect', 
				'priority' => 1,
				'is_active' => 1,
				'rule_class' => RewriteRule::RULE_CUSTOM,
				'description' => 'redirects s9y atom feed to habari feed',
			) );
			$rewrite->insert();

			// rss feed link:
			$rew_url = Plugins::is_loaded( 'RSS 2.0' )
							? URL::get( 'rss_feed', array( 'index' => 1 ), true, false, false )
							: URL::get( 'atom_feed', array( 'index' => 1 ), true, false, false );
			$rewrite = new RewriteRule( array(
				'name' => 'from_s9yimporter_rss_feed', 
				'parse_regex' => '%^feeds/index.rss(?P<r>.*)$%i', 
				'build_str' => $rew_url . '(/{$p})', 
				'handler' => 'actionHandler',
				'action' => 'redirect', 
				'priority' => 1,
				'is_active' => 1,
				'rule_class' => RewriteRule::RULE_CUSTOM,
				'description' => 'redirects s9y rss feed to habari feed',
			) );
			$rewrite->insert();
			
			// comments feed link:
			$rew_url = Plugins::is_loaded( 'RSS 2.0' )
							? URL::get( 'rss_feed_comments', array( ), true, false, false )
							: URL::get( 'atom_feed_comments', array( ), true, false, false );
			$rewrite = new RewriteRule( array(
				'name' => 'from_s9yimporter_comments_feed', 
				'parse_regex' => '%^feeds/comments.rss(?P<r>.*)$%i', 
				'build_str' => $rew_url . '(/{$p})', 
				'handler' => 'actionHandler',
				'action' => 'redirect', 
				'priority' => 1,
				'is_active' => 1,
				'rule_class' => RewriteRule::RULE_CUSTOM,
				'description' => 'redirects s9y rss feed to habari feed',
			) );
			$rewrite->insert();
		}

		if ( FALSE !== ( $this->s9ydb = $this->s9y_connect( $db_host, $db_name, $db_user, $db_pass, $db_prefix, $db_port ) ) ) {
			/*
			 * First step is to go through our import_user and
			 * merge_user arrays and see if we need to merge the
			 * incoming authoring user with an existing user in 
			 * Habari, ignore the user, or create a new Habari user.
			 *
			 * $import_user= array( [imported_user_id] => [1 | null], [next_imported_user_id] => [1 | null], ...)
			 * $merge_user= array( [imported_user_id] => [habari_user_id | "__new_user"], ...)
			 */
			$users = array();
			foreach ( $import_user as $import_user_id=>$import_this ) {
				/* Is this s9y user selected for import? */ 
				if ( $import_this != 1 ) 
					continue;
				
				$users[$import_user_id]= array('imported_user_id'=>$import_user_id);

				/* Was there a direct match for this imported user? */
				if ( in_array( $import_user_id, array_keys( $merge_user_matched ) ) )
					$users[$import_user_id]['habari_user_id']= $merge_user_matched[$import_user_id];
				
				/* Is this s9y user manually selected to merge with a habari user? */ 
				if ( isset($merge_user) && in_array( $import_user_id, array_keys( $merge_user ) ) )
					if ( $merge_user[$import_user_id] != '__new_user')
						$users[$import_user_id]['habari_user_id']= $merge_user[$import_user_id];
			}
			
			echo "Starting import transaction.<br />";
			$this->s9ydb->begin_transaction();

			if ( $category_import ) {
				/*
				 * If we are importing the categories as taxonomy, 
				 * let's go ahead and import the base category tags
				 * now, and during the post import, we'll attach
				 * the category tag to the relevant posts.
				 *
				 * mysql> desc s9y_category;
				 * +----------------------+--------------+------+-----+---------+----------------+
				 * | Field                | Type         | Null | Key | Default | Extra          |
				 * +----------------------+--------------+------+-----+---------+----------------+
				 * | categoryid           | int(11)      |      | PRI | NULL    | auto_increment |
				 * | category_name        | varchar(255) | YES  |     | NULL    |                |
				 * | category_icon        | varchar(255) | YES  |     | NULL    |                |
				 * | category_description | text         | YES  |     | NULL    |                |
				 * | authorid             | int(11)      | YES  | MUL | NULL    |                |
				 * | category_left        | int(11)      | YES  | MUL | 0       |                |
				 * | category_right       | int(11)      | YES  |     | 0       |                |
				 * | parentid             | int(11)      |      | MUL | 0       |                |
				 * +----------------------+--------------+------+-----+---------+----------------+
				 */
				$sql =<<<ENDOFSQL
SELECT categoryid, category_name
FROM `{$db_prefix}category
ENDOFSQL;
				if ( FALSE !== ( $imported_categories = $this->s9ydb->get_results( $sql, array(), 'QueryRecord' ) ) ) {
					$num_categories_imported = 0;
					foreach ( $imported_categories as $imported_category ) {
						if ( $tag_check = Tags::get_one( $imported_category->category_name ) ) {
							// tag already exists
							$this->imported_categories[$imported_category->categoryid]= $tag_check->id;
							$this->imported_category_names[$imported_category->categoryid] = $imported_category->category_name;
							++$num_categories_imported;
							continue;
						}
						if ( $new_tag = Tag::create( array( 'tag_text' => $imported_category->category_name ) ) ) {
							$this->imported_categories[$imported_category->categoryid]= $new_tag->id;
							$this->imported_category_names[$imported_category->categoryid] = $imported_category->category_name;
							++$num_categories_imported;
						}
						else {
							$this->s9ydb->rollback();
							return FALSE;
						}
					}
					printf("%d categories imported as tags...<br />", $num_categories_imported);
				}
			}

			/*
			 * Now that we have an array of the users to import, 
			 * let's grab some information about those users and 
			 * call the import_user() method for each one.
			 * 
			 * mysql> desc s9y_authors;
			 * +-----------------+-----------------+------+-----+---------+----------------+
			 * | Field           | Type            | Null | Key | Default | Extra          |
			 * +-----------------+-----------------+------+-----+---------+----------------+
			 * | realname        | varchar(255)    | NO   |     |         |                | 
			 * | username        | varchar(20)     | YES  |     | NULL    |                | 
			 * | password        | varchar(32)     | YES  |     | NULL    |                | 
			 * | authorid        | int(11)         | NO   | PRI | NULL    | auto_increment | 
			 * | mail_comments   | int(1)          | YES  |     | 1       |                | 
			 * | mail_trackbacks | int(1)          | YES  |     | 1       |                | 
			 * | email           | varchar(128)    | NO   |     |         |                | 
			 * | userlevel       | int(4) unsigned | NO   |     | 0       |                | 
			 * | right_publish   | int(1)          | YES  |     | 1       |                | 
			 * +-----------------+-----------------+------+-----+---------+----------------+
			 */
			$sql =<<<ENDOFSQL
SELECT a.authorid, a.realname, a.username, a.email
FROM `{$db_prefix}authors` a 
ENDOFSQL;
			$sql.= " WHERE a.authorid IN (" . implode(',', array_keys($users)) . ")";
			$import_users = $this->s9ydb->get_results( $sql, array(), 'QueryRecord' 	);
			$result = TRUE;

			foreach ($import_users as $import_user)
				$result&= $this->import_user( $import_user->authorid
					, $import_user
					, ( isset ( $users[$import_user->authorid]['habari_user_id'] )
							? (int) $users[$import_user->authorid]['habari_user_id']
							: NULL
						)
					);
			if ( $result ) {
				
				echo "Committing import transaction.<br />";
				$this->s9ydb->commit();
				return "import finished.";
				/* Display success */
			}
			else {
				echo "Rolling back failed import transaction.<br />";
				$this->s9ydb->rollback();
				/* Display failure -- but how..? */
				return FALSE;
			}
		}
	}

	/**
	 * Imports a single user from the s9y database into the
	 * habari database.
	 *
	 * Note that this function either creates a new habari
	 * user account or uses an existing habari account (merges).
	 *
	 * Also note that this function is the start of the import
	 * process for a user and will call import_post() for each
	 * of the user's posts, which in turn calls import_comment()
	 * for each of that post's comments.
	 *
	 * @param		import_user_id		ID in s9y database for this user
	 * @param		habari_user_id		ID of habari user to merge or 
	 * 														NULL for new account
	 * @param		import_user_info	QueryRecord of imported user information
	 * @return	TRUE or FALSE if import of user succeeded
	 */
	private function import_user( $import_user_id, $import_user_info = array(), $habari_user_id = NULL ) 
	{

		/*
		 * We either grab the user to merge with or create
		 * a new one, depending on if $habari_user_id is null
		 * or not...
		 */
		if ( is_null( $habari_user_id ) ) {
			/* New habari user account */
			$habari_user = new User();
			$habari_user->email = $import_user_info->email;
			$habari_user->username = $import_user_info->username;
			$habari_user->info->s9y_id = $import_user_info->authorid;
			$habari_user->info->s9y_realname = $import_user_info->realname;
		}
		else {
			$habari_user = User::get_by_id($habari_user_id);
			$habari_user->info->s9y_id = $import_user_info->authorid;
			$habari_user->info->s9y_realname = $import_user_info->realname;
		}

		try {
			if ( is_null( $habari_user_id ) )
				$habari_user->insert();
			else {
				echo "Merging s9y user \"<b>{$import_user_info->username}</b>\" into Habari user with email: " . $habari_user->email;
				if ($habari_user->update())
					echo "...merge " . $this->success_html() . "<br />";
				else
					echo "...merge " . $this->fail_html() . "<br />";
			}
		}
		catch( Exception $e ) { /** @TODO: This should be a specific exception, not the general one... */
			EventLog::log( $e->getMessage(), 'err', null, null, print_r( array( $habari_user, $e ), 1 ) );
			Session::error( $e->getMessage() );
			return FALSE;
		}

		/* 
		 * If we are here, the user has been inserted/updated, and so 
		 * we proceed with adding the imported user's posts, attaching
		 * them to the user just created or updated.

		 * mysql> desc s9y_entries;
		 * +-------------------+----------------------+------+-----+---------+----------------+
		 * | Field             | Type                 | Null | Key | Default | Extra          |
		 * +-------------------+----------------------+------+-----+---------+----------------+
		 * | id                | int(11)              |      | PRI | NULL    | auto_increment |
		 * | title             | varchar(200)         | YES  | MUL | NULL    |                |
		 * | timestamp         | int(10) unsigned     | YES  | MUL | NULL    |                |
		 * | body              | text                 | YES  |     | NULL    |                |
		 * | comments          | int(4) unsigned      | YES  |     | 0       |                |
		 * | trackbacks        | int(4) unsigned      | YES  |     | 0       |                |
		 * | extended          | text                 | YES  |     | NULL    |                |
		 * | exflag            | int(1)               | YES  |     | NULL    |                |
		 * | author            | varchar(20)          | YES  |     | NULL    |                |
		 * | authorid          | int(11)              | YES  | MUL | NULL    |                |
		 * | isdraft           | enum('true','false') |      | MUL | true    |                |
		 * | allow_comments    | enum('true','false') |      |     | true    |                |
		 * | last_modified     | int(10) unsigned     | YES  | MUL | NULL    |                |
		 * | moderate_comments | enum('true','false') |      |     | true    |                |
		 * +-------------------+----------------------+------+-----+---------+----------------+
		 *
		 */
		$sql =<<<ENDOFSQL
SELECT
  e.id
,	e.extended
, e.body
, e.title
, e.`timestamp`
, e.last_modified
, e.isdraft
FROM {$this->s9y_db_prefix}entries e
WHERE e.authorid = ?
ENDOFSQL;
		$posts = $this->s9ydb->get_results( $sql, array($import_user_id), 'QueryRecord' );
		if ( count( $posts ) > 0 ) {
			$result = TRUE;
			echo "Starting import of <b>" . count( $posts ) . "</b> posts...<br/ >";
			foreach ( $posts as $post )
				$result&= $this->import_post( $post, $habari_user->id );
			if ( $result )
				echo $this->success_html() . "<br />";
			else
				echo $this->fail_html() . "<br />";
			return $result;
		}
		else {
			echo "No posts to import for {$import_user_info->username}.<br />";
			return TRUE;
		}
	}

	/**
	 * Imports a single post from the s9y database into the 
	 * habari database.
	 *
	 * Note that this function calls import_comment() for each
	 * comment attached to the imported post
	 *
	 * @param		post_info					QueryRecord of imported post information
	 * @param		habari_user_id		The habari user ID of the post's author
	 * @return	TRUE or FALSE if import of post succeeded
	 */
	private function import_post( $post_info = array(), $habari_user_id ) 
	{

		/* 
		 * Import the post itself 
		 */
		$post = new Post();
		$post->user_id = $habari_user_id;
		$post->guid = $post->guid; /* @TODO: This works to create a GUID, but man, it's weird. */
		$post->info->s9y_id = $post_info->id;
		$post->title = $this->transcode( $post_info->title );
		$content = ( empty( $post_info->extended ) ? $post_info->body : $post_info->body . $post_info->extended );
		$post->content = $this->transcode( $content );
		$post->status = ( $post_info->isdraft == "true" ? Post::status( 'draft' ) : Post::status( 'published' ) );
		$post->content_type = Post::type( 'entry' );
		$post->updated = date('Y-m-d H:i:s', $post_info->last_modified);
		$post->pubdate = ( $post_info->isdraft == "false" ? date( 'Y-m-d H:i:s', $post_info->timestamp ) : NULL );
		if ( $this->category_import && isset ( $categories ) && $categories instanceof QueryRecord )
			$post->tags = $categories->to_array();

		if ( $post->insert() ) {
			
			if ( $this->port_rewrites ) {
				$rewrite = new RewriteRule( array(
					'name' => 'from_s9yimporter_' . $post_info->id, 
					'parse_regex' => '%^archives/' . $post_info->id . '-(?P<r>.*)$%i', 
					'build_str' => $post->slug . '(/{$p})', 
					'handler' => 'actionHandler',
					'action' => 'redirect', 
					'priority' => 1,
					'is_active' => 1,
					'rule_class' => RewriteRule::RULE_CUSTOM,
					'description' => 'redirects /archives/' . $post_info->id .' to /' . $post->slug,
				) );
				$rewrite->insert();
			}
			
			/*
			 * If we are going to import taxonomy, , then first check to see if 
			 * this post has any categories attached to it, and import the relationship
			 * using the cached habari->s9y tag map. 
			 *
			 * mysql> desc s9y_entrycat;
			 * +------------+---------+------+-----+---------+-------+
			 * | Field      | Type    | Null | Key | Default | Extra |
			 * +------------+---------+------+-----+---------+-------+
			 * | entryid    | int(11) | NO   | PRI | 0       |       | 
			 * | categoryid | int(11) | NO   | PRI | 0       |       | 
			 * +------------+---------+------+-----+---------+-------+
			 */
			$result = TRUE;
			if ( $this->category_import ) {
				$sql =<<<ENDOFSQL
SELECT c.categoryid
FROM {$this->s9y_db_prefix}category c 
INNER JOIN {$this->s9y_db_prefix}entrycat ec
ON c.categoryid = ec.categoryid
AND ec.entryid = ?
ENDOFSQL;
				if ( FALSE !== ( $categories = $this->s9ydb->get_results( $sql, array( $post_info->id ), 'QueryRecord' ) ) ) {
					foreach ( $categories as $category ) {
						$result&= $this->import_post_category( $post->id, $this->imported_categories[$category->categoryid] );
						if ( $this->port_rewrites && !isset( $this->rewritten_categories[ $category->categoryid ] ) ) {
							// rss feed link:
							$this->rewritten_categories[ $category->categoryid ] = 1;
							$rew_url = URL::get( 'atom_feed_tag', array( 'tag' => $this->imported_category_names[ $category->categoryid ] ), true, false, false );
							$rewrite = new RewriteRule( array(
								'name' => 'from_s9yimporter_category_feed', 
								'parse_regex' => '%^feeds/categories/' . $category->categoryid . '-(?P<r>.*)$%i', 
								'build_str' => $rew_url . '(/{$p})', 
								'handler' => 'actionHandler',
								'action' => 'redirect', 
								'priority' => 1,
								'is_active' => 1,
								'rule_class' => RewriteRule::RULE_CUSTOM,
								'description' => 'redirects s9y category feed to habari feed',
							) );
							$rewrite->insert();
						}
						
					}
				}
			}

			/* 
			 * Grab the comments and insert `em 
			 *  
			 * mysql> desc s9y_comments;
			 * +------------+----------------------+------+-----+---------+----------------+
			 * | Field      | Type                 | Null | Key | Default | Extra          |
			 * +------------+----------------------+------+-----+---------+----------------+
			 * | id         | int(11)              | NO   | PRI | NULL    | auto_increment | 
			 * | entry_id   | int(10) unsigned     | NO   | MUL | 0       |                | 
			 * | parent_id  | int(10) unsigned     | NO   | MUL | 0       |                | 
			 * | timestamp  | int(10) unsigned     | YES  |     | NULL    |                | 
			 * | title      | varchar(150)         | YES  |     | NULL    |                | 
			 * | author     | varchar(80)          | YES  |     | NULL    |                | 
			 * | email      | varchar(200)         | YES  |     | NULL    |                | 
			 * | url        | varchar(200)         | YES  |     | NULL    |                | 
			 * | ip         | varchar(15)          | YES  |     | NULL    |                | 
			 * | body       | text                 | YES  |     | NULL    |                | 
			 * | type       | varchar(100)         | YES  | MUL | regular |                | 
			 * | subscribed | enum('true','false') | NO   |     | true    |                | 
			 * | status     | varchar(50)          | NO   | MUL |         |                | 
			 * | referer    | varchar(200)         | YES  |     | NULL    |                | 
			 * +------------+----------------------+------+-----+---------+----------------+
			 */
			$sql =<<<ENDOFSQL
SELECT
	id
, parent_id
, `timestamp`
, title
, author
, email
, url
, ip
, body
, type
, subscribed
, status
, referer
FROM {$this->s9y_db_prefix}comments
WHERE entry_id = ?
ENDOFSQL;
			if ( $this->comments_ignore_unapproved )
				$sql.= " AND status = 'Approved' ";
			$comments = $this->s9ydb->get_results( $sql, array( $post_info->id ), 'QueryRecord' );
			if ( count( $comments ) > 0 ) {
				echo "Starting import of <b>" . count( $comments ) . "</b> comments for post \"" . $post->title . "\"...";
				foreach ( $comments as $comment )
					$result&= $this->import_comment( $comment, $post->id );
				if ( $result )
					echo $this->success_html() . "<br />";
				else
					echo $this->fail_html() . "<br />";
				return $result;
			}
			else
				return TRUE;
		}
		else { /* Something went wrong on $post->insert() */
			EventLog::log($e->getMessage(), 'err', null, null, print_r(array($post, $e), 1));
			Session::error( $e->getMessage() );
			return FALSE;
		}
	}

	/**
	 * Imports a single category from the s9y database into the
	 * habari database
	 *
	 * @param		post_id						ID of the new Habari post to attach tag to
	 * @param		tag_id						ID of the Habari tag to attach the post to
	 * @return	TRUE or FALSE if import of tag2post relationship succeeded
	 */
	private function import_post_category( $post_id, $tag_id )
	{
		if ( Tag::attach_to_post( $tag_id, $post_id ) )
			return TRUE;
		else {
			EventLog::log($e->getMessage(), 'err', null, null, print_r(array($tag_id, $post_id, $e), 1));
			Session::error( $e->getMessage() );
			return FALSE;
		}
	}

	/**
	 * Imports a single comment from the s9y database into the
	 * habari database
	 *
	 * @param		comment_info		 	QueryRecord of comment information to import
	 * @param		habari_post_id		ID of the post to attach the comment to
	 * @return	TRUE or FALSE if import of comment succeeded
	 */
	private function import_comment( $comment_info = array(), $habari_post_id )
	{
		/* A mapping for s9y comment status to habari comment status codes */
		$status_map = array(
			'APPROVED'=> Comment::STATUS_APPROVED
			, 'PENDING'=> Comment::STATUS_UNAPPROVED
		);
		/* A mapping for s9y comment type to habari comment type codes */
		$type_map = array(
			'TRACKBACK'=> Comment::TRACKBACK
			, 'NORMAL'=> Comment::COMMENT
		);

		$comment = new Comment();
		$comment->post_id = $habari_post_id;
		$comment->info->s9y_id = $comment_info->id;
		if ( ! empty( $comment_info->parent_id ) 
			&&  $comment_info->parent_id != "0" )
			$comment->info->s9y_parent_id = $comment_info->parent_id;
		$comment->ip = sprintf ("%u", ip2long($comment_info->ip) );
		$comment->status = $status_map[strtoupper($comment_info->status)];
		$comment->type = $type_map[strtoupper($comment_info->type)];
		$comment->name = $this->transcode( $comment_info->author );
		$comment->url = $comment_info->url;
		$comment->date = date('Y-m-d H:i:s', $comment_info->timestamp);
		$comment->email = $this->transcode( $comment_info->email );
		$comment->content = $this->transcode( $comment_info->body );
		if ( $comment->insert() )
			return TRUE;
		else {
			EventLog::log($e->getMessage(), 'err', null, null, print_r(array($comment, $e), 1));
			Session::error( $e->getMessage() );
			return FALSE;
		}
	}

	/**
	 * Utility method for stripping out POSTed fields that
	 * are not valid for a particular stage.
	 *
	 * @param		valid_fields	Array of field names that are valid
	 * @return	cleaned array
	 */
	private function get_valid_inputs( $valid_fields )
	{
		return array_intersect_key( $_POST, array_flip( $valid_fields ) );
	}

	/**
	 * Return a string with the warning message formatted
	 * for HTML output
	 *
	 * @param		message		Warning message string
	 * @return	string of HTML
	 */
	private function get_warning_html( $message )
	{
		return "<p style=\"color: red;\" class=\"warning\">{$message}</p>";
	}

	/**
	 * Tries to get and cache the version of s9y by retrieving the configuration
	 * file that Serendipity uses to store the version of software running on the 
	 * host
	 *
	 * @param		array		inputs from POST, cleaned.
	 * @return 	string 	version of s9y
	 */
	private function get_s9y_version( $inputs )
	{
		if (array_key_exists('s9y_version', array_values($inputs)))
			return $inputs['s9y_version'];

		/* Try to find the version from the s9y configuration file */
		if (! isset($inputs['s9y_root_web'])) {
			$this->add_error('Please provide a root web directory for finding the s9y cnfiguration file.');
			return false;
		}
		else
			$root_web = rtrim($input['s9y_root_web'], ' ' . DIRECTORY_SEPARATOR);

		$config_file = $root_web . DIRECTORY_SEPARATOR . S9Y_CONFIG_FILENAME;
		if (! is_readable($config_file)) {
			$this->add_error('The configuration file for s9y (' . $config_file . ') is not readable.');
			return false;
		}

		/* Pull in the configuration file and grab the version information from it */
		$config_file_contents = file_get_contents($config_file);
		$matches = array();
		if (1 == preg_match("/^\$serendipity\[\'version\'\]\s+\=\s+\'([^']+)\'", $config_file_contents, $matches))
			$version = $matches[1];
		else {
			$this->add_error('No version information found in s9y configuration file');
			return false;
		}

		return $version;
	}

	/**
	 * Attempt to connect to the Serendipity database
	 *
	 * @param string $db_host The hostname of the WP database
	 * @param string $db_name The name of the WP database
	 * @param string $db_user The user of the WP database
	 * @param string $db_pass The user's password for the WP database
	 * @param string $db_prefix The table prefix for the WP instance in the database
	 * @param string $db_port The port number of the WP database
	 * @return mixed false on failure, DatabseConnection on success
	 */
	private function s9y_connect( $db_host, $db_name, $db_user, $db_pass, $db_prefix, $db_port = null)
	{
		// Connect to the database or return false
		try {
			$s9ydb = new DatabaseConnection();
			$connect_str = "mysql:host={$db_host};dbname={$db_name}";
			if ( !is_null( $db_port ) ) {
				$connect_str .= ";port={$db_port}";
			}
			$s9ydb->connect( $connect_str, $db_user, $db_pass, $db_prefix );
			return $s9ydb;
		}
		catch( PDOException $e ) {
			return FALSE;
		}
	}

	private function success_html() {
		return "<b style=\"color: green;\">" . _t( "succeeded" ) . "</b>";
	}

	private function fail_html() {
		return "<b style=\"color: red;\">" . _t( "failed" ) . "</b>";
	}
	
	protected function transcode( $content ) {
		return iconv( 'Windows-1252', 'UTF-8', $content );
	}
}
?>
