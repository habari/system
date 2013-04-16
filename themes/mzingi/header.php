<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">
<html <?php if(isset($locale)): ?>lang="<?php echo $locale; endif; ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; <?php if(isset($charset)): ?>charset=<?php echo $charset; endif; ?>">
	<title><?php echo $page_title; ?></title>
	<meta name="generator" content="Habari">

	<link rel="Shortcut Icon" href="<?php Site::out_url( 'theme' ); ?>/favicon.ico">
	<?php echo $theme->header(); ?>
</head>
<body class="<?php echo $theme->body_class(); ?>">
	<!--begin wrapper-->
	<div id="wrapper">
		<!--begin masthead-->
		<div id="masthead">
			<div id="branding">
				<h1><a href="<?php Site::out_url( 'site'); ?>" title="<?php Options::out( 'title' ); ?>"> <?php Options::out( 'title' ); ?></a></h1>
				<h3 class="prepend-1"><em><?php Options::out( 'tagline' ); ?></em></h3>
			</div>
		</div>
	<!--end masthead-->


