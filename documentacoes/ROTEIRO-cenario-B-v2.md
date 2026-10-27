# Roteiro — aplicar e testar o Cenário B versão 2

> **Situação em 26/10/2026, 22h:** Partes 0 a 4 e testes T1 a T4 concluídos e aprovados. Faltam T5 (Hydra), T6 (ZAP final) e a Parte 6 (registro). Lembre: `export COMPOSE_FILE=compose.yml:compose.cenario-b.yml` a cada novo terminal.

Preparado em 26/10/2026. Tempo estimado: 1h a 1h30 (a maior parte é esperar o ZAP e o SQLMap).
Marque cada item ao concluir. Se algum resultado for diferente do **esperado**, pare e mande a saída para o Claude.

## O que muda na versão 2

| Arquivo | Mudança |
|---|---|
| `mfa-verificar.php` | No máximo 5 códigos por login; códigos errados também contam no limite por IP; código não pode ser reutilizado; tolerância de relógio de 1 passo (±30 s); dispara o `wp_login` ao concluir |
| `mfa-login.php` | Segredo TOTP de 160 bits (antes 80; RFC 4226 exige no mínimo 128) |
| `seguranca-headers.conf` | CSP com `frame-ancestors` e `form-action`; regra de cookie nas duas tabelas do Apache, sem duplicar atributos |
| `busca-produto.php` | Credenciais do banco lidas do ambiente; só retorna produtos publicados; ID precisa ser ≥ 1; erros do banco capturados (no PHP 8.1+ o mysqli lança exceções, e a checagem antiga não as pegava) |
| `compose.yml` | ~~Porta só em `127.0.0.1:8080`~~ — revertido para `8080:80` em 26/10; o isolamento foi feito no `passt` (ver `vm-rede-passt.md`) |
| `compose.cenario-b.yml` (novo) | Monta o `seguranca-headers.conf` e o `seguranca-php.ini` do repositório no contêiner e ativa o `mod_headers` |
| `seguranca-php.ini` (novo) | `expose_php = Off` versionado; `display_errors = Off` (erros vão para o log, não para a página) |
| `snapshots/cenario-B-v1/` (novo) | Cópia dos arquivos da versão 1, para registro |

---

## Parte 0 — No Manjaro: enviar a versão 2 para o GitHub

```bash
cd ~/Documentos/tcc-rep/tcc-ecommerce-seguranca
git pull
unzip -o ~/Downloads/cenario-b-v2.zip
git add -A
git status
git commit -m "Cenario B v2: correcoes de MFA, cookies, CSP, busca e compose (antes dos testes)"
git push
```

- [ ] `git status` mostrou só os arquivos da tabela acima, `snapshots/cenario-B-v1/` e `documentacoes/`

## Parte 1 — Na VM: atualizar o repositório

```bash
ssh -p 2222 bruna@localhost     # desde 26/10 (antes: bruna@192.168.15.150)
cd ~/tcc-ecommerce/cenario-a
git status
git pull
```

- [ ] `git pull` terminou sem erro. Se reclamar de "local changes", **pare** e mande a mensagem.

Daqui em diante, para o `docker compose` usar sempre os dois arquivos:

```bash
export COMPOSE_FILE=compose.yml:compose.cenario-b.yml
```

(Vale só para esta sessão do terminal. Se reconectar, rode de novo.)

## Parte 2 — Guardar o estado atual (antes da mudança)

```bash
mkdir -p snapshots/cenario-B-v2
curl -sI http://localhost:8080 > snapshots/cenario-B-v1/curl-cabecalhos-v1.txt
curl -sI "http://localhost:8080/?add-to-cart=39&page_id=7" > snapshots/cenario-B-v1/curl-cookies-v1.txt
docker compose exec wordpress ls /var/www/html/wp-content/plugins/ /var/www/html/busca-produto.php
```

- [ ] A listagem mostra `mfa-login.php`, `mfa-verificar.php`, `totp-lib.php` e `limite-login.php` **direto** em `plugins/`, e o `busca-produto.php` existe. Se estiverem dentro de uma subpasta, ajuste os caminhos da Parte 3.

## Parte 3 — Copiar os PHP novos para o contêiner

```bash
docker compose cp busca-produto.php wordpress:/var/www/html/busca-produto.php
docker compose cp mfa-login.php wordpress:/var/www/html/wp-content/plugins/mfa-login.php
docker compose cp mfa-verificar.php wordpress:/var/www/html/wp-content/plugins/mfa-verificar.php
```

## Parte 4 — Recriar o contêiner com a configuração versionada

```bash
docker compose up -d
docker compose ps
```

- [ ] `wordpress` e `db` aparecem como **running**. Se o `wordpress` reiniciar em loop: `docker compose logs wordpress --tail 30` e mande a saída.

**Se der errado e quiser voltar à versão 1:**

```bash
export COMPOSE_FILE=compose.yml
docker compose up -d
docker compose cp snapshots/cenario-B-v1/busca-produto.php wordpress:/var/www/html/busca-produto.php
docker compose cp snapshots/cenario-B-v1/mfa-login.php wordpress:/var/www/html/wp-content/plugins/mfa-login.php
docker compose cp snapshots/cenario-B-v1/mfa-verificar.php wordpress:/var/www/html/wp-content/plugins/mfa-verificar.php
```

(Nesse caso os cabeçalhos ficam sem efeito até reaplicar; avise o Claude.)

---

## Parte 5 — Testes

