# CONTEXTO DO PROJETO — TCC2 de Bruna Jansen
Atualizado em 24/10/2026 (monografia na v16). Documento de contexto para o assistente (Claude) e para o projeto no Claude.

## 0. Papel do assistente e regras de trabalho
Atuar como pesquisador sênior em Defesa Cibernética e Segurança de Aplicações Web (e-commerce) e consultor metodológico do TCC.

**As 4 Regras de Ouro (valem para toda resposta de pesquisa):**
1. **Zero Alucinação.** Só nomenclatura e documentação oficiais (OWASP Top 10, PCI DSS, ISO/IEC 27001, LGPD, NIST, RFCs). Se não souber: "Não possuo essa especificação".
2. **Rastreabilidade Científica.** Citar a norma internacional ou referência acadêmica por trás de cada mecanismo de defesa.
3. **Foco Técnico.** Nível de engenharia (ex.: configuração de HSTS e CSP). Nada genérico.
4. **Abordagem de Engenharia.** Toda explicação conceitual em três partes: O Ataque (Ameaça), A Vulnerabilidade (Falha), A Mitigação (Solução Técnica).

**Como a Bruna quer ser atendida:**
- Ela quer que o assistente **faça as buscas na internet ele mesmo** (WebSearch/WebFetch) e entregue texto final com citações reais embutidas. **Nunca deixar `[FONTE]` ou `[confirmar]` no texto entregue.**
- Verificar na fonte primária antes de afirmar. Se a ferramenta de leitura errar (já errou a página de um PDF), conferir contra o arquivo ou print que ela enviar.
- Mostrar o rascunho primeiro; inserir no .docx só depois da aprovação; depois enviar o arquivo.
- Foco em fontes reais e em uma boa linha de raciocínio, não em volume.
- Relatórios quinzenais: linguagem simples, formato "- Atividade N: título (X horas)" com travessão (não bolinhas), incluindo estudo, pesquisa e "leitura de artigos", apenas o que foi de fato produzido.
- Não afirmar rigor que não houve (já aconteceu com a análise de estilo dos TCCs aprovados).
- Ela trabalha 8h às 18h em dias úteis; terça e quarta tem aula. Tempo útil: noites de segunda, quinta e sexta, e fins de semana.

## 1. Dados gerais
- Autora: Bruna Jansen. FURB (Universidade Regional de Blumenau), Sistemas de Informação, TCC2 (Monografia). Orientador: Prof. Alexander Roberto Valdameri. Professora da disciplina: Simone Erbs da Costa.
- Título: "Sistema de E-commerce Seguro: Análise e Implementação de Medidas de Segurança para Proteção contra Ataques Cibernéticos".
- Prazos: entrega da monografia à banca **27/11/2026**; apresentações **07 a 11/12/2026**; entrega final **18/12/2026** (inclui a folha de aprovação assinada). Relatório quinzenal obrigatório com mínimo de 40h (último prazo conhecido 21/10; próximos por cálculo 04/11 e 18/11, a confirmar).
- Meta de tamanho: 40 a 50 páginas. A v16 renderiza com 42 páginas, mas boa parte é texto do modelo ainda a limpar.
- Cenário real: um comerciante de periféricos de informática que vendia só em marketplaces e vislumbrava loja própria. **É real, mantido anônimo; nunca chamar de "fictício".** A autora implantou e configurou a loja e aplicou as medidas. Não incluir cláusula de anonimato no texto.

## 2. Pergunta de pesquisa e objetivos
- Pergunta: quais medidas de segurança podem ser aplicadas sobre uma plataforma de e-commerce baseada em WordPress e WooCommerce para mitigar injeção de SQL, força bruta, falhas de autenticação e configurações inseguras de cabeçalhos HTTP, e qual é a sua eficácia quando comparadas, em ambiente controlado, a uma versão sem elas?
- Objetivo geral: analisar vulnerabilidades e implementar medidas de segurança em plataforma WordPress/WooCommerce, mitigando riscos e protegendo dados da loja e dos clientes.
- Específicos (a a d): identificar vulnerabilidades por ferramentas de auditoria; levantar boas práticas; desenvolver e aplicar soluções (consultas parametrizadas, cabeçalhos HTTP, mitigação de força bruta, MFA) em ambiente controlado; avaliar eficácia por simulações (injeção de SQL, força bruta) e verificação dos mecanismos de autenticação (phishing foi retirado do escopo).

