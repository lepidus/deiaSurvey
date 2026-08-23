# Avaliação do experimento

> **Estado: registro histórico inicial.** A falha ambiental descrita abaixo foi
> corrigida posteriormente. Consulte a
> [matriz dinâmica corrigida](matriz-dinamica.md) para o resultado atual.

## Veredito

A abordagem multiagente aumentou a cobertura e, principalmente, tornou visíveis
as divergências que um coordenador ou verificador precisa resolver. Ela é útil
para os próximos plugins, desde que não seja confundida com votação entre
agentes nem com substituição da validação dinâmica. Este primeiro ciclo deve ser
tratado como piloto parcial: a metodologia e a auditoria estática são
avaliáveis, mas a matriz dinâmica não terminou.

## O que funcionou

- um único coordenador fixou os três ramos por SHA após clone e pull;
- todos os agentes receberam os mesmos snapshots e trataram OJS/OPS e versões
  separadamente;
- autorização, fluxo de dados e cadeia de entrega produziram evidência
  complementar;
- duplicações como ORCID e relatórios puderam ser deduplicadas pela causa;
- controles positivos foram preservados, evitando relatar correções antigas
  como vulnerabilidades atuais;
- os backends e VMs existentes foram reaproveitados sem ampliar o escopo do
  projeto experimental;
- a contestação independente foi capaz de examinar conflitos entre revisores.

## O que precisa melhorar

- a matriz dinâmica não terminou por uma falha de DNS do banco dentro da VM;
- o manifesto resumido de PHP não substitui a matriz efetiva do plano e pode
  esconder lacunas como PHP 7.3/7.4 no ramo 3.3;
- o backend existente recalcula o plano; por isso o adaptador precisa comparar
  o JSON recalculado byte a byte com o plano autoritativo imediatamente antes
  de executar, além de conferir commits e configurações;
- achados dependentes de política, como quem pode acessar dados DEIA, precisam
  de uma matriz de autorização aprovada pelo produto;
- integrações externas como ORCID exigem reprodução controlada antes de uma
  divulgação definitiva;
- relatórios completos por agente são úteis para diagnóstico, mas o artefato
  versionado deve continuar deduplicado e curto.

## Uso recomendado de agentes no próximo plugin

1. Um coordenador mecânico prepara refs, SHAs, inventário e planos.
2. Três revisores percorrem todos os ramos por perspectiva, não um ramo cada.
3. O coordenador deduplica causas e identifica conflitos factuais.
4. Um revisor independente tenta refutar apenas achados altos/críticos e
   conflitos, consultando o core correspondente.
5. A matriz dinâmica reproduz os fluxos prioritários em VMs descartáveis.
6. Um relatório único separa confirmado, candidato, refutado, controle positivo
   e bloqueio ambiental.

Para custo, tarefas mecânicas podem usar um agente mais econômico; autorização,
criptografia, privacidade, cadeia de fornecimento e contestação de severidade
devem permanecer com modelo de maior capacidade. O retorno de qualquer agente
precisa incluir SHA, cobertura, primeiro erro causal e limitações.

## Gates antes de escalar a abordagem

- teste do projeto e `shellcheck` aprovados;
- checkout-fonte original com SHA e estado inalterados;
- nenhum remoto configurado no repositório experimental;
- três snapshots limpos e ligados à proveniência;
- seis planos gerados com rede externa proibida;
- altos/críticos revisados por agente independente;
- falhas ambientais separadas de falhas do plugin;
- nenhum log bruto, clone, segredo ou disco de VM versionado;
- commit seletivo apenas no projeto experimental e nenhum push.
