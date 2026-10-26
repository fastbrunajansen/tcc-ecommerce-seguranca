# Diário do projeto

Registro por data do que foi feito, decidido e corrigido. Entradas novas no topo.
Regra: erros e correções também entram (rastreabilidade).

## 2026-10-26 (segunda)

**Rascunho das seções 2.3 e 2.4 enviado em .docx para revisão.** 2.4 ampliada com Wang et al. (2021, JNCA/Elsevier; PDF lido, seções 2.2.1 e 4.1), Eipper e Pöhn (ICISSP 2023), Lina e Fernandes (2023; o nome "Meilina" sugerido por ferramenta estava errado) e Hariono e Ishomuddin (2026). Descartado: James e Dileesh (2020), periódico de baixa qualidade e tema diferente. Shah e Ayode: aguardando dados do IEEE.

**Seção 2.5 (MFA): normas lidas** (`rascunhos/notas-2.5_mfa-normas.md`): RFC 6238, RFC 4226, NIST SP 800-63B-4 (OTP não é resistente a phishing), OWASP MFA Cheat Sheet.

**Revisão do código do repositório (tornado público pela autora nesta data):**
- `mfa-verificar.php` não limita tentativas de código, não impede reuso do código e usa tolerância de 2 passos (±60 s).
- Com MFA ativo, o `wp_login` não dispara, então o `limite-login` não zera o contador após login com MFA (afeta uma frase da 2.4).
- Proposta de correção enviada (5 tentativas por token, falhas somadas ao limite por IP, uso único por passo de tempo, tolerância de 1 passo, disparo do `wp_login`). Lógica TOTP testada fora do WordPress; falta testar na VM. Decisão da autora pendente: corrigir ou registrar como limitação.

**Revisão geral do repositório (antes de voltar a privado):** criados `README.md` (visão geral, defesas, estrutura, resultados corretos, limitações), `snapshots/NOTA-correcoes-posteriores.txt` (aponta as correções do ZAP e do X-Powered-By sem alterar os registros históricos) e `documentacoes/evidencias-sqlmap/` (quatro saídas do SQLMap de 14/09, 17/09 e 22/09, recuperadas das conversas). `.gitignore` limpo (linha duplicada). Pendente: versionar o arquivo .ini do PHP com `expose_php = Off`, que existe só no contêiner.

**Cenário B versão 2 preparada (decisão da autora: aplicar todas as melhorias das prioridades 1 e 2 da revisão).** Arquivos: `mfa-verificar.php` (5 tentativas, falhas somadas ao limite por IP, uso único, tolerância de 1 passo, `wp_login`), `mfa-login.php` (segredo de 160 bits; RFC 4226 R6 exige 128), `seguranca-headers.conf` (CSP com frame-ancestors e form-action; cookie editado em `always` e `onsuccess`, conforme a documentação do mod_headers, sem duplicar atributos), `busca-produto.php` (credenciais do ambiente, só produtos publicados, ID ≥ 1, exceções do mysqli capturadas: no PHP 8.1+ a checagem `connect_error` da v1 não era alcançada), `compose.yml` (127.0.0.1), `compose.cenario-b.yml` e `seguranca-php.ini` (novos; expose_php e display_errors Off). Testes feitos aqui: sintaxe PHP, lógica do MFA com simulação do WordPress (acerto, reuso recusado, esgotamento na 5ª falha, eventos de login), busca sem banco (parâmetros inválidos recusados, erro genérico com HTTP 500), regex do cookie. Falta testar na VM: `documentacoes/ROTEIRO-cenario-B-v2.md`. Versão 1 preservada em `snapshots/cenario-B-v1/`.

**Repositório:** os commits de código foram feitos pela VM; a pasta `documentacoes` foi commitada pelo Manjaro. O segundo pacote (v17 e rascunho da 2.3) não chegou ao GitHub.

## 2026-10-25 (domingo)

**Seção 2.4 (força bruta): rascunho pronto, aguardando aprovação** (`rascunhos/secao-2.4_forca-bruta.md`).
- Artigos enviados pela autora e verificados: Rodrigues et al. (2025, JISA/SBC, RockYou2024), Li et al. (2020, WorldCIST/Springer), Thomas et al. (2019, USENIX Security), Shah e Ayode (2025, Australian Journal of Wireless Technologies; existe também registro no IEEE Xplore, doc. 10060966, cuja versão não foi confirmada).
- Encontrado e incluído: Pinto, Bento e Castaldin (2024, FatecSeg), ataque de força bruta em WordPress em ambiente simulado; candidato também a Correlato.
- Normas: NIST SP 800-63B-4 (jul. 2025; limite de 100 falhas, reinício após sucesso, blocklist), CWE-307 (v4.20), OWASP WSTG-ATHN-03 (5 a 10 tentativas, bloqueio de 5 a 30 min, risco de negação de serviço).
- Conclusão: os parâmetros do plugin (5 falhas, 15 min, por IP, reinício após login) estão dentro do recomendado. Limitação registrada: contagem por IP não contém ataque distribuído.
- Citações OWASP sem data: [s.d.d] = WSTG.

