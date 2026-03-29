<?php
/**
 * Plugin Name: Mailto Link Form
 * Description: Build configurable forms that redirect to a mailto: URL.
 * Version: 0.1.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: cum38898
 * License: GPL-2.0-or-later
 * Text Domain: mailto-link-form
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

define('MALIFO_VERSION', '0.1.2');
define('MALIFO_PLUGIN_FILE', __FILE__);
define('MALIFO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MALIFO_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once MALIFO_PLUGIN_DIR . 'includes/helpers.php';
require_once MALIFO_PLUGIN_DIR . 'includes/class-malifo-admin.php';
require_once MALIFO_PLUGIN_DIR . 'includes/class-malifo-shortcode.php';
require_once MALIFO_PLUGIN_DIR . 'includes/class-malifo-submit.php';
require_once MALIFO_PLUGIN_DIR . 'includes/class-malifo-plugin.php';

MALIFO_Plugin::init();
