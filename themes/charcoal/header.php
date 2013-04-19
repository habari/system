<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); }
?>
<!DOCTYPE HTML>
<html lang="<?php echo $locale; ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $page_title; ?></title>
	<meta name="generator" content="Habari">
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $theme->get_url( 'style.css' ); ?>">
	<!--[if lt IE 7]>
	<link rel="stylesheet" href="<?php echo $theme->get_url( 'ie.css' ); ?>" type="text/css" media="screen" />
	<script src="<?php Site::out_url('scripts'); ?>/jquery.js" type="text/javascript" charset="utf-8"></script>
	<script src="<?php echo $theme->get_url( 'scripts/jquery.pngfix.js' ); ?>" type="text/javascript" charset="utf-8"></script>
	<script src="<?php echo $theme->get_url( 'scripts/fixpngs.js' ); ?>" type="text/javascript" charset="utf-8"></script>
	<![endif]-->
	<?php echo $theme->header(); ?>
	<?php if ($localized_css): ?>
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $theme->get_url( $localized_css ); ?>">
	<?php endif; ?>
</head>
<body class="<?php echo $theme->body_class(); ?>">
	<div id="page-top">
		<div id="wrapper-top">
			<div id="top-primary">
				<div id="header">
					<div id="title">
					<?php if ( $show_title_image ) : ?>
						<h1><a href="<?php Site::out_url( 'habari' ); ?>"><img src="<?php echo $theme->get_url( $title_image ); ?>" alt="<?php Options::out( 'title' ); ?>" ></a><span class="hidden"><?php Options::out( 'title' ); ?></span></h1>
					<?php else : ?>
						<h1><a href="<?php Site::out_url( 'habari' ); ?>"><?php Options::out( 'title' ); ?></a></h1>
					<?php endif; ?>
						<p class="tagline"><?php Options::out( 'tagline' ); ?></p>
					</div>
					<div id="navbar">
						<ul>
						<?php echo $theme->area( 'nav' ); ?>
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
