# Guia da implementação — explicado arquivo por arquivo

Escrito em 26/10/2026, com base no repositório `tcc-ecommerce-seguranca` no commit `cd54e9b` (Cenário B versão 2, ainda não testada na VM).

A ideia deste guia é que você consiga abrir qualquer arquivo do repositório e saber **o que ele faz, por que existe, qual ataque ele impede e o que mudou na versão 2**, inclusive para responder à banca.

---

## 1. A visão geral, em uma frase

Você montou **uma loja virtual de verdade (WordPress + WooCommerce)** dentro de uma máquina virtual, e a estudou em dois estados:

- **Cenário A — "a loja como ela vem":** a configuração padrão, mais uma página de busca escrita de propósito com um erro clássico (SQL Injection). É o "antes".
- **Cenário B — "a loja protegida":** a mesma loja, com os mesmos dados, mais quatro defesas que você desenvolveu. É o "depois".

As mesmas ferramentas de ataque (OWASP ZAP, SQLMap e Hydra) foram usadas nos dois cenários, e a diferença entre os resultados é o que a monografia mede.

## 2. Onde as coisas rodam

```
Seu computador (Manjaro, IP 192.168.15.150 no Wi-Fi)
│   passt (rede da VM): encaminha 127.0.0.1:8080 → VM:8080 e 127.0.0.1:2222 → VM:22
└── Máquina virtual (Ubuntu Server 26.04, sem IP próprio na rede)
    └── Docker
        ├── contêiner "wordpress"  → Apache + PHP 8.3 + WordPress + WooCommerce (a loja)
        └── contêiner "db"         → MariaDB 11 (o banco de dados "loja")
```

- **Por que uma VM?** Para isolar o laboratório: os ataques acontecem dentro dela, sem tocar no seu computador.
- **Por que Docker?** Para que a loja seja recriável igual, sempre, a partir de um arquivo de configuração (`compose.yml`).
- **Volume `wp_data`:** é a "pasta persistente" do WordPress. Os plugins e o `busca-produto.php` ficam lá dentro e sobrevivem quando o contêiner é recriado.
- **Volume `db_data`:** a mesma coisa, para o banco.
- **`passt`:** é o componente de rede da VM (GNOME Boxes/libvirt). A VM não aparece na rede Wi-Fi com IP próprio; o único jeito de chegar nela é pelas portas que o `passt` abre **no Manjaro**. O `192.168.15.150` é o IP do Manjaro, não da VM. Desde 26/10, o `passt` só aceita conexões do próprio Manjaro (`127.0.0.1`); por isso o acesso é `ssh -p 2222 bruna@localhost` e `http://localhost:8080`. Detalhes em `documentacoes/vm-rede-passt.md`.

> **Detalhe importante (motivo de uma mudança da v2):** tudo o que fica **fora** desses volumes, como a configuração do Apache e do PHP, some se o contêiner for recriado. Na v1, essas configurações tinham sido copiadas à mão para dentro do contêiner. Na v2, elas passaram a vir dos arquivos do repositório (seção 4.2).

## 3. O mapa do repositório

| Arquivo ou pasta | Em uma linha | Cenário |
|---|---|---|
| `compose.yml` | Receita dos dois contêineres | A (base) |
| `compose.cenario-b.yml` | Complemento que liga o hardening | B (v2) |
| `seguranca-headers.conf` | Cabeçalhos de segurança do Apache | B |
| `seguranca-php.ini` | Ajustes de segurança do PHP | B (v2) |
| `busca-produto.php` | Página de busca **corrigida** | B |
| `snapshots/busca-produto-VULNERAVEL.php` | Página de busca **vulnerável** | A |
| `limite-login.php` | Plugin contra força bruta | B |
| `mfa-login.php` | Plugin de autenticação em duas etapas | B |
| `mfa-verificar.php` | Página onde se digita o código de 6 dígitos | B |
| `totp-lib.php` | Biblioteca que calcula o código de 6 dígitos | B |
| `zap.yaml` | Configuração do OWASP ZAP | auditoria |
| `baseline_inicial.sql` | Banco no estado inicial | A |
| `snapshots/cenario-A-banco.sql` | Banco congelado do Cenário A (após a 1ª auditoria) | A |
| `relatorio-zap-*.html` (raiz) | Relatórios do ZAP da época da implementação | auditoria |
| `snapshots/` | Registros de cada etapa (históricos, não alterados) | — |
| `snapshots/cenario-B-v1/` | Cópia dos arquivos da versão 1 do Cenário B | B (v1) |
| `documentacoes/` | Diário, decisões, evidências, rascunhos e versões da monografia | — |
| `README.md` | Apresentação do repositório | — |

