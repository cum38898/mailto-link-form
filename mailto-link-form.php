<?php
/**
 * Plugin Name: Mailto Link Form
 * Description: Build configurable forms that redirect to a mailto: URL.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: cum38898
 * License: GPL-2.0-or-later
 * Text Domain: mailto-link-form
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

define('WP_MAILTO_LINK_FORM_VERSION', '0.1.0');
define('WP_MAILTO_LINK_FORM_PLUGIN_FILE', __FILE__);
define('WP_MAILTO_LINK_FORM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_MAILTO_LINK_FORM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WP_MAILTO_LINK_FORM_PLUGIN_DIR . 'includes/helpers.php';
require_once WP_MAILTO_LINK_FORM_PLUGIN_DIR . 'includes/class-mlf-admin.php';
require_once WP_MAILTO_LINK_FORM_PLUGIN_DIR . 'includes/class-mlf-shortcode.php';
require_once WP_MAILTO_LINK_FORM_PLUGIN_DIR . 'includes/class-mlf-submit.php';
require_once WP_MAILTO_LINK_FORM_PLUGIN_DIR . 'includes/class-mlf-plugin.php';

MLF_Plugin::init();
