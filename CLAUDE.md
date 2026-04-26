# Contexto para Claude — abdf-comprovante

Este arquivo orienta agentes de IA (Claude Code, Claude no GitHub etc.) que vão modificar este plugin.

## Visão de produto

Plugin WordPress para a **Associação dos Bibliotecários e Profissionais da Ciência da Informação do DF (ABDF)**. Permite que associados(as) emitam, no frontend, um **comprovante em PDF** de que estão em dia com a anuidade, e que terceiros validem esse comprovante por número.

Público:
- **Associados(as)** — usuários anônimos que digitam nome ou e-mail e recebem o PDF.
- **Diretoria/secretaria** — administradores WP que cadastram, editam e auditam.
- **Terceiros** — quem precisa validar um comprovante (empregador, banca de concurso etc.).

## Princípios de design (NÃO viole sem combinar antes)

1. **Auto-contido**: nada de Composer, CDN obrigatório ou download na ativação. A biblioteca de PDF (`vendor/micropdf/micropdf.php`) é embutida porque o cliente exigiu. Não troque por FPDF/TCPDF/dompdf sem aprovação.
2. **Frontend público com defesa em camadas**: nonce + honeypot + min-time + rate-limit + reCAPTCHA opcional. Se acrescentar uma nova rota pública, replique TODAS essas camadas.
3. **Dados mínimos**: a verificação pública mostra apenas primeiro nome + iniciais — não exponha e-mail/CPF/telefone em endpoints públicos.
4. **Idempotência por janela**: se o(a) mesmo(a) associado(a) reemitir em < 30 min, **reaproveite** o certificado anterior (`ABDF_Certificates::recent_for_member`). Não gere número novo a cada F5.
5. **Numeração**: `AAAA-NNNNN` por exercício, contador em `option abdf_seq_counter_<ano>`. Nunca reaproveite números de anos anteriores.
6. **i18n**: textos do usuário sempre via `__()` / `_e()` com domínio `abdf-comprovante`.

## Camadas

```
abdf-comprovante.php (bootstrap, define constantes ABDF_*)
└── includes/class-plugin.php   ← registra hooks, init das demais classes
    ├── class-database.php      ← schema (3 tabelas), install/uninstall
    ├── class-members.php       ← CRUD, busca tolerante a acentos, import CSV, seed
    ├── class-certificates.php  ← regras de elegibilidade + numeração
    ├── class-pdf.php           ← compõe o PDF chamando ABDF_MicroPDF
    ├── class-rest.php          ← /abdf/v1/issue, /abdf/v1/verify, handler ?abdf_download=
    ├── class-shortcode.php     ← [abdf_comprovante], [abdf_verificar]
    ├── class-security.php      ← honeypot, min-time, rate-limit, reCAPTCHA, log
    └── class-admin.php         ← menus WP-Admin + handlers de admin-post
```

PDF: `class-pdf.php` é o "template" do comprovante. **MicroPDF** (vendor/) é a primitiva — tem só o necessário (texto posicionado, multi-line, line, rect, fill, fontes Helvetica embutidas via WinAnsiEncoding/CP1252). Se um requisito novo extrapolar (ex.: imagens raster, fontes TTF), considere se vale a pena estender MicroPDF ou se é melhor pedir aprovação para outra abordagem.

## Convenções

- Prefixo de tudo: `abdf_` / `ABDF_` / `[abdf_*]` / option `abdf_settings`.
- Banco: `wp_abdf_members`, `wp_abdf_certificates`, `wp_abdf_access_log` (todas via `ABDF_Database::table()`).
- Settings em uma única `option('abdf_settings')` (array). Sanitização em `ABDF_Admin::sanitize_settings`.
- Nonce REST aceito via header `X-WP-Nonce` (já enviado pelo JS).
- Datas: armazene em ISO; exiba via `date_i18n` ou `ABDF_PDF::format_date_pt`.
- IPs: sempre via `ABDF_Security::client_ip()` (lida com Cloudflare e proxy).

## Anti-padrões

- ❌ Aceitar requisição pública sem `ABDF_Security::check_bot_signals` + `check_rate_limit`.
- ❌ Expor PII (e-mail, CPF) na verificação pública.
- ❌ Gerar número novo de comprovante quando há um recente válido.
- ❌ Usar `wp_redirect` sem `wp_safe_redirect` em handlers admin-post.
- ❌ Adicionar dependência externa de PDF.
- ❌ Comentários explicativos do que o código faz (mantenha só o "por quê" quando não-óbvio).

## Testes manuais úteis

```bash
# Lint sintático de todos os PHP do plugin
PHP="C:/xampp-tainacan/php/php.exe"
find . -name "*.php" -exec "$PHP" -l {} \;

# Geração de PDF stand-alone (sem WP)
"$PHP" -r "
define('MICROPDF_STANDALONE', true);
require 'vendor/micropdf/micropdf.php';
\$pdf = new ABDF_MicroPDF();
\$pdf->set_font('helvetica','B',16);
\$pdf->text(20,30,'Olá ção çã áéíóú','C',170);
file_put_contents('teste.pdf', \$pdf->output());
echo 'OK';
"
```

## Ambiente do mantenedor

- XAMPP em `C:\xampp-tainacan\htdocs\wordpress\` (PHP em `C:\xampp-tainacan\php\php.exe`).
- Plugin instalado em `wp-content/plugins/abdf-comprovante/`.
- Outros plugins coexistentes: Tainacan e família — não há conflito conhecido (prefixo `abdf_` evita colisões).

## Roadmap (sugestões em aberto)

- QR Code no PDF (gerador puro PHP, ~150 linhas).
- Envio por e-mail do PDF (depende de SMTP configurado).
- Carteirinha do(a) associado(a) (variante do template, tamanho cartão).
- Bloco Gutenberg envolvendo os dois shortcodes.
- Webhook quando um comprovante é emitido (para integrar com Discord/Slack da diretoria).

Antes de implementar qualquer item desse roadmap, **confirme com o(a) mantenedor(a)** — vários têm impacto em LGPD, infraestrutura ou identidade visual.
