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

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $tablePartidas = $wpdb->prefix . "partidas_alcazaba";
        $tableJugadores = $wpdb->prefix . "jugadores_alcazaba";
        $tableUsers = $wpdb->prefix . "users";
        $charset_collate = $wpdb->get_charset_collate();

        $sql = <<<EOF
CREATE TABLE IF NOT EXISTS $tablePartidas (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      created_on datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      created_by bigint(20) NOT NULL,
      bgg_id bigint(20) DEFAULT NULL,
      gcal_id varchar(255) DEFAULT NULL,
      start_time datetime NOT NULL,
      name varchar(55) NOT NULL,
      joinable TINYINT(1) DEFAULT FALSE,
      max_players TINYINT(2) DEFAULT 0,
      PRIMARY KEY  (id)
    ) $charset_collate;
CREATE TABLE IF NOT EXISTS $tableJugadores (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      created_on datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      player_id bigint(20) unsigned NOT NULL,
      game_id mediumint(9) NOT NULL,
      amount TINYINT(2) DEFAULT 1,
      PRIMARY KEY  (id),
      CONSTRAINT `fk_player`
        FOREIGN KEY (player_id) REFERENCES $tableUsers (ID)
        ON DELETE CASCADE
        ON UPDATE RESTRICT,
      CONSTRAINT `fk_game`
        FOREIGN KEY (game_id) REFERENCES $tablePartidas (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
    ) $charset_collate;
EOF;
        dbDelta($sql);
    }
}
