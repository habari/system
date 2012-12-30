<?php
?>
<!DOCTYPE HTML>
<html>
<head>
	<meta charset="UTF-8">
	<title><?php if($request->display_entry && isset($post)) { echo $post->title_title . ' - '; } ?><?php echo Habari\Options::get('title'); ?></title>
	<meta name="generator" content="Habari">
	<meta name="viewport" content="width=device-width, maximum-scale = 1">
	<link rel="Shortcut Icon" href="<?php echo $theme->get_url('/favicon.png'); ?>">
	<?php echo $theme->header(); ?>
</head>
<body class="<?php echo $theme->body_class(); ?>" itemscope itemtype="http://schema.org/Blog">

<div id="wrapper">

	<header id="header">
		<hgroup>
			<h1 itemprop="name"><a href="<?php Habari\Site::out_url( 'habari' ); ?>/" itemprop="url"><?php echo Habari\Options::get('title'); ?></a></h1>
			<h2 itemprop="description"><?php echo Habari\Options::get('tagline'); ?></h2>
		</hgroup>
		<?php echo $theme->area('nav'); ?>
		<?php echo $theme->area('header'); ?>
	</header>

	<div id="content">
		<?php Habari\Session::messages_out(); ?>
