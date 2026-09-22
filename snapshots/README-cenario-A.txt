SNAPSHOT DO CENARIO A (VULNERAVEL) - estado congelado antes das defesas

Conteudo:
- cenario-A-banco.sql : dump do banco no estado do Cenario A
- busca-produto-VULNERAVEL.php : pagina com SQL Injection (sem sanitizacao)

Vulnerabilidades presentes neste estado:
- SQL Injection na pagina busca-produto.php (confirmado por SQLMap: boolean, time-based, UNION)
- Vazamento de credenciais (tabela wp_users extraida)
- Vazamento por erro HTTP 500 (tratamento inadequado de excecoes)
- 22 avisos de configuracao no OWASP ZAP (cabecalhos de seguranca ausentes)

Data do snapshot: 22/09/2026
