<?php

/**
 * This class implements the Pingback Spec, version 1.0
 * <http://www.hixie.ch/specs/pingback/pingback>
 */
class Pingback {
    /**
     * constructor Pingback
     * Costructor for Pingback class. Sends pingbacks to all links in a post
     * @param string Content of the source post
     * @param string Permalink to the source post
    **/
    public static function pingback_all_links( $text, $uri ) {
        preg_match_all( "@<a(?:\s+[^>]*)?\s+href=([\"'])(https?://.+?)\\1[^>]*>(?:.*?)</a>@is", $text, $matches );
        $links= array_unique( $matches[2] ); //Remove duplicate entries
        foreach ( $links as $link ) {
			Pingback::send_pingback( $link, $uri );
        }
    }

    /**
     * private function send_pingback
     * Sends pingback to a specific URI
     * @param string URI to pingback
     * @param string Permalink to the source post
    **/
    private static function send_pingback( $target_uri, $post_uri ) {
    	$rr= new RemoteRequest( $target_uri );
    	if ( ! $rr->execute() ) {
    		// request errored out
    		return;
    	}
    	
    	$headers= $rr->get_response_headers();
    	$body= $rr->get_response_body();
    	
        if ( preg_match( '/^X-Pingback: (\S*)/mi', $headers, $matches ) ) {
            // If remote sends an X-Pingback header, use the URI specified
            $pingback_endpoint= $matches[1];
        }
        elseif ( preg_match( '@<link rel="pingback" href="([^"]+)" ?/?>@si', $body, $matches ) ) {
        	// If there is a <link> element with a rel of "pingback"
			$pingback_endpoint= $matches[1];
        }
        else {
        	// no pingback facility found
        	return;
        }
        
        $pingback= new RPCClient( $pingback_endpoint, 'pingback.ping', array( $post_uri, $target_uri ) );
        $pingback->execute();
    }
}

?>