## 3. Ambiente técnico (Cenário A vulnerável × Cenário B com defesas)
- Camadas: notebook Manjaro Linux (host, também usado pela empresa, que tem Docker próprio) → VM **Ubuntu Server 26.04** no **GNOME Boxes** (4 GB RAM, 2 núcleos, 25 GB; hostname `tcc-server`, usuário `bruna`) → Docker Compose com dois contêineres.
- Imagens: `wordpress:php8.3-apache` e `mariadb:11`. Porta presa a `127.0.0.1:8080` (a loja só responde dentro da VM). Pastas: `~/tcc-ecommerce/cenario-a` e (provavelmente) `cenario-b`. Estado-base idêntico exportado em `baseline.sql`. Credenciais de laboratório, dados sintéticos (produtos de periféricos, 5 a 10 clientes fictícios), nenhuma integração externa.
- Ferramentas de auditoria: OWASP ZAP (`zaproxy/zap-stable`, `zap-baseline.py`, varredura passiva), SQLMap (ativo), Hydra com `rockyou.txt` (~14,3 milhões de senhas).
- **A VM estava desligada em 24/10; o código e as configurações ficam dentro dela.** O assistente não tem acesso à VM; a Bruna precisa rodar comandos e colar a saída.
- **Como religar (registrado numa etapa anterior da conversa):** no terminal do Manjaro, `virsh --connect qemu:///session start ubuntu25.10` (nome da VM no virsh; esperar 30 a 40 s). Depois `ssh -p 2222 bruna@192.168.15.150` (se der "Connection reset", a VM ainda está subindo). Na VM: `cd ~/tcc-ecommerce/cenario-a`, `docker compose up -d`, `docker compose ps` (dois contêineres Up, porta 8080). Confirmado em 24/10: a VM roda **Ubuntu 26.04.1 LTS** (`ubuntu25.10` é só o rótulo no virsh). Contêineres: `cenario-a-db-1` (mariadb:11) e `cenario-a-wordpress-1` (wordpress:php8.3-apache); a porta aparece como `0.0.0.0:8080->80` (não `127.0.0.1`), e a VM tem IP na rede local 192.168.15.150.
- As auditorias do Cenário B (ZAP) foram rodadas a partir da pasta `cenario-a`: provavelmente A e B compartilham a pasta e os estados ficam em **snapshots com READMEs** (citados nas notas técnicas). Conferir com `ls ~/tcc-ecommerce`.

## 4. As quatro defesas (Cenário B) e os resultados
1. **SQL Injection.** Cenário A: componente PHP que concatena `$_GET['id']` na consulta. Observação-chave: o SQLMap **não** achou injeção nos parâmetros nativos do WooCommerce; a falha foi introduzida de forma controlada em componente próprio, representando prática de codificação insegura. SQLMap confirmou por 3 técnicas (boolean-based blind, time-based blind, UNION) e extraiu `wp_users` (9 registros, hashes phpass/bcrypt). Correção em 3 camadas: `filter_input(FILTER_VALIDATE_INT)`, prepared statement (`bind_param("i")`), tratamento de erro seguro (`error_log` e mensagem genérica; corrige o HTTP 500 que vazava informação). Cenário B: SQLMap "all tested parameters do not appear to be injectable"; testes manuais retornam "Parâmetro inválido".
2. **Cabeçalhos HTTP (hardening Apache, módulo headers).** `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Content-Security-Policy`, `Referrer-Policy`, `Permissions-Policy`, `ServerTokens Prod`, regra de cookie. Verificado com `curl -I`. **HSTS não foi implementado** (o ambiente é HTTP em localhost; navegadores ignoram HSTS sobre HTTP).
3. **Força bruta.** Cenário A: Hydra descobriu a senha de uma conta fraca em **38 tentativas, ~4 s**, sem bloqueio. Cenário B: plugin próprio em PHP com hooks `authenticate`, `wp_login_failed`, `wp_login`; contagem por IP em transients; bloqueio após **5 falhas por 15 minutos**; contador zera no login bem-sucedido. Mensagem: "Acesso temporariamente bloqueado por excesso de tentativas". Achado metodológico: reexecução do Hydra no B deu **falso positivo** (a ferramenta infere sucesso pela ausência da string de erro; o bloqueio muda a resposta). A validação confiável foi o teste manual.
4. **MFA (TOTP, RFC 6238).** Biblioteca PHPGangsta/GoogleAuthenticator só para o cálculo; integração desenvolvida pela autora (segredo por usuário, hook `authenticate`, token temporário de 5 min em transient, página de verificação independente). Sem QR code externo (entrada manual da chave, para preservar o isolamento). Primeira abordagem (ocultar campos do formulário padrão por CSS/JS) falhou e foi trocada por página própria. Testado com Google Authenticator: senha correta sem código = acesso negado ("Código inválido. Tente novamente.").