---

## 4. Arquivo por arquivo

### 4.1 `compose.yml` — a receita da loja

**O que faz:** diz ao Docker para criar dois contêineres:
- `db`: um MariaDB 11 com o banco `loja`, o usuário `loja_user` e senhas de teste;
- `wordpress`: a imagem oficial `wordpress:php8.3-apache`, ligada ao banco por variáveis de ambiente (`WORDPRESS_DB_HOST`, `WORDPRESS_DB_USER` etc.).

**Mudou em 26/10 (e foi corrigido no mesmo dia):** a v2 tinha trocado a porta para `127.0.0.1:8080`, mas os testes mostraram que essa era a camada errada. A porta voltou para `8080:80`, e o isolamento foi feito no `passt`.

**Por quê:** a loja (inclusive a página vulnerável do Cenário A) e o SSH da VM estavam acessíveis para **qualquer aparelho do seu Wi-Fi**, porque o `passt` escutava em todas as interfaces do Manjaro (`*:8080` e `*:2222`). Prender o contêiner em `127.0.0.1` dentro da VM não resolvia isso (a VM já só é alcançável pelo `passt`) e ainda cortava o acesso pelo navegador. A correção certa foi acrescentar `address='127.0.0.1'` aos dois encaminhamentos do `passt` na configuração da VM (libvirt). Resultado: só o próprio Manjaro alcança a loja e o SSH. Isso é **isolamento do laboratório**, que importa ainda mais num ambiente com uma vulnerabilidade proposital.

### 4.2 `compose.cenario-b.yml` — o "interruptor" do Cenário B (novo na v2)

**O que faz:** quando usado junto com o `compose.yml`, ele:
1. "encaixa" o arquivo `seguranca-headers.conf` do repositório dentro do Apache;
2. "encaixa" o `seguranca-php.ini` dentro do PHP;
3. ativa o módulo `mod_headers` do Apache (sem ele, o Apache não entende as diretivas `Header`).

Uso: `docker compose -f compose.yml -f compose.cenario-b.yml up -d` (ou `export COMPOSE_FILE=compose.yml:compose.cenario-b.yml`).

**Por quê:** na v1, a configuração de segurança existia **só dentro do contêiner**, copiada à mão. Isso tinha dois problemas: (1) se o contêiner fosse recriado, as defesas sumiam sem aviso; (2) o repositório não guardava exatamente o que estava rodando (o `expose_php = Off`, por exemplo, não estava em lugar nenhum). Agora o Cenário A é o `compose.yml` sozinho, e o Cenário B é o `compose.yml` + este complemento. Isso é **reprodutibilidade**: qualquer pessoa recria os dois cenários a partir do repositório.

### 4.3 `seguranca-headers.conf` — os cabeçalhos de segurança (Defesa 2)

**A ideia, de forma simples:** toda página que o servidor envia vem acompanhada de "bilhetes" invisíveis, os **cabeçalhos HTTP**. Alguns desses bilhetes dão ordens ao navegador ("não deixe esta página ser embutida em outro site", "não adivinhe o tipo deste arquivo"…). Sem eles, o navegador usa comportamentos padrão que um atacante pode explorar. A OWASP classifica a ausência deles como *Security Misconfiguration* (A02 do OWASP Top 10:2025).

