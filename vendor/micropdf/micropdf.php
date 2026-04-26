<?php
/**
 * MicroPDF — biblioteca minimalista de geração de PDF auto-contida.
 *
 * Suporta o suficiente para o comprovante da ABDF: A4 retrato,
 * fontes Helvetica embutidas (Type1), texto posicionado, alinhamento,
 * quebra automática, linhas, retângulos e WinAnsiEncoding (CP1252)
 * para acentos do português.
 *
 * Não depende de extensões além de mb_convert_encoding (mbstring) e zlib (gzcompress).
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'MICROPDF_STANDALONE' ) ) { exit; }

class ABDF_MicroPDF {

	const PT_PER_MM = 2.83464567;

	private $page_w_pt = 595.28;
	private $page_h_pt = 841.89;
	private $margin_pt = 56.69;

	private $pages   = array();
	private $current = '';

	private $current_font = 'F1';
	private $current_size = 12;
	private $line_color   = '0 0 0';
	private $fill_color   = '0 0 0';
	private $text_color   = '0 0 0';

	private $font_map = array(
		'helvetica'    => 'F1',
		'helvetica-b'  => 'F2',
		'helvetica-i'  => 'F3',
		'helvetica-bi' => 'F4',
	);

	public function __construct() {
		$this->add_page();
	}

	public function add_page() {
		if ( '' !== $this->current ) {
			$this->pages[] = $this->current;
			$this->current = '';
		}
		if ( count( $this->pages ) === 0 ) {
			// primeira página: stream começa vazia
			$this->current = '';
		}
	}

	public function set_font( $family = 'helvetica', $style = '', $size = 12 ) {
		$key = strtolower( $family );
		if ( $style ) { $key .= '-' . strtolower( $style ); }
		if ( ! isset( $this->font_map[ $key ] ) ) {
			$key = 'helvetica';
		}
		$this->current_font = $this->font_map[ $key ];
		$this->current_size = (float) $size;
	}

	public function set_text_color( $r, $g, $b ) {
		$this->text_color = sprintf( '%.3f %.3f %.3f', $r / 255, $g / 255, $b / 255 );
	}

	public function set_draw_color( $r, $g, $b ) {
		$this->line_color = sprintf( '%.3f %.3f %.3f', $r / 255, $g / 255, $b / 255 );
	}

	public function set_fill_color( $r, $g, $b ) {
		$this->fill_color = sprintf( '%.3f %.3f %.3f', $r / 255, $g / 255, $b / 255 );
	}

	/**
	 * Escreve texto em (x_mm, y_mm), alinhado por padrão à esquerda.
	 *
	 * @param float  $x_mm
	 * @param float  $y_mm  posição da linha-base contada do topo da página.
	 * @param string $text
	 * @param string $align L|C|R
	 * @param float  $width_mm largura disponível (necessária para C e R).
	 */
	public function text( $x_mm, $y_mm, $text, $align = 'L', $width_mm = 0 ) {
		$encoded = $this->encode( $text );
		$text_width_pt = $this->string_width_pt( $text );

		if ( 'C' === $align && $width_mm > 0 ) {
			$x_mm += ( $width_mm - $text_width_pt / self::PT_PER_MM ) / 2;
		} elseif ( 'R' === $align && $width_mm > 0 ) {
			$x_mm += ( $width_mm - $text_width_pt / self::PT_PER_MM );
		}

		$x_pt = $x_mm * self::PT_PER_MM;
		$y_pt = $this->page_h_pt - ( $y_mm * self::PT_PER_MM );

		$this->current .= "q\n";
		$this->current .= "{$this->text_color} rg\n";
		$this->current .= "BT /{$this->current_font} {$this->current_size} Tf {$x_pt} {$y_pt} Td ({$encoded}) Tj ET\n";
		$this->current .= "Q\n";
	}

	/**
	 * Texto com quebra automática dentro de uma largura (mm).
	 * Retorna a próxima posição Y (mm).
	 */
	public function multi_text( $x_mm, $y_mm, $width_mm, $line_height_mm, $text, $align = 'L' ) {
		$max_pt = $width_mm * self::PT_PER_MM;
		$paragraphs = preg_split( "/\r\n|\n|\r/", $text );
		$y = $y_mm;

		foreach ( $paragraphs as $paragraph ) {
			$paragraph = trim( $paragraph );
			if ( '' === $paragraph ) {
				$y += $line_height_mm;
				continue;
			}
			$words = preg_split( '/\s+/', $paragraph );
			$line  = '';
			foreach ( $words as $word ) {
				$try   = '' === $line ? $word : $line . ' ' . $word;
				$try_w = $this->string_width_pt( $try );
				if ( $try_w > $max_pt && '' !== $line ) {
					$this->text( $x_mm, $y, $line, $align, $width_mm );
					$y    += $line_height_mm;
					$line  = $word;
				} else {
					$line = $try;
				}
			}
			if ( '' !== $line ) {
				$this->text( $x_mm, $y, $line, $align, $width_mm );
				$y += $line_height_mm;
			}
		}
		return $y;
	}

	public function line( $x1_mm, $y1_mm, $x2_mm, $y2_mm, $width_pt = 0.5 ) {
		$x1 = $x1_mm * self::PT_PER_MM;
		$y1 = $this->page_h_pt - $y1_mm * self::PT_PER_MM;
		$x2 = $x2_mm * self::PT_PER_MM;
		$y2 = $this->page_h_pt - $y2_mm * self::PT_PER_MM;
		$this->current .= "q\n{$this->line_color} RG\n{$width_pt} w\n{$x1} {$y1} m {$x2} {$y2} l S\nQ\n";
	}

	public function rect( $x_mm, $y_mm, $w_mm, $h_mm, $style = 'D', $width_pt = 0.5 ) {
		$x = $x_mm * self::PT_PER_MM;
		$y = $this->page_h_pt - ( $y_mm + $h_mm ) * self::PT_PER_MM;
		$w = $w_mm * self::PT_PER_MM;
		$h = $h_mm * self::PT_PER_MM;
		$op = 'S';
		if ( 'F' === $style ) { $op = 'f'; }
		if ( 'DF' === $style || 'FD' === $style ) { $op = 'B'; }
		$this->current .= "q\n{$this->line_color} RG\n{$this->fill_color} rg\n{$width_pt} w\n{$x} {$y} {$w} {$h} re {$op}\nQ\n";
	}

	public function output() {
		// fecha página corrente
		$this->pages[] = $this->current;
		$this->current = '';

		$objects   = array();
		$obj_index = 0;

		// 1: catalog, 2: pages
		$page_count   = count( $this->pages );
		$page_obj_ids = array();
		// Reservamos IDs: 1 catalog, 2 pages, 3..6 fonts, depois pares (page, content)
		for ( $i = 0; $i < $page_count; $i++ ) {
			$page_obj_ids[ $i ] = 7 + $i * 2;
		}

		$objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

		$kids = '';
		foreach ( $page_obj_ids as $id ) { $kids .= $id . ' 0 R '; }
		$objects[2] = sprintf(
			'<< /Type /Pages /Kids [%s] /Count %d /MediaBox [0 0 %.2f %.2f] >>',
			trim( $kids ),
			$page_count,
			$this->page_w_pt,
			$this->page_h_pt
		);

		$objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
		$objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
		$objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>';
		$objects[6] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-BoldOblique /Encoding /WinAnsiEncoding >>';

		foreach ( $this->pages as $i => $stream ) {
			$page_id    = $page_obj_ids[ $i ];
			$content_id = $page_id + 1;

			$objects[ $page_id ] = sprintf(
				'<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 3 0 R /F2 4 0 R /F3 5 0 R /F4 6 0 R >> >> /Contents %d 0 R >>',
				$content_id
			);

			$compressed = function_exists( 'gzcompress' ) ? gzcompress( $stream ) : false;
			if ( false !== $compressed ) {
				$len = strlen( $compressed );
				$objects[ $content_id ] = "<< /Length {$len} /Filter /FlateDecode >>\nstream\n" . $compressed . "\nendstream";
			} else {
				$len = strlen( $stream );
				$objects[ $content_id ] = "<< /Length {$len} >>\nstream\n" . $stream . "\nendstream";
			}
		}

		ksort( $objects );

		$pdf     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
		$offsets = array();
		foreach ( $objects as $id => $body ) {
			$offsets[ $id ] = strlen( $pdf );
			$pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
		}

		$xref_offset = strlen( $pdf );
		$max_id      = max( array_keys( $objects ) );
		$pdf .= "xref\n0 " . ( $max_id + 1 ) . "\n";
		$pdf .= "0000000000 65535 f \n";
		for ( $i = 1; $i <= $max_id; $i++ ) {
			if ( isset( $offsets[ $i ] ) ) {
				$pdf .= str_pad( $offsets[ $i ], 10, '0', STR_PAD_LEFT ) . " 00000 n \n";
			} else {
				$pdf .= "0000000000 65535 f \n";
			}
		}

		$pdf .= "trailer\n<< /Size " . ( $max_id + 1 ) . " /Root 1 0 R >>\n";
		$pdf .= "startxref\n{$xref_offset}\n%%EOF\n";

		return $pdf;
	}

	public function save( $path ) {
		return file_put_contents( $path, $this->output() );
	}

	private function encode( $text ) {
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$text = @mb_convert_encoding( $text, 'Windows-1252', 'UTF-8' );
		} else {
			$text = utf8_decode( $text );
		}
		$text = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
		return $text;
	}

	/**
	 * Largura aproximada da string em pontos (Helvetica).
	 * Usa larguras médias ponderadas — suficiente para layout de comprovante.
	 */
	private function string_width_pt( $text ) {
		$encoded = function_exists( 'mb_convert_encoding' )
			? @mb_convert_encoding( $text, 'Windows-1252', 'UTF-8' )
			: utf8_decode( $text );

		$widths = $this->helvetica_widths();
		$total  = 0;
		$len    = strlen( $encoded );
		for ( $i = 0; $i < $len; $i++ ) {
			$c = ord( $encoded[ $i ] );
			$total += isset( $widths[ $c ] ) ? $widths[ $c ] : 500;
		}
		// Helvetica negrito ≈ 1.05x mais largo
		$factor = ( 'F2' === $this->current_font || 'F4' === $this->current_font ) ? 1.05 : 1.0;
		return $total * $this->current_size / 1000 * $factor;
	}

	private function helvetica_widths() {
		// AFM padrão da Helvetica (subset relevante para Latin-1).
		static $w = null;
		if ( null !== $w ) { return $w; }
		$w = array_fill( 0, 256, 500 );
		$pairs = array(
			32=>278,33=>278,34=>355,35=>556,36=>556,37=>889,38=>667,39=>191,40=>333,41=>333,
			42=>389,43=>584,44=>278,45=>333,46=>278,47=>278,48=>556,49=>556,50=>556,51=>556,
			52=>556,53=>556,54=>556,55=>556,56=>556,57=>556,58=>278,59=>278,60=>584,61=>584,
			62=>584,63=>556,64=>1015,65=>667,66=>667,67=>722,68=>722,69=>667,70=>611,71=>778,
			72=>722,73=>278,74=>500,75=>667,76=>556,77=>833,78=>722,79=>778,80=>667,81=>778,
			82=>722,83=>667,84=>611,85=>722,86=>667,87=>944,88=>667,89=>667,90=>611,91=>278,
			92=>278,93=>278,94=>469,95=>556,96=>333,97=>556,98=>556,99=>500,100=>556,101=>556,
			102=>278,103=>556,104=>556,105=>222,106=>222,107=>500,108=>222,109=>833,110=>556,
			111=>556,112=>556,113=>556,114=>333,115=>500,116=>278,117=>556,118=>500,119=>722,
			120=>500,121=>500,122=>500,123=>334,124=>260,125=>334,126=>584,
			// Latin-1 Supplement (acentos PT)
			192=>667,193=>667,194=>667,195=>667,199=>722,200=>667,201=>667,202=>667,205=>278,
			211=>778,212=>778,213=>778,218=>722,224=>556,225=>556,226=>556,227=>556,231=>500,
			232=>556,233=>556,234=>556,237=>222,243=>556,244=>556,245=>556,250=>556,231=>500,
		);
		foreach ( $pairs as $k => $v ) { $w[ $k ] = $v; }
		return $w;
	}
}
