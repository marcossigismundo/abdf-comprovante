<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ABDF_REST {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'init',          array( __CLASS__, 'maybe_handle_download' ) );
	}

	public static function register_routes() {
		register_rest_route( 'abdf/v1', '/issue', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_issue' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'abdf/v1', '/verify', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_verify' ),
			'permission_callback' => '__return_true',
		) );
	}

	public static function handle_issue( WP_REST_Request $req ) {
		$params = $req->get_json_params();
		if ( empty( $params ) ) { $params = $req->get_params(); }

		$term  = isset( $params['term'] ) ? trim( wp_unslash( (string) $params['term'] ) ) : '';
		$consent = ! empty( $params['consent'] );

		// Honeypot + min-time
		$bot_check = ABDF_Security::check_bot_signals( $params );
		if ( is_wp_error( $bot_check ) ) {
			ABDF_Security::log( $term, false );
			return new WP_REST_Response( array( 'success' => false, 'message' => $bot_check->get_error_message() ), 400 );
		}

		// Rate limit
		$rl = ABDF_Security::check_rate_limit( ABDF_Security::client_ip() );
		if ( is_wp_error( $rl ) ) {
			ABDF_Security::log( $term, false );
			return new WP_REST_Response( array( 'success' => false, 'message' => $rl->get_error_message() ), 429 );
		}

		// reCAPTCHA opcional
		$rc = ABDF_Security::maybe_check_recaptcha( isset( $params['recaptcha'] ) ? $params['recaptcha'] : '' );
		if ( is_wp_error( $rc ) ) {
			ABDF_Security::log( $term, false );
			return new WP_REST_Response( array( 'success' => false, 'message' => $rc->get_error_message() ), 400 );
		}

		if ( ! $consent ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'É necessário concordar com o termo de uso dos dados.', 'abdf-comprovante' ) ), 400 );
		}
		if ( strlen( $term ) < 4 ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Informe seu nome completo ou e-mail.', 'abdf-comprovante' ) ), 400 );
		}

		$member = ABDF_Members::find( $term );
		if ( ! $member ) {
			ABDF_Security::log( $term, false );
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Não localizamos um cadastro correspondente. Verifique seu nome completo ou e-mail. Em caso de dúvida, contate a secretaria.', 'abdf-comprovante' ) ), 404 );
		}

		if ( ! ABDF_Certificates::is_member_eligible( $member ) ) {
			ABDF_Security::log( $term, false, $member->id );
			return new WP_REST_Response( array(
				'success' => false,
				'message' => __( 'Localizamos seu cadastro, porém ele não consta como em dia. Por favor, contate a secretaria da ABDF.', 'abdf-comprovante' ),
			), 403 );
		}

		$year = (int) date( 'Y' );
		// Reaproveita um certificado emitido nos últimos 30 minutos para evitar duplicação por F5.
		$cert = ABDF_Certificates::recent_for_member( $member->id, $year );
		if ( ! $cert ) {
			$cert = ABDF_Certificates::issue( $member, $year );
		}

		ABDF_Security::log( $term, true, $member->id );

		$download_url = add_query_arg( array(
			'abdf_download' => $cert->certificate_number,
			'h'             => substr( $cert->verification_hash, 0, 12 ),
		), home_url( '/' ) );

		return new WP_REST_Response( array(
			'success'      => true,
			'member_name'  => $member->full_name,
			'cert_number'  => $cert->certificate_number,
			'download_url' => $download_url,
			'message'      => __( 'Comprovante gerado com sucesso.', 'abdf-comprovante' ),
		), 200 );
	}

	public static function handle_verify( WP_REST_Request $req ) {
		$params = $req->get_json_params();
		if ( empty( $params ) ) { $params = $req->get_params(); }

		$number = isset( $params['number'] ) ? trim( wp_unslash( (string) $params['number'] ) ) : '';
		$bot    = ABDF_Security::check_bot_signals( $params );
		if ( is_wp_error( $bot ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $bot->get_error_message() ), 400 );
		}
		if ( ! $number ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Informe o número do comprovante.', 'abdf-comprovante' ) ), 400 );
		}

		$cert = ABDF_Certificates::find_by_number( $number );
		if ( ! $cert ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Número não localizado.', 'abdf-comprovante' ) ), 404 );
		}

		$member = ABDF_Members::get( $cert->member_id );
		if ( ! $member ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Comprovante órfão — registro de associado removido.', 'abdf-comprovante' ) ), 404 );
		}

		// Mostra apenas o primeiro nome + iniciais para preservar privacidade.
		$parts = preg_split( '/\s+/', trim( $member->full_name ) );
		$first = array_shift( $parts );
		$initials = '';
		foreach ( $parts as $p ) { $initials .= mb_substr( $p, 0, 1 ) . '. '; }
		$display_name = trim( $first . ' ' . $initials );

		return new WP_REST_Response( array(
			'success'     => true,
			'cert_number' => $cert->certificate_number,
			'name'        => $display_name,
			'year'        => (int) $cert->issued_for_year,
			'issued_at'   => $cert->issued_at,
			'still_valid' => ABDF_Certificates::is_member_eligible( $member ),
		), 200 );
	}

	public static function maybe_handle_download() {
		if ( empty( $_GET['abdf_download'] ) ) { return; }

		$number = sanitize_text_field( wp_unslash( $_GET['abdf_download'] ) );
		$hint   = isset( $_GET['h'] ) ? sanitize_text_field( wp_unslash( $_GET['h'] ) ) : '';

		$cert = ABDF_Certificates::find_by_number( $number );
		if ( ! $cert ) { wp_die( __( 'Comprovante não encontrado.', 'abdf-comprovante' ), 404 ); }

		// Pequena verificação: o hash-prefix evita scraping sequencial.
		if ( ! $hint || substr( $cert->verification_hash, 0, 12 ) !== $hint ) {
			wp_die( __( 'Link de download inválido.', 'abdf-comprovante' ), 403 );
		}

		$member = ABDF_Members::get( $cert->member_id );
		if ( ! $member ) { wp_die( __( 'Associado não encontrado.', 'abdf-comprovante' ), 404 ); }

		$pdf = ABDF_PDF::render( $member, $cert );

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="comprovante-abdf-' . $cert->certificate_number . '.pdf"' );
		header( 'Content-Length: ' . strlen( $pdf ) );
		echo $pdf;
		exit;
	}
}
