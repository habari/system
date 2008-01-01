<?php
/**
 * Habari Pingback plugin, enables pingback support between sites.
 * @link http://www.hixie.ch/specs/pingback/pingback The Pingback spec
 *
 * @package Habari
 */

class Pingback extends Plugin {

	/**
	 * Provide plugin info to the system
	 */
	public function info() {
		return array(
			'name' => 'Pingback',
			'version' => '1.0',
			'url' => 'http://habariproject.org/',
			'author' =>	'Habari Community',
			'authorurl' => 'http://habariproject.org/',
			'license' => 'Apache License 2.0',
			'description' => 'Adds support Pingback 1.0 methods to the XML-RPC server.',
			'copyright' => '2008'
		);
	}

	/**
	 * Register the Pingback event type with the event log
	 */
	public function action_plugin_activation( $file ) {
		if ( realpath( $file ) == __FILE__ ) {
			EventLog::register_type( 'Pingback' );
		}
	}

	/**
	 * Unregister the Pingback event type on deactivation
	 * @todo Should we be doing this?
	 */
	public function action_plugin_deactivation( $file ) {
		if ( realpath( $file ) == __FILE__ ) {
			EventLog::unregister_type( 'Pingback' );
		}
	}

	/**
	 * Pingback links from the post content when a post is inserted into the database.
	 * @param post The post from which to send pingbacks
	 */
	public function action_post_insert_after( $post ) {
		// only execute if this is a published post
		if ( Post::status('published') != $post->status ) {
			return;
		}
		self::pingback_all_links( $post->content, $post->permalink, $post );
	}

	/**
	 * Pingback mentioned links when a post is updated.
	 * @param Post $post The post is updated
	 * We invoke this function regardless of what might have been updated
	 * in the post because:
	 * 	- this will only execute if the post is published
	 *	- the pingback_all_links function keeps track of links its
	 *		already pinged, so if the content hasnt changed no
	 *		pings will be sent`
	 */
	public function action_post_update_after( $post ) {
		// only execute if this is a published post
		if ( Post::status('published') != $post->status) {
			return;
		}
		self::pingback_all_links( $post->content, $post->permalink, $post );
	}

	/**
	 * Add the Pingback header on single post/page requests
	 * Not to the entire site.  Clever.
	 */
	public function action_add_template_vars() {
		$action= Controller::get_action();
		if ( $action == 'display_post' ) {
			header( 'X-Pingback: ' . URL::get( 'xmlrpc' ) );
		}
		else {
			header( 'X-action: ' . $action );
		}
	}

	/**
	 * Receive a Pingback via XMLRPC
	 * @param array $params An array of XMLRPC parameters from teh remote call
	 * @return string The success state of the pingback
	 */
	public function xmlrpc_pingback__ping( $params ) {
		try {
			list( $source_uri, $target_uri )= $params;

			// This should really be done by an Habari core function
			$target_parse= InputFilter::parse_url( $target_uri );
			$target_stub= $target_parse['path'];
			$base_url= Site::get_path( 'base', TRUE );

			if ( '/' != $base_url) {
				$target_stub= str_replace( $base_url, '', $target_stub );
			}

			$target_stub= trim( $target_stub, '/' );

			if ( strpos( $target_stub, '?' ) !== FALSE ) {
				list($target_stub, $query_string)= explode('?', $target_stub);
			}

			// Can this be used as a target?
			$target_slug= URL::parse( $target_stub )->named_arg_values['slug'];

			if ( $target_slug === FALSE ) {
				throw new XMLRPCException( 33 );
			}

			// Does the target exist?
			$target_post= Post::get( array( 'slug' => $target_slug ) );

			if ( $target_post === FALSE ) {
				throw new XMLRPCException( 32 );
			}

			// Is this Pingback already registered?
			if ( Comments::get( array( 'post_id' => $target_post->id, 'url' => $source_uri, 'type' => Comment::PINGBACK ) )->count() > 0 ) {
				throw new XMLRPCException( 48 );
			}

			// Retrieve source contents
			$rr= new RemoteRequest( $source_uri );
			if ( ! $rr->execute() ) {
				throw new XMLRPCException( 16 );
			}
			$source_contents= $rr->get_response_body();

			// Find the page's title
			preg_match( '/<title>(.*)<\/title>/is', $source_contents, $matches );
			$source_title= $matches[1];

			// Find the reciprocal links and their context
			preg_match( '/<body[^>]*>(.+)<\/body>/is', $source_contents, $matches );
			$source_contents_filtered= preg_replace( '/\s{2,}/is', ' ', strip_tags( $matches[1], '<a>' ) );

			if ( !preg_match( '%.{0,100}?<a[^>]*?href\\s*=\\s*("|\'|)' . $target_uri . '\\1[^>]*?'.'>(.+?)</a>.{0,100}%s', $source_contents_filtered, $source_excerpt ) ) {
				throw new XMLRPCException( 17 );
			}

			$source_excerpt= '...' . InputFilter::filter( $source_excerpt[0] ) . '...';

			// Add a new pingback comment
			$pingback= new Comment( array(
				'post_id'	=>	$target_post->id,
				'name'		=>	$source_title,
				'email'		=>	'',
				'url'		=>	$source_uri,
				'ip'		=>	'',
				'content'	=>	$source_excerpt,
				'status'	=>	Comment::STATUS_APPROVED,
				'date'		=>	date( 'Y-m-d H:i:s' ),
				'type' 		=> 	Comment::PINGBACK,
				) );
			$pingback->insert();

			// Respond to the Pingback
			return 'The pingback has been registered';
		} catch (XMLRPCException $e) {
			$e->output_fault_xml();
		}
	}

