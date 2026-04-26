<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ABDF_Security {

	const RATE_LIMIT_PER_HOUR = 8;   // tentativas por IP / hora
	const RATE_LIMIT_PER_DAY  = 30;  // tentativas por IP / dia
	const TRANSIENT_PREFIX    = 'abdf_rl_';

	public static function client_ip() {
		$candidates = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		foreach ( $candidates as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = explode( ',', $_SERVER[ $key ] )[0];
				$ip = trim( $ip );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) { return $ip; }
			}
		}
		return '0.0.0.0';
	}

	public static function check_rate_limit( $ip ) {
		$hour_key = self::TRANSIENT_PREFIX . 'h_' . md5( $ip );
		$day_key  = self::TRANSIENT_PREFIX . 'd_' . md5( $ip );

		$hour = (int) get_transient( $hour_key );
		$day  = (int) get_transient( $day_key );

		if ( $hour >= self::RATE_LIMIT_PER_HOUR ) {
			return new WP_Error( 'rate_limit_hour', __( 'Muitas tentativas nesta hora. Tente novamente mais tarde.', 'abdf-comprovante' ) );
		}
		if ( $day >= self::RATE_LIMIT_PER_DAY ) {
			return new WP_Error( 'rate_limit_day', __( 'Limite diário de tentativas excedido para este IP.', 'abdf-comprovante' ) );
		}

		set_transient( $hour_key, $hour + 1, HOUR_IN_SECONDS );
		set_transient( $day_key, $day + 1, DAY_IN_SECONDS );
		return true;
	}

	/**
	 * Honeypot: o campo escondido (`abdf_website`) deve ficar vazio.
	 * Min-time: o formulário deve ter sido apresentado há ≥ 2s.
	 */
	public static function check_bot_signals( $request ) {
		if ( ! empty( $request['abdf_website'] ) ) {
			return new WP_Error( 'honeypot', __( 'Detecção de bot.', 'abdf-comprovante' ) );
		}
		$rendered_at = isset( $request['abdf_rendered_at'] ) ? (int) $request['abdf_rendered_at'] : 0;
		if ( $rendered_at && ( time() - $rendered_at ) < 2 ) {
			return new WP_Error( 'too_fast', __( 'Envio rápido demais — tente novamente.', 'abdf-comprovante' ) );
		}
		return true;
	}

	public static function maybe_check_recaptcha( $token ) {
		$settings = get_option( 'abdf_settings', array() );
		$secret   = isset( $settings['recaptcha_secret'] ) ? trim( $settings['recaptcha_secret'] ) : '';
		if ( ! $secret ) { return true; } // recaptcha desativado

		if ( ! $token ) {
			return new WP_Error( 'no_recaptcha', __( 'Validação reCAPTCHA ausente.', 'abdf-comprovante' ) );
		}
		$response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
			'timeout' => 8,
			'body'    => array(
				'secret'   => $secret,
				'response' => $token,
				'remoteip' => self::client_ip(),
			),
		) );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'recaptcha_unreachable', __( 'Falha de comunicação com reCAPTCHA.', 'abdf-comprovante' ) );
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$min  = isset( $settings['recaptcha_min_score'] ) ? (float) $settings['recaptcha_min_score'] : 0.5;
		if ( empty( $body['success'] ) || ( isset( $body['score'] ) && $body['score'] < $min ) ) {
			return new WP_Error( 'recaptcha_failed', __( 'Validação reCAPTCHA não aprovada.', 'abdf-comprovante' ) );
		}
		return true;
	}

	public static function log( $term, $success, $member_id = null ) {
		global $wpdb;
		$wpdb->insert( ABDF_Database::table( 'access_log' ), array(
			'ip_address' => self::client_ip(),
			'search_term'=> mb_substr( (string) $term, 0, 250 ),
			'success'    => $success ? 1 : 0,
			'member_id'  => $member_id ? (int) $member_id : null,
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? mb_substr( wp_strip_all_tags( $_SERVER['HTTP_USER_AGENT'] ), 0, 250 ) : null,
			'created_at' => current_time( 'mysql' ),
		) );
	}
}
