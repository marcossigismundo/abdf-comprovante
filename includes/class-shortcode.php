<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ABDF_Shortcode {

	public static function init() {
		add_shortcode( 'abdf_comprovante', array( __CLASS__, 'render_form' ) );
		add_shortcode( 'abdf_verificar',   array( __CLASS__, 'render_verify' ) );
	}

	public static function enqueue_assets() {
		global $post;
		$has_form  = ( $post && has_shortcode( $post->post_content, 'abdf_comprovante' ) );
		$has_verify= ( $post && has_shortcode( $post->post_content, 'abdf_verificar' ) );
		if ( ! $has_form && ! $has_verify ) { return; }

		wp_enqueue_style(  'abdf-frontend', ABDF_URL . 'assets/css/frontend.css', array(), ABDF_VERSION );
		wp_enqueue_script( 'abdf-frontend', ABDF_URL . 'assets/js/frontend.js', array(), ABDF_VERSION, true );

		$settings = get_option( 'abdf_settings', array() );
		wp_localize_script( 'abdf-frontend', 'ABDF_DATA', array(
			'rest_url'        => esc_url_raw( rest_url( 'abdf/v1/issue' ) ),
			'verify_rest_url' => esc_url_raw( rest_url( 'abdf/v1/verify' ) ),
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'recaptcha_site'  => isset( $settings['recaptcha_site'] ) ? $settings['recaptcha_site'] : '',
			'rendered_at'     => time(),
			'i18n'            => array(
				'searching' => __( 'Verificando...', 'abdf-comprovante' ),
				'generic_error' => __( 'Ocorreu um erro. Tente novamente.', 'abdf-comprovante' ),
			),
		) );

		if ( ! empty( $settings['recaptcha_site'] ) ) {
			wp_enqueue_script( 'abdf-recaptcha',
				'https://www.google.com/recaptcha/api.js?render=' . urlencode( $settings['recaptcha_site'] ),
				array(), null, true );
		}
	}

	public static function render_form( $atts = array() ) {
		ob_start();
		include ABDF_DIR . 'templates/form.php';
		return ob_get_clean();
	}

	public static function render_verify( $atts = array() ) {
		ob_start();
		include ABDF_DIR . 'templates/verify.php';
		return ob_get_clean();
	}
}