## 2026-10-24 (sábado)

**Introdução (Cap. 1) fechada** com fontes verificadas: LGPD art. 46 (BRASIL, 2018), Verizon DBIR 2026 (relatório completo, p. 94, setor Retail), OWASP Top 10:2025 (A02, A05, A07), Sebrae/IBRE-FGV (2026) e três estudos sobre código gerado por IA (Pearce et al., 2021; Perry et al., 2023; Tóth et al., 2024). Monografia na versão v16.

**Ambiente.** VM religada (Ubuntu Server 26.04.1 LTS; o nome no virsh ainda é `ubuntu25.10`). Configuração real dos cabeçalhos conferida na VM: `seguranca-headers.conf` + `expose_php = Off`.

**ZAP: comparação refeita (correção importante).**
- Erro meu: eu havia dito que o ZAP de hoje usava "regras mais novas" e que 22 e 16 não eram comparáveis. Estava errado; o arquivo de 15/10 que usei estava cortado.
- Comparação válida: mesma ferramenta (ZAP 2.17.0) e 171 endpoints nos dois relatórios HTML (A em 14/09, B em 24/10).
- Resultado: Medium+Low 16 → 11 (31%); terminal 22 → 16 (27%). O "22 → 11 (50%)" de 04/10 misturava duas medidas e não deve ser usado.
- A regra de cookie não eliminou o SameSite. CSP `unsafe-inline` e "sem fallback" já existiam no A (vêm do WordPress).
- Detalhe em `CONTEXTO.md` e `decisoes.md`.

**Registro no GitHub.** Pasta `documentacoes/` criada no repositório privado `tcc-ecommerce-seguranca` (commit feito pela autora, 24/10, 20h39). O Claude não teve acesso ao repositório (autorização do GitHub sem repositórios liberados), então os arquivos são entregues em `.zip` e commitados manualmente. Primeiro upload pelo navegador achatou as pastas; corrigido com `git mv`.

**Monografia v17 (20h50).** Atualizados com a comparação corrigida: Cap. 3.1 ("dezesseis alertas de risco médio e baixo"), Tabela 2 (A: 7 Médio + 9 Baixo = 16; B: 5 + 6 = 11; redução de 31%), parágrafo do Cap. 3.4 (6 alertas atribuíveis, 2 prováveis não reivindicados, 8 persistentes com explicação de cookies, CSRF e `unsafe-inline`, 3 novos não relacionados) e o parágrafo final do 3.4 (31%). Nenhuma outra ocorrência de "22" ou "50%" no texto. Correção no contexto: eram 6 atribuíveis, não 7.

**Seção 2.3 (cabeçalhos HTTP): rascunho pronto, aguardando aprovação** (`rascunhos/secao-2.3_cabecalhos-http.md`). Fontes primárias lidas nesta data: RFC 7034, RFC 6797, W3C CSP Level 3, W3C Referrer Policy, W3C Permissions Policy, OWASP HTTP Headers e CSP Cheat Sheets, MDN (nosniff). Artigos verificados: Kishnani e Das (2024, preprint), Calzavara et al. (USENIX Security 2020), Weichselbaum et al. (ACM CCS 2016), Roth et al. (NDSS 2020; conferir o ano no PDF). Decisões: não afirmar que o `unsafe-inline` é necessário ao WordPress (nenhuma fonte confirma e não foi testado); HSTS apenas como limitação (ambiente em HTTP); citações OWASP sem data passam a [s.d.a] (CSP), [s.d.b] (HTTP Headers), [s.d.c] (SQL Injection, seção 2.2).

**Comparação com TCCs reais do curso (seis TCCs de 2025/2 e 2026/1):** 25 a 62 referências; artigos e anais ~35% a ~70% (contagem automática aproximada); o restante é livros, TCCs/teses, normas e sites. Nenhum usa só artigos. Distribuição ideal: confirmar com o orientador.

**Pendente:** aprovar a 2.3 e gerar a v18 (inserir seção, renumerar [s.d.a-c], 12 referências novas); seções 2.4 (força bruta), MFA e Correlatos.

## 2026-10-05
Correção do `X-Powered-By` (`expose_php = Off`), confirmada por `curl -I`.

## 2026-10-04
Auditoria final comparativa A×B (arquivo `auditoria-final-comparativo.txt`; números de ZAP superados pela correção de 24/10).

## 2026-10-03
Defesas de força bruta (plugin `limite-login.php`; Hydra: 38 tentativas no A, bloqueio na 5ª no B) e MFA TOTP (RFC 6238; `mfa-login.php`, `mfa-verificar.php`).

## 2026-09-22
SQL Injection corrigido em `busca-produto.php` (validação de tipo + consulta parametrizada + erro seguro) e hardening de cabeçalhos no Apache. Snapshots do Cenário A congelados.

## 2026-09-14
Baseline do ZAP no Cenário A (`relatorio-zap-baseline.html`).
