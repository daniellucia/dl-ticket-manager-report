<?php

/**
 * Plugin Name: Reports for Ticket Manager
 * Description: Informes para el gestor de tickets.
 * Version: 0.0.1
 * Author: Daniel Lúcia
 * Author URI: http://www.daniellucia.es
 * textdomain: dl-ticket-manager-report
 * Requires Plugins: dl-ticket-manager
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/src/Plugin.php';

add_action('plugins_loaded', function () {

    load_plugin_textdomain('dl-ticket-manager-report', false, dirname(plugin_basename(__FILE__)) . '/languages');

    $plugin = new TMReportPlugin();
    $plugin->init();
});
