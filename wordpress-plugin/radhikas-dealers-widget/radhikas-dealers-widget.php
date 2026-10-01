<?php
/**
 * Plugin Name: Radhikas Dealers Directory for Elementor
 * Plugin URI:  https://radhikastradeintl.com
 * Description: Displays authorized dealers and special dealers from Radhikas ERP API with live search, district filters, pagination, and modern responsive grid cards in Elementor.
 * Version:     1.0.0
 * Author:      Radhikas Trade International
 * Author URI:  https://radhikastradeintl.com
 * Text Domain: radhikas-dealers
 * License:     GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('RADHIKAS_DEALERS_VERSION', '1.0.0');
define('RADHIKAS_DEALERS_PATH', plugin_dir_path(__FILE__));
define('RADHIKAS_DEALERS_URL', plugin_dir_url(__FILE__));

/**
 * Main Radhikas Dealers Plugin Class
 */
final class Radhikas_Dealers_Plugin {

    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
        add_action('init', [$this, 'handle_statement_short_url']);
    }

    public function init() {
        // Check if Elementor is installed and active
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_main_plugin']);
            return;
        }

        // Register custom Elementor widget category
        add_action('elementor/elements/categories_registered', [$this, 'add_elementor_widget_categories']);

        // Register Elementor Widget
        add_action('elementor/widgets/register', [$this, 'register_widgets']);

        // Register Frontend Scripts & Styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Register Shortcode as fallback for non-Elementor pages
        add_shortcode('radhikas_dealers', [$this, 'render_shortcode']);
    }

    public function admin_notice_missing_main_plugin() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'radhikas-dealers'),
            '<strong>' . esc_html__('Radhikas Dealers Directory', 'radhikas-dealers') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'radhikas-dealers') . '</strong>'
        );
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    public function add_elementor_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'radhikas-widgets',
            [
                'title' => esc_html__('Radhikas ERP Widgets', 'radhikas-dealers'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    public function register_widgets($widgets_manager) {
        require_once RADHIKAS_DEALERS_PATH . 'includes/class-radhikas-dealers-widget.php';
        $widgets_manager->register(new \Radhikas_Dealers_Widget());
    }

    public function enqueue_frontend_assets() {
        wp_register_style(
            'radhikas-dealers-css',
            RADHIKAS_DEALERS_URL . 'assets/css/dealers-widget.css',
            [],
            RADHIKAS_DEALERS_VERSION
        );

        wp_register_script(
            'radhikas-dealers-js',
            RADHIKAS_DEALERS_URL . 'assets/js/dealers-widget.js',
            [],
            RADHIKAS_DEALERS_VERSION,
            true
        );
    }

    public function render_shortcode($atts) {
        // Enqueue assets
        wp_enqueue_style('radhikas-dealers-css');
        wp_enqueue_script('radhikas-dealers-js');

        $atts = shortcode_atts([
            'api_url' => 'https://erp.radhikastradeintl.com/api/dealers',
            'per_page' => 12,
            'columns' => 3,
            'default_type' => 'all',
            'show_search' => 'yes',
            'show_filter' => 'yes',
            'show_districts' => 'yes',
        ], $atts, 'radhikas_dealers');

        ob_start();
        $widget_id = 'rdw_' . wp_rand(1000, 9999);
        include RADHIKAS_DEALERS_PATH . 'templates/dealers-container.php';
        return ob_get_clean();
    }

    /**
     * Intercepts /s/{token} requests on WordPress and proxies the ERP PDF stream
     * so the user's browser stays on radhikastradeintl.com without revealing erp.
     */
    public function handle_statement_short_url() {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (preg_match('#^/s/([a-zA-Z0-9_-]{6,16})/?$#', $path, $matches)) {
            $token = sanitize_text_field($matches[1]);
            $erp_url = 'https://erp.radhikastradeintl.com/s/' . $token;

            $response = wp_remote_get($erp_url, [
                'timeout' => 25,
                'sslverify' => false,
                'headers' => ['Accept' => 'application/pdf']
            ]);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="Statement_' . $token . '.pdf"');
                header('Cache-Control: private, max-age=3600');
                echo wp_remote_retrieve_body($response);
                exit;
            }

            // Fallback: 302 redirect
            wp_redirect($erp_url, 302);
            exit;
        }
    }
}

Radhikas_Dealers_Plugin::instance();
