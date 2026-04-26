<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap abdf-admin">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Associados(as)', 'abdf-comprovante' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=abdf-members&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Adicionar', 'abdf-comprovante' ); ?></a>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=abdf-import' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Importar CSV', 'abdf-comprovante' ); ?></a>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Cadastro salvo.', 'abdf-comprovante' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'Cadastro removido.', 'abdf-comprovante' ); ?></p></div>
	<?php endif; ?>

	<form method="get">
		<input type="hidden" name="page" value="abdf-members" />
		<p class="search-box">
			<label class="screen-reader-text" for="abdf-search-input"><?php esc_html_e( 'Buscar:', 'abdf-comprovante' ); ?></label>
			<input type="search" id="abdf-search-input" name="s" value="<?php echo esc_attr( $_GET['s'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Nome ou e-mail', 'abdf-comprovante' ); ?>" />
			<input type="submit" class="button" value="<?php esc_attr_e( 'Buscar', 'abdf-comprovante' ); ?>" />
		</p>
	</form>

	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Nome', 'abdf-comprovante' ); ?></th>
				<th><?php esc_html_e( 'E-mail', 'abdf-comprovante' ); ?></th>
				<th><?php esc_html_e( 'Status', 'abdf-comprovante' ); ?></th>
				<th><?php esc_html_e( 'Em dia até', 'abdf-comprovante' ); ?></th>
				<th><?php esc_html_e( 'Ações', 'abdf-comprovante' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $members ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'Nenhum associado(a) encontrado(a).', 'abdf-comprovante' ); ?></td></tr>
			<?php else : foreach ( $members as $m ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $m->full_name ); ?></strong></td>
					<td><?php echo esc_html( $m->email ); ?></td>
					<td>
						<?php
						$labels = array(
							'active'    => '<span class="abdf-pill abdf-pill-ok">Ativo</span>',
							'expired'   => '<span class="abdf-pill abdf-pill-warn">Vencido</span>',
							'suspended' => '<span class="abdf-pill abdf-pill-bad">Suspenso</span>',
						);
						echo $labels[ $m->membership_status ] ?? esc_html( $m->membership_status );
						?>
					</td>
					<td><?php echo $m->paid_until ? esc_html( date_i18n( 'd/m/Y', strtotime( $m->paid_until ) ) ) : '—'; ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=abdf-members&action=edit&id=' . $m->id ) ); ?>"><?php esc_html_e( 'Editar', 'abdf-comprovante' ); ?></a> |
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=abdf_delete_member&id=' . $m->id ), 'abdf_delete_member' ) ); ?>" onclick="return confirm('Confirmar exclusão?')" style="color:#a00"><?php esc_html_e( 'Remover', 'abdf-comprovante' ); ?></a>
					</td>
				</tr>
			<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
