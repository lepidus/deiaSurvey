# Baseline em VM

> **Estado: registro histórico inicial.** O diagnóstico de DNS abaixo foi
> resolvido posteriormente. Consulte a
> [matriz dinâmica corrigida](matriz-dinamica.md) para o resultado atual.

## Infraestrutura reutilizada

- cliente PKP: `validacao-plugins-pkp`;
- executor efêmero: `executor-efemero`;
- configuração do validador: cópia isolada gerada dentro da execução;
- rede externa nos seis planos: `forbidden`.

O projeto experimental apenas prepara snapshots e delega. Não reimplementa
libvirt, overlays, Docker, banco, PHPUnit ou Cypress.

## Resultados

| Execução | Alvo/modo | Resultado | Interpretação |
|---|---|---|---|
| `20260823-173506-310702` | OJS 3.5 / smoke | aprovado, saída 0 | backend e snapshot conseguiram completar o smoke |
| `20260823-173656-318413` | OJS 3.5 / unidade | falha, saída 1 | bloqueio ambiental antes dos testes do plugin |

Na tentativa de unidade, o primeiro erro causal foi:

```text
PDO::connect(): php_network_getaddresses: getaddrinfo for mariadb failed: Temporary failure in name resolution
```

O validador encerrou com `não foi possível preparar o banco para PHP 8.4 em
ojs-3.5`. A memória foi classificada como suficiente, sem OOM. Logo, essa
tentativa não sustenta conclusão positiva nem negativa sobre a suíte do plugin.

A VM da tentativa aprovada foi descartada automaticamente. A VM preservada pela
falha de unidade foi descartada explicitamente após a coleta com o controlador
do `executor-efemero`; os artefatos diagnósticos locais foram mantidos.

## Cobertura não executada

- unidade completa OJS 3.3, 3.4 e 3.5;
- integração/Cypress OJS 3.3, 3.4 e 3.5;
- integração/Cypress OPS 3.3, 3.4 e 3.5;
- PostgreSQL, que não faz parte dos perfis atuais;
- reprodução dinâmica dos achados de segurança.

Antes de tratar a matriz como gate, é necessário corrigir ou estabilizar a
resolução DNS da rede Docker interna e repetir a execução completa.
