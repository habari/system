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
$db_connection = array(
	'connection_string'=>'pgsql:host={$db_host} dbname={$db_schema}',
	'username'=>'{$db_user}',
	'password'=>'{$db_pass}',
	'prefix'=>'{$table_prefix}'
);

// $locale = '{$locale}';
?>
