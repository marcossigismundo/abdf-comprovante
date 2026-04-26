<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ABDF_Admin {

	public static function init() {
		add_action( 'admin_menu',         array( __CLASS__, 'register_menus' ) );
		add_action( 'admin_init',         array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_abdf_save_member',   array( __CLASS__, 'handle_save_member' ) );
		add_action( 'admin_post_abdf_delete_member', array( __CLASS__, 'handle_delete_member' ) );
		add_action( 'admin_post_abdf_import_csv',    array( __CLASS__, 'handle_import_csv' ) );
	}

	public static function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'abdf-' ) === false ) { return; }
		wp_enqueue_style( 'abdf-admin', ABDF_URL . 'assets/css/admin.css', array(), ABDF_VERSION );
		wp_enqueue_script( 'abdf-admin', ABDF_URL . 'assets/js/admin.js', array( 'jquery' ), ABDF_VERSION, true );
	}

	public static function register_menus() {
		add_menu_page(
			__( 'ABDF Comprovantes', 'abdf-comprovante' ),
			__( 'ABDF', 'abdf-comprovante' ),
			'manage_options',
			'abdf-members',
			array( __CLASS__, 'page_members' ),
			'dashicons-id-alt',
			58
		);
		add_submenu_page( 'abdf-members', __( 'Associados(as)', 'abdf-comprovante' ), __( 'Associados(as)', 'abdf-comprovante' ),
			'manage_options', 'abdf-members', array( __CLASS__, 'page_members' ) );
		add_submenu_page( 'abdf-members', __( 'Importar CSV', 'abdf-comprovante' ), __( 'Importar CSV', 'abdf-comprovante' ),
			'manage_options', 'abdf-import', array( __CLASS__, 'page_import' ) );
		add_submenu_page( 'abdf-members', __( 'Comprovantes Emitidos', 'abdf-comprovante' ), __( 'Comprovantes', 'abdf-comprovante' ),
			'manage_options', 'abdf-certificates', array( __CLASS__, 'page_certificates' ) );
		add_submenu_page( 'abdf-members', __( 'Acessos / Log', 'abdf-comprovante' ), __( 'Log de Acessos', 'abdf-comprovante' ),
			'manage_options', 'abdf-log', array( __CLASS__, 'page_log' ) );
		add_submenu_page( 'abdf-members', __( 'Configurações', 'abdf-comprovante' ), __( 'Configurações', 'abdf-comprovante' ),
			'manage_options', 'abdf-settings', array( __CLASS__, 'page_settings' ) );
	}

	public static function register_settings() {
		register_setting( 'abdf_settings_group', 'abdf_settings', array( __CLASS__, 'sanitize_settings' ) );
	}

	public static function sanitize_settings( $input ) {
		$out = array();
		$out['association_name']     = sanitize_text_field( $input['association_name'] ?? '' );
		$out['cnpj']                 = sanitize_text_field( $input['cnpj'] ?? '' );
		$out['address']              = sanitize_textarea_field( $input['address'] ?? '' );
		$out['phones']               = sanitize_text_field( $input['phones'] ?? '' );
		$out['certificate_template'] = wp_kses_post( $input['certificate_template'] ?? '' );
		$out['logo_url']             = esc_url_raw( $input['logo_url'] ?? '' );
		$out['contact_email']        = sanitize_email( $input['contact_email'] ?? '' );
		$out['recaptcha_site']       = sanitize_text_field( $input['recaptcha_site'] ?? '' );
		$out['recaptcha_secret']     = sanitize_text_field( $input['recaptcha_secret'] ?? '' );
		$out['recaptcha_min_score']  = (float) ( $input['recaptcha_min_score'] ?? 0.5 );
		$out['verify_page_id']       = (int)   ( $input['verify_page_id'] ?? 0 );
		return $out;
	}

	public static function page_members() {
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';
		if ( in_array( $action, array( 'edit', 'add' ), true ) ) {
			$member = null;
			if ( 'edit' === $action && isset( $_GET['id'] ) ) {
				$member = ABDF_Members::get( (int) $_GET['id'] );
			}
			include ABDF_DIR . 'templates/admin/member-form.php';
		} else {
			$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			$members = ABDF_Members::all( array( 'search' => $search ) );
			include ABDF_DIR . 'templates/admin/members.php';
		}
	}

	public static function page_import() {
		include ABDF_DIR . 'templates/admin/import.php';
	}

	public static function page_certificates() {
		global $wpdb;
		$ct = ABDF_Database::table( 'certificates' );
		$mt = ABDF_Database::table( 'members' );
		$rows = $wpdb->get_results( "SELECT c.*, m.full_name, m.email FROM {$ct} c LEFT JOIN {$mt} m ON m.id = c.member_id ORDER BY c.issued_at DESC LIMIT 200" );
		include ABDF_DIR . 'templates/admin/certificates.php';
	}

	public static function page_log() {
		global $wpdb;
		$lt = ABDF_Database::table( 'access_log' );
		$rows = $wpdb->get_results( "SELECT * FROM {$lt} ORDER BY created_at DESC LIMIT 200" );
		include ABDF_DIR . 'templates/admin/log.php';
	}

	public static function page_settings() {
		$settings = get_option( 'abdf_settings', array() );
		include ABDF_DIR . 'templates/admin/settings.php';
	}

	public static function handle_save_member() {
		check_admin_referer( 'abdf_save_member' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }

		$id = ABDF_Members::upsert( array(
			'id'                => $_POST['id'] ?? 0,
			'full_name'         => $_POST['full_name'] ?? '',
			'email'             => $_POST['email'] ?? '',
			'cpf'               => $_POST['cpf'] ?? '',
			'membership_status' => $_POST['membership_status'] ?? 'active',
			'paid_until'        => $_POST['paid_until'] ?? '',
			'notes'             => $_POST['notes'] ?? '',
		) );

		wp_safe_redirect( admin_url( 'admin.php?page=abdf-members&saved=' . $id ) );
		exit;
	}

	public static function handle_delete_member() {
		check_admin_referer( 'abdf_delete_member' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }
		$id = (int) ( $_REQUEST['id'] ?? 0 );
		if ( $id ) { ABDF_Members::delete( $id ); }
		wp_safe_redirect( admin_url( 'admin.php?page=abdf-members&deleted=1' ) );
		exit;
	}

	public static function handle_import_csv() {
		check_admin_referer( 'abdf_import_csv' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }
		if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=abdf-import&error=nofile' ) );
			exit;
		}
		$result = ABDF_Members::import_csv( $_FILES['csv_file']['tmp_name'] );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=abdf-import&error=' . urlencode( $result->get_error_code() ) ) );
			exit;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=abdf-import&created=' . $result['created'] . '&updated=' . $result['updated'] . '&skipped=' . $result['skipped'] ) );
		exit;
	}
}