**Auditoria ZAP A×B (corrigida em 24/10/2026):** mesma ferramenta (ZAP 2.17.0) e 171 endpoints nos dois relatórios HTML (A: 14/09/2026; B: 24/10/2026). A: High 0, Medium 7, Low 9, Info 10 (Medium+Low = 16). B: High 0, Medium 5, Low 6, Info 10 (Medium+Low = 11). **Redução de 16 para 11 (31%).** Terminal (`WARN-NEW`): 22 → 16 (27%). O "22 → 11 (50%)" da auditoria de 04/10 misturava medidas (22 = contagem do terminal com informativos; 11 = Medium+Low do HTML) e NÃO deve ser usado. Eliminados (8): Missing Anti-clickjacking, CSP Not Set, X-Content-Type-Options, Permissions Policy, Server version, X-Powered-By (7 ligados às diretivas), mais In Page Banner e CSP Wildcard (inferidos, não testados). Persistem (8): Anti-CSRF, CSP sem fallback, CSP unsafe-inline (script-src e style-src; já existiam no A, vindos do WordPress), Cookie sem HttpOnly (4), Cookie sem SameSite (3), CORP, Timestamp. Novos no B (3, não atribuídos ao hardening): Vulnerable JS Library (moment.min.js do WordPress), COEP, COOP. A regra de cookie NÃO eliminou o SameSite. SQLMap: mitigação de 100% no ponto testado. Hydra e MFA: validação qualitativa. Não existe índice único de mitigação (decisão deliberada de rigor).

