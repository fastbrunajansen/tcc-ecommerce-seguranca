HARDENING DE CABECALHOS - CENARIO B

Defesa aplicada via configuracao do Apache (seguranca-headers.conf):
- X-Frame-Options: SAMEORIGIN (anti-clickjacking)
- X-Content-Type-Options: nosniff
- Content-Security-Policy (restringe origens de conteudo)
- Referrer-Policy: strict-origin-when-cross-origin
- Permissions-Policy (restringe camera, microfone, geolocalizacao)
- Cookies com HttpOnly e SameSite
- ServerTokens Prod (suprime versao do servidor)

Modulo Apache headers ativado (a2enmod headers).
Verificado via curl -I: cabecalhos presentes na resposta.

Observacao: X-Powered-By ainda exposto pelo PHP (ajuste pendente via expose_php=Off).

Data: 22/09/2026

ATUALIZACAO: X-Powered-By corrigido
Causa: ServerTokens Prod controla o cabecalho "Server" (Apache), mas o
"X-Powered-By" e adicionado pelo proprio PHP, exigindo configuracao propria.
Correcao: expose_php = Off adicionado via arquivo de configuracao do PHP.
Verificado via curl -I: X-Powered-By nao aparece mais na resposta.
Data: 05/10/2026
