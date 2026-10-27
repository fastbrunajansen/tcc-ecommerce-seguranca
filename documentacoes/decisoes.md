# Decisões e motivos

| Data | Decisão | Motivo |
|---|---|---|
| 24/10 | Resultado do ZAP apresentado como Medium+Low 16 → 11 (31%), com o terminal 22 → 16 como medida complementar | Mesma ferramenta, versão e endpoints nos dois relatórios; o "22 → 11 (50%)" misturava duas medidas |
| 24/10 | Não buscar zerar os alertas do ZAP | O TCC demonstra as quatro defesas; alertas remanescentes (cookies, CSRF, `unsafe-inline`) entram como limitações e Extensões |
| 24/10 | Atribuir ao hardening só o que foi diretamente configurado (X-Frame-Options, X-Content-Type-Options, CSP, Permissions-Policy, ServerTokens, expose_php); In Page Banner e CSP Wildcard ficam como "provável", não afirmado | Rigor: não reivindicar causa que não foi testada |
| 24/10 | Regra de cookie sai da lista de "atribuíveis" | O alerta de SameSite persiste (3 ocorrências) nos dois cenários |
| 24/10 | Citar o relatório completo do Verizon DBIR 2026 (p. 94), não o resumo executivo | Setor Retail tem seção própria no relatório completo |
| 24/10 | Estudos sobre IA e código inseguro entram só como menção na justificativa, sem percentuais além dos verificados | Escopo do TCC; Tóth et al. tem inconsistência numérica |
| 24/10 | Documentação do projeto guardada na pasta `documentacoes/` do repositório | Pedido da autora; evita perda de contexto |
| 26/10 | Aplicar todas as melhorias das prioridades 1 e 2 no Cenário B (versão 2) e refazer a auditoria final | Alinhar o MFA às RFC 4226/6238 e ao NIST, corrigir a regra de cookie e a CSP antes da banca; uma rodada única de testes |
| 26/10 | Preservar a versão 1 em `snapshots/cenario-B-v1/` | Rastreabilidade: a monografia pode citar a evolução |
| 26/10 | Configuração do Cenário B montada por `compose.cenario-b.yml` | Reprodutibilidade: antes, a configuração existia só dentro do contêiner |
| 26/10 | Registrar cada sessão (do "oi" ao "tchau") em `documentacoes/sessoes/AAAA-MM-DD_sessao.md`, entregue ao final para download e commit | Pedido da autora: ter o histórico completo |
| 26/10 | Isolar o laboratório no `passt` (127.0.0.1 no Manjaro) e manter o contêiner em `8080:80` | A VM só é alcançável pelo `passt`; a porta do contêiner não era o ponto de exposição |
| 26/10 | SQLMap da auditoria final pelo pacote do Ubuntu (1.10.4), mesmos parâmetros | Falha no download da imagem Docker; registrar a diferença de método na monografia |
