<!-- header -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
 <title><?php Options::out( 'title' ); ?></title>
 <meta http-equiv="Content-Type" content="text/html">
 <meta name="generator" content="Habari">

 <link rel="alternate" type="application/atom+xml" title="Atom 1.0" href="<?php $theme->feed_alternate(); ?>">
 <link rel="edit" type="application/atom+xml" title="Atom Publishing Protocol" href="<?php URL::out( 'atompub_servicedocument' ); ?>">
 <link rel="EditURI" type="application/rsd+xml" title="RSD" href="<?php URL::out( 'rsd' ); ?>">

 <link rel="stylesheet" type="text/css" media="screen" href="<?php Site::out_url( 'theme' ); ?>/style.css">

<?php $theme->header(); ?>
</head>

<body class="home">
 <div id="page">
  <div id="header">

   <h1><a href="<?php Site::out_url( 'habari' ); ?>"><?php Options::out( 'title' ); ?></a></h1>
   <p class="description"><?php Options::out( 'tagline' ); ?></p>

   <ul class="menu">
    <li><a href="http://habariproject.org/" title="Habari Project">Habari Project</a></li>
<?php
// Menu tabs
foreach ( $pages as $tab ) {
?>
    <li><a href="<?php echo $tab->permalink; ?>" title="<?php echo $tab->title; ?>"><?php echo $tab->title; ?></a></li>
<?php
}
if ( $user ) { ?>
    <li class="admintab"><a href="<?php Site::out_url( 'admin' ); ?>" title="Admin area">Admin</a></li>
<?php } ?>
   </ul>

  </div>

  <hr>
<!-- /header -->

