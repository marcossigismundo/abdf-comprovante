<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABDF_DIR . 'vendor/micropdf/micropdf.php';

class ABDF_PDF {

	public static function render( $member, $certificate ) {
		$settings = get_option( 'abdf_settings', array() );

		$association_name = isset( $settings['association_name'] ) ? $settings['association_name']
			: 'ASSOCIAÇÃO DOS BIBLIOTECÁRIOS E PROFISSIONAIS DA CIÊNCIA DA INFORMAÇÃO DO DF';
		$cnpj    = isset( $settings['cnpj'] ) ? $settings['cnpj'] : '00.109.942/0001-02';
		$address = isset( $settings['address'] ) ? $settings['address']
			: "SCRN 702/703, Bl. G, Ed. Coencisa, nº 49, sala 4\n70720-670, Brasília-DF";
		$phones = isset( $settings['phones'] ) ? $settings['phones']
			: 'Tel.: (61) 3327-7198    Celular: (61) 98592-8008';

		$year      = (int) $certificate->issued_for_year;
		$cert_no   = $certificate->certificate_number;
		$verify_url = self::verification_url( $certificate );
		$body_text = self::compose_body( $member, $year );

		$pdf = new ABDF_MicroPDF();

		// Cabeçalho — barra azul-marinho discreta no topo
		$pdf->set_fill_color( 24, 56, 102 );
		$pdf->rect( 0, 0, 210, 6, 'F' );

		// Cabeçalho institucional
		$top = 20;
		$pdf->set_text_color( 24, 56, 102 );
		$pdf->set_font( 'helvetica', 'B', 13 );
		$pdf->multi_text( 20, $top, 170, 6, $association_name, 'C' );

		$pdf->set_text_color( 60, 60, 60 );
		$pdf->set_font( 'helvetica', '', 10 );
		$pdf->text( 20, 38, "CNPJ: {$cnpj}", 'C', 170 );
		$pdf->multi_text( 20, 44, 170, 5, $address, 'C' );
		$pdf->text( 20, 56, $phones, 'C', 170 );

		// Linha separadora
		$pdf->set_draw_color( 24, 56, 102 );
		$pdf->line( 20, 64, 190, 64, 0.8 );

		// Título do documento
		$pdf->set_text_color( 24, 56, 102 );
		$pdf->set_font( 'helvetica', 'B', 18 );
		$pdf->text( 20, 80, 'COMPROVANTE DE SITUAÇÃO CADASTRAL', 'C', 170 );

		$pdf->set_text_color( 90, 90, 90 );
		$pdf->set_font( 'helvetica', '', 10 );
		$pdf->text( 20, 88, 'Documento referente ao exercício de ' . $year, 'C', 170 );

		// Caixa do número
		$pdf->set_draw_color( 200, 200, 200 );
		$pdf->set_fill_color( 246, 248, 252 );
		$pdf->rect( 70, 96, 70, 12, 'DF', 0.4 );
		$pdf->set_text_color( 24, 56, 102 );
		$pdf->set_font( 'helvetica', 'B', 11 );
		$pdf->text( 70, 104, 'Nº ' . $cert_no, 'C', 70 );

		// Corpo do comprovante
		$pdf->set_text_color( 30, 30, 30 );
		$pdf->set_font( 'helvetica', '', 12 );
		$pdf->multi_text( 25, 124, 160, 7, $body_text, 'L' );

		// Local e data
		$pdf->set_font( 'helvetica', '', 11 );
		$pdf->text( 20, 200, 'Brasília-DF, ' . self::format_date_pt( current_time( 'Y-m-d' ) ) . '.', 'L', 170 );

		// Linha de assinatura
		$pdf->set_draw_color( 80, 80, 80 );
		$pdf->line( 70, 230, 140, 230, 0.4 );
		$pdf->set_text_color( 60, 60, 60 );
		$pdf->set_font( 'helvetica', '', 10 );
		$pdf->text( 70, 235, 'Diretoria — ABDF', 'C', 70 );

		// Box de verificação no rodapé
		$pdf->set_draw_color( 200, 200, 200 );
		$pdf->set_fill_color( 250, 250, 250 );
		$pdf->rect( 20, 255, 170, 22, 'DF', 0.3 );

		$pdf->set_text_color( 24, 56, 102 );
		$pdf->set_font( 'helvetica', 'B', 9 );
		$pdf->text( 24, 261, 'AUTENTICIDADE', 'L', 80 );

		$pdf->set_text_color( 50, 50, 50 );
		$pdf->set_font( 'helvetica', '', 9 );
		$pdf->multi_text( 24, 266, 162, 4.5,
			"Este comprovante pode ser verificado em:\n" . $verify_url
			. "\nNº de verificação: " . $cert_no
			. "  |  Hash: " . substr( $certificate->verification_hash, 0, 16 ) . '...',
			'L'
		);

		// Rodapé
		$pdf->set_text_color( 130, 130, 130 );
		$pdf->set_font( 'helvetica', 'I', 8 );
		$pdf->text( 20, 287, 'Documento emitido eletronicamente em ' . self::format_datetime_pt( current_time( 'mysql' ) ) . '.', 'C', 170 );

		return $pdf->output();
	}

	public static function compose_body( $member, $year ) {
		$default = "Declaramos, para os devidos fins, que {NOME} é associado(a) regular da Associação dos "
			. "Bibliotecários e Profissionais da Ciência da Informação do Distrito Federal — ABDF, "
			. "encontrando-se em pleno gozo de seus direitos estatutários e em situação regular "
			. "quanto ao pagamento da anuidade do exercício de {ANO}, conforme nossos registros internos.\n\n"
			. "O presente comprovante é emitido eletronicamente e sua autenticidade pode ser conferida no "
			. "endereço informado ao final deste documento, por meio do número de verificação correspondente.";
		$settings = get_option( 'abdf_settings', array() );
		$template = ! empty( $settings['certificate_template'] ) ? $settings['certificate_template'] : $default;

		return strtr( $template, array(
			'{NOME}'  => $member->full_name,
			'{ANO}'   => (string) $year,
			'{EMAIL}' => $member->email,
		) );
	}

	public static function verification_url( $certificate ) {
		$settings = get_option( 'abdf_settings', array() );
		$page_id  = isset( $settings['verify_page_id'] ) ? (int) $settings['verify_page_id'] : 0;
		$base     = $page_id ? get_permalink( $page_id ) : home_url( '/verificar-comprovante/' );
		$base     = $base ? $base : home_url( '/' );
		return add_query_arg( array( 'n' => $certificate->certificate_number ), $base );
	}

	public static function format_date_pt( $ymd ) {
		$months = array( 1=>'janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro' );
		$ts     = strtotime( $ymd );
		return date( 'd', $ts ) . ' de ' . $months[ (int) date( 'n', $ts ) ] . ' de ' . date( 'Y', $ts );
	}

	public static function format_datetime_pt( $mysql ) {
		$ts = strtotime( $mysql );
		return date( 'd/m/Y \à\s H:i', $ts );
	}
}
