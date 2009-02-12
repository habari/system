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
<form method="get" id="search-form" action="<?php URL::out('display_search'); ?>">
	<input type="text" name="criteria" id="search-box" value="<?php if ( isset( $criteria ) ) { echo htmlentities($criteria, ENT_COMPAT, 'UTF-8');} else { printf( _t("Search %s"), Options::get("title")) ;}  ?>" onfocus="if (this.value == '<?php printf( _t("Search %s"), str_replace("'", "\'", Options::get("title")) ); ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php printf( _t("Search %s"), str_replace("'", "\'", Options::get("title")) ); ?>';}" >
	<input type="submit" id="search-btn" value="" title="<?php _e( "Go" ); ?>">
</form>
