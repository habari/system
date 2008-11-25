<?php

class MediaAdminPage extends AdminPage
{
	public function ajax_media( $handler_vars )
	{
		$path = $handler_vars['path'];
		$rpath = $path;
		$silo = Media::get_silo( $rpath, true );  // get_silo sets $rpath by reference to the path inside the silo
		$assets = Media::dir( $path );
		$output = array(
			'ok' => 1,
			'dirs' => array(),
			'files' => array(),
			'path' => $path,
		);
		foreach ( $assets as $asset ) {
			if ( $asset->is_dir ) {
				$output['dirs'][$asset->basename]= $asset->get_props();
			}
			else {
				$output['files'][$asset->basename]= $asset->get_props();
			}
		}
		$controls = array();
		$controls = Plugins::filter( 'media_controls', $controls, $silo, $rpath, '' );
		$output['controls']= '<li>' . implode( '</li><li>', $controls ) . '</li>';

		echo json_encode( $output );
	}

	public function ajax_media_panel( $handler_vars )
	{
		$path = $handler_vars['path'];
		$panelname = $handler_vars['panel'];
		$rpath = $path;
		$silo = Media::get_silo( $rpath, true );  // get_silo sets $rpath by reference to the path inside the silo

		$panel = '';
		$panel = Plugins::filter( 'media_panels', $panel, $silo, $rpath, $panelname );
		$controls = array();
		$controls = Plugins::filter( 'media_controls', $controls, $silo, $rpath, $panelname );
		$controls = '<li>' . implode( '</li><li>', $controls ) . '</li>';
		$output = array(
			'controls' => $controls,
			'panel' => $panel,
		);

		header( 'content-type:text/javascript' );
		echo json_encode( $output );
	}
}

?>