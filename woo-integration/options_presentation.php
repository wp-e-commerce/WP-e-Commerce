<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" type="text/css" href="<?php echo WPSC_URL; ?>/woo-integration/css/style.css"/>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.0/jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo WPSC_URL; ?>/woo-integration/js/script.js"></script>
    <title>Dirty Options Presentaion</title>
</head>
<body>
	<?php
	require_once( WPSC_FILE_PATH . '/wpsc-admin/includes/settings-pages/presentation.php' );
	echo wpsc_options_presentation();
	?>
</body>
</html>