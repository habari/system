<?php
class curl {
  var $timeout;
  var $url;
  var $file_contents;
  function getFile($url,$timeout=0) {
    # use CURL library to fetch remote file
    $ch = curl_init();
    $url;
    $timeout;
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $file_contents = curl_exec($ch);
    if ( curl_getinfo($ch,CURLINFO_HTTP_CODE) !== 200 ) {
      return('Bad Data File '.$url);
    } else {
      return $file_contents;
    }
  }
}
?>