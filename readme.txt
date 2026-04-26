=== ABDF — Comprovante de Situação Cadastral ===
Contributors: abdf
Tags: bibliotecários, abdf, comprovante, pdf, anuidade
Requires at least: 5.5
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Plugin para a Associação dos Bibliotecários e Profissionais da Ciência da Informação do DF: o(a) associado(a) digita seu nome ou e-mail e baixa, no frontend, um PDF comprovando que está em dia com a anuidade.

== Description ==

Funcionalidades:

* Página pública com shortcode `[abdf_comprovante]` (formulário moderno, com modais explicativos).
* Página pública com shortcode `[abdf_verificar]` para validar autenticidade pelo número do comprovante.
* PDF gerado por biblioteca interna (sem dependências externas) — vendor/micropdf.
* Cadastro inicial com 29 associados(as) carregados na ativação.
* Importação por CSV (cabeçalhos `nome, email, cpf, status, paid_until, notes`).
* Configuração do texto do comprovante com variáveis `{NOME}`, `{ANO}`, `{EMAIL}`.
* Proteções: nonce REST, honeypot, *min-time* anti-bot, *rate limit* por IP, reCAPTCHA v3 opcional.
* Reaproveitamento de certificado nos 30 minutos seguintes (evita duplicidade por F5).
* Log de acessos e relatório de comprovantes emitidos.

== Como instalar ==

1. Copie a pasta `abdf-comprovante` para `wp-content/plugins/`.
2. Ative o plugin em **Plugins**.
3. Crie uma página com o shortcode `[abdf_comprovante]` e divulgue o link para os(as) associados(as).
4. (Opcional) Em **ABDF → Configurações**, ajuste razão social, endereço, texto do comprovante e reCAPTCHA.

== Estrutura de arquivos ==

* `abdf-comprovante.php` — bootstrap.
* `includes/` — classes (banco, associados, certificados, segurança, REST, admin, PDF).
* `templates/` — formulário público, verificação e telas administrativas.
* `assets/` — CSS/JS.
* `vendor/micropdf/micropdf.php` — biblioteca de PDF embutida.

== Changelog ==

= 1.0.0 =
* Versão inicial.
