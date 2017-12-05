<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */
define('WP_CACHE', TRUE);
// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', getenv('DB_NAME'));

/** MySQL database username */
define('DB_USER', getenv('DB_USER'));

/** MySQL database password */
define('DB_PASSWORD', getenv('DB_PASSWORD'));

/** MySQL hostname */
define('DB_HOST', getenv('DB_HOST'));

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
 define('AUTH_KEY',         'NXqv U#d7L8-GOND4+=Q(!Dcvga&K7x($|k7i,_Z=VM$}Z~;l4S% =YSXgldC B(');
 define('SECURE_AUTH_KEY',  'W=0)rWQyzj|2-PT|GT_77kds`%?/,9|y!J3iM!x>7c*#$ML%k/;q|mg-K*_``_RI');
 define('LOGGED_IN_KEY',    '|#>X&xlTnYy=7 ipqI7<8rg9qZ@or614LRw|Hdu,NQmMhnQ9-M?}b]bGCxwpru{D');
 define('NONCE_KEY',        '+V1mEX|1^Ay)wV*[;T 2.YLh(0*N6J>g~C3=X+b<~%8@R./[BQs#Gu+$+7^c/d7N');
 define('AUTH_SALT',        '0~A@xt^}/3Z}qcd=qHn5-{q+fUnbbBgvm0]JG=`z3U+jzj9*&@[GxvNt-3l<Dv5+');
 define('SECURE_AUTH_SALT', 'P3};-|.AxY%!_pp&Kf|x-O$Wc(7{|b>kgl(kMS&|NQ~6IZP:/Q@|R:O}d2,HOrJp');
 define('LOGGED_IN_SALT',   'gO$Y0.jWKyGUK~a[Ru|P(MybJO$|KM3TxEXdQNjq}NBI%AC{Ue8pZAer+-eY)#CN');
 define('NONCE_SALT',       'J(~e5/v0-a6c:j<iB)+tj$dGMZZOhz21/*L4sl,g}qJ@S`);6L{.u5[o1}Hfkzoq');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = getenv('DB_TABLE_PREFIX');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
//@ini_set('log_errors','On'); // enable or disable php error logging (use 'On' or 'Off')
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', false);
define('SCRIPT_DEBUG', false);
define('SAVEQUERIES', false);
define('WP_ALLOW_REPAIR', false);
define('WP_MEMORY_LIMIT', '256M');

define('RELOCATE',true);


/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