**Diretiva por diretiva:**

| Diretiva | O que ordena ao navegador | Ataque que dificulta |
|---|---|---|
| `X-Frame-Options "SAMEORIGIN"` | Só o próprio site pode exibir esta página dentro de um quadro (iframe) | *Clickjacking*: um site falso coloca a sua loja invisível por cima de um botão e o usuário clica sem saber (RFC 7034) |
| `X-Content-Type-Options "nosniff"` | Respeite o tipo de arquivo que o servidor declarou; não tente adivinhar | Um arquivo enviado como texto ser executado como página ou script |
| `Content-Security-Policy` (CSP) | Só aceite scripts, estilos e imagens vindos do próprio site | Reduz o estrago de uma injeção de script (XSS) |
| `Referrer-Policy "strict-origin-when-cross-origin"` | Ao sair para outro site, informe só o domínio, não o endereço completo | Vazamento de dados que estejam na URL |
| `Permissions-Policy "geolocation=(), microphone=(), camera=()"` | Ninguém nesta página pode usar localização, microfone ou câmera | Um script injetado tentar ativar esses recursos |
| `Header ... edit Set-Cookie ... HttpOnly / SameSite=Strict` | Scripts não podem ler os cookies; cookies não são enviados em requisições vindas de outros sites | Roubo de sessão por XSS; CSRF |
| `ServerTokens Prod`, `ServerSignature Off` | O servidor diz só "Apache", sem a versão | Atacante descobrir versões com falhas conhecidas |
| `Header unset X-Powered-By` | Não informar a linguagem (PHP) e sua versão | Idem |

**O que mudou na v2 e por quê:**

1. **CSP ganhou `frame-ancestors 'self'` e `form-action 'self'`.**
   Essas duas diretivas **não herdam** o valor de `default-src`. Sem elas declaradas, é como se estivessem liberadas. O ZAP apontava isso (alerta 10055). Com `frame-ancestors`, a CSP passa a fazer o mesmo papel do X-Frame-Options (a OWASP considera o X-Frame-Options obsoleto diante dela); com `form-action`, formulários da loja só podem enviar dados para a própria loja.

2. **A regra dos cookies foi repetida com `always` e `onsuccess`.**
   O Apache guarda os cabeçalhos em duas "listas" internas. A v1 usava só `always`. Os cookies do WordPress e do WooCommerce são criados pelo PHP, e a documentação oficial do `mod_headers` orienta repetir o `edit` nas duas listas quando o cabeçalho não foi criado pelo próprio Apache. **Hipótese:** é por isso que o ZAP continuava reclamando de cookies sem HttpOnly e SameSite. O teste T2 do roteiro confirma ou derruba essa hipótese.
   Além disso, a regra agora só acrescenta o atributo **se ele ainda não existir** (o trecho `(?!...)`), para não gerar `HttpOnly; HttpOnly` nos cookies do WordPress que já vinham com ele.

3. **`unset X-Powered-By` também repetido com `onsuccess`**, pelo mesmo motivo do item 2.

**Limitações que continuam (e devem aparecer no texto):**
- A CSP usa `'unsafe-inline'` para scripts e estilos, o que permite código embutido na página e enfraquece a proteção contra XSS. Ela foi mantida assim; remover exigiria testar o funcionamento da loja.
- Não há HSTS: o ambiente usa HTTP, e o navegador ignora HSTS recebido sem HTTPS (RFC 6797, seção 8.1).

### 4.4 `seguranca-php.ini` — ajustes do PHP (novo na v2)

| Linha | O que faz | Por quê |
|---|---|---|
| `expose_php = Off` | O PHP para de enviar o cabeçalho `X-Powered-By: PHP/8.3.x` | Correção de 05/10, que antes existia só dentro do contêiner |
| `display_errors = Off` | Erros não aparecem na página | Uma mensagem de erro pode revelar caminhos de arquivos, nomes de tabelas e consultas. A imagem oficial do WordPress já traz esse valor (`error-logging.ini`); a linha deixa a decisão explícita e versionada no repositório |
| `log_errors = On` | Erros vão para o log do servidor | Para quem administra continuar vendo os problemas |