	/**
	 * Send a single Pingback
	 * @param string $source_uri The URI source of the ping (here)
	 * @param string $target_uri The URI destination of the ping (there, the site linked to in the content)
	 * @param Post $post The post	object that is initiating the ping, used to track the pings that were sent
	 * @todo If receive error code of already pinged, add to the successful.
	 */
	public function send_pingback( $source_uri, $target_uri, $post= NULL ) {
		// RemoteRequest makes it easier to retrieve the headers.
		$rr= new RemoteRequest( $target_uri );
		if ( ! $rr->execute() ) {
			return false;
		}

		$headers= $rr->get_response_headers();
		$body= $rr->get_response_body();

		// Find a Pingback endpoint.
		if ( preg_match( '/^X-Pingback: (\S*)/im', $headers, $matches ) ) {
			$pingback_endpoint= $matches[1];
		}
		elseif ( preg_match( '/<link rel="pingback" href="([^"]+)" ?\/?'.'>/is', $body, $matches ) ) {
			$pingback_endpoint= $matches[1];
		}
		else {
			// No Pingback endpoint found.
			return false;
		}

		$response= XMLRPCClient::open( $pingback_endpoint )->pingback->ping( $source_uri, $target_uri );

		if ( isset( $response->faultString ) ) {
			EventLog::log( $response->faultCode . ' - ' . $response->faultString . ' (Source: ' . $source_uri . ' | Target: ' . $target_uri . ')', 'info', 'Pingback' );
			return false;
		}
		else {
			// The pingback has been registered and is stored as a successful pingback.
			if ( is_object( $post ) ) {
				if ( isset( $post->info->pingbacks_successful ) ) {
					$post->info->pingbacks_successful[]= $target_uri;
				}
				else {
					$post->info->pingbacks_successful= array( $target_uri );
				}
			}
			return true;
		}
	}

	/**
	 * Scan all links in the content and send them a Pingback.
	 * @param string $content The post content to search
	 * @param string $source_uri The source of the content
	 * @param Post $post The post object of the source of the ping
	 * @param boolean $force If true, force the system to ping all links even if that had been pinged before
	 */
	public static function pingback_all_links( $content, $source_uri, $post= NULL, $force= false ) {
		preg_match_all( '/<a[^>]+href=(?:"|\')((?=https?\:\/\/)[^>]+)(?:"|\')[^>]*>[^>]+<\/a>/is', $content, $matches );

		if ( is_object( $post ) && isset( $post->info->pingbacks_successful ) ) {
			$fn= ( $force === TRUE ) ? 'array_merge' : 'array_diff';
			$links= $fn( $matches[1], $post->info->pingbacks_successful );
		}
		else {
			$links= $matches[1];
		}

		$links= array_unique( $links );

		foreach ( $links as $target_uri ) {
			if ( self::send_pingback( $source_uri, $target_uri, $post ) ) {
				EventLog::log( sprintf( _t( 'Sent pingbacks for "%1$s", target: %2$s' ), $post->title, $target_uri ), 'info', 'Pingback' );
			}
		}
	}
}
?>
