<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ABDF_Database {

	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'abdf_' . $name;
	}

	public static function install() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		$members      = self::table( 'members' );
		$certificates = self::table( 'certificates' );
		$logs         = self::table( 'access_log' );

		$sql = array();

		$sql[] = "CREATE TABLE {$members} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			full_name VARCHAR(255) NOT NULL,
			email VARCHAR(255) NOT NULL,
			cpf VARCHAR(14) DEFAULT NULL,
			membership_status VARCHAR(20) NOT NULL DEFAULT 'active',
			paid_until DATE DEFAULT NULL,
			notes TEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_email (email),
			KEY idx_name (full_name(100)),
			KEY idx_status (membership_status)
		) {$charset};";

		$sql[] = "CREATE TABLE {$certificates} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			member_id BIGINT UNSIGNED NOT NULL,
			certificate_number VARCHAR(32) NOT NULL,
			verification_hash VARCHAR(64) NOT NULL,
			issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			issued_for_year SMALLINT NOT NULL,
			issued_ip VARCHAR(45) DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_number (certificate_number),
			KEY idx_member (member_id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$logs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			ip_address VARCHAR(45) DEFAULT NULL,
			search_term VARCHAR(255) DEFAULT NULL,
			success TINYINT(1) NOT NULL DEFAULT 0,
			member_id BIGINT UNSIGNED DEFAULT NULL,
			user_agent VARCHAR(255) DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_ip (ip_address),
			KEY idx_created (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		update_option( 'abdf_db_version', ABDF_DB_VERSION );
	}

	public static function uninstall() {
		global $wpdb;
		$tables = array( self::table( 'access_log' ), self::table( 'certificates' ), self::table( 'members' ) );
		foreach ( $tables as $t ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$t}" );
		}
		delete_option( 'abdf_db_version' );
		delete_option( 'abdf_settings' );
		delete_option( 'abdf_seq_counter' );
	}
}
