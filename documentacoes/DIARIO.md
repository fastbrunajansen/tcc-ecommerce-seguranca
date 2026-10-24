# Diário do projeto

Registro por data do que foi feito, decidido e corrigido. Entradas novas no topo.
Regra: erros e correções também entram (rastreabilidade).

## 2026-10-24 (sábado)

**Introdução (Cap. 1) fechada** com fontes verificadas: LGPD art. 46 (BRASIL, 2018), Verizon DBIR 2026 (relatório completo, p. 94, setor Retail), OWASP Top 10:2025 (A02, A05, A07), Sebrae/IBRE-FGV (2026) e três estudos sobre código gerado por IA (Pearce et al., 2021; Perry et al., 2023; Tóth et al., 2024). Monografia na versão v16.

**Ambiente.** VM religada (Ubuntu Server 26.04.1 LTS; o nome no virsh ainda é `ubuntu25.10`). Configuração real dos cabeçalhos conferida na VM: `seguranca-headers.conf` + `expose_php = Off`.

**ZAP: comparação refeita (correção importante).**
- Erro meu: eu havia dito que o ZAP de hoje usava "regras mais novas" e que 22 e 16 não eram comparáveis. Estava errado; o arquivo de 15/10 que usei estava cortado.
- Comparação válida: mesma ferramenta (ZAP 2.17.0) e 171 endpoints nos dois relatórios HTML (A em 14/09, B em 24/10).
- Resultado: Medium+Low 16 → 11 (31%); terminal 22 → 16 (27%). O "22 → 11 (50%)" de 04/10 misturava duas medidas e não deve ser usado.
- A regra de cookie não eliminou o SameSite. CSP `unsafe-inline` e "sem fallback" já existiam no A (vêm do WordPress).
- Detalhe em `CONTEXTO.md` e `decisoes.md`.

**Pendente da sessão:** atualizar Tabela 2 e parágrafo do Cap. 3.4 (v17), conferir se "22" e "50%" aparecem em Resumo/Conclusão, conectar o GitHub e subir esta pasta.

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
