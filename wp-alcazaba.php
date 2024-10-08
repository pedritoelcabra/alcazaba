<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Plugin_Name
 *
 * @wordpress-plugin
 * Plugin Name:       Gestionador de partidas Alcazaba
 * Plugin URI:        http://alcazabadejuegos.es
 * Description:       Un plugin para gestionar partidas.
 * Version:           1.0.0
 * Author:            La Alcazaba
 * Author URI:        http://alcazabadejuegos.es
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-alcazaba
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
ob_start();


/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('PLUGIN_NAME_VERSION', '1.0.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-alcazaba-activator.php
 */
function activate_plugin_name()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-wp-alcazaba-activator.php';
    Plugin_Name_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-alcazaba-deactivator.php
 */
function deactivate_plugin_name()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-wp-alcazaba-deactivator.php';
    Plugin_Name_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_plugin_name');
register_deactivation_hook(__FILE__, 'deactivate_plugin_name');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'vendor/autoload.php';
require plugin_dir_path(__FILE__) . 'includes/class-wp-alcazaba.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/GameList.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/Statistics.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/IsBggItem.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/GameRegister.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/BoardgameRepository.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/UserDataRepository.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/BggDataRepository.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/Boardgame.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/TemplateParser.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/GameCron.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/Game.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/GoogleSync.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/GamePlayer.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/GameRepository.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/GamePlayerRepository.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/Logger.php';
require plugin_dir_path(__FILE__) . 'includes/Alcazaba/TelegramBot/TelegramBot.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_plugin_name()
{
    $plugin = new Plugin_Name();
    $plugin->run();
}

run_plugin_name();