CORRECAO DO SQL INJECTION - CENARIO B

Defesa desenvolvida na pagina busca-produto.php:
1. Validacao de tipo (filter_input FILTER_VALIDATE_INT) - rejeita entradas nao-inteiras
2. Consulta parametrizada (prepared statement) - separa comando SQL de dados
3. Tratamento de erro seguro - nao expoe detalhes internos (corrige o HTTP 500)

Resultado da validacao:
- Cenario A (antes): SQLMap confirmou injecao (boolean, time-based, UNION); extraiu wp_users
- Cenario B (depois): SQLMap concluiu "all tested parameters do not appear to be injectable"
- Testes manuais: ?id=1 OR 1=1 e UNION retornam "Parametro invalido" (bloqueados na entrada)

Data: 22/09/2026
