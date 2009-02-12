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
<?php
/**
 * @package Habari
 *
 */

/**
 * URL class which handles creation of URLs based on the rewrite
 * rules in the database.  Uses rules to construct pretty URLs for use
 * by the system and especially the theme's template engine
 *
 */
class URL extends Singleton
{
	// static collection of rules ( pulled from RewriteController )
	private $rules = NULL;
	private $matched_rule = NULL;
	private static $stub = NULL;

	/**
	 * Enables singleton working properly
	 *
	 * @see singleton.php
	 */
	protected static function instance()
	{
		return self::getInstanceOf( get_class() );
	}

	/**
	 * A simple caching mechanism to avoid reloading rule array
	 */
	private function load_rules()
	{
		if ( URL::instance()->rules != NULL ) {
			return;
		}
		URL::instance()->rules = RewriteRules::get_active();
	}

	/**
	 * Get the matched RewriteRule that was matched in parse().
	 *
	 * @return RewriteRule matched rule, or NULL
	 */
	public static function get_matched_rule()
	{
		return URL::instance()->matched_rule;
	}

	/**
	 * Cause the matched rule to be unset in the case of a 404
	 *
	 * @return RewriteRule A rewrite rule that represents a 404 error - no match on the URL requested
	 */
	public static function set_404()
	{
		if( empty(URL::instance()->matched_rule) || (URL::instance()->matched_rule->name != 'display_404') ) {
			$rule = RewriteRules::by_name('display_404');
			URL::instance()->matched_rule = reset($rule);
			URL::instance()->matched_rule->match(self::$stub);
		}
		return URL::instance()->matched_rule;
	}

	/**
	 * Match a URL/URI against the rewrite rules stored in the DB.
	 * This method is used by the Controller class for parsing
	 * requests, and by other classes, such as Pingback, which
	 * uses it to determine the post slug for a given URL.
	 *
	 * Returns the matched RewriteRule object, or FALSE.
	 *
	 * @param string $from_url URL string to parse
	 * @return RewriteRule matched rule, or FALSE
	 */
	public static function parse( $from_url )
	{
		$base_url = Site::get_path( 'base', true );

		/*
		 * Strip out the base URL from the requested URL
		 * but only if the base URL isn't /
		 */
		if ( strpos( $from_url, $base_url ) === 0 ) {
			$from_url = substr( $from_url, strlen( $base_url ) );
		}

		/* Trim off any leading or trailing slashes */
		$from_url = trim( $from_url, '/' );

		/* Remove the querystring from the URL */
		if ( strpos( $from_url, '?' ) !== FALSE ) {
			list( $from_url, )= explode( '?', $from_url );
		}

		$url = URL::instance();
		$url->load_rules(); // Cached in singleton

		/*
		 * Run the stub through the regex matcher
		 */
		$pattern_matches = array();
		self::$stub = $from_url;
		foreach ( $url->rules as $rule ) {
			if ( $rule->match( $from_url ) ) {
				$url->matched_rule = $rule;
				/* Stop processing at first matched rule... */
				return $rule;
			}
		}

		return FALSE;
	}

