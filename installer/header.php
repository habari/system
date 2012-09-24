<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="Content-Language" content="en"/>
	<meta name="robots" content="no index,no follow" />
	<link rel="shortcut icon" href="<?php Site::out_url('habari'); ?>/favicon.ico" />
	<link href="<?php Site::out_url('habari'); ?>/system/installer/style.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="<?php Site::out_url('vendor'); ?>/jquery.js"></script>
	<script type="text/javascript">
	var habari = {
		url:{
			check_mysql_credentials: '<?php Site::out_url('habari', '/'); ?>ajax/check_mysql_credentials',
			check_pgsql_credentials: '<?php Site::out_url('habari', '/'); ?>ajax/check_pgsql_credentials',
			check_sqlite_credentials: '<?php Site::out_url('habari', '/'); ?>ajax/check_sqlite_credentials'
		}
	};
	</script>
	<title><?php _e('Install Habari'); ?></title>
</head>
<body id="installer">
	<div id="wrapper">
		<?php $tab = 1; ?>
		<div id="masthead">
			<h1>Habari</h1>
			<div id="footer">
				<p class="left"><a href="<?php Site::out_url( 'habari' ); ?>/doc/manual/index.html" onclick="popUp(this.href); return false;" title="<?php _e('Read the user manual'); ?>" tabindex="<?php echo $tab++ ?>"><?php _e('Manual'); ?></a> &middot;
					<a href="http://wiki.habariproject.org/" title="<?php _e('Read the Habari wiki'); ?>" tabindex="<?php echo $tab++ ?>"><?php _e('Wiki'); ?></a> &middot;
					<a href="http://groups.google.com/group/habari-users" title="<?php _e('Ask the community'); ?>" tabindex="<?php echo $tab++ ?>"><?php _e('Mailing List'); ?></a>
				</p>
			</div>
		</div>
		<?php include "locale_dropdown.php"; ?>
