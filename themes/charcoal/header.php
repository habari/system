<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?php Options::out( 'title' ) ?><?php if ($request->display_entry && isset($post)) { echo " :: {$post->title}"; } ?></title>
	<meta name="generator" content="Habari">
	<link rel="stylesheet" type="text/css" media="screen" href="<?php Site::out_url( 'theme' ); ?>/style.css">
	<?php if ($localized_css): ?>
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo Site::get_url( 'theme', true ) . $localized_css; ?>">
	<?php endif; ?>
	<!--[if lt IE 7]>
	<link rel="stylesheet" href="<?php Site::out_url( 'theme' ); ?>/ie.css" type="text/css" media="screen" />
	<script src="<?php Site::out_url('scripts'); ?>/jquery.js" type="text/javascript" charset="utf-8"></script>
	<script src="<?php Site::out_url( 'theme' ); ?>/scripts/jquery.pngfix.js" type="text/javascript" charset="utf-8"></script>
	<script src="<?php Site::out_url( 'theme' ); ?>/scripts/fixpngs.js" type="text/javascript" charset="utf-8"></script>
	<![endif]-->
	<?php $theme->header(); ?>
</head>
<body class="<?php $theme->body_class(); ?>">
	<div id="page-top">
		<div id="wrapper-top">
			<div id="top-primary">
				<div id="header">
					<div id="title">
					<?php if ( $show_title_image ) : ?>
						<h1><a href="<?php Site::out_url( 'habari' ); ?>"><img src="<?php Site::out_url( 'theme' ); ?>/images/sample-title.png" alt="<?php Options::out( 'title' ); ?>" ></a><span class="hidden"><?php Options::out( 'title' ); ?></span></h1>
					<?php else : ?>
						<h1><a href="<?php Site::out_url( 'habari' ); ?>"><?php Options::out( 'title' ); ?></a></h1>
					<?php endif; ?>
						<p class="tagline"><?php Options::out( 'tagline' ); ?></p>
					</div>
					<div id="navbar">
						<ul>
						<?php $theme->area( 'nav' ); ?>
						<?php if ( $display_login ): ?>
							<li class="login">
							<?php if ( $loggedin ) : ?>
								<a href="<?php Site::out_url( 'admin' ); ?>" title="<?php _e( "Admin area" ); ?>"><?php _e( "Admin" ); ?></a>
							<?php else: ?>
								<a href="<?php Site::out_url( 'login' ); ?>" title="<?php _e( "Login" ); ?>"><?php _e( "Login" ); ?></a>
							</li>
							<?php endif; ?>
						<?php endif; ?>
						</ul>
					</div>
				</div>
