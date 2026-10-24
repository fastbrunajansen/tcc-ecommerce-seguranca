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
