# Experimento de auditoria multiagente — 2026-08-23

> **Uso interno — contém achados de segurança.** O Draft do ramo 3.3 também
> contém correções propostas para avaliação pela equipe de desenvolvimento.

Documentação provisória do experimento de auditoria de segurança e
compatibilidade do plugin `deiaSurvey` em OJS e OPS 3.3, 3.4 e 3.5.

## Resultado atual

- [Relatório estático de segurança](relatorio-seguranca.md)
- [Revisão independente](revisao-independente.md)
- [Matriz dinâmica corrigida](matriz-dinamica.md)
- [Correções propostas para PKP 3.3](correcoes-propostas-3.3.md)

O relatório da matriz dinâmica é a referência atual para a execução em VM. Ele
substitui as conclusões ambientais da baseline e da avaliação inicial: o
problema de DNS foi corrigido, os seis alvos foram executados e OJS 3.5
permaneceu com falhas de teste que exigem investigação própria.

## Método e registros históricos

- [Metodologia](metodologia.md)
- [Escopo inicial do piloto](piloto-deiaSurvey.md)
- [Baseline inicial em VM](baseline-vm-inicial.md)
- [Avaliação inicial do experimento](avaliacao-inicial.md)

Os dois últimos arquivos preservam a cronologia do piloto e não representam o
estado final da matriz.

## Limites

- Os snapshots foram atualizados e fixados por SHA antes da análise.
- A auditoria não alterou os ramos 3.3, 3.4 ou 3.5 do plugin.
- Não há evidência de exploração ou comprometimento.
- Os achados dependentes de política permanecem condicionais.
- Logs brutos, clones, credenciais sintéticas, discos de VM e caminhos locais
  não fazem parte desta documentação.
- Este Draft não autoriza merge, release, implantação ou divulgação pública.
