<?php
/**
 * @wordpress-plugin
 * Plugin Name: Qualpay Add-on for Gravity forms
 * Plugin URI: https://www.qualpay.com
 * Description: Qualpay Add-on for Gravity forms
 * Version: 1.3
 * Author: Qualpay
 * Author URI: https://qualpay.com
 * Text Domain: gravityformsqualpay
 * Domain Path: /languages
 * License:     GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @package   GFP_Qualpay
 * @version   1.3
 * @author    QUALPAY <support@qualpay.com>
 * @license   GPL-2.0+
 * @link      https://qualpay.com
 *
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {

	die;

}

define( 'GFP_QUALPAY_CURRENT_VERSION', '1.3' );

define( 'GFP_QUALPAY_FILE', __FILE__ );

define( 'GFP_QUALPAY_PATH', plugin_dir_path( __FILE__ ) );

define( 'GFP_QUALPAY_URL', plugin_dir_url( __FILE__ ) );

define( 'GFP_QUALPAY_SLUG', plugin_basename( dirname( __FILE__ ) ) );


require_once( 'includes/class-loader.php' );


GFP_Qualpay_Loader::load();

$gravityformsqualpay = new GFP_Qualpay();

$gravityformsqualpay->run();
//require_once 'includes/class-qualpay-webhook.php';