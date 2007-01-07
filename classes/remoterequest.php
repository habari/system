<?php
  	include('utils.php');

class RemoteRequest {
  var $timeout;
  var $url;
  var $file_contents;

	function set_timeout($timeout) {
		self::$timeout = $timeout;
	}

  function get( $url, $params = array() ) 
	{
  	$urlparts = parse_url( $url );
  	parse_str($urlparts['query'], $query);
  Utils::debug($urlparts);
  	if( $params == null ) {
  		$query = array();
  	}
  	else {
  		$query = array_merge($query, $params);
  	}
  	$urlparts['query'] = http_build_query($query);
  	$url = Utils::glue_url($urlparts);
  	switch(true) {
		case function_exists( 'curl_init' ):
	    # use CURL library to fetch remote file
	    $ch = curl_init();
	    curl_setopt ($ch, CURLOPT_URL, $url);
	    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, self::$timeout);
	    $file_contents = curl_exec($ch);
	    if ( curl_getinfo( $ch, CURLINFO_HTTP_CODE) !== 200 ) {
	      return Error::raise( sprintf( 'Bad return code (%s) for: %s', $url ) );
	    } else {
	      return $file_contents;
	    }
    default :
    	return file_get_contents( $url );
    }
  }
}

echo RemoteRequest::get('http://google.com/search', array('q'=>'hello'));
?>
