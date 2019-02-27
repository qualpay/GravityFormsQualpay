<?php
/**
 * @wordpress-plugin
 * Plugin Name: Gravity Forms Qualpay Add-On
 * Plugin URI: https://www.qualpay.com
 * Description: Integrate Gravity Forms with Qualpay
 * Version: 1.2.1
 * Author: gravity+ for Qualpay
 * Author URI: https://qualpay.com
 * Text Domain: gravityformsqualpay
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package   GFP_Qualpay
 * @version   1.2.1
 * @author    gravity+ <support@gravityplus.pro>
 * @license   GPL-2.0+
 * @link      https://gravityplus.pro
 * @copyright 2018 gravity+
 *
 * last updated: feb-27, 2019, wordpress version update
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {

	die;

}

define( 'GFP_QUALPAY_CURRENT_VERSION', '1.2' );

define( 'GFP_QUALPAY_FILE', __FILE__ );

define( 'GFP_QUALPAY_PATH', plugin_dir_path( __FILE__ ) );

define( 'GFP_QUALPAY_URL', plugin_dir_url( __FILE__ ) );

define( 'GFP_QUALPAY_SLUG', plugin_basename( dirname( __FILE__ ) ) );


require_once( 'includes/class-loader.php' );


GFP_Qualpay_Loader::load();

$gravityformsqualpay = new GFP_Qualpay();

$gravityformsqualpay->run();