	/**
	 * Builds the required pretty URL given a supplied
	 * rule name and a set of placeholder replacement
	 * values and returns the built URL.
	 *
	 * <code>
	 * 	URL::get( 'display_entries_by_date', array(
	 * 		'year' => '2000',
	 *    	'month' => '05',
	 *    	'day' => '01',
	 * 	) );
	 * </code>
	 *
	 * @param mixed $rule_names string name of the rule or array of rules which would build the URL
	 * @param mixed $args (optional) array or object of placeholder replacement values
	 * @param boolean $useall If true (default), then all passed parameters that are not part of the built URL are tacked onto the URL as querystring
	 * @param boolean $prepend_site If true (default), a full URL is returned, if false, only the path part of the URL is returned
	 */
	public static function get( $rule_names='', $args = array(), $useall = true, $noamp = false, $prepend_site = true )
	{
		$args = self::extract_args( $args );

		$url = URL::instance();
		if ( $rule_names == '' ) {
			// Retrieve current matched RewriteRule
			$selectedrule = $url->get_matched_rule();

			// Retrieve arguments name the RewriteRule can use to build a URL.
			$rr_named_args = $selectedrule->named_args;

			$rr_args = array_merge( $rr_named_args['required'], $rr_named_args['optional']  );
			// For each argument, check if the handler_vars array has that argument and if it does, use it.
			$rr_args_values = array();

			foreach ( $rr_args as $rr_arg ) {
				if ( !isset( $args[$rr_arg] ) ) {
					$rr_arg_value = Controller::get_var( $rr_arg );
					if ( $rr_arg_value != '' ) {
						$args[$rr_arg] = $rr_arg_value;
					}
				}
			}

		}
		else {
			$url->load_rules();
			$selectedrule = null;

			if ( !is_array( $rule_names ) ) {
				$rule_names = array( $rule_names );
			}
			foreach ( $rule_names as $rule_name ) {
				if ( $rules = $url->rules->by_name( $rule_name ) ) {
					$rating = null;
					foreach ( $rules as $rule ) {
						$newrating = $rule->arg_match( $args );
						// Is the rating perfect?
						if ( $rating == 0 ) {
							$selectedrule = $rule;
							break;
						}
						if ( empty( $rating ) || ( $newrating < $rating ) ) {
							$rating = $newrating;
							$selectedrule = $rule;
						}
					}
					if ( isset( $selectedrule ) ) {
						break;
					}
				}
			}
		}

		if ( $selectedrule instanceOf RewriteRule ) {
			$return_url = $selectedrule->build( $args, $useall, $noamp );
			if ( $prepend_site ) {
				return Site::get_url( 'habari', true ) . $return_url;
			}
			else {
				return $return_url;
			}
		}
		else {
			$error = new Exception();
			$error_trace = $error->getTrace();
			// Since URL::out() calls this function, the index 0 is URL::get() which is not the proper failing call.
			if ( isset($error_trace[1]['class']) && isset($error_trace[1]['function']) && ($error_trace[1]['class'] == 'URL') && ($error_trace[1]['function'] == 'out') ) {
				$error_args = $error_trace[1];
			}
			// When calling URL::get() directly, the index 0 is the proper file and line of the failing call.
			else {
				$error_args = $error_trace[0];
			}
			EventLog::log( sprintf( _t('Could not find a rule matching the following names: %s. File: %s (line %s)'), implode(', ', $rule_names), $error_args['file'], $error_args['line'] ) , 'notice', 'rewriterules', 'habari' );
		}
	}

	/**
	 * Helper wrapper function.  Outputs the URL via echo.
	 * @param string $rule_name name of the rule which would build the URL
	 * @param array $args (optional) array of placeholder replacement values
	 * @param boolean $useall If true (default), then all passed parameters that are not part of the built URL are tacked onto the URL as querystring
	 * @param boolean $prepend_site If true (default), a full URL is returned, if false, only the path part of the URL is returned
	 */
	public static function out( $rule_name = null, $args = array(), $useall = true, $noamp = true, $prepend_site = true )
	{
		echo URL::get( $rule_name, $args, $useall, $noamp, $prepend_site );
	}

	/**
	 * Get a fully-qualified URL from a filesystem path
	 *
	 * @param string $path The filesystem path
	 * @param bool whether to include a trailing slash.  Default: No
	 * @return string URL
	 */
	public static function get_from_filesystem($path, $trail = false)
	{
		$url = Site::get_url('habari') . substr(dirname($path), strlen(HABARI_PATH));
		// Replace windows paths with forward slashes
		$url = str_replace( '\\', '/', $url);
		$url .= ( $trail ) ? '/' : '';
		return $url;
	}

	/**
	 * Extract the possible arguments to use in the URL from the passed variable
	 * @param mixed $args An array of values or a URLProperties object with properties to use in the construction of a URL
	 * @return array Properties to use to construct  a URL
	 **/
	public static function extract_args( $args, $prefix = '' )
	{
		if ( is_object( $args ) ) {
			if ( $args instanceof URLProperties ) {
				$args = $args->get_url_args();
			}
			else {
				$args_ob = array();
				foreach ( $args as $key => $value ) {
					$args_ob[$key] = $value;
				}
				$args = $args_ob;
			}
		}
		else {
			$args = Utils::get_params( $args );
		}
		// can this be done with array_walk?
		if ( $prefix && $args ) {
			$args_out = array();
			foreach ( $args as $key => $value ) {
				$args_out[$prefix.$key] = $value;
			}
			$args = $args_out;
		}
		return $args;
	}

}

?>
