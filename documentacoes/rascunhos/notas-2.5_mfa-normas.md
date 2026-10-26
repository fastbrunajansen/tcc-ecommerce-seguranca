# Notas para a seção 2.5 — MFA / TOTP (26/10/2026)

Fontes normativas lidas nesta data (para redigir a 2.5):

- **RFC 6238 (TOTP)**: M'Raihi, Machani, Pei, Rydell. IETF, maio 2011, Informational. TOTP = HOTP(K, T), T = (tempo Unix atual − T0) / X; X padrão de 30 s (§4). Recomenda aceitar no máximo um passo de tempo de atraso de rede (§5.2); o verificador NÃO deve aceitar a segunda tentativa de um OTP já validado (§5.2); segredo único por usuário (§3, R5); chaves guardadas em área segura e comunicação por canal seguro (§5.1).
- **RFC 4226 (HOTP)**: M'Raihi, Bellare, Hoornaert, Naccache, Ranen. IETF, dez. 2005, Informational. HOTP(K,C) = Truncate(HMAC-SHA-1(K,C)) (§5.2); mínimo de 6 dígitos (§5.3); o truncamento torna a força bruta possível, por isso limitar tentativas, com bloqueio valendo entre sessões (§7.3).
- **NIST SP 800-63B-4 (jul. 2025)**: "OTP authentication is not phishing-resistant" (§3.1.4 e §3.1.5); verificador aceita cada OTP só uma vez (resistência a replay); deve haver limitação de tentativas; segredo protegido por controle de acesso; resistência a phishing exige autenticação criptográfica (§3.2.5).
- **OWASP Multifactor Authentication Cheat Sheet [s.d.]**: MFA é "de longe a melhor defesa" contra a maioria dos ataques relacionados a senhas (força bruta, credential stuffing, password spraying); cita análise da Microsoft de que teria impedido 99,9% dos comprometimentos de conta; fatores: algo que você sabe, tem, é; TOTP: barato e difundido, mas suscetível a phishing (embora de curta duração); preferir FIDO2/WebAuthn, resistentes a phishing; aplicar limites rígidos de tentativas para OTP.

## Pontos a conferir na implementação (antes de escrever)
1. O plugin aceita o mesmo código duas vezes dentro da janela de 30 s? (RFC 6238 §5.2 e NIST pedem uso único)
2. Há limite de tentativas na página de verificação do código? (RFC 4226 §7.3, NIST, OWASP)
3. Qual a tolerância (discrepancy) usada no verifyCode da PHPGangsta? (RFC 6238 recomenda no máximo um passo)
