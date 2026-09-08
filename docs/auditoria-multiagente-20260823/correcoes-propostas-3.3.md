# Correções propostas para PKP 3.3

> **Draft interno.** Este documento acompanha código para revisão e não autoriza
> merge, release ou implantação.

## Base e objetivo

- target: `stable-3_3_0`;
- SHA-base revalidado: `68d8ded69301bf32e93d94723ca5bcc1e595abe0`;
- aplicações: OJS e OPS 3.3;
- PHP suportado pelo core: 7.3 a 8.2;
- `version.xml`, tags e releases: inalterados.

O ramo 3.3 foi escolhido para o piloto de correções porque concentra mais
achados e permanece amplamente implantado. Cada conclusão abaixo distingue
correção implementada, proposta ainda sem código e decisão humana pendente.

## Matriz atual

| ID | CWE | Estado neste Draft | Evidência ou próximo gate |
|---|---|---|---|
| SEC-01 | a confirmar | proposta, sem código | bearer validado antes do callback, `state` expirável, callback sem segredo e identidade preservada na recuperação |
| SEC-02 | a confirmar | proposta, sem código | token CSPRNG com hash/TTL, resgate único por POST, sessão curta e recuperação também para respondentes |
| SEC-03 | a confirmar | decisão humana | definir acesso por ação, contexto, nível de permissão e, quando necessário, `user_group_id` |
| SEC-04 | CWE-1236 | corrigido para revisão | neutralização de prefixos e preservação dos limites das células CSV |
| AUTH-03 | a confirmar | candidato | reproduzir HTTP e definir se participação entre contextos é admitida |
| AUTH-04 | a confirmar | confirmado, sem código | teste negativo de leitura de opções entre contextos |
| LOGIC-01 | sem CWE precisa | confirmado, sem código | teste de banco e decisão entre rejeitar replay ou substituir respostas |
| SC-01/02 | a confirmar | fora deste patch | correção estrutural pertence também aos templates centrais de CI |
| COMP-01 | não aplicável | alegação refutada; hardening aplicado | o core fornece polyfill; `strncmp()` remove a dependência transitiva |

## SEC-04 — fórmula em CSV

Comportamento anterior: respostas textuais e metadados configuráveis chegavam
diretamente a `fputcsv()`. Uma planilha podia interpretar células iniciadas por
`=`, `+`, `-`, `@` ou controles como fórmulas.

O commit inicial `6a6ab7e144be63c7980e24977aad20e3f810f737` neutralizou
prefixos, mas a revisão de 2026-09-07 demonstrou um contorno: aspas precedidas
de barra invertida podiam encerrar uma célula em leitores de planilha e expor
uma fórmula na célula seguinte. O leitor PHP usado no teste não detectava essa
diferença de interpretação.

A correção complementar usa `CsvRowWriter` nos sete pontos de escrita dos
relatórios individual e agregado. Ele aplica `CsvCellSanitizer`, envolve cada
campo em aspas e duplica todas as aspas internas; a barra invertida permanece
literal. Isso preserva os limites das células sem exigir o parâmetro
`escape=''`, disponível em `fputcsv()` somente a partir do PHP 7.4. A CI deste
ramo continua incluindo PHP 7.3. O BOM, a ordem das colunas e os valores
exportados são preservados, exceto pelo apóstrofo de neutralização já previsto.

Provas focalizadas no OJS 3.3/PHP 7.4:

- antes da correção: o caso `cell-boundaries` falhou na comparação dos bytes
  do CSV, mostrando aspas internas não duplicadas;
- depois: 28 testes e 50 asserções passaram, cobrindo o escritor, o sanitizador
  e os dois relatórios; os novos casos verificam bytes esperados, sem depender
  do escape proprietário de `fgetcsv()`;
- leitura independente por `csv.reader` do Python preservou nove linhas com
  duas células cada, incluindo vírgulas, aspas, barras invertidas e quebras de
  linha;
- LibreOffice 24.2, isolado e sem rede, com avaliação de fórmulas habilitada:
  uma fórmula aritmética no CSV anterior e nenhuma no CSV corrigido.

