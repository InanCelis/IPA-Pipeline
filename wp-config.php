<?php
define( 'WP_CACHE', true ); // Added by WP Rocket

 // Added by WP Rocket

 // Added by WP Rocket

 // Added by WP Rocket

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

/** Enable W3 Total Cache */
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */


ini_set('memory_limit', '512M');
ini_set('max_input_vars', '10000');
ini_set('max_input_time', '7200');
// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'internationalpropertyalertscom');
/** Database username */
define( 'DB_USER', 'internationalpropertyalertscom');
/** Database password */
define( 'DB_PASSWORD', 'p3Fo3f1KlpiE6JF');
/** Database hostname */
define( 'DB_HOST', 'localhost');
/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );
/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

define('WP_MAX_MEMORY_LIMIT', '512M');
define('WP_MEMORY_LIMIT', '512M');

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '9fzeirooolg4ec5fapkikymfms9fprh3zc7zwudmkglsrrocnhwehn4rlkeaedfs' );
define( 'SECURE_AUTH_KEY',  '47ugxvbhqlzqontfm9vanybllpveg5gf27youo63atzhkhqxusy4w4ivxpjxm7nz' );
define( 'LOGGED_IN_KEY',    'wmj5y69iz14i14n6h55ejem7ryze9rpfgv3r4px0tjkn9rf9vgxiwe5tfyu4kqs5' );
define( 'NONCE_KEY',        'cvai4egak7aalfg9a7zoawnuwsqwt3xaih1o4c63h546av2kx7ps85wp947ankg0' );
define( 'AUTH_SALT',        'thx45qh81wj5bgsozzsej09i32zn857m7uwsv43kquhwexk5zkt4hkjhgdrebllf' );
define( 'SECURE_AUTH_SALT', 'krrcjpsqx10pyjq8wdifytwugjpzzplrcd0yividxaff6cvwij7qbqie8w3jtvw4' );
define( 'LOGGED_IN_SALT',   'xoyqddthypbrpo2bb0vesht00ifjes5oipuxlnebvawspidpnhviotso5fzjnhex' );
define( 'NONCE_SALT',       'po6gt5js5ta3pvolq2hewxpbqo1vp2vsya9mj3st1yadkoluxzpxjpjrlzfczfn7' );
/**#@-*/
/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wpm9_';
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
/* Add any custom values between this line and the "stop editing" line. */
/* That's all, stop editing! Happy publishing. */
/** Absolute path to the WordPress directory. */






if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
set_time_limit(7200);
ini_set( 'upload_max_filesize', '64M' );
ini_set( 'post_max_size', '64M' );
ini_set( 'max_execution_time', '7200' );
