<?php

/**
 * Plugin Name: Reports for Ticket Manager
 * Description: Reports for the Ticket Manager.
 * Version: 0.0.1
 * Author: Daniel Lúcia
 * Author URI: http://www.daniellucia.es
 * textdomain: dl-ticket-manager-report
 * Requires Plugins: dl-ticket-manager
 */

use DL\TicketsReport\Plugin;

defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';

add_action('plugins_loaded', function () {

    load_plugin_textdomain('dl-ticket-manager-report', false, dirname(plugin_basename(__FILE__)) . '/languages');

    (new Plugin())->init();
});
