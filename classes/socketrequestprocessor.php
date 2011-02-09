<?php

	class SocketRequestProcessor extends RequestProcessor {
		
		public function __construct ( ) {
			
			// see if we can follow Location: headers
			if ( ini_get( 'safe_mode' ) || ini_get( 'open_basedir' ) ) {
				$this->can_followlocation = false;
			}
			
		}
		
		public function execute ( $method, $url, $headers, $body, $config ) {
			
			$merged_headers = array();
			foreach ( $headers as $k => $v ) {
				$merged_headers[] = $k . ': '. $v;
			}
			
			// parse out the URL so we can refer to individual pieces
			$url_pieces = InputFilter::parse_url( $url );
			
			// set up the options we'll use when creating the request's context
			$options = array(
				'http' => array(
					'method' => $method,
					'header' => implode( "\n", $merged_headers ),
					'timeout' => $config['timeout'],
					'follow_location' => $this->can_followlocation,		// 5.3.4+, should be ignored by others
					'max_redirects' => $config['max_redirects'],

					// and now for our ssl-specific portions, which will be ignored for non-HTTPS requests
					'verify_peer' => $config['ssl']['verify_peer'],
					//'verify_host' => $config['ssl']['verify_host'],	// there doesn't appear to be an equiv of this for sockets - the host is matched by default and you can't just turn that off, only substitute other hostnames
					'cafile' => $config['ssl']['cafile'],
					'capath' => $config['ssl']['capath'],
					'local_cert' => $config['ssl']['local_cert'],
					'passphrase' => $config['ssl']['passphrase'],
				),
			);
			
			if ( $method == 'POST' ) {
				$options['http']['content'] = $body;
			}
			
			
			if ( $config['proxy']['server'] != '' && !in_array( $url_pieces['host'], $config['proxy']['exceptions'] ) ) {
				$proxy = $config['proxy']['server'] . ':' . $config['proxy']['port'];
				
				if ( $config['proxy']['username'] != '' ) {
					$proxy = $config['proxy']['username'] . ':' . $config['proxy']['password'] . '@' . $proxy;
				}
				
				$options['http']['proxy'] = 'tcp://' . $proxy;
			}

			// create the context
			$context = stream_context_create( $options );
			
			// perform the actual request - we use fopen so stream_get_meta_data works
			$fh = @fopen( $url, 'r', false, $context );
			
			if ( $fh === false ) {
				throw new Exception( _t( 'Unable to connect to %s', array( $url_pieces['host'] ) ) );
			}
			
			// read in all the contents -- this is the same as file_get_contens, only for a specific stream handle
			$body = stream_get_contents( $fh );
			
			// get meta data
			$meta = stream_get_meta_data( $fh );
			
			// close the connection before we do anything else
			fclose( $fh );
			
			// did we timeout?
			if ( $meta['timed_out'] == true ) {
				throw new RemoteRequest_Timeout( _t( 'Request timed out' ) );
			}
			
			
			// $meta['wrapper_data'] should be a list of the headers, the same as is loaded into $http_response_header
			$headers = array();
			foreach ( $meta['wrapper_data'] as $header ) {
				
				// break the header up into field and value
				$pieces = explode( ': ', $header, 2 );
				
				if ( count( $pieces ) > 1 ) {
					// if the header was a key: value format, store it keyed in the array
					$headers[ $pieces[0] ] = $pieces[1];
				}
				else {
					// some headers (like the HTTP version in use) aren't keyed, so just store it keyed as itself
					$headers[ $pieces[0] ] = $pieces[0];
				}
				
			}
			
			$this->response_headers = $headers;
			$this->response_body = $body;
			$this->executed = true;
			
			return true;
			
		}
		
	}

?>