# Piloto: deiaSurvey

> **Estado: registro histórico inicial.** As conclusões dinâmicas foram
> substituídas pela [matriz dinâmica corrigida](matriz-dinamica.md).

## Escopo confiável inicial

- Fonte declarada: `git@gitlab.lepidus.com.br:softwares-pkp/plugins_ojs/deiaSurvey.git`.
- PKP 3.3: `stable-3_3_0`, OJS e OPS.
- PKP 3.4: `stable-3_4_0`, OJS e OPS.
- PKP 3.5: `main`, cuja CI inclui o template `stable-3_5_0`, OJS e OPS.
- Modo: auditoria abrangente e somente leitura.
- Fora de escopo: alterações, correções, commits no plugin, push, MR, tag e release.

O ramo `main` é tratado como 3.5 porque a CI observada inclui explicitamente o template PKP `stable-3_5_0`. Essa associação será revalidada no snapshot remoto preparado e registrada como fato observado, não como convenção universal.

## Superfícies esperadas

- coleta e persistência de respostas potencialmente sensíveis;
- criptografia de dados;
- integração ORCID;
- envio de e-mails;
- questionário em perfil e submissão;
- exportação e relatório;
- templates e JavaScript;
- migrations, schemas e consultas próprias;
- isolamento entre periódicos/editoras e usuários;
- compatibilidade OJS/OPS nos três ramos.

## Pergunta do experimento

Os três agentes especializados encontram evidência complementar, mantêm conclusões consistentes entre versões e evitam promover candidatos automáticos a vulnerabilidades confirmadas?

## Critérios de avaliação posterior

- cobertura de superfícies e ramos;
- quantidade de achados duplicados eliminados;
- achados refutados pela revisão independente;
- divergências legítimas entre versões;
- clareza das limitações;
- custo e duração por perspectiva;
- utilidade das recomendações para eventual correção.

## Execução realizada

Execução local no projeto experimental, não anexada a esta MR:
`.execucoes/deiaSurvey-20260823`.

| Linha | Ramo remoto | SHA auditado |
|---|---|---|
| PKP 3.3 | `stable-3_3_0` | `68d8ded69301bf32e93d94723ca5bcc1e595abe0` |
| PKP 3.4 | `stable-3_4_0` | `a3a78fbf31fcfca9c55abfa7b3b6c789985b10d2` |
| PKP 3.5 | `main` | `0faa02348b53a52900a8416605bb322696380fe1` |

Os três alvos foram clonados diretamente do remoto, atualizados com
`git pull --ff-only` e permaneceram limpos. A referência usada pelo backend de
validação também foi clonada dentro da execução, para não depender das refs do
checkout original.

Foram produzidos seis planos, um para cada combinação OJS/OPS e 3.3/3.4/3.5,
com rede externa proibida. O smoke OJS 3.5 passou em VM efêmera. A tentativa de
unidade OJS 3.5 parou na preparação do banco por falha de resolução do hostname
`mariadb`, antes de executar os testes do plugin; portanto, não é um veredito de
compatibilidade. A VM dessa tentativa foi descartada após a coleta.

Os resultados consolidados originais ficam no projeto experimental local em
`resultados/deiaSurvey-20260823/`; esta MR preserva somente os Markdown
selecionados.

Na execução local exploratória, o diretório `planos-vm-isolados` é o
autoritativo. O diretório inicial `planos-vm` foi uma tentativa diagnóstica
anterior à criação da referência isolada e não deve ser usado como evidência.
Nenhum desses diretórios descartáveis faz parte desta MR.
