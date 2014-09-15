<?php
	/**
	 * @package Habari
	 *
	 */

	/**
	 * Allows REST responses to return based on requests accepts headers
	 *
	 */
class RestResponse
{
	protected $response;

	/**
	 * Constructor for a REST response, sets the intended response for the request
	 * @param array|string $response The intended response to the REST request
	 */
	public function __construct($response) {
		$this->response = $response;
	}

	/**
	 * Output the response
	 */
	public function out() {
		$accept = $this->get_accept();
		header('content-type: ' . $accept);
		echo $this->get();
	}

	/**
	 * Determine the best mimetype to respond to the client with based on the accept header and the mimetypes available
	 * @param array $mime_types An array of available mimetypes
	 * @return null|string The best mimetype available to return based on the client accept header
	 */
	public function get_best_mime($mime_types = null) {
		// Values will be stored in this array
		$accept_types = array();

		// Accept header is case insensitive, and whitespace isnâ€™t important
		$accept = strtolower(str_replace(' ', '', $_SERVER['HTTP_ACCEPT']));
		// divide it into parts in the place of a ","
		$accept = explode(',', $accept);
		foreach ($accept as $a) {
			// the default quality is 1.
			$q = 1;
			// check if there is a different quality
			if (strpos($a, ';q=')) {
				// divide "mime/type;q=X" into two parts: "mime/type" and "X"
				list($a, $q) = explode(';q=', $a);
			}
			// mime-type $a is accepted with the quality $q
			// WARNING: $q == 0 means, that mime-type isnâ€™t supported!
			$accept_types[$a] = $q;
		}
		arsort($accept_types);

		// if no parameter was passed, just return parsed data
		if (!$mime_types) {
			return $accept_types;
		}

		$mime_types = array_map('strtolower', (array)$mime_types);

		// let's check our supported types:
		foreach ($accept_types as $mime => $q) {
			if ($q && in_array($mime, $mime_types)) {
				return $mime;
			}
		}
		// no mime-type found
		return null;
	}

	public function get_mime_list() {
		static $mimelist = null;
		if(is_null($mimelist)) {
			$mimelist = array(
				'text/plain' => array($this, 'convert_text_plain'),
				'text/html' => array($this, 'convert_text_html'),
				'application/json' => array($this, 'convert_application_json'),
				'application/xml' => array($this, 'convert_application_xml'),
			);
			$mimelist = Plugins::filter('rest_mime_list', $mimelist);
		}
		return $mimelist;
	}

	public function get_accept()
	{
		$mimelist = $this->get_mime_list();
		$accept = $this->get_best_mime(array_keys($mimelist));
		return $accept;
	}

	public function get() {
		$mimelist = $this->get_mime_list();
		$accept = $this->get_accept();
		$response = null;
		
		if(is_string($this->response)) {
			$response = $this->response;
		}
		elseif(is_array($this->response)) {
			$response = $mimelist[$accept]($this->response);
		}
		elseif($this->response instanceof DOMElement) {
			$response = $mimelist[$accept]($this->response);
		}

		$response = Plugins::filter('rest_response', $response, $accept, $this->response);
		return $response;
	}
}
