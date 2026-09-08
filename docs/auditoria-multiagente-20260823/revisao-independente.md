# Revisão independente

> **Uso interno — contém achados ainda não corrigidos. Estado: revisão estática
> dos SHAs indicados.** Os limites dinâmicos originais foram atualizados na
> [matriz dinâmica corrigida](matriz-dinamica.md).

Revisor: `gpt-5.6-sol`, esforço alto, somente leitura. O revisor consultou os
três snapshots e cores OJS/OPS locais, sem executar o plugin nem acessar o
remoto.

Terminologia de permissões esclarecida em 2026-09-07: `ROLE_ID_*` representa
níveis de permissão; os papéis configuráveis são `user_group`. Esse ajuste
documental não representa nova execução da revisão estática original.

## Contestação

| Alegação | Parecer |
|---|---|
| callback ORCID sem correlação com bearer/`state` | validado estaticamente, alto, em 3.3–3.5 e OJS/OPS; exploração não reproduzida |
| geração e ciclo de vida do bearer | geração/URL/ausência de expiração confirmadas; facilidade prática de adivinhação inconclusiva |
| relatório individual a usuário com nível `ROLE_ID_SUB_EDITOR` no contexto | acesso técnico confirmado em 3.3/3.4 e OJS/OPS; violação e severidade dependem de política organizacional ainda não definida, sem equiparar nível a grupo |
| relatório site-wide 3.5 a usuário com permissões somente no contexto | refutado no ciclo PHP convencional; registro depende de site admin |
| fórmula CSV | confirmado como vetor de respondente em 3.3/3.4, severidade média; em 3.5 resta somente metadado privilegiado |
| usuário autenticado sem vínculo responde noutro contexto | candidato estático em todos os ramos/apps; faltam teste HTTP e requisito do produto |
| publicação por tag sem gates | dependência entre jobs ausente nos três ramos; impacto condicionado às políticas externas não observadas |
| `str_starts_with()` no PHP 7.3/7.4 | alegação refutada: o core 3.3 carrega `symfony/polyfill-php80`; resta dependência transitiva |

O revisor observou que o relatório 3.5 não repete o defeito 3.3/3.4 porque
`DeiaSurveyPlugin.php:60-65` só registra o report plugin quando
`Validation::isSiteAdmin()`. Recomenda-se ainda defesa no `display()` e teste
HTTP negativo para ambientes persistentes.

## Críticas ao protótipo incorporadas

- push dos snapshots passou a ser desabilitado localmente;
- SHA da árvore e SHA-256 do índice recursivo passaram a integrar a
  proveniência e são revalidados em coleta, planejamento e execução;
- backend executável passou a usar allowlist exata;
- configuração isolada do backend passou a ser obrigatória;
- execução passou a conferir drift do plano, fonte, commits, configurações e
  política de rede;
- foi definido um único diretório de plano autoritativo;
- adulteração do snapshot e backend hostil passaram a ter regressões;
- identidade dos agentes, modelos e hashes dos prompts foi preservada;
- o relatório consolidado foi corrigido nos pontos refutados ou rebaixados.

## Limites e atualização dinâmica

- permissões do mesmo usuário Unix não constituem sandbox forte para revisores;
- a matriz dinâmica foi repetida após a correção do hostname interno do banco;
  cinco dos seis alvos passaram e OJS 3.5 manteve falhas próprias de teste;
- não houve teste HTTP negativo nem reprodução ORCID/CSV;
- a política organizacional de acesso a respostas DEIA ainda precisa ser
  explicitada.

Por isso, esta revisão registra uma **auditoria estática contestada e uma matriz
dinâmica com cobertura conhecida**, não uma ferramenta pronta para execução
autônoma nem prova de exploração dos achados.
