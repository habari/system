<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } 
header( 'X-Frame-Options: DENY' );
?>
<!doctype html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?php Options::out('title'); ?> &middot; <?php echo $admin_title; ?></title>
	<script type="text/javascript">
	var habari = {
		url: {
			habari: '<?php Site::out_url( 'habari' ); ?>',
			ajaxDashboard: '<?php echo URL::get( 'admin_ajax', array( 'context' => 'dashboard' ) ); ?>',
			ajaxUpdatePosts: '<?php echo URL::get( 'admin_ajax', array( 'context' => 'update_posts' ) ); ?>',
			ajaxLogDelete: '<?php echo URL::get( 'admin_ajax', array( 'context' => 'delete_logs' ) ); ?>',
			ajaxUpdateUsers: '<?php echo URL::get( 'admin_ajax', array( 'context' => 'update_users' ) ); ?>',
			ajaxUpdateGroups: '<?php echo URL::get( 'admin_ajax', array( 'context' => 'update_groups' ) ); ?>',
			ajaxUpdateComment: '<?php echo URL::get( 'admin_ajax', array( 'context' => 'update_comment' ) ); ?>',
			ajaxAddBlock: '<?php echo URL::get( 'admin_ajax', array( 'context' => 'add_block' ) ); ?>',
			ajaxDeleteBlock: '<?php echo URL::get( 'admin_ajax', array( 'context' => 'delete_block' ) ); ?>',
			ajaxSaveAreas: '<?php echo URL::get( 'admin_ajax', array( 'context' => 'save_areas' ) ); ?>',
			ajaxConfigModule: '<?php echo URL::get('admin_ajax', array('context' => 'dashboard')); ?>'
		}
	};
	// An almost "catch all" for those old browsers that don't support the X-Frame-Options header.  We don't bust out, we just don't show any content
	if ( top != self ) {
		self.location.replace( 'about:blank' );
	}
	</script>
	<?php
		Plugins::act( 'admin_header', $this );
		Stack::out( 'admin_header_javascript', array( 'Stack', 'scripts' ) );
		Stack::out( 'admin_stylesheet', array( 'Stack', 'styles' ) );
	?>
	<!--[if IE 7]>
	<link rel="stylesheet" type="text/css" href="<?php Site::out_url( 'admin_theme' ); ?>/css/ie.css" media="screen">
	<![endif]-->

	<?php
		Plugins::act( 'admin_header_after', $this );
	?>

</head>
<body class="page-<?php echo $page; ?>">

<div id="menubar">

	<div id="menu" class="dropbutton">
		<h1 id="menubutton"><a href="<?php echo $admin_page_url; ?>"><?php echo ( isset( $mainmenu[$admin_page]['text'] ) ? $mainmenu[$admin_page]['text'] : $admin_page ); ?> <span class="hotkey">Q</span></a></h1>

		<div id="menulist" class="dropbuttonlist">
			<ul>
			<?php foreach ( $mainmenu as $menu_id => $menu ): ?>
				<li id="link-<?php echo $menu_id ?>" class="<?php if ( $menu['selected'] == true ) { echo 'selected'; } if ( isset( $menu['submenu'] ) ) { echo ' submenu'; } if ( isset( $menu['class'] ) ) { echo " " . $menu['class']; } ?>" title="<?php echo $menu['title']; ?>"><a class="top" href="<?php echo $menu['url']; ?>"><?php echo $menu['text']; ?>
				<?php if ( isset( $menu['hotkey'] ) && $menu['hotkey'] != '' ): ?><span class="hotkey"><?php echo $menu['hotkey']; ?></span><?php endif; ?>
				</a>
				<?php if ( isset( $menu['submenu'] ) ): ?>
				<ul class="submenu">
				 <?php foreach ( $menu['submenu'] as $submenu_id => $submenu_item ): ?>
				 	<li id="link-<?php echo $submenu_id ?>" title="<?php echo $submenu_item['title']; ?>" class="sub<?php if ( isset($submenu_item['hotkey'] ) && $submenu_item['hotkey'] != '' ): ?> hotkey-<?php echo $submenu_item['hotkey']; if ( isset( $submenu_item['class'] ) ) { echo " " . $submenu_item['class']; } ?><?php endif; ?>"><a href="<?php echo $submenu_item['url']; ?>"><?php echo $submenu_item['text']; ?>
				 	<?php if ( isset( $submenu_item['hotkey'] ) && $submenu_item['hotkey'] != '' ): ?><span class="hotkey"><?php echo $submenu_item['hotkey']; ?></span><?php endif; ?>
				 	</a></li>
				 <?php endforeach; ?>
				 </ul>
				<?php endif; ?>
				</li>
			<?php endforeach; ?>
			</ul>
		</div>
	</div>

	<a href="<?php Site::out_url( 'habari' ); ?>" id="site" title="<?php _e( 'Go to Site' ); ?>"><?php Options::out( 'title' ); ?></a>

</div>

<div id="spinner"></div>

<div id="page">

<?php Plugins::act( 'admin_info', $theme, $page ); ?>