**Configuração real verificada na VM em 24/10:** arquivo `/etc/apache2/conf-enabled/seguranca-headers.conf` (dentro do contêiner `cenario-a-wordpress-1`) com `Header always set` de X-Frame-Options "SAMEORIGIN", X-Content-Type-Options "nosniff", Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;", Referrer-Policy "strict-origin-when-cross-origin", Permissions-Policy "geolocation=(), microphone=(), camera=()"; `Header always edit Set-Cookie ^(.*)$ "$1; HttpOnly; SameSite=Strict"`; `Header always unset X-Powered-By`; `ServerTokens Prod`. `php -i` mostra `expose_php => Off`. `curl -I` confirma os cinco cabeçalhos, `Server: Apache` sem versão e **sem** `X-Powered-By`. Sem HSTS (ambiente HTTP). **O CSP usa 'unsafe-inline' em script-src e style-src**, o que permite scripts inline e enfraquece a mitigação de XSS (compromisso comum no WordPress); não escrever que o CSP "mitiga XSS" sem essa ressalva.
**Datas da implementação (snapshots):** hardening de cabeçalhos em 22/09; auditoria final comparativa (ZAP 22 → 11, medidas misturadas; ver correção de 24/10) em **04/10**; correção do X-Powered-By (`expose_php = Off`) em **05/10**, depois da auditoria final. O ZAP de 15/10 anexado também é anterior à correção.
**Reauditoria ZAP em 24/10 (Cenário B atual):** 209 URLs; `FAIL-NEW: 0 WARN-NEW: 16 PASS: 52`. PASS agora para X-Powered-By, Anti-clickjacking, X-Content-Type-Options, CSP Not Set e Permissions Policy. Continuam WARN: Cookie No HttpOnly (4 ocorrências, em respostas de add-to-cart e ?p=38), Cookie without SameSite (3, add-to-cart), CSP: Failure to Define Directive with No Fallback (10055), Anti-CSRF ausente, COEP ausente, moment.min.js vulnerável (biblioteca do WordPress), entre outros (vários informativos). **Conjunto de regras idêntico ao do baseline (A: 22 WARN/45 PASS = 67 regras; hoje 16 WARN/52 PASS = 68; só a 20020 é nova e passou), logo a comparação é válida. A explicação anterior de "regras mais novas" estava errada (vinha de um arquivo de 15/10 cortado).** Decisão: atualizar Tabela 2 e Cap. 3.4 para 16 → 11 (31%). A regra de cookie do Apache não alcança todos os cookies do WooCommerce (limitação a registrar). O aviso 10055 aponta diretivas do CSP sem fallback (frame-ancestors, form-action e similares; conferir qual no relatório HTML): melhoria para Extensões.
Pasta `~/tcc-ecommerce/cenario-a/snapshots/`: README-cenario-A.txt, README-cenario-B-sqli.txt, README-cenario-B-hardening.txt, busca-produto-VULNERAVEL.php, cenario-A-banco.sql, forca-bruta-cenario-A.txt, forca-bruta-cenario-B.txt, mfa-cenario-B.txt, auditoria-final-comparativo.txt. Reverse proxy/filtros ficaram como trabalho futuro. Evidências devem ter cópia fora da VM.

## 5. Estrutura da monografia (modelo FURB) e estado na v16
- **Pré-textuais:** capa e folha de rosto preenchidas. Pendentes: Dedicatória, Agradecimentos, Epígrafe (só dela), Resumo (150 a 500 palavras: objetivo, metodologia, resultados, conclusões) e Abstract, palavras-chave, **lista de abreviaturas e siglas** (hoje só tem exemplos do modelo), folha de aprovação (só na versão final, 18/12). Sumário e listas de figuras/quadros/tabelas são campos do Word (atualizar com Ctrl+A, F9; `updateFields` já está ligado).
- **1 Introdução (fechada na v16):** 5 parágrafos, 1.1 Objetivos, 1.2 Estrutura ("quatro capítulos, incluindo esta introdução"). Parágrafo 1 com dados de LGPD art. 46, Verizon 2026 (varejo: 99% externos, 85% financeiro, 95% três padrões, vetores 42/14/9, p. 94) e OWASP 2025 (A02, A05, A07). Parágrafo 4 (justificativa) com LGPD, Sebrae/FGV e IA (Pearce, Perry, Tóth). **Ainda no parágrafo 2: afirmação sem fonte sobre responsabilidade de segurança no marketplace.** Parágrafo 3 sem fonte para "plataforma amplamente utilizada" (W3Techs: WordPress em 39,6% de todos os sites e 58,3% dos com CMS conhecido, medido em 22/10/2026).
- **2 Fundamentação:** texto pronto: abertura, 2.1 Comércio eletrônico e segurança da informação, 2.2 SQL Injection. **Faltam:** 2.3 Cabeçalhos HTTP (escopo real: CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy; HSTS só como contexto), 2.4 Força bruta e autenticação, nova seção MFA/TOTP, Correlatos (hoje a linha de abertura; reaproveitar a tabela do TCC1). Os títulos "TÍTULO DA SEÇÃO-3/4" ainda são do modelo. Cuidado: a numeração dos Ttulo2 é automática; não digitar números.
- **3 Desenvolvimento:** 3.1 Requisitos, 3.2 Especificação (Figuras 2 e 3: diagramas estrutural e comportamental), 3.3 Implementação (3.3.1 Técnicas e ferramentas, 3.3.2 Operacionalidade, com 3 casos A×B), 3.4 Resultados e discussões (Tabela 2). Escrito, mas passa por revisão completa.
- **4 Conclusões e Extensões:** só placeholders do modelo.
- **Referências (12):** BRASIL (LGPD), CENTRAL DO VAREJO, HALFOND et al. (2006), NIST FIPS 199 (2004), OWASP Top 10:2025, OWASP SQLi Cheat Sheet [s.d.], PEARCE et al. (2021), PERRY et al. (2023), SEBRAE (2026), TÓTH et al. (2024), VERIZON (2026, relatório completo). Apêndices A e B e Anexo A ainda são exemplos do modelo (decidir com o orientador).

