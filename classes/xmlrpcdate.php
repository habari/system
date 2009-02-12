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
 * XMLRPC Date type
 * Used to hold dates for transmission in XMLRPC calls.
 *
 */
class XMLRPCDate
{
	private $rpcdate;
	
	public function __set($name, $value)
	{
		switch($name) {
		case 'date':
			if(is_numeric($value)) {
				$this->rpcdate = $value;
			}
			else {
				$this->rpcdate = strtotime($value);
			}
		}
	}
	
	public function __get($name)
	{
		switch($name) {
		case 'date':
			return $this->rpcdate;
		}
	}
	
	public function __construct($date = null)
	{
		if(isset($date)) {
			$this->date = $date;
		}
	}
}

?>