### 4.5 A página de busca — Defesa 1 (SQL Injection)

#### Versão vulnerável (`snapshots/busca-produto-VULNERAVEL.php`, Cenário A)

A linha do problema é esta:

```php
$id = $_GET['id'];
$sql = "SELECT ID, post_title, post_content FROM wp_posts WHERE ID = " . $id;
```

O valor digitado na URL é **colado direto** no comando SQL. Se alguém acessa `busca-produto.php?id=1 OR 1=1`, o banco recebe `... WHERE ID = 1 OR 1=1`, e o `OR 1=1` é sempre verdadeiro. Com técnicas assim, o SQLMap conseguiu **ler a tabela `wp_users` inteira** (usuários e hashes de senha) em 22/09.

Além disso, a versão vulnerável mostrava o erro do banco na tela (`echo "Erro: " . $conexao->error`), o que ajuda o atacante a entender a estrutura do banco.

#### Versão corrigida (`busca-produto.php`, Cenário B)

São três camadas de defesa:

1. **Validação de tipo:** `filter_input(..., FILTER_VALIDATE_INT, min_range 1)`. Se o valor não for um número inteiro positivo, a página responde "Parâmetro inválido." e para ali.
2. **Consulta parametrizada (*prepared statement*):** o comando SQL é preparado com um `?` no lugar do valor, e o valor é enviado separado (`bind_param("i", $id)`). O banco **sempre** trata o valor como dado, nunca como parte do comando. É a defesa recomendada pela OWASP.
3. **Tratamento de erro seguro:** se algo der errado com o banco, o usuário vê só "Erro interno", e o detalhe vai para o log.

**O que mudou na v2 e por quê:**

| Mudança | Motivo |
|---|---|
| A consulta passou a filtrar `post_type = 'product' AND post_status = 'publish'` | Na v1, **qualquer** ID da tabela `wp_posts` devolvia o título, inclusive rascunhos, páginas privadas e revisões. A página se chama "busca de produto", então deve mostrar só produtos publicados (falha de controle de acesso, categoria A01 da OWASP) |
| As credenciais do banco passaram a vir de `getenv(...)` | Na v1, a senha estava escrita no código (fraqueza CWE-798). As variáveis já existem no `compose.yml`, para o próprio WordPress |
| O código passou a usar `try/catch` com `mysqli_sql_exception` | A partir do PHP 8.1, o `mysqli` lança uma exceção quando há erro, e a checagem `if ($conexao->connect_error)` da v1 nunca chegava a ser executada. O vazamento de detalhes não acontecia porque a própria imagem do WordPress já desliga a exibição de erros (arquivo `error-logging.ini`), mas o usuário recebia uma página 500 em branco em vez da mensagem genérica, e o erro não era registrado pelo código. Agora o tratamento previsto no código é o que de fato roda |
| `min_range => 1` na validação | IDs zero ou negativos não existem; são recusados logo na entrada |
| O `post_content` saiu da consulta | Não era exibido; não há motivo para buscá-lo (princípio do mínimo necessário) |

### 4.6 `limite-login.php` — Defesa 3 (força bruta)

**O ataque:** a ferramenta Hydra testa milhares de senhas por segundo no formulário de login. No Cenário A, achou a senha `123456` da `carla_souza` em 38 tentativas e cerca de 4 segundos, porque o WordPress, por padrão, não limita tentativas.

**Como o plugin funciona (passo a passo):**

