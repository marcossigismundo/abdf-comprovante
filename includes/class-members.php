<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ABDF_Members {

	public static function find( $term ) {
		global $wpdb;
		$table = ABDF_Database::table( 'members' );
		$norm  = self::normalize( $term );
		$like  = '%' . $wpdb->esc_like( $term ) . '%';

		// Prioriza correspondência exata por e-mail; depois nome normalizado.
		$exact = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE LOWER(email) = %s OR LOWER(full_name) = %s LIMIT 1",
			strtolower( $term ), strtolower( $term )
		) );
		if ( $exact ) { return $exact; }

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE full_name LIKE %s OR email LIKE %s LIMIT 5",
			$like, $like
		) );

		// Se houver apenas 1 resultado próximo, aceita; se houver mais, exige especificidade.
		if ( count( $rows ) === 1 ) { return $rows[0]; }

		// Tenta normalização (sem acentos) — match exato.
		foreach ( $rows as $r ) {
			if ( self::normalize( $r->full_name ) === $norm ) {
				return $r;
			}
		}
		return null;
	}

	public static function get( $id ) {
		global $wpdb;
		$table = ABDF_Database::table( 'members' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
	}

	public static function all( $args = array() ) {
		global $wpdb;
		$table   = ABDF_Database::table( 'members' );
		$where   = '1=1';
		$params  = array();
		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where   .= ' AND (full_name LIKE %s OR email LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}
		if ( ! empty( $args['status'] ) ) {
			$where    .= ' AND membership_status = %s';
			$params[]  = $args['status'];
		}
		$order = ! empty( $args['order'] ) ? $args['order'] : 'full_name ASC';
		$order = preg_replace( '/[^a-zA-Z_ ]/', '', $order );
		$sql   = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$order}";
		if ( $params ) {
			$sql = $wpdb->prepare( $sql, $params );
		}
		return $wpdb->get_results( $sql );
	}

	public static function upsert( $data ) {
		global $wpdb;
		$table = ABDF_Database::table( 'members' );
		$row   = array(
			'full_name'         => sanitize_text_field( $data['full_name'] ),
			'email'             => sanitize_email( $data['email'] ),
			'cpf'               => isset( $data['cpf'] ) ? preg_replace( '/[^0-9]/', '', $data['cpf'] ) : null,
			'membership_status' => isset( $data['membership_status'] ) ? sanitize_key( $data['membership_status'] ) : 'active',
			'paid_until'        => isset( $data['paid_until'] ) && $data['paid_until'] ? sanitize_text_field( $data['paid_until'] ) : null,
			'notes'             => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : null,
			'updated_at'        => current_time( 'mysql' ),
		);
		if ( ! empty( $data['id'] ) ) {
			$wpdb->update( $table, $row, array( 'id' => (int) $data['id'] ) );
			return (int) $data['id'];
		}
		$row['created_at'] = current_time( 'mysql' );
		$wpdb->insert( $table, $row );
		return (int) $wpdb->insert_id;
	}

	public static function delete( $id ) {
		global $wpdb;
		$table = ABDF_Database::table( 'members' );
		return $wpdb->delete( $table, array( 'id' => (int) $id ) );
	}

	public static function import_csv( $file_path ) {
		$created = 0;
		$updated = 0;
		$skipped = 0;
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'no_file', __( 'Arquivo CSV não encontrado.', 'abdf-comprovante' ) );
		}
		$fh = fopen( $file_path, 'r' );
		if ( ! $fh ) { return new WP_Error( 'no_open', __( 'Não foi possível abrir o CSV.', 'abdf-comprovante' ) ); }

		$header = fgetcsv( $fh, 0, ',' );
		if ( ! $header ) {
			fclose( $fh );
			return new WP_Error( 'no_header', __( 'CSV sem cabeçalho.', 'abdf-comprovante' ) );
		}
		$header = array_map( 'strtolower', array_map( 'trim', $header ) );
		// Detecta separador alternativo (;)
		if ( count( $header ) === 1 && false !== strpos( $header[0], ';' ) ) {
			rewind( $fh );
			$header = fgetcsv( $fh, 0, ';' );
			$header = array_map( 'strtolower', array_map( 'trim', $header ) );
			$sep    = ';';
		} else {
			$sep = ',';
		}

		while ( ( $row = fgetcsv( $fh, 0, $sep ) ) !== false ) {
			$assoc = array_combine( $header, array_pad( $row, count( $header ), '' ) );
			$name  = trim( $assoc['nome'] ?? $assoc['full_name'] ?? '' );
			$email = trim( $assoc['email'] ?? $assoc['e-mail'] ?? '' );
			if ( ! $name && ! $email ) { $skipped++; continue; }

			$existing = ABDF_Members::find( $email ?: $name );
			$payload  = array(
				'full_name'         => $name,
				'email'             => $email,
				'cpf'               => $assoc['cpf'] ?? null,
				'membership_status' => $assoc['status'] ?? 'active',
				'paid_until'        => $assoc['paid_until'] ?? $assoc['vigencia'] ?? null,
				'notes'             => $assoc['notes'] ?? null,
			);
			if ( $existing ) {
				$payload['id'] = $existing->id;
				self::upsert( $payload );
				$updated++;
			} else {
				self::upsert( $payload );
				$created++;
			}
		}
		fclose( $fh );
		return compact( 'created', 'updated', 'skipped' );
	}

	public static function seed_initial() {
		global $wpdb;
		$table = ABDF_Database::table( 'members' );
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $count > 0 ) { return; }

		$year   = (int) date( 'Y' );
		$valid  = $year . '-12-31';
		$people = array(
			array( 'Alcemir Gomes Morais', 'alcemirgomes@gmail.com' ),
			array( 'Ana Carolina Simionato Arakaki', 'simionato.ac@gmail.com' ),
			array( 'Ana Regina Luz Lacerda', 'analuzbiblio@hotmail.com' ),
			array( 'Dulce Maria Baptista', 'baptistadm368@gmail.com' ),
			array( 'Eduardo Pereira de Souza', 'dudaleu1@gmail.com' ),
			array( 'Fabiano Antonio S. Leal', 'fasleal@gmail.com' ),
			array( 'Fábio Lima Cordeiro', 'agaciel@hotmail.com' ),
			array( 'Felipe Augusto Arakaki', 'fe.arakaki@gmail.com' ),
			array( 'Gabriela Fernanda Ribeiro Rodrigues', 'gabyfrr@gmail.com' ),
			array( 'Hallison Phelipe Lopes de Castro', 'phelipe.hallisonlc@gmail.com' ),
			array( 'Janice de Oliveira e Silva Silveira', 'janice.silveira@camara.leg.br' ),
			array( 'Karin Torres Schiessl', 'karin.tschiessl@gmail.com' ),
			array( 'Kristina Borja de Sousa', 'kristinaborjas@gmail.com' ),
			array( 'Laysse Noleto Balbino Teixeira', 'layssenoleto@gmail.com' ),
			array( 'Leila Fernandes dos Santos', 'leilacompetencias@gmail.com' ),
			array( 'Lorena Nelza Ferreira Silva', 'lorelice04@gmail.com' ),
			array( 'Luiza Gallo Pestano', 'LuizaP@stf.jus.br' ),
			array( 'Maria Tereza Walter', 'terezaw@gmail.com' ),
			array( 'Miguel Ângelo Bueno Portela', 'miguel.buenoportela@gmail.com' ),
			array( 'Neide Alves Dias de Sordi', 'nsordi@gmail.com' ),
			array( 'Neide Aparecida Gomes', 'nagomes2005@gmail.com' ),
			array( 'Sebastião Dimas Justo da Silva', 'dimas.justo@gmail.com' ),
			array( 'Stella Maria Vaz Valadares Chervenski', 'stella@senado.leg.br' ),
			array( 'Tania Cristina Oliveira', 'tania.cristina.ol@gmail.com' ),
			array( 'Tavínia Pinheiro Timbo', 'tavinia@gmail.com' ),
			array( 'Vanessa Madalena da Silva', 'vanessamadas@gmail.com' ),
			array( 'Vinícius Cordeiro Galhardo', 'viniciuscgalhardo@gmail.com' ),
			array( 'Walter Eler do Couto', 'walterellerc@gmail.com' ),
			array( 'Wilza Rosa da Silva Lima', 'wilza_rs@hotmail.com' ),
		);
		foreach ( $people as $p ) {
			self::upsert( array(
				'full_name'         => $p[0],
				'email'             => $p[1],
				'membership_status' => 'active',
				'paid_until'        => $valid,
			) );
		}
	}

	public static function normalize( $text ) {
		$text = mb_strtolower( trim( (string) $text ), 'UTF-8' );
		if ( function_exists( 'iconv' ) ) {
			$ascii = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $text );
			if ( false !== $ascii ) { $text = $ascii; }
		}
		$text = preg_replace( '/\s+/', ' ', $text );
		return $text;
	}
}
