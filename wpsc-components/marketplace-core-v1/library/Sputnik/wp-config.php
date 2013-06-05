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
define('DB_NAME', 'zaowsai3_wp8012');

/** MySQL database username */
define('DB_USER', 'zaowsai3_wp8012');

/** MySQL database password */
define('DB_PASSWORD', '68P1Syklz5');

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
define('AUTH_KEY',         'swv521ek9ibqwnpxgmkprltc8b5bl2ekw9ba5lcazu2xygfab4jqhsjgwaokrmxm');
define('SECURE_AUTH_KEY',  'asupktcfripmcjh23u8nfqe66kqsbzx7d8emwl0sdaafdlhb8rxsdz496cpvxokx');
define('LOGGED_IN_KEY',    'qyktrywuby9fydm7adxyb9xue3pnr1zapobo7ddr3zfndfpk5npm2eogogdlcc13');
define('NONCE_KEY',        'wuz1xutuuxsijag8ffij8pnqlooghkxj9uvksh98hpdha2elozooforbfduygedw');
define('AUTH_SALT',        'dmr3luiwk2kep4vmcbgx7eac06lxiivwsqfjjqavxipitsqwzqgm0xlggrgye9zh');
define('SECURE_AUTH_SALT', '3vq2pzdmqa0dvnnetjfjm0jxbdfvc8ozm9x1tscsgwovhn5tlqtn4cqojot5sqr6');
define('LOGGED_IN_SALT',   'pz60ryawmk8uyvsvslcwfgknqihcjajwf12cpdqgfidjkemvldtkacwfhvuurfoi');
define('NONCE_SALT',       'sdkol6c0mbqah9kblvoh3hef38muru0drwcowrsse4k87sjrzwt6bqaqohuttwrv');

/**#@-*/

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
 * Change this to localize WordPress.  A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define ('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
