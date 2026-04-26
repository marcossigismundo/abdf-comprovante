<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap abdf-admin">
	<h1><?php esc_html_e( 'Comprovantes emitidos', 'abdf-comprovante' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Últimos 200 registros.', 'abdf-comprovante' ); ?></p>
	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Nº', 'abdf-comprovante' ); ?></th>
				<th><?php esc_html_e( 'Associado(a)', 'abdf-comprovante' ); ?></th>
				<th><?php esc_html_e( 'E-mail', 'abdf-comprovante' ); ?></th>
				<th><?php esc_html_e( 'Ano', 'abdf-comprovante' ); ?></th>
				<th><?php esc_html_e( 'Emitido em', 'abdf-comprovante' ); ?></th>
				<th>IP</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'Nenhum comprovante emitido ainda.', 'abdf-comprovante' ); ?></td></tr>
			<?php else : foreach ( $rows as $r ) : ?>
				<tr>
					<td><code><?php echo esc_html( $r->certificate_number ); ?></code></td>
					<td><?php echo esc_html( $r->full_name ); ?></td>
					<td><?php echo esc_html( $r->email ); ?></td>
					<td><?php echo (int) $r->issued_for_year; ?></td>
					<td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $r->issued_at ) ) ); ?></td>
					<td><code><?php echo esc_html( $r->issued_ip ); ?></code></td>
				</tr>
			<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
