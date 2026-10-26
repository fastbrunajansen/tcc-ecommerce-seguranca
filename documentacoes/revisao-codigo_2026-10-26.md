# Revisão do código do Cenário B — 26/10/2026

Prioridade pensando na banca (27/11). Cada mudança no Cenário B exige refazer o teste correspondente; a proposta é uma única rodada final de auditoria depois dos ajustes.

## Prioridade 1 — corrigir (afeta o que o texto afirma ou fere uma norma; poucas linhas)

| # | Arquivo | Problema | Norma | Correção |
|---|---|---|---|---|
| 1 | `mfa-verificar.php` | Tentativas de código ilimitadas; o mesmo código pode ser reaceito; tolerância de 2 passos (±60 s); com MFA, o `wp_login` não dispara e o contador do `limite-login` não zera | RFC 4226 §7.3; RFC 6238 §5.2; NIST SP 800-63B-4 §3.1.4/3.1.5; OWASP MFA | Aplicada na versão 2 (26/10) |
| 2 | `mfa-login.php` | `createSecret()` usa 16 caracteres base32 = **80 bits**; a RFC 4226 (R6) exige **no mínimo 128 bits** e recomenda 160 | RFC 4226 §4, R6 | `createSecret(32)` (160 bits) e recadastrar o segredo no aplicativo |
| 3 | `seguranca-headers.conf` | `Header always edit Set-Cookie` atua só em uma das tabelas de cabeçalhos do Apache; a documentação manda repetir o `edit` com `always` e `onsuccess` quando o cabeçalho não foi criado pelo próprio mod_headers. Provável causa dos alertas de cookie que persistem no ZAP | Apache mod_headers (documentação oficial) | Acrescentar `Header onsuccess edit Set-Cookie ...` e testar com `curl -I` em `/?add-to-cart=39&page_id=7`. Cuidado com atributo duplicado em cookies que já têm HttpOnly |

## Prioridade 2 — ganhos rápidos (opcionais; melhoram o ZAP e o texto)

| # | Arquivo | Melhoria |
|---|---|---|
| 4 | `seguranca-headers.conf` | Acrescentar `frame-ancestors 'self'; form-action 'self'` à CSP (resolve o alerta 10055 e alinha com a OWASP, que considera o frame-ancestors substituto do X-Frame-Options). Testar o painel do WordPress depois |
| 5 | `busca-produto.php` | Restringir a consulta a produtos publicados (`AND post_type = 'product' AND post_status = 'publish'`): hoje, qualquer ID de `wp_posts` devolve o título, inclusive rascunhos e páginas privadas |
| 6 | `busca-produto.php` | Credenciais do banco escritas no código (CWE-798); ler das variáveis de ambiente que o `compose.yml` já define (`getenv('WORDPRESS_DB_PASSWORD')` etc.) |
| 7 | `compose.yml` | Porta em `0.0.0.0:8080`; usar `127.0.0.1:8080` para isolar o laboratório da rede |

## Prioridade 3 — apenas citar em Extensões (não implementar agora)

- Usuário de banco com privilégio mínimo (só leitura) para a página de busca.
- Cifrar o segredo TOTP no banco (RFC 6238 §5.1 recomenda armazenamento seguro) e não exibi-lo após a ativação.
- Exigir a confirmação de um código antes de ativar o MFA (evita bloquear o próprio usuário).
- Autenticação resistente a phishing (FIDO2/WebAuthn), que o NIST e a OWASP recomendam.
- Desativar o XML-RPC e a enumeração de usuários (`?author=`, `/wp-json/wp/v2/users`).
- Bloqueio por IP no firewall de rede para ataques distribuídos (Hariono e Ishomuddin, 2026).
- HTTPS + HSTS.

## O que está bom (vale defender na banca)

- `busca-produto.php`: validação de tipo + consulta parametrizada + erro genérico com `error_log` + `htmlspecialchars` na saída. Defesa em camadas correta.
- `limite-login.php`: usa `REMOTE_ADDR` (não confia em `X-Forwarded-For`, que o atacante controla); reinicia o contador após login; prioridade 30 no `authenticate`, antes do MFA (40).
- `mfa-login.php`: não reimplementa a criptografia (usa biblioteca), não usa QR code de serviço externo, token temporário aleatório de 32 caracteres com validade de 5 min.
