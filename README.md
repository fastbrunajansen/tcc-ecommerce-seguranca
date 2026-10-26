# Sistema de E-commerce Seguro — ambiente de testes do TCC

Repositório da implementação prática do Trabalho de Conclusão de Curso **"Sistema de E-commerce Seguro: Análise e Implementação de Medidas de Segurança para Proteção contra Ataques Cibernéticos"**, de Bruna Jansen (Sistemas de Informação, FURB, 2026), orientado pelo Prof. Alexander Roberto Valdameri.

O trabalho compara duas versões da mesma loja virtual (WordPress + WooCommerce):

- **Cenário A (vulnerável):** configuração padrão, com uma página de consulta propositalmente vulnerável a SQL Injection.
- **Cenário B (protegido):** a mesma loja e os mesmos dados, com quatro defesas desenvolvidas pela autora.

> ⚠️ **Ambiente de laboratório.** As senhas em `compose.yml` e os usuários nos dumps são fictícios e de teste. Não use estes arquivos em produção nem exponha o ambiente na internet.

## As quatro defesas (Cenário B)

| Defesa | Arquivo | Onde fica no contêiner |
|---|---|---|
| Consulta parametrizada + validação de tipo + erro seguro (SQL Injection) | `busca-produto.php` | raiz do site (`/var/www/html/`) |
| Cabeçalhos HTTP de segurança e supressão de versão | `seguranca-headers.conf` e `seguranca-php.ini` | `/etc/apache2/conf-enabled/` e `/usr/local/etc/php/conf.d/` |
| Limite de tentativas de login (força bruta): 5 falhas por IP → bloqueio de 15 min | `limite-login.php` | `wp-content/plugins/` |
| Autenticação multifator TOTP (RFC 6238) com página de verificação própria | `mfa-login.php`, `mfa-verificar.php`, `totp-lib.php` | `wp-content/plugins/` |

A versão vulnerável da página de busca está em `snapshots/busca-produto-VULNERAVEL.php`.

## Estrutura

```
compose.yml                 contêineres (wordpress:php8.3-apache + mariadb:11) — Cenário A
compose.cenario-b.yml       complemento do Cenário B (configuração de hardening)
seguranca-php.ini           hardening do PHP (expose_php e display_errors)
baseline_inicial.sql        banco no estado inicial (antes das auditorias)
busca-produto.php           página de busca CORRIGIDA (Cenário B)
seguranca-headers.conf      hardening de cabeçalhos no Apache
limite-login.php            plugin de limite de tentativas
mfa-login.php               plugin de MFA (intercepta o login)
mfa-verificar.php           página de verificação do código
totp-lib.php                biblioteca PHPGangsta/GoogleAuthenticator (cálculo TOTP)
zap.yaml                    configuração do OWASP ZAP
relatorio-zap-*.html        relatórios do ZAP da época da implementação
snapshots/                  estado do Cenário A e registros de cada etapa (históricos)
documentacoes/              diário, decisões, contexto, evidências, rascunhos e versões da monografia
```

## Resultados (resumo)

| Ferramenta | Cenário A | Cenário B |
|---|---|---|
| OWASP ZAP (Medium + Low, ZAP 2.17.0, 171 endpoints) | 16 | 11 (−31%) |
| SQLMap em `busca-produto.php` | injetável (boolean, time-based, UNION); extração de `wp_users` | "all tested parameters do not appear to be injectable" |
| Hydra (`rockyou.txt`) contra `wp-login.php` | senha descoberta em 38 tentativas, ~4 s | bloqueio a partir da 5ª tentativa |
| Login com senha correta, sem código MFA | acesso concedido | acesso negado |

Evidências brutas em `documentacoes/evidencias-zap/` e `documentacoes/evidencias-sqlmap/`. Os números de ZAP em `snapshots/auditoria-final-comparativo.txt` foram corrigidos depois; ver `snapshots/NOTA-correcoes-posteriores.txt`.

## Como subir o ambiente

```bash
# Cenário A (configuração padrão)
docker compose up -d

# Cenário B (monta seguranca-headers.conf e seguranca-php.ini e ativa o mod_headers)
docker compose -f compose.yml -f compose.cenario-b.yml up -d
```

Os plugins (`limite-login.php`, `mfa-*.php`, `totp-lib.php`) ficam em `wp-content/plugins/` e o `busca-produto.php` na raiz do site, dentro do volume `wp_data`.

O site fica em `http://localhost:8080`, acessível apenas de dentro da VM; do computador hospedeiro, use um túnel SSH (`ssh -L 8080:localhost:8080 ...`). O banco pode ser restaurado a partir de `baseline_inicial.sql` (estado inicial) ou de `snapshots/cenario-A-banco.sql` (Cenário A congelado).

## Limitações conhecidas

- HSTS não aplicado (ambiente em HTTP).
- CSP com `'unsafe-inline'` em `script-src` e `style-src`; diretivas `frame-ancestors` e `form-action` não definidas.
- A regra de cookie do Apache não alcança todos os cookies do WooCommerce (HttpOnly e SameSite).
- O limite de login conta falhas por IP; não contém ataques distribuídos.
- MFA por TOTP não é resistente a phishing (NIST SP 800-63B-4); o segredo fica sem cifra no banco.

## Versões do Cenário B

- **v1 (22/09 a 05/10/2026):** arquivos em `snapshots/cenario-B-v1/`.
- **v2 (26/10/2026):** limite e uso único no código MFA, segredo TOTP de 160 bits, CSP com `frame-ancestors` e `form-action`, regra de cookie corrigida, busca restrita a produtos publicados, erros capturados, configuração versionada. Roteiro de testes em `documentacoes/ROTEIRO-cenario-B-v2.md`.
