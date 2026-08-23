# Metodologia do experimento

> **Estado: atual.** Metodologia aplicada ao piloto de 23 de agosto de 2026.

## Objetivo

Avaliar se agentes com perspectivas complementares aumentam cobertura e reduzem falsos positivos numa auditoria de segurança PKP multiversão, preservando uma fonte única e reproduzível por SHA.

## Unidade de análise

A unidade não é apenas o plugin. É a combinação:

```text
plugin + ramo + aplicação + versão PKP + SHA
```

Código comum pode ser analisado uma vez, mas a conclusão precisa ser projetada e confirmada em cada alvo suportado.

## Fases

### 1. Preparação centralizada

Somente o coordenador acessa o remoto. Ele valida o manifesto, clona cada ramo em uma execução nova, realiza `git pull --ff-only` e registra o SHA. Os revisores não manipulam Git.

### 2. Inventário estático

O coletor automático registra SHA e contagens por tipo. Os agentes então
inventariam CI, versão, dependências, testes, handlers, hooks,
DAOs/repositórios, templates, JavaScript, integrações externas e operações de
estado, registrando a cobertura no contrato de retorno. Nenhuma dessas etapas
executa conteúdo do plugin.

Antes de qualquer prova dinâmica, o coordenador gera os seis planos OJS/OPS por meio do `validacao-plugins-pkp`. Esse projeto continua responsável por perfis, imagens fixadas, banco, PHPUnit, Cypress e proveniência; o `executor-efemero` continua responsável pelas VMs libvirt. O experimento não duplica essas responsabilidades.

Há um único diretório de planos autoritativos por execução. A execução dinâmica
recusa drift da fonte, do plano, dos commits das ferramentas, das configurações
ou da política de rede desde o planejamento.

### 3. Revisão especializada

Três agentes recebem os mesmos snapshots e perspectivas distintas:

1. autorização, isolamento de contexto e lógica de negócio;
2. fluxos de dados, entradas, saídas e superfície web;
3. compatibilidade PKP, dependências, CI, testes e cadeia de suprimentos.

Cada agente percorre os três ramos. Não há um agente por ramo, salvo quando as implementações forem materialmente diferentes.

### 4. Contestação independente

Achados altos e críticos são enviados a um revisor que não participou da descoberta. Sua tarefa é tentar refutar a alegação, procurando proteção no core, precondições ausentes, entrada não controlável ou ramo não afetado.

### 5. Consolidação

O coordenador não decide por maioria. Ele inspeciona a evidência, deduplica a mesma causa entre ramos e preserva divergências ou incertezas.

## Taxonomia de saída

- `confirmado`: fluxo e impacto sustentados por evidência;
- `candidato`: sinal relevante ainda sem fluxo completo;
- `não afetado`: proteção ou ausência do código demonstrada;
- `não verificável`: faltou core, runtime, dependência, rede ou dado necessário;
- `controle positivo`: proteção relevante confirmada.

## Evidência mínima de achado

- identificador estável;
- severidade e confiança;
- CWE e requisito ASVS quando aplicáveis;
- caminho e linha em cada ramo;
- origem controlável e operação sensível;
- precondições e atores;
- impacto técnico e de negócio;
- correção e mitigação;
- possível falso positivo;
- estado em 3.3, 3.4 e 3.5;
- validação independente para severidade alta ou crítica.

## Práticas adicionais

Além das orientações locais, a cobertura usa:

- OWASP ASVS como catálogo de requisitos técnicos verificáveis;
- OWASP SAMM para combinar baseline automatizada e revisão manual guiada por risco;
- OWASP SCVS e SLSA para componentes, artefatos e proveniência;
- NIST SSDF para rastreabilidade, resposta e prevenção de recorrência;
- CWE para classificar a causa, nunca para substituir a demonstração do risco.

## Limites de execução

- preparação não executa PHP, JavaScript, Composer, hooks ou testes do plugin;
- validação dinâmica reutiliza exclusivamente o backend já instalado e exige confirmação explícita;
- testes dinâmicos exigem ambiente descartável, sem segredos e com rede mínima;
- falha de ferramenta ou rede não é evidência de ausência de vulnerabilidade;
- CI verde não prova segurança, mas define o contrato técnico executável do ramo;
- o experimento termina no relatório; correção e publicação ficam fora de escopo.