### T1. Cabeçalhos e PHP

```bash
curl -sI http://localhost:8080 | tee snapshots/cenario-B-v2/curl-cabecalhos-v2.txt
docker compose exec wordpress php -i | grep -E "expose_php|display_errors"
```

Esperado:
- [ ] `Content-Security-Policy` termina com `frame-ancestors 'self'; form-action 'self'`
- [ ] `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` e `Permissions-Policy` presentes
- [ ] `Server: Apache` (sem versão) e **nenhum** `X-Powered-By`
- [ ] `expose_php => Off => Off` e `display_errors => Off => Off`

### T2. Cookies

```bash
curl -sI "http://localhost:8080/?add-to-cart=39&page_id=7" | grep -i set-cookie | tee snapshots/cenario-B-v2/curl-cookies-v2.txt
```

Esperado:
- [ ] Cada `Set-Cookie` tem `HttpOnly` e `SameSite=Strict`, **uma vez só**.
  Se continuar faltando, a hipótese não se confirmou: anote e siga (vira limitação no texto).

### T3. Busca de produto e SQL Injection

```bash
curl -s "http://localhost:8080/busca-produto.php?id=39"; echo
curl -s "http://localhost:8080/busca-produto.php?id=1"; echo
curl -s "http://localhost:8080/busca-produto.php?id=1%20OR%201=1"; echo
curl -s "http://localhost:8080/busca-produto.php?id=0"; echo
```

Esperado:
- [ ] `id=39` → nome de um produto
- [ ] `id=1` → `Nenhum produto encontrado.` (é um post, não um produto)
- [ ] `id=1 OR 1=1` e `id=0` → `Parâmetro inválido.`

```bash
docker run --rm -it --network host parrotsec/sqlmap \
  -u "http://localhost:8080/busca-produto.php?id=39" \
  --batch --level=2 --risk=2 2>&1 | tee snapshots/cenario-B-v2/sqlmap-cenarioB-v2.txt
```

- [ ] Termina com `all tested parameters do not appear to be injectable`

### T4. MFA (no navegador do Manjaro)

Acesse `http://localhost:8080/wp-admin` no navegador do Manjaro (sem túnel: o `passt` já encaminha a porta), com o usuário que tem MFA (o mesmo do teste de 03/10).

**Gerar a chave nova de 160 bits:**
1. Perfil → desmarque **Ativar MFA** → Salvar. A chave nova (32 caracteres) aparece.
2. No Google Authenticator, apague a conta antiga e adicione a nova chave (inserir chave → baseada em tempo).
3. Marque **Ativar MFA** → Salvar → Sair.

**Testes (tire print de cada um):**
- [ ] **4a.** Login + código certo → entra no painel
- [ ] **4b.** Saia e faça login de novo **antes de o código mudar**, digitando o mesmo código → `Codigo invalido` (uso único)
- [ ] **4c.** Na mesma tela, erre o código até aparecer `Tentativas esgotadas. Faca login novamente.`
- [ ] **4d.** Tente fazer login de novo → `Acesso temporariamente bloqueado por excesso de tentativas` (as falhas do código contaram no limite por IP)

**Liberar o bloqueio (na VM):**

```bash
docker compose exec db mariadb -u loja_user -psenha_teste_local loja \
  -e "DELETE FROM wp_options WHERE option_name LIKE '%ll_tentativas%';"
```

- [ ] **4e.** Login + código certo → entra (confirma que tudo voltou ao normal)

### T5. Força bruta (Hydra) — regressão

```bash
hydra -l carla_souza -P rockyou.txt localhost -s 8080 http-post-form \
  "/wp-login.php:log=^USER^&pwd=^PASS^&wp-submit=Acessar:F=está incorreta" -V -f \
  2>&1 | tee snapshots/cenario-B-v2/hydra-cenarioB-v2.txt
```

- [ ] Senha real (`123456`) **não** é descoberta. (Um "falso positivo" com outra senha é esperado, como em 03/10.)
- [ ] Teste manual no navegador: após 5 senhas erradas com `carla_souza`, aparece a mensagem de bloqueio.

Depois, libere o bloqueio com o mesmo comando do T4.

### T6. Auditoria final do ZAP

```bash
docker run --rm --network host -v $(pwd):/zap/wrk/:rw zaproxy/zap-stable \
  zap-baseline.py -t http://localhost:8080 -r relatorio-zap-cenarioB-v2.html \
  2>&1 | tee snapshots/cenario-B-v2/zap-saida-v2.txt
mv relatorio-zap-cenarioB-v2.html snapshots/cenario-B-v2/
```

- [ ] Terminou com a linha `FAIL-NEW: 0 ... PASS: ...`
- [ ] Esperado: o alerta `CSP: Failure to Define Directive with No Fallback [10055]` sumiu; se o T2 funcionou, os de cookie também.

---

## Parte 6 — Registrar e enviar

```bash
git add snapshots/cenario-B-v1 snapshots/cenario-B-v2
git status
git commit -m "Cenario B v2: evidencias da auditoria final"
git push
```

Mande para o Claude: `zap-saida-v2.txt`, `relatorio-zap-cenarioB-v2.html`, `curl-cabecalhos-v2.txt`, `curl-cookies-v2.txt`, `sqlmap-cenarioB-v2.txt`, `hydra-cenarioB-v2.txt` e os prints do T4. Com eles, o Cap. 3 e a Tabela 2 são atualizados com os números finais.