Referências: [CWE-1236](https://cwe.mitre.org/data/definitions/1236.html) e
[contrato de escape de fputcsv](https://www.php.net/manual/en/function.fputcsv.php).

Risco residual: importadores que alterem o dialeto ou removam deliberadamente
o apóstrofo exigem validação própria. A prova não demonstra execução de comandos
nem exfiltração. Esta correção limita-se ao ramo 3.3; não constitui backport
automático para 3.4/3.5.

## SEC-01 e SEC-02 — desenho coordenado para a próxima rodada

A correção completa não deve apenas trocar MD5 por `random_bytes()`. O contrato
proposto é:

1. reutilizar `deiaToken` como registro versionado contendo somente hash e
   expiração;
2. emitir 32 bytes aleatórios, rotacionando o link anterior;
3. negar tokens legados sem prazo confiável;
4. apresentar confirmação local e consumir o token por `POST` com CSRF e
   compare-and-set atômico;
5. trocar o link por concessão curta de sessão e redirecionar para URL limpa;
6. iniciar ORCID somente dentro dessa sessão, com `state` aleatório e expirável;
7. remover autor e bearer do `redirect_uri` e revalidar autor/contexto antes de
   gravar o ORCID;
8. oferecer recuperação/reemissão tanto para autores pendentes quanto para
   autores sem cadastro que já responderam, mediante comprovação de
   titularidade pelo canal de e-mail associado à identidade existente. O novo
   acesso deve permanecer vinculado ao autor e contexto corretos, sem aceitar
   um endereço arbitrário enviado pelo solicitante nem trocar silenciosamente
   a identidade externa usada nas respostas.

Esse desenho invalida links antigos e exige reemissão controlada para autores
pendentes **e para quem já respondeu**. O remetente atual
(`classes/DataCollectionEmailSender.php:24-25`) não envia convite a quem já
respondeu; portanto, não basta reutilizar o fluxo de convite para recuperar
acesso. Hoje, consulta e exclusão pelo autor externo dependem do mesmo bearer
em `pages/deia/QuestionnaireHandler.php`, e a documentação pública
(`docs/README-pt_BR.md:36`) prevê acesso posterior aos próprios dados.

Após expirar o link ou a sessão, o respondente deve poder recuperar acesso,
consultar e excluir as respostas existentes, sem ser obrigado a responder de
novo ou criar uma conta. A recuperação não deve gravar novo consentimento,
duplicar respostas nem mudar sua associação a e-mail/ORCID. Respostas públicas
genéricas e limites de envio/tentativas precisam evitar enumeração e abuso do
canal; os valores desses limites e os prazos ainda dependem de decisão.

Antes de apresentar a implementação como pronta para merge, demonstrar:

- recuperação após invalidação de link legado, expiração e consumo, tanto para
  pendentes quanto para respondentes, inclusive quem já respondeu antes da
  migração;
- consulta e exclusão após recuperação, preservando a identidade e o contexto
  originais e sem recriar respostas ou consentimento;
- negação para autor/contexto incorretos, falta de comprovação de titularidade
  e reuso do token consumido, com controles de abuso e CSRF preservados.

Esta rodada corrige somente a proposta documental; SEC-01/02 continuam sem
implementação de segurança no runtime. O fluxo de recuperação/reenvio, sua
migração e os prazos precisam de decisão e testes próprios.

## Decisões solicitadas

1. Autorizar a invalidação e reemissão dos links legados, incluindo a recuperação
   de acesso dos autores sem cadastro que já responderam?
2. Quais devem ser os prazos dos links e da sessão curta, o fluxo de recuperação
   e os limites de envio/tentativas?
3. Quais níveis de permissão e grupos (`user_group_id`), em cada contexto,
   devem acessar CSV individual com respostas DEIA? `ROLE_ID_SUB_EDITOR` é um
   nível compartilhável por vários papéis, não um papel único. Uma restrição
   além da autorização do core exige requisito explícito e testes de paridade
   ou da divergência aprovada.
4. Um usuário registrado pode responder em qualquer contexto ou somente onde
   possui vínculo?
5. Em replay sequencial, a última resposta deve substituir a anterior ou a
   requisição deve ser rejeitada?