1. No filtro `authenticate`, o WordPress confere a senha na prioridade 20, e o plugin entra logo depois, na prioridade 30. Ele olha quantas falhas aquele **endereço IP** já teve. Se forem 5 ou mais, troca o resultado por "Acesso temporariamente bloqueado por excesso de tentativas", **mesmo que a senha esteja certa**. Por isso o Hydra não descobre a senha depois do bloqueio.
2. **Quando o login falha** (evento `wp_login_failed`), soma +1 no contador daquele IP. O contador fica guardado num *transient* do WordPress (um valor temporário no banco) que expira em 15 minutos.
3. **Quando o login dá certo** (evento `wp_login`), apaga o contador daquele IP.

**Por que esses números:** 5 tentativas e 15 minutos estão dentro das faixas sugeridas pela OWASP (5 a 10 tentativas, bloqueio de 5 a 30 minutos) e bem abaixo do limite máximo do NIST (100 falhas). O reinício após um login correto também é recomendação do NIST.

**Por que contar por IP:** usa `$_SERVER['REMOTE_ADDR']`, que é o endereço real da conexão, e **não** cabeçalhos como `X-Forwarded-For`, que o atacante pode inventar. E, contando por IP em vez de por conta, um atacante não consegue bloquear a conta de um usuário legítimo a partir de outro lugar.

**Limitação:** um ataque distribuído, a partir de muitos IPs diferentes, não é contido pelo mesmo limite.

**Mudou na v2?** Este arquivo **não mudou**. Mas a v2 do MFA passou a "conversar" com ele (seção 4.8).

### 4.7 `mfa-login.php` — Defesa 4, parte 1 (autenticação em duas etapas)

**A ideia:** mesmo que o atacante descubra a senha (por força bruta ou porque ela vazou em outro site), ele ainda precisa de um código de 6 dígitos que muda a cada 30 segundos e só existe no celular do usuário.

**Como funciona:**

1. **No perfil do usuário**, o plugin gera uma **chave secreta** (o segredo TOTP), guarda no banco e mostra na tela. O usuário digita essa chave no Google Authenticator, que passa a gerar os códigos. Há uma caixa "Ativar MFA".
2. **No login**, depois que o WordPress confirma que a senha está certa (filtro `authenticate`, prioridade 40, ou seja, **depois** do limite de login), o plugin verifica se aquele usuário tem MFA ativo. Se tiver:
   - gera um **token aleatório** de 32 caracteres, válido por 5 minutos, que significa "esta pessoa acertou a senha e está no meio do login";
   - **interrompe** o login normal e redireciona para a página `mfa-verificar.php?t=<token>`.
   - Se a senha estiver errada, o plugin não interfere e o WordPress mostra o erro normal.

**Boas escolhas que já estavam na v1:** não reimplementa a criptografia (usa uma biblioteca conhecida) e não usa QR code gerado por serviço externo (a chave é digitada à mão), para manter o laboratório isolado.

**O que mudou na v2 e por quê:**

| Mudança | Motivo |
|---|---|
| A chave passou de 16 para **32 caracteres** (`createSecret(32)`) | Cada caractere base32 vale 5 bits: 16 caracteres = **80 bits**. A RFC 4226 exige **no mínimo 128 bits** ("MUST") e recomenda 160. 32 caracteres = 160 bits |
| Chaves antigas são trocadas quando o MFA está desativado, e um aviso aparece no perfil | Para não "trancar" quem já usa MFA: o usuário desativa, salva, recebe a chave nova, cadastra no app e reativa |
| Ao trocar a chave, apaga o registro `mfa_ultimo_passo` | Esse registro pertence à chave antiga (ver seção 4.8) |

### 4.8 `mfa-verificar.php` — Defesa 4, parte 2 (a página do código)

**Como funciona:**

1. Lê o token da URL. Se ele não existe ou expirou: "Sessão de verificação expirada".
2. O usuário digita o código de 6 dígitos.
3. O código é comparado com o que a biblioteca calcula a partir da chave secreta e do horário atual.
4. Se estiver certo: apaga o token, cria a sessão do WordPress (`wp_set_auth_cookie`) e leva ao painel.

**O que estava errado na v1 (e por que importa):**

