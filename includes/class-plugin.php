<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ABDF_Plugin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		load_plugin_textdomain( 'abdf-comprovante', false, dirname( plugin_basename( ABDF_FILE ) ) . '/languages' );

		ABDF_Shortcode::init();
		ABDF_REST::init();
		if ( is_admin() ) {
			ABDF_Admin::init();
		}

		add_action( 'wp_enqueue_scripts', array( 'ABDF_Shortcode', 'enqueue_assets' ) );
	}

	public static function activate() {
		ABDF_Database::install();
		ABDF_Members::seed_initial();

		// Cria página de verificação se ainda não existir
		$settings = get_option( 'abdf_settings', array() );
		if ( empty( $settings['verify_page_id'] ) ) {
			$page_id = wp_insert_post( array(
				'post_title'   => 'Verificar Comprovante',
				'post_name'    => 'verificar-comprovante',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '[abdf_verificar]',
			) );
			if ( $page_id && ! is_wp_error( $page_id ) ) {
				$settings['verify_page_id'] = (int) $page_id;
			}
		}
		// Texto-padrão do comprovante (caso ainda não esteja salvo)
		if ( empty( $settings['certificate_template'] ) ) {
			$settings['certificate_template'] = "Declaramos, para os devidos fins, que {NOME} é associado(a) regular da Associação dos Bibliotecários e Profissionais da Ciência da Informação do Distrito Federal — ABDF, encontrando-se em pleno gozo de seus direitos estatutários e em situação regular quanto ao pagamento da anuidade do exercício de {ANO}, conforme nossos registros internos.\n\nO presente comprovante é emitido eletronicamente e sua autenticidade pode ser conferida no endereço informado ao final deste documento, por meio do número de verificação correspondente.";
		}
		update_option( 'abdf_settings', $settings );
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
