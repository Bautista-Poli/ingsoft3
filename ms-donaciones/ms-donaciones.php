<?php
/**
 * Plugin Name: MS Donaciones
 * Description: Formulario de donaciones para Módulo Sanitario.
 * Version: 1.1.0
 * Author: Equipo Ingeniería en Informática UCA
 * Text Domain: ms-donaciones
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MS_DONACIONES_PATH', plugin_dir_path(__FILE__));
define('MS_DONACIONES_URL', plugin_dir_url(__FILE__));
define('MS_DONACIONES_VERSION', '1.1.0');

require_once MS_DONACIONES_PATH . 'includes/class-shortcodes.php';
require_once MS_DONACIONES_PATH . 'includes/class-rest.php';
require_once MS_DONACIONES_PATH . 'includes/class-admin.php';
require_once MS_DONACIONES_PATH . 'includes/class-about.php';

function ms_donaciones_init() {

    MS_Donaciones_Shortcodes::init();
    MS_Donaciones_REST::init();
    MS_Donaciones_Admin::init();
    MS_Donaciones_About::init();

}

add_action(
    'plugins_loaded',
    'ms_donaciones_init'
);

/*
|--------------------------------------------------------------------------
| Link "Ver equipo" debajo del plugin
|--------------------------------------------------------------------------
*/

add_filter(
    'plugin_row_meta',
    function ($links, $file) {

        if ($file !== plugin_basename(__FILE__)) {
            return $links;
        }

        $url = admin_url(
            'admin.php?page=ms-donaciones-equipo'
        );

        $links[] =
            '<a href="' .
            esc_url($url) .
            '">Ver equipo</a>';

        return $links;

    },
    10,
    2
);
