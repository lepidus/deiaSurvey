# Relatório consolidado — deiaSurvey

> **Uso interno — contém achados ainda não corrigidos. Estado: análise estática
> dos SHAs indicados.** A execução em VM foi atualizada posteriormente; consulte
> a [matriz dinâmica corrigida](matriz-dinamica.md).

## Escopo e proveniência

Auditoria estática somente leitura de OJS e OPS nos snapshots:

| Linha | Ramo | SHA |
|---|---|---|
| PKP 3.3 | `stable-3_3_0` | `68d8ded69301bf32e93d94723ca5bcc1e595abe0` |
| PKP 3.4 | `stable-3_4_0` | `a3a78fbf31fcfca9c55abfa7b3b6c789985b10d2` |
| PKP 3.5 | `main` | `0faa02348b53a52900a8416605bb322696380fe1` |

Os três revisores especializados inspecionaram os mesmos snapshots. Um quarto
revisor independente tentou refutar achados altos e conflitos. Nenhum agente
editou o plugin. Os detalhes completos de proveniência, inventário, JSON e
planos permanecem no projeto experimental local e não fazem parte desta MR,
que preserva apenas os relatórios Markdown.

## Resumo

| ID | Classificação | Ramos | Síntese |
|---|---|---|---|
| SEC-01 | alta | 3.3–3.5 | callback ORCID não valida o bearer do autor e não usa `state` |
| SEC-02 | alta se exposto; adivinhação inconclusiva | 3.3–3.5 | bearer persistente, reutilizável e transportado na URL |
| SEC-03 | alta, condicionada à política | 3.3/3.4 | subeditor pode exportar respostas DEIA individuais |
| SEC-04 | média | 3.3/3.4 | respostas controláveis em CSV não são neutralizadas |
| SC-01 | alta potencial; lacuna de garantia | 3.3–3.5 | publicação por tag não depende dos gates de teste/segurança observados |
| COMP-01 | alta operacional | 3.3 | `str_starts_with()` quebra PHP 7.3/7.4 suportado |
| AUTH-03 | candidato médio | 3.3–3.5 | possível resposta em outro contexto; requer teste HTTP e requisito de produto |
| LOGIC-01 | média | 3.3 | replay sequencial cria respostas externas duplicadas |
| AUTH-04 | baixa | 3.3–3.5 | gerente pode consultar opções de pergunta de outro contexto pelo ID |

Não há evidência de exploração, comprometimento da cadeia ou exposição já
ocorrida. Severidade descreve o impacto potencial sob as precondições listadas.

## Achados prioritários

### SEC-01 — callback ORCID sem correlação de autorização

Nos três ramos, `authorize()` exige `authorId` e algum `authorToken`, mas não
compara o valor com `deiaToken` antes de `orcidVerify()`. `OrcidClient` não gera
`state`; após trocar o código, o callback grava o ORCID retornado no autor.

- 3.3: `pages/deia/QuestionnaireHandler.php:123-137,262-310`;
- 3.4: `pages/deia/QuestionnaireHandler.php:101-115,249-297`;
- 3.5: `pages/deia/QuestionnaireHandler.php:112-127,275-324`;
- todos: `classes/OrcidClient.php:31-92`.

Ator e precondições: pessoa não autenticada com conta ORCID, conhecimento de um
`authorId`, ORCID habilitado e aceitação do `redirect_uri` pelo provedor.
Impacto: vincular o ORCID do atacante a outro autor e desviar o identificador
usado pelas respostas. A correção deve usar `state` aleatório, expirável,
unívoco e ligado a autor/contexto, além de validar novamente o bearer antes do
sink. A reprodução no sandbox ORCID ainda é necessária.

O caminho foi validado estaticamente; a exploração não foi reproduzida.

### SEC-02 — ciclo de vida inseguro do bearer externo

`DataCollectionEmailSender` usa `md5(microtime() . email)`, persiste o token em
claro e o propaga na query string. Não há expiração, rotação automática,
limitação própria de tentativas ou troca por sessão restrita.