| Problema na v1 | Por que é grave | Norma |
|---|---|---|
| **Tentativas ilimitadas** de código durante os 5 minutos do token; e, ao expirar, bastava fazer login de novo com a senha para ganhar outro token | Quem já tem a senha podia "chutar" os 6 dígitos à vontade. É justamente o cenário que o MFA deveria impedir | RFC 4226, seção 7.3; NIST SP 800-63B-4; OWASP |
| **O mesmo código podia ser aceito de novo**, num novo login, enquanto ainda era válido | Quem visse o código (por cima do ombro, num site falso) poderia reutilizá-lo | RFC 6238, seção 5.2; NIST |
| **Tolerância de relógio de 2 passos** (`verifyCode(..., 2)`): ±60 s, ou seja, cinco códigos válidos ao mesmo tempo | Quanto mais códigos válidos, maior a chance de um chute acertar | RFC 6238 recomenda no máximo 1 passo |
| **O evento `wp_login` não disparava** no fim do login com MFA | O contador do `limite-login.php` não era zerado para quem usa MFA, e a frase da monografia "a contagem é reiniciada após login bem-sucedido" não valia para esses usuários | — |

**Como a v2 funciona:**

1. **Até 5 tentativas por token.** Cada erro soma +1 num contador do token. Na 5ª falha, o token é apagado: "Tentativas esgotadas. Faça login novamente."
2. **Cada código errado também conta no `limite-login.php`** (dispara `wp_login_failed`). Assim, depois de 5 falhas somadas (de senha ou de código), o IP fica bloqueado por 15 minutos e não consegue nem tentar a senha de novo. As duas defesas passam a trabalhar juntas.
3. **Uso único:** quando um código é aceito, a página guarda **em qual intervalo de 30 segundos** ele foi gerado (`mfa_ultimo_passo`). Daí em diante, só aceita códigos de intervalos **mais novos**. Um código repetido é recusado (e conta como tentativa errada).
4. **Tolerância de 1 passo:** aceita o código atual e os vizinhos de ±30 s (para compensar relógio do celular um pouco adiantado ou atrasado), como recomenda a RFC 6238.
5. **Ao concluir o login, dispara `wp_login`**, o que zera o contador do limite de login.
6. Só aceita códigos com exatamente 6 dígitos, e a comparação é feita com `hash_equals` (resistente a ataques de tempo).

**Limitações que continuam:**
- O TOTP **não é resistente a *phishing***: se o usuário digitar senha e código num site falso, o atacante pode usá-los na hora. O NIST diz isso literalmente; a alternativa resistente é FIDO2/WebAuthn (Extensões).
- A chave secreta fica sem cifra no banco e continua visível no perfil.

### 4.9 `totp-lib.php` — o "relógio" dos códigos

É a biblioteca **PHPGangsta/GoogleAuthenticator**, de terceiros, usada só para o cálculo. Como o código é gerado, em linguagem simples:

1. Pega o horário atual e divide por 30 → um número que muda a cada 30 segundos (o "passo de tempo").
2. Mistura esse número com a chave secreta usando HMAC-SHA1 (uma função criptográfica).
3. Corta o resultado em 6 dígitos.

O celular e o servidor fazem a mesma conta com a mesma chave e o mesmo horário, então chegam ao mesmo código, **sem trocar nenhuma mensagem**. Isso é o TOTP (RFC 6238), construído sobre o HOTP (RFC 4226).

**Não foi alterada** na v2: a v2 só usa a função `getCode` dela de outro jeito (para saber o passo de tempo e garantir o uso único).

### 4.10 Arquivos de auditoria e de dados

