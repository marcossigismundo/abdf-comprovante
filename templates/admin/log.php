<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap abdf-admin">
	<h1><?php esc_html_e( 'Log de acessos', 'abdf-comprovante' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Últimos 200 registros — útil para identificar tentativas suspeitas.', 'abdf-comprovante' ); ?></p>
	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Quando', 'abdf-comprovante' ); ?></th>
				<th>IP</th>
				<th><?php esc_html_e( 'Termo buscado', 'abdf-comprovante' ); ?></th>
				<th><?php esc_html_e( 'Sucesso?', 'abdf-comprovante' ); ?></th>
				<th>User-Agent</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'Sem registros.', 'abdf-comprovante' ); ?></td></tr>
			<?php else : foreach ( $rows as $r ) : ?>
				<tr>
					<td><?php echo esc_html( date_i18n( 'd/m/Y H:i:s', strtotime( $r->created_at ) ) ); ?></td>
					<td><code><?php echo esc_html( $r->ip_address ); ?></code></td>
					<td><?php echo esc_html( $r->search_term ); ?></td>
					<td><?php echo $r->success ? '<span class="abdf-pill abdf-pill-ok">sim</span>' : '<span class="abdf-pill abdf-pill-bad">não</span>'; ?></td>
					<td style="font-size:11px;color:#666;"><?php echo esc_html( $r->user_agent ); ?></td>
				</tr>
			<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
