<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * Fired during plugin activation.
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */
class Plugin_Name_Activator
{
    public static function activate(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "partidas_alcazaba";
        $charset_collate = $wpdb->get_charset_collate();

        $sql = <<<EOF
CREATE TABLE IF NOT EXISTS $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      created_on datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      created_by bigint(20) NOT NULL,
      bgg_id bigint(20) DEFAULT NULL,
      start_time datetime NOT NULL,
      name varchar(55) NOT NULL,
      joinable TINYINT(1) DEFAULT FALSE,
      max_players TINYINT(2) DEFAULT 0,
      PRIMARY KEY  (id)
    ) $charset_collate;
EOF;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }
}