- **`zap.yaml`:** configuração do OWASP ZAP (rastreador + varredura passiva). O ZAP **não ataca**; ele navega pela loja e aponta configurações fracas.
- **`relatorio-zap-baseline.html`** (14/09, Cenário A) e **`relatorio-zap-cenarioB.html`** (03/10, Cenário B v1, ainda com `X-Powered-By`): relatórios da época. Os comparativos corretos estão em `documentacoes/evidencias-zap/`.
- **`baseline_inicial.sql`:** o banco logo depois da montagem da loja (produtos e 9 usuários fictícios: o administrador e mais 8 com e-mails `@example.com`).
- **`snapshots/cenario-A-banco.sql`:** o mesmo banco depois da primeira auditoria (a diferença é, por exemplo, um comentário de teste deixado pelo ZAP).
- **`.gitignore`:** impede que a `rockyou.txt` (a lista de 14 milhões de senhas usada pelo Hydra) vá para o repositório.

### 4.11 Pastas de registro

- **`snapshots/`:** fotografias de cada etapa (READMEs, saídas do Hydra, auditoria de 04/10). **São históricas e não foram alteradas.** Onde havia informação depois corrigida, há a `NOTA-correcoes-posteriores.txt`.
- **`snapshots/cenario-B-v1/`:** os arquivos do Cenário B como estavam até 05/10. Servem para comparar e, se for preciso, voltar.
- **`documentacoes/`:** o diário (`DIARIO.md`), as decisões (`decisoes.md`), o contexto do projeto, as evidências do ZAP e do SQLMap, os rascunhos das seções, as versões da monografia, este guia e o roteiro de testes da v2.

---

## 5. O caminho completo de um login no Cenário B (v2)

```
Usuário digita usuário + senha em wp-login.php
│
├─ [WordPress, prioridade 20] Senha correta?
│
├─ [limite-login, prioridade 30] Este IP já tem 5 falhas?
│     └─ sim → "Acesso temporariamente bloqueado", mesmo com a senha certa (fim)
│
├─ Senha errada → +1 falha no IP → "senha incorreta" (fim)
│
├─ [mfa-login, prioridade 40] Usuário tem MFA ativo?
│     └─ não → login concluído → contador do IP zerado (fim)
│
└─ sim → cria token (5 min) → vai para mfa-verificar.php
      │
      ├─ código certo e ainda não usado → login concluído → contador do IP zerado
      │
      └─ código errado ou repetido → +1 no token e +1 no IP
            └─ 5ª falha → token apagado → "Tentativas esgotadas"
```

(Os números são as prioridades no filtro `authenticate`: quanto menor, mais cedo roda. O MFA, em 40, só age se nada antes dele tiver devolvido erro.)

## 6. Resumo das alterações da v2, com o motivo

| # | Arquivo | Alteração | Motivo | Fonte |
|---|---|---|---|---|
| 1 | `mfa-verificar.php` | Limite de 5 tentativas por token | Impedir que quem tem a senha "chute" o código | RFC 4226 §7.3; NIST SP 800-63B-4; OWASP |
| 2 | `mfa-verificar.php` | Falhas do código contam no limite por IP | Integrar as defesas: 5 falhas no total bloqueiam o IP | Mesma |
| 3 | `mfa-verificar.php` | Uso único do código | Impedir reutilização | RFC 6238 §5.2; NIST |
| 4 | `mfa-verificar.php` | Tolerância de ±30 s (era ±60 s) | Menos códigos válidos ao mesmo tempo | RFC 6238 §5.2 |
| 5 | `mfa-verificar.php` | Dispara `wp_login` | Zerar o contador do limite após login com MFA | NIST (reinício após sucesso) |
| 6 | `mfa-login.php` | Chave de 160 bits (era 80) | Cumprir o mínimo de 128 bits | RFC 4226, R6 |
| 7 | `seguranca-headers.conf` | `frame-ancestors` e `form-action` na CSP | Diretivas sem valor alternativo; alerta 10055 do ZAP | W3C CSP; OWASP |
| 8 | `seguranca-headers.conf` | Cookie editado em `always` e `onsuccess`, sem duplicar | Cookies criados pelo PHP não eram alcançados (hipótese a confirmar no T2) | Documentação do Apache mod_headers |
| 9 | `busca-produto.php` | Só produtos publicados | Não expor rascunhos e páginas privadas | OWASP A01 |
| 10 | `busca-produto.php` | Credenciais vindas do ambiente | Não deixar senha no código | CWE-798 |
| 11 | `busca-produto.php` | `try/catch` das exceções do mysqli | Fazer o tratamento de erro do próprio código ser executado no PHP 8.3 (antes, quem evitava o vazamento era só a configuração da imagem) | — |
| 12 | `seguranca-php.ini` | `expose_php`, `display_errors`, `log_errors` | Versionar o que existia só no contêiner (`no-expose.ini`) e deixar explícita a não exibição de erros | OWASP |
| 13 | Rede da VM (`passt`, fora do repositório) | Encaminhamentos 8080 e 2222 restritos a `127.0.0.1` do Manjaro; `compose.yml` mantido em `8080:80` | Isolar o laboratório da rede Wi-Fi (antes, loja e SSH estavam expostos) | — |
| 14 | `compose.cenario-b.yml` | Configuração do B montada a partir do repositório | Reprodutibilidade | — |

