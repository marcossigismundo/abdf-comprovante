<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ABDF_Certificates {

	public static function issue( $member, $year = null ) {
		global $wpdb;
		$table = ABDF_Database::table( 'certificates' );
		$year  = $year ? (int) $year : (int) date( 'Y' );

		$number = self::next_number( $year );
		$hash   = wp_generate_password( 24, false, false );

		$wpdb->insert( $table, array(
			'member_id'         => (int) $member->id,
			'certificate_number'=> $number,
			'verification_hash' => $hash,
			'issued_at'         => current_time( 'mysql' ),
			'issued_for_year'   => $year,
			'issued_ip'         => ABDF_Security::client_ip(),
		) );
		return self::get( $wpdb->insert_id );
	}

	public static function get( $id ) {
		global $wpdb;
		$table = ABDF_Database::table( 'certificates' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
	}

	public static function find_by_number( $number ) {
		global $wpdb;
		$table = ABDF_Database::table( 'certificates' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE certificate_number = %s", $number ) );
	}

	public static function recent_for_member( $member_id, $year ) {
		global $wpdb;
		$table = ABDF_Database::table( 'certificates' );
		// Reaproveita certificados emitidos nos últimos 30 minutos (evita duplicação por F5).
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table}
			 WHERE member_id = %d AND issued_for_year = %d
			 AND issued_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
			 ORDER BY issued_at DESC LIMIT 1",
			(int) $member_id, (int) $year
		) );
	}

	public static function next_number( $year ) {
		$counter = (int) get_option( 'abdf_seq_counter_' . $year, 0 );
		$counter++;
		update_option( 'abdf_seq_counter_' . $year, $counter );
		return sprintf( '%d-%05d', $year, $counter );
	}

	public static function is_member_eligible( $member ) {
		if ( ! $member ) { return false; }
		if ( 'active' !== $member->membership_status ) { return false; }
		if ( $member->paid_until ) {
			$today = date( 'Y-m-d' );
			if ( $member->paid_until < $today ) { return false; }
		}
		return true;
	}
}
