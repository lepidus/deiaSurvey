[English](/README.md) | **Português Brasileiro**

# Questionário DEIA (Diversidade, Equidade, Inclusão e Acessibilidade)

Este plugin permite a coleta de dados DEIA dos usuários através de um questionário.

## Compatibilidade

Este plugin é compatível com as seguintes aplicações PKP:

- OJS e OPS nas versões 3.3 e 3.4

Verifique a última versão compatível com a sua aplicação na [Página de Versões](https://github.com/lepidus/deiaSurvey/releases).

## Download do plugin 

Para fazer download do plugin, vá até a [Página de Versões](https://github.com/lepidus/deiaSurvey/releases) e faça download do pacote tar.gz da última versão compatível com o seu OJS/OPS.

## Instalação

1. Entre na área administrativa do seu site OJS através do __Painel de Controle__.
2. Navegue até `Configurações`> `Website`> `Plugins`> `Carregar um novo plugin`.
3. Em __Carregar arquivo__, selecione o arquivo __deiaSurvey.tar.gz__.
4. Clique em __Salvar__ e o plugin será instalado no seu OJS/OPS.

## Instruções de uso
Assim que o plugin for ativado, o questionário será exibido na página de perfil do usuário. Ao acessar essa página, o usuário encontrará uma nova aba, chamada "Questionário DEIA", onde poderá consentir em responder o questionário ou não.

![](screenshots/Questionnaire-pt_BR.png)

Para autores que não estiverem registrados no sistema, um e-mail será enviado quando a submissão for aceita, requisitando que estes preencham o questionário. Esse e-mail é enviado apenas para autores para os quais não há um usuário no sistema com o mesmo endereço de e-mail.

O e-mail enviado oferece duas maneiras de preencher o questionário. A primeira requere que um registro ORCID seja autenticado, de forma que os dados fiquem associados a esse registro. A segunda utiliza o endereço de e-mail do(a) autor(a) e é recomendada apenas para aqueles que não possuem um registro ORCID.

Os usuários podem ver e excluir seus dados a qualquer momento. Para os autores sem cadastro que preencheram o questionário, caso estes criem um usuário no sistema com o mesmo endereço de e-mail ou ORCID utilizado, seus dados serão migrados para este novo usuário.

## Créditos
Este plugin foi patrocinado por Lepidus Tecnologia, Scientific Electronic Library Online (SciELO), Revista Encontros Bibli (UFSC) e outros (iremos atualizar em breve).

Desenvolvido por Lepidus Tecnologia.

## License

__Este plugin é licenciado sob a GNU General Public License v3.0__

__Copyright (c) 2024-2025 Lepidus Tecnologia__