## 6. Fontes verificadas (24/10/2026)
- **OWASP Top 10:2025 (owasp.org/Top10/2025):** A01 Broken Access Control, A02 Security Misconfiguration, A03 Software Supply Chain Failures, A04 Cryptographic Failures, A05 Injection (caiu de 3º para 5º), A06 Insecure Design, A07 Authentication Failures, A08 Software or Data Integrity Failures, A09 Security Logging & Alerting Failures, A10 Mishandling of Exceptional Conditions. A07 cita força bruta/ataques automatizados e MFA ausente ou ineficaz; A02 cita servidor que não envia cabeçalhos de segurança.
- **LGPD art. 46 (Planalto):** conferido pela Bruna na página oficial.
- **Sebrae/IBRE-FGV (Agência Sebrae, 27/07/2026):** "Impactos dos golpes digitais e/ou por telefone nas empresas", 4.466 empreendedores; 44% dos pequenos negócios tiveram tentativa nos 12 meses anteriores; 45% estimam impacto "grave ou muito grave" (NÃO é "fechariam"; o erro veio de uma reportagem). Trata de golpes em geral, não só de ataques web.
- **Verizon DBIR 2026:** relatório completo (121 p.), seção Retail na p. 94 (conferida pela Bruna). Resumo executivo (23 p.) traz Retail na p. 16 e PMEs: 100% dos três padrões, exploração de vulnerabilidades 26%. O DBIR não é amostra estatística de todas as empresas.
- **Estudos sobre IA e código:** Pearce et al. 2021 (89 cenários, 1.689 programas, ~40% vulneráveis; preprint arXiv); Perry et al. 2023 (ACM CCS '23; 47 participantes analisados; assistente = código menos seguro e excesso de confiança); Tóth, Bisztray, Erdődi 2024 (2.500 sites PHP do GPT-4; preprint arXiv; resumo diz 11,56% e o corpo 11,16%, não usar esse número sem conferir). Candidatos em português ainda não lidos: Graeml et al. 2026 (SBC), Oliveira e Oliveira 2026 (Research, Society and Development).
- Ruohonen 2018 (plugins WordPress; preprint arXiv) verificado.

## 7. Decisões tomadas
- Monografia (não artigo). Tom acadêmico; seguir o estilo dos TCCs aprovados lidos (aberturas com "tem + particípio", transições "Nesse contexto").
- Reaproveitar e aprofundar o TCC1 na Introdução e Fundamentação; Desenvolvimento e Conclusão são novos. Confirmar com o orientador quanto reaproveitar.
- Revisar **todos** os capítulos, inclusive os "prontos", em ciclos curtos (busca, leitura, escrita da seção).
- IA entra só como um trecho de justificativa (código gerado por IA e vulnerabilidades), não como tema.
- Fonte primária primeiro; TCC serve de mapa de fontes, não de fonte; blogs de empresa não são base teórica.
- Cenário A usa falha de codificação controlada (e não versão antiga), para poder desenvolver a correção.

## 8. Pendências e inconsistências conhecidas
1. **Cap. 3.3.1** diz que o hardening suprimiu "a linguagem de programação utilizada": isso é verdadeiro no estado atual (X-Powered-By removido, `expose_php=Off`, flags de cookie aplicadas). O que está desatualizado são as notas técnicas e o ZAP de 15/10. ZAP já rerodado em 24/10: atualizar a Tabela 2 (16 → 11, 31%) e o parágrafo das categorias atribuíveis (ver bloco de auditoria ZAP corrigida).
2. Cap. 3 tem **`[CITAR REFERÊNCIA]` (2x)** e **`[CONFIRMAR ESCOPO EXATO DE CADA TRABALHO]`**, além do colchete do modelo "[Implementação – mostrar como foi realizada...]" sob o título 3.3.
3. Cap. 3.4 cita **Vitorino e Silva (2025), Topolniak et al. (2022), Franzese (2023)**, que **não estão na lista de Referências**. Trazer do TCC1 e conferir.
4. Seções do modelo ainda no documento ("observações gerais", "formatação", exemplos de citação) a apagar.
5. Referência de 2.2 aponta "seção 3.3.1" para detalhar a correção de SQLi; conferir se a seção realmente a detalha.
6. HSTS: tratar como limitação do ambiente HTTP e como extensão futura.
7. Seção MFA/TOTP e Correlatos (vira 2.6) por escrever; Resumo/Abstract/siglas por fazer.
8. Cópias das evidências fora da VM.
9. Dúvidas com o orientador: escopo das quatro defesas (reverse proxy ficou como trabalho futuro), Apêndices/Anexo, reaproveitamento do TCC1, data de revisão do rascunho.

## 9. Plano até a entrega
- S1 (24 a 30/10): Introdução fechada (feito); 2.3 Cabeçalhos HTTP; iniciar 2.4.
- S2 (31/10 a 06/11): 2.4 e seção MFA; revisar 2.1 e 2.2. Relatório ~04/11.
- S3 (07 a 13/11): Correlatos; revisar 3.1 e 3.2.
- S4 (14 a 20/11): revisar 3.3 e 3.4; Conclusões e Extensões; enviar rascunho completo ao orientador ~17/11. (Feriado 20/11.)
- S5 (21 a 27/11): Resumo, Abstract, palavras-chave, siglas, dedicatória/agradecimentos/epígrafe; conferir citação × referência; limpar o modelo; atualizar sumário e listas; formatação; entregar até 25/11.
- Depois: apresentação e estudo para a banca (07 a 11/12); entrega final em 18/12.
- Plano de pesquisa por tema (12 temas, com strings de busca): arquivo `Plano_Pesquisa_Embasamento_TCC2.docx`.

## 10. Triagem das referências do TCC1
Manter: Albertin e Moura (RAE, 1998), Nogueira (Computação Brasil/SBC, 2024), OWASP (trocar para 2025), Stallings e Brown, Ferreira (Casa do Código). Usar como correlatos/mapa (TCCs): Franzese (UFU, 2023), Sampaio (UFC, 2021), Oliveira (2019), Mota (PUC Goiás, 2021), Vitorino e Silva (UFMS, 2025; WordPress). Rever/substituir (blogs e matérias): ClearSale, Contábeis, Castro (E-Commerce Brasil), Tatara (BRY); links do Google Books e Academia.edu.

## 11. Arquivos (pasta de saídas)
`Monografia_Bruna_v16.docx` (atual; v12 a v15 são intermediárias), `Notas_Tecnicas_Cenarios_A_e_B.docx` (fonte principal da implementação), `Notas_Tecnicas_Cenario_A.docx`, `Guia_Estudo_Banca_Bruna.docx`, `Guia_Tecnico_R2_Ambiente.docx`, `Guia_VM_Passo_Zero.docx`, `Mini_Guia_Auditoria_ZAP_SQLMap.docx`, `Plano_TCC2_Bruna_v2.docx`, `Plano_Pesquisa_Embasamento_TCC2.docx`, `Diagrama_Estrutural.png`, `Diagrama_Comportamental.png`, `produtos_perifericos.csv`, `referencias_acumuladas.md` (desatualizado; a lista válida está na v16). Anexos dela: TCC1, projeto, aulas 1 a 6 da FURB, modelo da monografia e da apresentação, 6 TCCs aprovados, saídas de terminal do ZAP, SQLMap e Hydra.

## 12. Limites deste contexto
O histórico da conversa anterior ao último resumo não está disponível; este documento foi reconstruído a partir do resumo, das notas técnicas, do texto da monografia e dos anexos. O código das defesas e as configurações do Apache/PHP estão **dentro da VM** e não foram vistos pelo assistente.
