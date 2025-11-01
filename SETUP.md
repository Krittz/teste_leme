# 📘 Task Manager API – Guia de Instalação

Guia passo a passo para configurar e executar a API localmente.

---

## 📋 Pré-requisitos

Certifique-se de ter instalado:

- **Docker** ≥ 20.10  
- **Docker Compose** ≥ 1.29  
- **Git**  

Verifique a instalação:

```bash
docker --version
docker-compose --version
git --version
```

---

## 🚀 Passo 1 – Clonar o Repositório
```bash
# HTTPS
git clone https://github.com/krittz/teste_leme.git
cd teste_leme/

# SSH (Linux/macOS)
git clone git@github:krittz/teste_leme.git
cd teste_leme/
```
---

## 🔧 Passo 2 – Configurar Variáveis de Ambiente
### 2.1 Banco de Dados

Dentro do diretório databases:
```bash
cp .env.example .env
```

Preencha as variáveis:
```bash
MYSQL_USER=seu_usuario
MYSQL_PASSWORD=sua_senha
MYSQL_ROOT_PASSWORD=root_senha
MYSQL_DATABASE=task_manager
```

Crie o container do banco:
```bash
docker compose up -d databases
```

---

### 2.2 API

Dentro do diretório task-manager-api:
```bash
cp .env.example .env
```

Atualize com as informações do banco:
```bash
DB_HOST=databases
DB_PORT=3306
DB_NAME=task_manager
DB_USER=seu_usuario
DB_PASS=sua_senha
DB_CHARSET=utf8mb4
```
#### 2.2.1 Gerar JWT Secret
```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

Ou utilize a ferramenta online: [JWT Gen](https://krittz.github.io/jwt-gen/)

Atualize no .env:
```bash
JWT_SECRET=seu_token_gerado
```

---

## 🐳 Passo 3 – Iniciar Container da API

Na raiz do projeto (onde está o docker-compose.yml):
```bash
docker compose up -d api
```

Verifique os containers:
```bash
docker ps
```

Exemplo de saída esperada:
```bash
NAME                   STATUS              PORTS
databases              Up (healthy)        3306/tcp
task_manager_api       Up (healthy)        0.0.0.0:8080->80/tcp
```
---

## ✅ Passo 4 – Verificar Instalação
### 4.1 Health Check da API
```bash
curl http://localhost:8080/health
```

Resposta esperada:
```json
{
  "success": true,
  "message": "API funcionando corretamente",
  "data": {
    "status": "ok",
    "timestamp": "2025-11-01T12:00:57-03:00",
    "database": "connected",
    "version": "1.0.0"
  }
}
```
### 4.2 Verificar Banco de Dados
```bash
docker exec -it databases mysql -u seu_usuario -pSuaSenha -e "USE task_manager; SHOW TABLES;"
```

Saída esperada:
```bash
+-------------------------+
| Tables_in_task_manager  |
+-------------------------+
| project_members         |
| projects                |
| tasks                   |
| users                   |
+-------------------------+
```

---

## 🧪 Passo 5 – Testar a API
### 5.1 Registrar Usuário
```bash
curl -X POST http://localhost:8080/api/auth/register \
-H "Content-Type: application/json" \
-d '{
    "name": "Usuário Teste",
    "email": "teste@email.com",
    "password": "senha123"
}'
```

### 5.2 Login
```bash
curl -X POST http://localhost:8080/api/auth/login \
-H "Content-Type: application/json" \
-c cookies.txt \
-d '{
    "email": "teste@email.com",
    "password": "senha123"
}'
``` 

### 5.3 Criar Projeto (autenticado)
```bash
curl -X POST http://localhost:8080/api/projects \
-H "Content-Type: application/json" \
-b cookies.txt \
-d '{
    "title": "Meu Projeto",
    "description": "Descrição do projeto",
    "start_date": "2025-01-01",
    "end_date": "2025-12-31"
}'
```

### 5.4 Acessar Dashboard
```bash
curl -X GET http://localhost:8080/api/dashboard/summary \
-H "Content-Type: application/json" \
-b cookies.txt
```

---

## Front-End:

**Recomendo não utilizar o frontend para testar a aplicação**
**Segue a collection do postman para testar os endpoints de maneira funcional**

Realmente frontends não são meu ponto forte, então assumo que tentei muitas ferramentas, usando React, Vue, até mesmo Angular.
Porém em todos acabei me perdendo nas inumeras configurações do projeto o que me tomou muito tempo, então resolvi tentar de uma forma mais simples usando o HTML com JS puro, pois a ideia principal era apenas consumir os endpoints, porém novamente acabei me perdendo em tantos pontos que as coisas não saíram como planejado, não sei se é devido ao js não persistir os estados, ou o que seja, realmente admito minha falta de expertise no quesito frontend, que vem me assombrando a algum tempo.

A ideia esta parcialmente funcional. Está mostrando os dados do dashboard, trazendo os itens, as tasks e os projetos, mas me perdi primeiramente no momento da adição de membros aos projetos, de edição/atualização das tasks e dos projetos, *sem desculpas* erro meu *assumo minha responsabilidade*.

Caso queira realmente ver o quão ruim ficou o front-end, bastar iniciar um servidor http com `python`
```bash
cd frontend/
python3 -m http.server 5500
```

**Como dito, segue anexo ao projeto a collection para testar a API em si**
`collection.json`