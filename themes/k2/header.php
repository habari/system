<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="<?php echo $locale; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
<title><?php echo $theme->page_title; ?></title>
<meta name="generator" content="Habari">

<link rel="stylesheet" type="text/css" media="screen" href="<?php Site::out_url( 'theme' ); ?>/style.css">

<?php echo $theme->header(); ?>
</head>

<body class="<?php echo $theme->body_class(); ?>">
<div id="page">
	<div id="header">

	<h1><a href="<?php Site::out_url( 'habari' ); ?>"><?php Options::out( 'title' ); ?></a></h1>
	<p class="description"><?php Options::out( 'tagline' ); ?></p>

	<?php echo $theme->area('nav'); ?>

	</div>

	<hr>
<!-- /header -->
