<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap abdf-admin">
	<h1><?php echo $member ? esc_html__( 'Editar associado(a)', 'abdf-comprovante' ) : esc_html__( 'Novo associado(a)', 'abdf-comprovante' ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="abdf-form">
		<?php wp_nonce_field( 'abdf_save_member' ); ?>
		<input type="hidden" name="action" value="abdf_save_member" />
		<input type="hidden" name="id" value="<?php echo esc_attr( $member->id ?? '' ); ?>" />

		<table class="form-table" role="presentation">
			<tr>
				<th><label for="full_name"><?php esc_html_e( 'Nome completo', 'abdf-comprovante' ); ?> *</label></th>
				<td><input required name="full_name" id="full_name" type="text" class="regular-text" value="<?php echo esc_attr( $member->full_name ?? '' ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="email">E-mail *</label></th>
				<td><input required name="email" id="email" type="email" class="regular-text" value="<?php echo esc_attr( $member->email ?? '' ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="cpf">CPF</label></th>
				<td><input name="cpf" id="cpf" type="text" class="regular-text" value="<?php echo esc_attr( $member->cpf ?? '' ); ?>" placeholder="apenas números" /></td>
			</tr>
			<tr>
				<th><label for="membership_status"><?php esc_html_e( 'Status', 'abdf-comprovante' ); ?></label></th>
				<td>
					<select name="membership_status" id="membership_status">
						<?php
						$status = $member->membership_status ?? 'active';
						foreach ( array( 'active' => 'Ativo', 'expired' => 'Vencido', 'suspended' => 'Suspenso' ) as $k => $v ) {
							echo '<option value="' . esc_attr( $k ) . '"' . selected( $status, $k, false ) . '>' . esc_html( $v ) . '</option>';
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="paid_until"><?php esc_html_e( 'Em dia até', 'abdf-comprovante' ); ?></label></th>
				<td><input type="date" id="paid_until" name="paid_until" value="<?php echo esc_attr( $member->paid_until ?? '' ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="notes"><?php esc_html_e( 'Observações', 'abdf-comprovante' ); ?></label></th>
				<td><textarea name="notes" id="notes" rows="4" class="large-text"><?php echo esc_textarea( $member->notes ?? '' ); ?></textarea></td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Salvar', 'abdf-comprovante' ); ?></button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=abdf-members' ) ); ?>" class="button"><?php esc_html_e( 'Cancelar', 'abdf-comprovante' ); ?></a>
		</p>
	</form>
</div>