- 3.3: `classes/DataCollectionEmailSender.php:70-95`;
- 3.4: `classes/DataCollectionEmailSender.php:71-95`;
- 3.5: `classes/DataCollectionEmailSender.php:69-93`;
- validação e uso: `pages/deia/QuestionnaireHandler.php` nos três ramos.

Ator e precondições: obter a URL por e-mail, histórico, referer ou log; a
viabilidade de adivinhar o token apenas pela janela temporal não foi medida e
permanece inconclusiva. Impacto após exposição: ler, substituir ou excluir
respostas. A
correção deve usar `random_bytes`, hash no armazenamento, expiração/rotação e
sessão curta. É controle positivo relevante que o token atual está `writeOnly`,
não aparece no resumo da API 3.5, não pode ser escrito pela API e é comparado
com `hash_equals`; a antiga exposição pela API não foi reproduzida.

### SEC-03 — relatório individual disponível a subeditor em 3.3/3.4

O core autoriza operações de relatório a `ROLE_ID_SUB_EDITOR`; o plugin não
aplica gate adicional no relatório de contexto e exporta uma linha por pessoa,
inclusive texto livre, embora sem uma coluna direta de identidade.

- core 3.3: `lib/pkp/pages/stats/PKPStatsHandler.inc.php:27-44,441-452`;
- core 3.4: `lib/pkp/pages/stats/PKPStatsHandler.php:41-60,577-589`;
- plugin: `report/DeiaSurveyReportForm.php:41-82` e
  `report/classes/ContextReport.php:42-49,96-138`.

Ator e precondições: subeditor autenticado no contexto, plugin e respostas
existentes. Impacto: reidentificação e divulgação de atributos sensíveis. A
classificação de segurança pressupõe que subeditores não tenham autorização
organizacional para dados DEIA individuais; essa matriz de negócio precisa ser
confirmada. Recomenda-se autorização dedicada e agregação com limiar mínimo.

### SEC-04 — injeção de fórmula em CSV

`fputcsv()` delimita campos, mas não impede que a planilha interprete células
iniciadas por `=`, `+`, `-`, `@` ou controles. Em 3.3/3.4, respostas textuais de
participantes chegam ao relatório individual; nos três ramos, títulos e opções
configuráveis chegam a cabeçalhos/relatórios.

- 3.3/3.4: `report/classes/ContextReport.php:62-81,96-138`;
- todos: `report/classes/factories/ContextStatisticsFactory.php:41-87` e
  `report/classes/SiteStatisticsReport.php:40-84`.

Ator e precondições: participante com bearer em 3.3/3.4, seguido de abertura em
uma planilha que interpreta fórmula. Recomenda-se uma função central de
neutralização e testes com todos os prefixos perigosos. Em 3.5 não existe o
relatório individual; resta apenas metadado administrável por papel privilegiado
e ele não foi promovido como o mesmo vetor.

### SC-01 — lacuna de garantia na release por tag

Os jobs GitLab de unidade, Cypress, SAST, detecção de segredos e estilo pulam
tags, enquanto empacotamento/release executam em tags sem `needs` para esses
gates. O workflow GitHub também publica tags `v*` diretamente.

Ator e precondições: capacidade de criar tag/release; regras externas de tag
protegida e permissões efetivas não foram observadas. Portanto, confirma-se a
ausência de dependência entre os jobs observados, não um bypass das políticas
externas. O impacto potencial é publicar código que não passou por esses gates.
Recomenda-se pipeline de candidato por SHA, dependências explícitas, promoção
do mesmo artefato e proteção de tags.
Também devem ser fixados por SHA os includes GitLab e actions GitHub hoje
referenciados por versões/ramos mutáveis. Não há evidência de adulteração.

### COMP-01 — PHP 7.x declarado, mas uso de API PHP 8

O ramo 3.3 chama `str_starts_with()` em
`classes/DataEncryption.php:38-45`, alcançado pela leitura em
`classes/deiaResponse/DAO.php:87-104` e pela migração em
`classes/migrations/EncryptResponsesMigration.inc.php:18-36`. O plano cobre
PHP 7.3/7.4/8.1 e o README inglês declara PHP 7.3+, mas a função só existe no
PHP 8. Em PHP 7.3/7.4 isso causa `Error`, não capturado pelo `catch (Exception)`.

