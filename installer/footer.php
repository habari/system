<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
	</div><!-- end wrapper -->
	<script type="text/javascript">
function _t() {
	var domain = <?php
		// retrieve only the translated strings that are used by this page's javascript functions.
		$messages = array_intersect_key( HabariLocale::get_messages(), array_flip( array( "All selected", "%s selected", "None selected" ) ) );
		echo json_encode( $messages );
	?>;
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
</script>
</body>
</html>
