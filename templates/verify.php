<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="abdf-wrap">
	<div class="abdf-card">
		<div class="abdf-card__head">
			<svg class="abdf-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 4 5v6c0 5 3.4 9.6 8 11 4.6-1.4 8-6 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4" fill="none" stroke="#fff" stroke-width="2"/></svg>
			<h2><?php esc_html_e( 'Verificação de Autenticidade', 'abdf-comprovante' ); ?></h2>
			<p><?php esc_html_e( 'Confirme se um comprovante emitido pela ABDF é válido.', 'abdf-comprovante' ); ?></p>
		</div>

		<form id="abdf-verify-form">
			<label class="abdf-label" for="abdf-cert-num"><?php esc_html_e( 'Número do comprovante', 'abdf-comprovante' ); ?></label>
			<input id="abdf-cert-num" name="number" type="text" placeholder="Ex.: <?php echo esc_attr( date( 'Y' ) ); ?>-00001"
				value="<?php echo esc_attr( $_GET['n'] ?? '' ); ?>" required />
			<div class="abdf-honeypot" aria-hidden="true">
				<label>Website (não preencha)
					<input type="text" name="abdf_website" tabindex="-1" autocomplete="off" />
				</label>
			</div>
			<input type="hidden" name="abdf_rendered_at" value="<?php echo esc_attr( time() ); ?>" />

			<button type="submit" class="abdf-btn">
				<span class="abdf-btn__label"><?php esc_html_e( 'Verificar', 'abdf-comprovante' ); ?></span>
				<span class="abdf-btn__spinner" hidden></span>
			</button>
		</form>

		<div id="abdf-verify-result" class="abdf-feedback" role="status" aria-live="polite"></div>
	</div>
</div>

<?php if ( ! empty( $_GET['n'] ) ) : ?>
<script>document.addEventListener('DOMContentLoaded', () => {
	const f = document.getElementById('abdf-verify-form'); if(f){ setTimeout(() => f.requestSubmit(), 200); }
});</script>
<?php endif;