É uma incompatibilidade operacional, não uma vulnerabilidade por si só.
Substituir a comparação por implementação compatível e testar leitura e
migração em toda a matriz 3.3.

## Achados médios e baixos

- **AUTH-03:** `TabHandler` exige autenticação, mas não demonstra política de
  vínculo ao contexto; um usuário da instalação pode aparentar conseguir enviar
  `QuestionsForm` em outro contexto. Precisa de teste HTTP e confirmação do
  requisito antes de correção, pois o produto pode admitir participação global.
- **LOGIC-01:** em 3.3, `registerExternalAuthorResponses()` sempre insere novas
  linhas; reenvio com o mesmo bearer infla estatísticas. 3.4/3.5 substituem as
  respostas sequencialmente em transação. Reproduzir em banco antes de migrar.
- **AUTH-04:** o listbuilder recebe `deiaQuestionId` e consulta opções sem
  validar que a pergunta pertence ao contexto do gerente. O impacto observado é
  a configuração da pergunta, não respostas pessoais.
- **Assurance:** somente MariaDB é planejado; OPS não tem PHPUnit; as matrizes
  PHP 3.4/3.5 são estreitas e não há prova de equivalência entre digests das
  imagens dos planos e os templates CI locais.

## Divergências e achados não promovidos

- **Relatório site-wide 3.5:** refutado pela revisão independente para o ciclo
  PHP convencional. Embora o core
  permita papéis de contexto na rota genérica, `DeiaSurveyPlugin.php:60-65`
  registra o report plugin somente quando `Validation::isSiteAdmin()` é
  verdadeiro. O relatório percorre todos os contextos, mas não ficou acessível
  a subeditor/gerente nos snapshots auditados. Defesa adicional no `display()`
  e teste HTTP negativo continuam recomendados para runtimes persistentes.
- **“Já respondeu” sem filtro de contexto:** o collector realmente não filtra
  contexto, mas há testes e comportamento de reutilização entre contextos. Sem
  requisito de negócio aprovado, permanece divergência de privacidade/produto,
  não vulnerabilidade confirmada.
- Upload temporário sem CSRF explícito, `unserialize()` legado, limites de
  tamanho, corrida de gravação e contexto de e-mail assíncrono ficaram como
  candidatos por falta de elo explorável ou runtime.
- A ausência de CSP, WAF ou rate limit no repositório não prova ausência no
  ambiente implantado.

## Controles positivos

- token do autor contido na serialização/API e comparado com `hash_equals`;
- vínculo autor-contexto nos handlers principais;
- POST/CSRF em gravação 3.4/3.5 e exclusão atual;
- validação server-side de perguntas, contexto, tipos e opções;
- queries com bindings, sem SQL controlável encontrado;
- criptografia autenticada das respostas;
- importação ligada ao arquivo temporário do usuário, removido em `finally`;
- exportação de blocos com filtro de contexto e CSRF;
- sem segredo/resposta encontrado nos logs do código de produção;
- sem `eval`, JSONP, `getScript` ou AJAX script no jQuery auditado.

## Validação dinâmica e limites

Foram gerados seis planos com rede externa proibida. Após corrigir o contrato do
hostname interno do banco, os seis alvos foram executados. OJS 3.3, OJS 3.4 e
OPS 3.3–3.5 passaram as suítes disponíveis. OJS 3.5 falhou no teste de unidade
e em seis testes Cypress por causas do snapshot/testes, não por DNS ou memória.
Os detalhes e contagens estão na
[matriz dinâmica corrigida](matriz-dinamica.md). Não houve reprodução do ORCID,
autorização HTTP ou CSV em planilha, nem publicação de release.

O relatório é um diagnóstico para triagem e correção posterior. A MR documental
interna foi autorizada separadamente; ela não autoriza alterar ramos de suporte,
corrigir achados, divulgar publicamente ou fazer release.
