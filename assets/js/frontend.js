(function(){
	'use strict';

	const data = window.ABDF_DATA || {};

	function $(sel, root){ return (root||document).querySelector(sel); }
	function setBusy(btn, busy){
		const label = btn.querySelector('.abdf-btn__label');
		const spin  = btn.querySelector('.abdf-btn__spinner');
		btn.disabled = busy;
		if (label) label.style.opacity = busy ? .6 : 1;
		if (spin)  spin.hidden = !busy;
	}

	async function withRecaptcha(action){
		if (!data.recaptcha_site || !window.grecaptcha) return '';
		return new Promise(resolve => {
			grecaptcha.ready(() => grecaptcha.execute(data.recaptcha_site, { action }).then(resolve).catch(()=>resolve('')));
		});
	}

	function showModal(name){
		const m = document.getElementById('abdf-modal-' + name);
		if (m){ m.hidden = false; document.body.style.overflow = 'hidden'; }
	}
	function closeModal(m){ m.hidden = true; document.body.style.overflow = ''; }

	document.addEventListener('click', (e) => {
		const opener = e.target.closest('[data-abdf-modal]');
		if (opener){ e.preventDefault(); showModal(opener.dataset.abdfModal); return; }
		const closer = e.target.closest('[data-abdf-close]');
		if (closer){ const m = closer.closest('.abdf-modal'); if (m) closeModal(m); }
	});

	// FORM PRINCIPAL
	const form = document.getElementById('abdf-form');
	if (form){
		form.addEventListener('submit', async (ev) => {
			ev.preventDefault();
			const btn = form.querySelector('button[type=submit]');
			const fb  = document.getElementById('abdf-feedback');
			fb.className = 'abdf-feedback';
			fb.textContent = '';

			if (!form.querySelector('#abdf-consent').checked){
				fb.className = 'abdf-feedback is-error';
				fb.textContent = 'Confirme o termo antes de prosseguir.';
				return;
			}

			setBusy(btn, true);
			const recaptcha = await withRecaptcha('abdf_issue');

			const payload = {
				term:    form.term.value.trim(),
				consent: form.querySelector('#abdf-consent').checked,
				abdf_website:    form.abdf_website.value,
				abdf_rendered_at: parseInt(form.abdf_rendered_at.value || '0', 10),
				recaptcha
			};

			try {
				const res = await fetch(data.rest_url, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': data.nonce },
					body: JSON.stringify(payload),
					credentials: 'same-origin',
				});
				const json = await res.json();
				if (!res.ok || !json.success){
					fb.className = 'abdf-feedback is-error';
					fb.textContent = json.message || (data.i18n.generic_error);
					return;
				}
				$('#abdf-success-text').textContent = 'Olá, ' + json.member_name + '! Seu comprovante foi gerado.';
				$('#abdf-cert-number').textContent = 'Nº de verificação: ' + json.cert_number;
				$('#abdf-download-link').href = json.download_url;
				showModal('sucesso');
				fb.className = 'abdf-feedback is-ok';
				fb.innerHTML = 'Pronto! <a href="'+ json.download_url +'">Baixar PDF</a>.';
			} catch (err){
				fb.className = 'abdf-feedback is-error';
				fb.textContent = data.i18n.generic_error;
			} finally {
				setBusy(btn, false);
			}
		});
	}

	// FORM DE VERIFICAÇÃO
	const vf = document.getElementById('abdf-verify-form');
	if (vf){
		vf.addEventListener('submit', async (ev) => {
			ev.preventDefault();
			const btn = vf.querySelector('button[type=submit]');
			const out = document.getElementById('abdf-verify-result');
			out.className = 'abdf-feedback';
			out.textContent = '';
			setBusy(btn, true);

			const payload = {
				number: vf.number.value.trim(),
				abdf_website: vf.abdf_website.value,
				abdf_rendered_at: parseInt(vf.abdf_rendered_at.value || '0', 10),
			};

			try {
				const res = await fetch(data.verify_rest_url, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': data.nonce },
					body: JSON.stringify(payload),
					credentials: 'same-origin',
				});
				const json = await res.json();
				if (!res.ok || !json.success){
					out.className = 'abdf-feedback is-error';
					out.textContent = json.message || data.i18n.generic_error;
					return;
				}
				out.className = 'abdf-feedback is-ok';
				out.innerHTML = '<strong>Comprovante autêntico.</strong><br>'
					+ 'Número: <code>'+ json.cert_number +'</code><br>'
					+ 'Titular (parcial): '+ json.name +'<br>'
					+ 'Exercício: '+ json.year +'<br>'
					+ 'Emitido em: '+ new Date(json.issued_at.replace(' ','T')).toLocaleString('pt-BR')
					+ (json.still_valid ? '' : '<br><em>Atenção: o associado(a) pode não estar mais em dia atualmente.</em>');
			} catch (err){
				out.className = 'abdf-feedback is-error';
				out.textContent = data.i18n.generic_error;
			} finally {
				setBusy(btn, false);
			}
		});
	}
})();
