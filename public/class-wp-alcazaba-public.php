<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/public
 * @author     Your Name <email@example.com>
 */
class Plugin_Name_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Plugin_Name_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Plugin_Name_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/wp-alcazaba-public.css',
            [],
            $this->version,
            'all'
        );
        wp_enqueue_style(
            'flatpickr_css',
            plugin_dir_url(__FILE__) . 'css/flatpickr.min.css',
            [],
            $this->version,
            'all'
        );
        wp_enqueue_style('jquery_ui', plugin_dir_url(__FILE__) . 'css/jquery-ui.min.css', [], $this->version, 'all');
        wp_enqueue_style(
            'google_fonts',
            'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined',
            [],
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Plugin_Name_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Plugin_Name_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/wp-alcazaba-public.js',
            ['jquery'],
            $this->version,
            false
        );
        wp_enqueue_script('flatpickr_js', plugin_dir_url(__FILE__) . 'js/flatpickr.js', [], $this->version, false);
        wp_enqueue_script('flatpickr_es', plugin_dir_url(__FILE__) . 'js/es.js', [], $this->version, false);
        wp_enqueue_script('jquery_ui', plugin_dir_url(__FILE__) . 'js/jquery-ui.min.js', [], $this->version, false);
    }

    public function registerShortcodes(): void
    {
        add_shortcode('lista_partidas', ['GameList', 'listGames']);
        add_shortcode('top_partidas', ['GameList', 'topGames']);
        add_shortcode('ludoteca', ['GameRegister', 'ludoteca']);
        add_shortcode('crear_partida', ['GameList', 'createGameForm']);
        add_shortcode('alcazaba_stats', ['Statistics', 'stats']);

        add_action('wp_ajax_search_game', ['GameList', 'ajaxListGames']);
        add_action('wp_ajax_nopriv_search_game', ['GameList', 'ajaxListGames']);
        add_action('wp_ajax_telegram_bot', ['TelegramBot', 'execute']);
        add_action('wp_ajax_nopriv_telegram_bot', ['TelegramBot', 'execute']);

        add_action('al_cron_hook', ['GameCron', 'cron']);
        add_action('al_cron_hook_daily', ['GameCron', 'dailyCron']);

        add_filter( 'cron_schedules', 'alAddMinuteSchedule' );
    }
}

function alAddMinuteSchedule( $schedules ) {
    $schedules['minutely'] = array(
        'interval' => 60, // One minute in seconds
        'display'  => __( 'Each Minute' ),
    );

    return $schedules;
}