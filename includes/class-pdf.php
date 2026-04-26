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

		// Logo institucional (centralizada)
		$logo_path = self::ensure_logo();
		$header_offset = 0;
		if ( $logo_path ) {
			$logo_w_mm = 42;
			$placed = $pdf->image_jpeg( $logo_path, ( 210 - $logo_w_mm ) / 2, 10, $logo_w_mm );
			if ( $placed ) {
				$header_offset = (int) ceil( $placed['h_mm'] ) + 4;
			}
		}

		// Cabeçalho institucional (deslocado se houver logo)
		$top = 20 + $header_offset;
		$pdf->set_text_color( 24, 56, 102 );
		$pdf->set_font( 'helvetica', 'B', 13 );
		$pdf->multi_text( 20, $top, 170, 6, $association_name, 'C' );

		$pdf->set_text_color( 60, 60, 60 );
		$pdf->set_font( 'helvetica', '', 10 );
		$pdf->text( 20, $top + 18, "CNPJ: {$cnpj}", 'C', 170 );
		$pdf->multi_text( 20, $top + 24, 170, 5, $address, 'C' );
		$pdf->text( 20, $top + 36, $phones, 'C', 170 );

		// Linha separadora
		$sep_y = $top + 44;
		$pdf->set_draw_color( 24, 56, 102 );
		$pdf->line( 20, $sep_y, 190, $sep_y, 0.8 );

		// Título do documento
		$title_y = $sep_y + 16;
		$pdf->set_text_color( 24, 56, 102 );
		$pdf->set_font( 'helvetica', 'B', 18 );
		$pdf->text( 20, $title_y, 'COMPROVANTE DE SITUAÇÃO CADASTRAL', 'C', 170 );

		$pdf->set_text_color( 90, 90, 90 );
		$pdf->set_font( 'helvetica', '', 10 );
		$pdf->text( 20, $title_y + 8, 'Documento referente ao exercício de ' . self::format_period( $year ), 'C', 170 );

		// Caixa do número
		$box_y = $title_y + 16;
		$pdf->set_draw_color( 200, 200, 200 );
		$pdf->set_fill_color( 246, 248, 252 );
		$pdf->rect( 70, $box_y, 70, 12, 'DF', 0.4 );
		$pdf->set_text_color( 24, 56, 102 );
		$pdf->set_font( 'helvetica', 'B', 11 );
		$pdf->text( 70, $box_y + 8, 'Nº ' . $cert_no, 'C', 70 );

		// Corpo do comprovante
		$pdf->set_text_color( 30, 30, 30 );
		$pdf->set_font( 'helvetica', '', 12 );
		$pdf->multi_text( 25, $box_y + 28, 160, 7, $body_text, 'L' );

		// Local e data — alinhada à direita, com folga acima do corpo
		$pdf->set_text_color( 60, 60, 60 );
		$pdf->set_font( 'helvetica', '', 11 );
		$pdf->text( 20, 218, 'Brasília-DF, ' . self::format_date_pt( current_time( 'Y-m-d' ) ) . '.', 'R', 170 );

		// Identificação institucional ao final (sem assinatura nem "Diretoria")
		$pdf->set_text_color( 30, 30, 30 );
		$pdf->set_font( 'helvetica', 'B', 10 );
		$pdf->multi_text( 20, 240, 170, 5,
			'ABDF - ASSOCIAÇÃO DOS BIBLIOTECÁRIOS E PROFISSIONAIS DA CIÊNCIA DA INFORMAÇÃO DO DISTRITO FEDERAL',
			'L'
		);

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
			'{NOME}'      => $member->full_name,
			'{ANO}'       => self::format_period( $year ),
			'{EXERCICIO}' => self::format_period( $year ),
			'{EMAIL}'     => $member->email,
		) );
	}

	/**
	 * Exercício no formato "YYYY-1/YYYY" (ex.: 2025/2026).
	 */
	public static function format_period( $year ) {
		$year = (int) $year;
		return ( $year - 1 ) . '/' . $year;
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

	/**
	 * Garante que existe uma cópia local da logo em JPEG (PDF embute melhor JPEG via /DCTDecode).
	 * Baixa a URL configurada (default: site da ABDF) e converte de PNG para JPEG via GD.
	 * Recacheia automaticamente se a URL configurada mudou.
	 */
	public static function ensure_logo() {
		$settings = get_option( 'abdf_settings', array() );
		$url = ! empty( $settings['logo_url'] )
			? $settings['logo_url']
			: 'https://abdf.org.br/wp-content/uploads/2024/08/logo2.png';

		if ( ! function_exists( 'wp_upload_dir' ) ) { return null; }
		$upload = wp_upload_dir();
		if ( empty( $upload['basedir'] ) ) { return null; }
		$dir = trailingslashit( $upload['basedir'] ) . 'abdf-comprovante';
		if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); }
		$file        = $dir . '/logo.jpg';
		$url_marker  = $dir . '/logo.url.txt';
		$cached_url  = file_exists( $url_marker ) ? trim( (string) file_get_contents( $url_marker ) ) : '';

		if ( file_exists( $file ) && $cached_url === $url ) {
			return $file;
		}

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return file_exists( $file ) ? $file : null;
		}
		$body = wp_remote_retrieve_body( $response );
		if ( ! $body ) { return file_exists( $file ) ? $file : null; }

		if ( ! function_exists( 'imagecreatefromstring' ) ) {
			return file_exists( $file ) ? $file : null;
		}
		$src = @imagecreatefromstring( $body );
		if ( ! $src ) { return file_exists( $file ) ? $file : null; }

		$w = imagesx( $src );
		$h = imagesy( $src );
		$dst = imagecreatetruecolor( $w, $h );
		$white = imagecolorallocate( $dst, 255, 255, 255 );
		imagefilledrectangle( $dst, 0, 0, $w, $h, $white );
		imagealphablending( $dst, true );
		imagecopy( $dst, $src, 0, 0, 0, 0, $w, $h );
		imagejpeg( $dst, $file, 92 );
		imagedestroy( $src );
		imagedestroy( $dst );
		file_put_contents( $url_marker, $url );

		return $file;
	}
}
