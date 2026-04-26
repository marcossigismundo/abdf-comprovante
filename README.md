# ABDF — Comprovante de Situação Cadastral

Plugin WordPress para a **Associação dos Bibliotecários e Profissionais da Ciência da Informação do DF (ABDF)**.

O(a) associado(a) acessa uma página pública, digita seu nome ou e-mail, e baixa um **PDF assinado eletronicamente** comprovando que está em dia com a anuidade. O comprovante é numerado e pode ser verificado por terceiros em uma página pública de validação.

---

## Recursos

- 🪪 **Página pública** com formulário moderno (`[abdf_comprovante]`).
- 📄 **PDF gerado em PHP puro** — biblioteca interna `MicroPDF` (Helvetica + acentos PT, sem dependências externas).
- 🔢 **Número sequencial** `AAAA-NNNNN` por exercício, com hash de verificação.
- 🔍 **Página de validação** (`[abdf_verificar]`) — criada automaticamente em `/verificar-comprovante/`.
- 🛡 **Anti-bot em camadas**: nonce REST + honeypot + min-time + rate-limit por IP + reCAPTCHA v3 opcional.
- 📥 **Importação por CSV** com upsert por e-mail (`nome,email,cpf,status,paid_until,notes`).
- 📊 **Painel administrativo** com associados, comprovantes emitidos, log de acessos e configurações.
- ♻️ **Reaproveitamento** de certificado por 30 minutos (evita duplicação por F5).
- 🌱 **Seed inicial** com 29 associados(as) carregados na ativação.
- 🧩 **Texto do comprovante editável** com placeholders `{NOME}`, `{ANO}`, `{EMAIL}`.

---

## Instalação

```bash
# 1. Clone o repositório dentro de wp-content/plugins
cd wp-content/plugins
git clone https://github.com/marcossigismundo/abdf-comprovante.git

# 2. Ative no WP-Admin → Plugins
```

Requisitos:

- WordPress ≥ 5.5
- PHP ≥ 7.4
- Extensão **mbstring** (já vem habilitada no XAMPP).

---

## Uso

### 1. Crie a página pública para emissão

Crie uma página em **Páginas → Adicionar nova** chamada *"Comprovante"* contendo apenas o shortcode:

```
[abdf_comprovante]
```

Divulgue esse link aos(às) associados(as).

### 2. Página de verificação

Já é criada automaticamente em **/verificar-comprovante/**. Para customizar, vá em **ABDF → Configurações → Página pública de verificação**.

### 3. Cadastro de associados(as)

Em **ABDF → Associados(as)** você pode:

- Editar/adicionar manualmente.
- Importar CSV com cabeçalho `nome,email,cpf,status,paid_until,notes` (separador `,` ou `;`).

### 4. Personalização do comprovante

Em **ABDF → Configurações** ajuste razão social, CNPJ, endereço, telefones e o texto do comprovante (com placeholders `{NOME}`, `{ANO}`, `{EMAIL}`).

### 5. (Opcional) reCAPTCHA v3

Em **ABDF → Configurações → reCAPTCHA v3** preencha *Site key* e *Secret key*. Deixe em branco para desativar.

---

## Arquitetura

```
abdf-comprovante/
├── abdf-comprovante.php          # bootstrap WP
├── uninstall.php
├── includes/
│   ├── class-plugin.php          # bootstrapper, ativação/desativação
│   ├── class-database.php        # schema (3 tabelas)
│   ├── class-members.php         # CRUD + busca + import CSV + seed
│   ├── class-certificates.php    # numeração, emissão, regras de elegibilidade
│   ├── class-pdf.php             # composição do PDF (cabeçalho ABDF + corpo + rodapé)
│   ├── class-rest.php            # endpoints /abdf/v1/issue e /abdf/v1/verify + handler de download
│   ├── class-shortcode.php       # shortcodes [abdf_comprovante] e [abdf_verificar]
│   ├── class-security.php        # honeypot, min-time, rate-limit, reCAPTCHA, log
│   └── class-admin.php           # menus e telas WP-Admin
├── templates/
│   ├── form.php                  # form público de emissão
│   ├── verify.php                # form público de verificação
│   └── admin/                    # telas do WP-Admin
├── assets/
│   ├── css/{frontend,admin}.css
│   └── js/{frontend,admin}.js
├── vendor/micropdf/
│   └── micropdf.php              # biblioteca PDF embutida (~250 linhas)
├── readme.txt                    # readme padrão do WP
├── README.md                     # este arquivo
├── CLAUDE.md                     # contexto para Claude/IA
└── .gitignore
```

### Tabelas criadas

- `wp_abdf_members` — associados(as) (nome, e-mail, CPF, status, vigência).
- `wp_abdf_certificates` — comprovantes emitidos (número, hash, IP, ano).
- `wp_abdf_access_log` — log de tentativas (IP, termo, sucesso/falha, user-agent).

### Endpoints REST

| Método | Rota | Uso |
|---|---|---|
| `POST` | `/wp-json/abdf/v1/issue`  | Localiza o associado e gera o link de download. |
| `POST` | `/wp-json/abdf/v1/verify` | Verifica autenticidade pelo número do comprovante. |
| `GET`  | `/?abdf_download=NNN&h=…` | Streaming do PDF (autenticado por hash-prefix). |

---

## Segurança

| Camada | Descrição |
|---|---|
| Nonce REST | `X-WP-Nonce` enviado pelo JS. |
| Honeypot | Campo escondido `abdf_website` deve ficar vazio. |
| Min-time | Submissão antes de 2s rejeitada. |
| Rate-limit | 8 tentativas/hora e 30/dia por IP (transients). |
| reCAPTCHA v3 | Opcional, score mínimo configurável. |
| Hash de download | `?h=` exige conhecer o prefixo do hash do certificado. |
| Privacidade na verificação | Mostra só primeiro nome + iniciais ("João S. M."). |

---

## Desinstalação

Em **Plugins → ABDF Comprovante → Excluir**, o `uninstall.php` apaga as 3 tabelas e todas as opções `abdf_*`.

---

## Licença

GPL-2.0-or-later.

---

## Créditos

Desenvolvido para a ABDF, com Claude Code (Anthropic).
