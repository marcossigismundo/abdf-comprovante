<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap abdf-admin">
	<h1><?php esc_html_e( 'Importar associados(as) por CSV', 'abdf-comprovante' ); ?></h1>

	<?php if ( isset( $_GET['created'] ) || isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success">
			<p>
				<?php
				printf(
					esc_html__( 'Importação concluída — %d criados, %d atualizados, %d ignorados.', 'abdf-comprovante' ),
					(int) $_GET['created'], (int) $_GET['updated'], (int) ( $_GET['skipped'] ?? 0 )
				);
				?>
			</p>
		</div>
	<?php elseif ( isset( $_GET['error'] ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $_GET['error'] ); ?></p></div>
	<?php endif; ?>

	<div class="abdf-card">
		<h2><?php esc_html_e( 'Cabeçalhos suportados', 'abdf-comprovante' ); ?></h2>
		<p><code>nome, email, cpf, status, paid_until, notes</code> (separador <code>,</code> ou <code>;</code>).</p>
		<p><?php esc_html_e( 'Registros com mesmo e-mail são atualizados; novos são criados.', 'abdf-comprovante' ); ?></p>
	</div>

	<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'abdf_import_csv' ); ?>
		<input type="hidden" name="action" value="abdf_import_csv" />
		<p><input type="file" name="csv_file" accept=".csv,text/csv" required /></p>
		<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Importar', 'abdf-comprovante' ); ?></button></p>
	</form>
</div>
