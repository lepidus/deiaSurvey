# Matriz dinâmica corrigida — deiaSurvey

> **Estado: atual para os SHAs indicados.** Resultado dinâmico mais recente do
> experimento de 23 de agosto de 2026.

## Conclusão

O problema de DNS observado na primeira rodada não foi uma restrição de
segurança. A rede Docker interna estava disponível, mas o OJS recebia
`host = mariadb` enquanto o serviço criado pelo validador se chamava
`database`. Após corrigir o contrato, um smoke sem egress confirmou, na mesma
execução:

- `host = database` no `config.inc.php` efetivo;
- resolução interna de `database` para `172.18.0.2`;
- conexão SQL com `SELECT 1`;
- memória suficiente e ausência de OOM;
- descarte da VM.

A matriz funcional chegou aos seis alvos. Cinco passaram as suítes disponíveis.
OJS 3.5 apresentou uma falha no teste de unidade e seis falhas Cypress; são
falhas do snapshot/testes do plugin, não de DNS ou memória.

## Proveniência

| Linha | Ramo atualizado com `pull --ff-only` | SHA testado |
|---|---|---|
| PKP 3.3 | `stable-3_3_0` | `68d8ded69301bf32e93d94723ca5bcc1e595abe0` |
| PKP 3.4 | `stable-3_4_0` | `a3a78fbf31fcfca9c55abfa7b3b6c789985b10d2` |
| PKP 3.5 | `main` | `0faa02348b53a52900a8416605bb322696380fe1` |

Os planos v3 fixaram o validador experimental em
`d06be52806e5931fbe25675a12d35083f0fff018`, o executor em
`c5fa358503daa2f2683726d08b92d3a32f7ec166` e rede externa como `forbidden`.
Os testes de unidade anteriores, ainda válidos, usaram o validador em
`616e9cab00ee1d2522c333481f107286be1cf6b3`; a mudança posterior afetou apenas
a sanitização dos artefatos.

## Resultado efetivo

| Alvo | Teste de unidade | Integração Cypress | Resultado |
|---|---|---|---|
| OJS 3.3 | PHP 7.3, 7.4 e 8.1: 98 testes/205 asserções em cada versão | 36/36 | passou |
| OJS 3.4 | PHP 8.2: 118 testes/223 asserções | 35/35 | passou |
| OJS 3.5 | PHP 8.4: 106 testes/205 asserções, 1 erro | 27/33, 6 falhas | falhou |
| OPS 3.3 | desabilitado pelo perfil | 36/36 | passou com cobertura parcial |
| OPS 3.4 | desabilitado pelo perfil | 35/35 | passou com cobertura parcial |
| OPS 3.5 | desabilitado pelo perfil | 33/33 | passou com cobertura parcial |

Todas as execuções efetivas classificaram memória como suficiente, não
detectaram OOM e terminaram com comprovante de descarte da VM.

### OJS 3.5

O teste de unidade falhou em `DeiaSurveyPluginTest.php:53`. O plugin chama
tradução em `DeiaSurveyPlugin.php:144`, chegando a
`Locale::setDefault()` com locale nulo. O erro é um `TypeError`, não falha de
infraestrutura.

Na integração, a primeira falha observada foi a expectativa por um link com o
texto `Register` em `Test2_externalContributors.cy.js`. A suíte terminou com uma
falha nessa spec e cinco em `Test3_multipleContexts.cy.js`, incluindo rotas
`/operahouse/en/login` com 404 e elementos de sessão/contexto ausentes. É
necessário revisar os fixtures e seletores para OJS 3.5 e, se continuarem
falhando após essa adaptação, investigar regressão funcional do plugin.

## Execuções usadas

| Execução | Uso |
|---|---|
| `20260823-184501-845503` | smoke OJS 3.5 e prova do backend corrigido |
| `20260823-184905-872156` | unidade OJS 3.3; integração antiga desconsiderada |
| `20260823-185454-919080` | unidade OJS 3.4; integração antiga desconsiderada |
| `20260823-184620-849123` | unidade OJS 3.5 |
| `20260823-191156-959004` | integração OJS 3.3 |
| `20260823-191623-1003778` | integração OJS 3.4 |
| `20260823-192134-1036589` | integração OJS 3.5 |
| `20260823-192706-1045687` | integração OPS 3.3 |
| `20260823-193041-1052585` | integração OPS 3.4 |
| `20260823-193449-1097796` | integração OPS 3.5 |

As tentativas `20260823-184905-872156`, `20260823-185454-919080` e
`20260823-190629-945910` usaram um fixture ORCID sintético rejeitado pela
validação do OJS. As partes Cypress dessas tentativas não são evidência contra o
plugin. A execução `20260823-193839-1149895` repetiu OPS 3.5 durante uma corrida
de monitoramento e também passou 33/33; foi preservada como confirmação
redundante, não como voto adicional.

## Avaliação da abordagem multiagente

A separação de papéis funcionou quando agentes produziram evidências
independentes e o orquestrador determinístico reteve mutações e VMs:

- revisores especializados dividiram autorização/dados, compatibilidade/cadeia
  de fornecimento e refutação independente;
- um agente mecânico executou a matriz sequencial, sem poder de publicar;
- achados altos não dependeram de maioria: exigiram caminho, precondição,
  impacto e revisão adversarial;
- clones, planos, credenciais sintéticas e logs brutos ficaram fora do Git;
- somente relatórios sem segredo e o validador experimental foram registrados.

O principal aprendizado é validar fixtures antes de consumir VMs. Também é
necessário tornar o monitor idempotente: uma chamada interrompida pode continuar
no executor e não deve ser repetida até consultar status, domínio, overlay e
comprovante de descarte.

## Próximos gates concretos

1. Corrigir ou adaptar os testes OJS 3.5 e repetir somente esse alvo.
2. Adicionar PostgreSQL aos seis alvos; a rodada atual prova apenas MariaDB.
3. Cobrir pisos de PHP: 3.4 em 8.0/8.1 e 3.5 em 8.2/8.3; decidir se 3.3 também
   deve ter PHP 8.2.
4. Criar teste de unidade executável em OPS ou documentar formalmente por que a
   suíte OJS é equivalente.
5. Gerar MAC por execução e falhar no preflight se houver colisão ativa.
6. Validar formato e comprimento de todas as variáveis sintéticas antes da VM.
7. Manter sanitização como gate de aprovação; credenciais reais não devem ser
   usadas nessa matriz.

O relatório estático de segurança e seus achados continuam em
`relatorio-seguranca.md`. A execução original não alterou o plugin. O Draft
posterior para PKP 3.3 acrescenta correções identificadas separadamente em
`correcoes-propostas-3.3.md`; isso não altera a proveniência da matriz acima.
