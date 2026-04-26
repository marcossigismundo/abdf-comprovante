<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$abdf_settings = get_option( 'abdf_settings', array() );
$abdf_contact_email = ! empty( $abdf_settings['contact_email'] ) ? $abdf_settings['contact_email'] : 'abdf@abdf.org.br';
?>
<div class="abdf-wrap">
	<div class="abdf-card">
		<div class="abdf-card__head">
			<svg class="abdf-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 7V3.5L19.5 9H14Z"/><path d="M9 13h6M9 17h6" stroke="#fff" stroke-width="1.5" fill="none"/></svg>
			<h2><?php esc_html_e( 'Comprovante de Situação Cadastral', 'abdf-comprovante' ); ?></h2>
			<p><?php esc_html_e( 'Gere em segundos o comprovante de que você está em dia com a anuidade da ABDF.', 'abdf-comprovante' ); ?></p>
		</div>

		<div class="abdf-steps" aria-hidden="true">
			<span class="abdf-step abdf-step--on">1. Identificar</span>
			<span class="abdf-step">2. Validar</span>
			<span class="abdf-step">3. Baixar PDF</span>
		</div>

		<form id="abdf-form" novalidate>
			<label class="abdf-label" for="abdf-term"><?php esc_html_e( 'Nome completo ou e-mail cadastrado', 'abdf-comprovante' ); ?></label>
			<input id="abdf-term" name="term" type="text" autocomplete="off"
				placeholder="<?php esc_attr_e( 'Ex.: Maria da Silva Souza ou maria@exemplo.org', 'abdf-comprovante' ); ?>"
				required minlength="4" />

			<label class="abdf-checkbox">
				<input type="checkbox" id="abdf-consent" name="consent" required />
				<span><?php esc_html_e( 'Confirmo que sou o(a) titular dos dados informados e autorizo a consulta para emissão do comprovante.', 'abdf-comprovante' ); ?></span>
			</label>

			<!-- honeypot escondido -->
			<div class="abdf-honeypot" aria-hidden="true">
				<label>Website (não preencha)
					<input type="text" name="abdf_website" tabindex="-1" autocomplete="off" />
				</label>
			</div>
			<input type="hidden" name="abdf_rendered_at" value="<?php echo esc_attr( time() ); ?>" />
			<input type="hidden" name="recaptcha" value="" />

			<button type="submit" class="abdf-btn">
				<span class="abdf-btn__label"><?php esc_html_e( 'Gerar comprovante', 'abdf-comprovante' ); ?></span>
				<span class="abdf-btn__spinner" hidden></span>
			</button>

			<button type="button" class="abdf-link" data-abdf-modal="ajuda"><?php esc_html_e( 'Não consigo localizar meu cadastro', 'abdf-comprovante' ); ?></button>
		</form>

		<div id="abdf-feedback" class="abdf-feedback" role="status" aria-live="polite"></div>
	</div>

	<aside class="abdf-card abdf-card--soft">
		<h3><?php esc_html_e( 'Como funciona', 'abdf-comprovante' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Informe seu nome completo OU o e-mail cadastrado na ABDF.', 'abdf-comprovante' ); ?></li>
			<li><?php esc_html_e( 'Confirme a autoria e clique em "Gerar comprovante".', 'abdf-comprovante' ); ?></li>
			<li><?php esc_html_e( 'O sistema valida sua situação e gera um PDF com número único de verificação.', 'abdf-comprovante' ); ?></li>
		</ol>
		<p class="abdf-note"><?php esc_html_e( 'Os comprovantes são autênticos: cada PDF traz um número que pode ser conferido na página de verificação.', 'abdf-comprovante' ); ?></p>
	</aside>
</div>

<!-- Modal de ajuda -->
<div class="abdf-modal" id="abdf-modal-ajuda" hidden>
	<div class="abdf-modal__backdrop" data-abdf-close></div>
	<div class="abdf-modal__panel" role="dialog" aria-labelledby="abdf-modal-ajuda-title">
		<button class="abdf-modal__close" data-abdf-close aria-label="Fechar">×</button>
		<h3 id="abdf-modal-ajuda-title"><?php esc_html_e( 'Não localizei meu cadastro — e agora?', 'abdf-comprovante' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Tente o e-mail informado no momento da filiação.', 'abdf-comprovante' ); ?></li>
			<li><?php esc_html_e( 'Use o nome completo, com acentos.', 'abdf-comprovante' ); ?></li>
			<li>
				<?php esc_html_e( 'Ainda assim sem sucesso? Entre em contato com a secretaria da ABDF pelo e-mail', 'abdf-comprovante' ); ?>
				<a class="abdf-contact" href="mailto:<?php echo esc_attr( $abdf_contact_email ); ?>?subject=<?php echo rawurlencode( 'Comprovante de situação cadastral' ); ?>"><?php echo esc_html( $abdf_contact_email ); ?></a>.
			</li>
		</ul>
		<p class="abdf-note"><?php esc_html_e( 'Por segurança, há limite de tentativas por IP em curto intervalo.', 'abdf-comprovante' ); ?></p>
	</div>
</div>

<!-- Modal de sucesso -->
<div class="abdf-modal" id="abdf-modal-sucesso" hidden>
	<div class="abdf-modal__backdrop" data-abdf-close></div>
	<div class="abdf-modal__panel" role="dialog" aria-labelledby="abdf-modal-sucesso-title">
		<button class="abdf-modal__close" data-abdf-close aria-label="Fechar">×</button>
		<svg class="abdf-success-mark" viewBox="0 0 52 52"><circle cx="26" cy="26" r="24" fill="none" stroke="#1f7a3a" stroke-width="3"/><path d="M14 27 l8 8 16-18" fill="none" stroke="#1f7a3a" stroke-width="4" stroke-linecap="round"/></svg>
		<h3 id="abdf-modal-sucesso-title"><?php esc_html_e( 'Comprovante pronto!', 'abdf-comprovante' ); ?></h3>
		<p id="abdf-success-text"></p>
		<a id="abdf-download-link" href="#" class="abdf-btn abdf-btn--success" download><?php esc_html_e( 'Baixar PDF', 'abdf-comprovante' ); ?></a>
		<p class="abdf-note" id="abdf-cert-number"></p>
	</div>
</div>
