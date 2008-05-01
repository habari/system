<form method="get" id="search-form" action="<?php URL::out('display_search'); ?>">
	<input type="text" name="criteria" id="search-box" value="<?php if ( isset( $criteria ) ) { echo htmlentities($criteria, ENT_COMPAT, 'UTF-8');} else {echo "Search ".Options::get( 'title' );}  ?>" onfocus="if (this.value == 'Search <?php Options::out( 'title' ) ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = 'Search <?php Options::out( 'title' ) ?>';}" >
	<input type="submit" id="search-btn" value="" title="Go">
</form>