**Testado na VM em 26/10:** T1 (cabeçalhos), T2 (cookies), T3 (busca e SQLMap 1.10.4) e T4 (MFA: login, uso único, esgotamento, bloqueio integrado e liberação) aprovados. Faltam T5 (Hydra) e T6 (ZAP final).

**Testado antes do envio (fora da VM):** sintaxe dos PHP; lógica do MFA com uma simulação do WordPress (acerto, reuso recusado, esgotamento na 5ª falha, disparo dos eventos); busca sem banco (entradas inválidas recusadas, erro genérico com HTTP 500); a expressão regular dos cookies.


## 7. Perguntas que a banca pode fazer (e onde está a resposta)

- **"Por que cinco tentativas?"** → seção 4.6: dentro da faixa da OWASP (5 a 10), abaixo do limite do NIST (100), equilibrando segurança e usabilidade (Wang et al., 2021).
- **"Por que bloquear por IP e não por conta?"** → seção 4.6: evita que o atacante bloqueie a conta de outra pessoa; limitação: ataque distribuído.
- **"O seu MFA impede *phishing*?"** → seção 4.8: não, e o NIST diz isso; a alternativa é FIDO2/WebAuthn.
- **"A sua CSP protege contra XSS?"** → seção 4.3: reduz, mas o `'unsafe-inline'` enfraquece; é defesa em profundidade, não a primeira linha.
- **"Por que o ZAP ainda mostra alertas?"** → muitos vêm do próprio WordPress ou são informativos; o trabalho mede o que é atribuível às defesas.
- **"O laboratório estava isolado?"** → seção 2: desde 26/10, sim (`passt` restrito ao próprio computador); antes, a loja era acessível pela rede Wi-Fi, o que foi identificado e corrigido.
- **"Como alguém reproduz o seu experimento?"** → `README.md` e seção 4.2: Cenário A = `compose.yml`; Cenário B = `compose.yml` + `compose.cenario-b.yml`.

## 8. Glossário rápido

- **Contêiner:** um "mini-computador" isolado que roda um programa (aqui, o site ou o banco).
- **Volume:** pasta que guarda dados do contêiner de forma permanente.
- **Cabeçalho HTTP:** informação invisível que acompanha cada página enviada pelo servidor.
- **Hook / filtro / ação (WordPress):** pontos em que um plugin pode "entrar" no funcionamento do WordPress (ex.: `authenticate`, `wp_login_failed`).
- **Transient:** valor temporário guardado pelo WordPress, que expira sozinho.
- **Prepared statement:** consulta SQL em que o comando e os dados vão separados.
- **TOTP:** código de uso único baseado no horário (RFC 6238).
- **Passo de tempo:** cada janela de 30 segundos em que um código TOTP vale.
- **Força bruta:** testar muitas senhas até acertar.
- ***Credential stuffing*:** testar pares de usuário e senha vazados de outros sites.
- ***Phishing*:** site ou mensagem falsa que engana o usuário para que ele entregue senha e código.
