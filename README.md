# 🧠 Teste Técnico - Vaga de Desenvolvedor PHP Pleno
**Empresa:** Leme Inteligência Forense

---

## 💬 Introdução

Antes de tudo, gostaria de expressar minha sincera gratidão pela oportunidade de participar deste processo seletivo.
Também aproveito para pedir desculpas pelas remarcações de horário e pelo estado atual deste repositório - que foge do escopo originalmente solicitado.

Este documento, que normalmente serviria como documentação técnica do projeto, será na verdade um espaço para explicar **as motivações e decisões** que me levaram a **abordar o desafio de uma forma diferente do esperado**.

---

## 🎯 Motivação e Contexto

Após a entrevista técnica com o Tech Lead da Leme, saí com uma sensação amarga por não ter conseguido expressar de forma clara o conhecimento qu possuo, especialmente em perguntas conceitualmente simples, das quais eu sabia as respostas, mas acabei me enrolando na hora de responder.

No entanto, as palavras do Tech Lead ficaram na minha cabeça:
> "Mesmo sendo um teste simples, você pode mostrar que entende padrões, arquiteturas e boas práticas."

Essa frase virou combustível.
Quando recebi o documento do desafio, um CRUD simples, percebi que poderia resolvê-lo rapidamente em **Laravel**, ou até mesmo com ajuda de IA, em **duas ou três horas**. Mas isso não mostraria **quem eu sour como desenvolvedor**.

---

## ⚙️ A abordagem Escolhida

Decidi seguir um caminho diferente:
ao invés de apenas entregar o CRUD funcional, **quis construir a base de uma aplicação sólida e escalável**, do zero, apenas com **PHP puro e documentação técnica**, sem frameworks, sem IA, sem dependências externas.

Meu foco foi em mostrar **como penso um projeto desde o setup**, antes mesmo da implementação das features.

Entre as decisões e construções que realizei:

- Estruturação manual completa do projeto (pastas, autoload e middlewares).
- Implementação de **conexão com MySQL via PDO**, utilizando o padrão **Singleton** para garantir instância única de banco.
- Criação de **helpers personalizados** (validação, resposta JSON, segurança e checagens gerais).
- Desenvolvimento de **middlewares** para segurança, CORS, tratamento de JSON e limitação de requisições.
- Estrutura de **serviços e controladores** seguindo uma separação limpa de responsabilidades.
- Configuração de **JWT manual**, sem dependências externas.
- Logs e cache configuráveis em diretórios dedicados.

A ideia era construir uma **base de aplicação robusta, segura e extensível**, pronta para reeber o CRUD e novas funcionalidades sobre uma fundação sólida.

---

Infelizmente, por questões pessoais e de tempo, dispondo apenas do domingo entre as 10h e as 19h para desenvolver, precisei priorizar as etapadas que mais demonstrassem minha visão de arquitetura, em ve da entrega final completa do desaafio.

Alguns pontos planejados, mas não concluídos por questão de tempo:

- Implementação completa dos repositórios e abstração de modelo.
- Finalização dos endpoints CRUD.
- Documentação técnica formal e testes automatizados.

Mesmo assim, mantive o foco em entregar algo que **representasse meu raciocínio, minha forma de pensar código e minha busca constante por aprimoramento**.

---

## Estrutura do Projeto

A seguir, um resumo dos diretórios principais e suas responsabilidades dentro da aplicação:

```plaintext
task-manager-api/
│
├── config/ # Arquivos de configuração (app, banco, CORS, JWT)
│
├── public/ # Ponto de entrada da aplicação (index.php) e uploads
│
├── src/
│ ├── Controllers/ # Controladores que recebem e tratam as requisições
│ ├── Database/ # Conexão com o banco (PDO + Singleton)
│ ├── Helpers/ # Funções auxiliares e utilitárias (Response, Security, etc.)
│ ├── Middlewares/ # Camada intermediária para validação, segurança e controle
│ ├── Models/ # Modelos de dados e abstrações de entidades
│ ├── Routes/ # Definição das rotas da API e sistema de roteamento simples
│ └── Services/ # Regras de negócio e serviços (Auth, JWT, etc.)
│
├── storage/
│ ├── cache/ # Cache de dados temporários
│ └── logs/ # Logs da aplicação (acesso, erros e app)
│
├── databases/
│ └── migrations/ # Scripts SQL para criação da estrutura inicial do banco
│
├── docker-compose.yml # Setup do ambiente em container
└── README.md # Documento explicativo do projeto e decisões técnicas

```


## 🚀 Conclusão

O que você encontrará neste repositório **não é apenas código**, mas o resultado de um desafio pessoal.
Busquei demonstrar que **penso como engenheiro de software**, e não apenas como executor de tarefas.

Preferi entregar uma estrutura sólida, construída a mão, que mostra minha preocupação com segurança, arquitetura e boas práticas, mesmo que isso significasse não cumprir 100% do escopo funcional.

> "Escolhi mostrar como eu penso, e não penas o que eu sei digitar."

Agradeço novamente pela oportunidade e pelo tempo dedicado à avaliação.
Foi um exercício valioso, que certamente me fez evoluir como desenvolvedor.

---

**Cristian Alves Silva**
📧 cristianalvessilvak@gmail.com | contato@cran.com.br
💻 Analista e Desenvolvedor de Sistemas