<?php
/**
 * Plugin Name: ABDF — Comprovante de Situação Cadastral
 * Plugin URI:  https://abdf.org.br/
 * Description: Permite que associados(as) da ABDF gerem o comprovante em PDF de que estão em dia com a anuidade. Inclui cadastro de associados, importação por CSV, verificação pública por número e proteções contra bots.
 * Version:     1.0.0
 * Author:      ABDF / desenvolvido com Claude Code
 * Text Domain: abdf-comprovante
 * Domain Path: /languages
 * License:     GPL v2 or later
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'ABDF_VERSION', '1.0.0' );
define( 'ABDF_FILE', __FILE__ );
define( 'ABDF_DIR', plugin_dir_path( __FILE__ ) );
define( 'ABDF_URL', plugin_dir_url( __FILE__ ) );
define( 'ABDF_DB_VERSION', '1.0.0' );

require_once ABDF_DIR . 'includes/class-database.php';
require_once ABDF_DIR . 'includes/class-members.php';
require_once ABDF_DIR . 'includes/class-security.php';
require_once ABDF_DIR . 'includes/class-certificates.php';
require_once ABDF_DIR . 'includes/class-pdf.php';
require_once ABDF_DIR . 'includes/class-shortcode.php';
require_once ABDF_DIR . 'includes/class-rest.php';
require_once ABDF_DIR . 'includes/class-admin.php';
require_once ABDF_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'ABDF_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ABDF_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'ABDF_Plugin', 'instance' ) );
