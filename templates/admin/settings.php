<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap abdf-admin">
	<h1><?php esc_html_e( 'Configurações — ABDF Comprovante', 'abdf-comprovante' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'abdf_settings_group' ); ?>
		<h2><?php esc_html_e( 'Identificação institucional', 'abdf-comprovante' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label><?php esc_html_e( 'Nome da associação', 'abdf-comprovante' ); ?></label></th>
				<td><input type="text" class="large-text" name="abdf_settings[association_name]" value="<?php echo esc_attr( $settings['association_name'] ?? '' ); ?>" /></td>
			</tr>
			<tr>
				<th><label>CNPJ</label></th>
				<td><input type="text" name="abdf_settings[cnpj]" value="<?php echo esc_attr( $settings['cnpj'] ?? '' ); ?>" /></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Endereço', 'abdf-comprovante' ); ?></label></th>
				<td><textarea class="large-text" rows="3" name="abdf_settings[address]"><?php echo esc_textarea( $settings['address'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Telefones', 'abdf-comprovante' ); ?></label></th>
				<td><input type="text" class="large-text" name="abdf_settings[phones]" value="<?php echo esc_attr( $settings['phones'] ?? '' ); ?>" /></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Texto do comprovante', 'abdf-comprovante' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Use {NOME}, {ANO} e {EMAIL} como variáveis.', 'abdf-comprovante' ); ?></p>
		<textarea class="large-text" rows="8" name="abdf_settings[certificate_template]"><?php echo esc_textarea( $settings['certificate_template'] ?? '' ); ?></textarea>

		<h2><?php esc_html_e( 'Página pública de verificação', 'abdf-comprovante' ); ?></h2>
		<p>
			<label><?php esc_html_e( 'Página com [abdf_verificar]:', 'abdf-comprovante' ); ?></label>
			<?php
			wp_dropdown_pages( array(
				'name'              => 'abdf_settings[verify_page_id]',
				'selected'          => $settings['verify_page_id'] ?? 0,
				'show_option_none'  => '— ' . __( 'usar /verificar-comprovante/', 'abdf-comprovante' ) . ' —',
				'option_none_value' => 0,
			) );
			?>
		</p>

		<h2><?php esc_html_e( 'reCAPTCHA v3 (opcional)', 'abdf-comprovante' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Se preenchido, os formulários frontend exigirão validação reCAPTCHA. Deixe em branco para desativar.', 'abdf-comprovante' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th><label>Site key</label></th>
				<td><input type="text" class="regular-text" name="abdf_settings[recaptcha_site]" value="<?php echo esc_attr( $settings['recaptcha_site'] ?? '' ); ?>" /></td>
			</tr>
			<tr>
				<th><label>Secret key</label></th>
				<td><input type="text" class="regular-text" name="abdf_settings[recaptcha_secret]" value="<?php echo esc_attr( $settings['recaptcha_secret'] ?? '' ); ?>" /></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Pontuação mínima', 'abdf-comprovante' ); ?></label></th>
				<td><input type="number" min="0.1" max="1" step="0.1" name="abdf_settings[recaptcha_min_score]" value="<?php echo esc_attr( $settings['recaptcha_min_score'] ?? 0.5 ); ?>" /></td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>

	<div class="abdf-card">
		<h2><?php esc_html_e( 'Como usar', 'abdf-comprovante' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Crie uma página com o shortcode', 'abdf-comprovante' ); ?> <code>[abdf_comprovante]</code>.</li>
			<li><?php esc_html_e( 'Crie outra página com', 'abdf-comprovante' ); ?> <code>[abdf_verificar]</code> <?php esc_html_e( '(o plugin já cria uma em /verificar-comprovante/ automaticamente).', 'abdf-comprovante' ); ?></li>
			<li><?php esc_html_e( 'Mantenha o cadastro de associados atualizado em "Associados(as)" ou "Importar CSV".', 'abdf-comprovante' ); ?></li>
		</ol>
	</div>
</div>
