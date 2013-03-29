<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wpeconomy');

/** MySQL database username */
define('DB_USER', 'wpeconomy');

/** MySQL database password */
define('DB_PASSWORD', 'disoxi!23is');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'R9-5[PV5arG*:V E@y,Cn-+)9{-$@kK~-;uGVfXln1+[BmL]*AG!-D&F-C1*{2|t');
define('SECURE_AUTH_KEY',  'i-U2nkB=}]Z/,^7BHuL8pYn.iH!+NwBByKPQC]aD#Rjenc+HX-?+,^OyF}SG`I91');
define('LOGGED_IN_KEY',    ' aD@FJyc..MINSIQcn39C&:~`h[5O-z~|gZ;yLMi]Hk4*;h-d??q{|1LrtmSh~%1');
define('NONCE_KEY',        'In]&RtB!_TTQs!7:9<kuxPd9q9e#DvH(-8-b=8sB`)h}#}#``{~8,;5l^.@Yq-kQ');
define('AUTH_SALT',        'X5d7H|u2DQ99Fj#]>L+;5/r46XM@NIyG:InsE4YpM6|B~QgTj?*ae)_ht-Li#z$J');
define('SECURE_AUTH_SALT', 'S4._;AwMwJ+wnD<3g4jMu!!W)m|5(U,ofN_53 V)}1!-,bc2UejYewPC<IkZ|2}X');
define('LOGGED_IN_SALT',   'n5NG.w,9~`U/.v##(i93,gF2$FBANiN h639=ls/D!Cnj!3RpN>Kfx>V5r``eT+|');
define('NONCE_SALT',       '-`94@NU$%GoN.rMHj4?<r:ro7{-&-Z!FXb=E#@n[Xxff72q/`Hfv=M~_i_dlm{;1');

/**#@-*/


define('FS_CHMOD_FILE', 0755);
define('FS_CHMOD_DIR', 0755);
define('FS_METHOD', 'direct');
define('FTP_BASE', '/var/www/html/getshopped.org/');
define('FTP_CONTENT_DIR', '/var/www/html/getshopped.org/wp-content/');
define('FTP_PLUGIN_DIR ', '/var/www/html/getshopped.org/wp-content/plugins/');
define('FTP_USER', 'webmaster');
define('FTP_PASS', 'getshopped123!');
define('FTP_HOST', 'getshopped.org');
define('FTP_SSL', true);

define('TY_BKLOCATION', '/var/www/html/api.wpeconomy.org');

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', true);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

