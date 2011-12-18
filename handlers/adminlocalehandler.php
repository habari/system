<?php
/**
 * @package Habari
 *
 */

/**
 * Habari AdminLocaleHandler Class
 * Returns locale data as javascript for the admin
 *
 */
class AdminLocaleHandler extends AdminHandler
{
	public function get_locale() {

		header('Expires: ' . gmdate('D, d M Y H:i:s ', time() + 432000) . 'GMT');
		header('content-type: text/javascript');

		$domain = HabariLocale::get_messages();
		$domain_json = json_encode($domain);

		echo <<< tee
function _t() {
	var domain = {$domain_json};
	var s = arguments[0];

	if(domain[s] != undefined) {
		s = domain[s][1][0];
	}

	for(var i = 1; i <= arguments.length; i++) {
		r = new RegExp('%' + (i) + '\\\\\$s', 'g');
		if(!s.match(r)) {
			r = new RegExp('%s');
		}
		s = s.replace(r, arguments[i]);
	}
	return s;
}

tee;


	}
}
