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
| SEC-01 | a confirmar | proposta, sem código | bearer validado antes do callback, `state` expirável e callback sem segredo na URL |
| SEC-02 | a confirmar | proposta, sem código | token CSPRNG com hash/TTL, resgate único por POST e sessão curta |
| SEC-03 | a confirmar | decisão humana | definir se subeditor pode exportar respostas individuais |
| SEC-04 | CWE-1236 | corrigido | neutralização central em todas as células CSV e testes de relatório |
| AUTH-03 | a confirmar | candidato | reproduzir HTTP e definir se participação entre contextos é admitida |
| AUTH-04 | a confirmar | confirmado, sem código | teste negativo de leitura de opções entre contextos |
| LOGIC-01 | sem CWE precisa | confirmado, sem código | teste de banco e decisão entre rejeitar replay ou substituir respostas |
| SC-01/02 | a confirmar | fora deste patch | correção estrutural pertence também aos templates centrais de CI |
| COMP-01 | não aplicável | alegação refutada; hardening aplicado | o core fornece polyfill; `strncmp()` remove a dependência transitiva |

## SEC-04 — fórmula em CSV

Comportamento anterior: respostas textuais e metadados configuráveis chegavam
diretamente a `fputcsv()`. Uma planilha podia interpretar células iniciadas por
`=`, `+`, `-`, `@` ou controles como fórmulas.

Correção: `CsvCellSanitizer` prefixa apóstrofo em células perigosas antes de
todos os sinks CSV dos relatórios individual e agregado. Valores comuns e tipos
não textuais são preservados. O teste cobre prefixos diretos, whitespace,
caracteres de controle e o CSV produzido por `ContextReport`.

Commit: `6a6ab7e144be63c7980e24977aad20e3f810f737`.

Risco residual: a neutralização reduz interpretação de fórmulas em leitores de
planilha; não controla importadores que removam deliberadamente o apóstrofo.

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
   gravar o ORCID.

Esse desenho invalida links antigos e exige reemissão controlada para autores
pendentes. O fluxo de reenvio e o prazo do link precisam de decisão antes de a
implementação ser apresentada como pronta para merge.

## Decisões solicitadas

1. Autorizar a invalidação e reemissão dos links legados?
2. Qual deve ser o prazo do link inicial e da sessão curta?
3. Subeditores podem acessar CSV individual com respostas DEIA?
4. Um usuário registrado pode responder em qualquer contexto ou somente onde
   possui vínculo?
5. Em replay sequencial, a última resposta deve substituir a anterior ou a
   requisição deve ser rejeitada?
