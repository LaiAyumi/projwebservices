# API CRUD - Web Services em PHP

API REST em PHP puro (sem framework e sem Composer) para o CRUD de alunos, usando PDO para acesso ao banco de dados MySQL/MariaDB.

## ✨ Funcionalidades

- Endpoints de criação, leitura, atualização e remoção de alunos
- Respostas em JSON no formato `{"status": "success" | "error", "data": ...}`
- Validação dos dados enviados (nome, email e telefone)
- Acesso a banco via PDO com prepared statements
- Separação entre roteamento (`index.php`), camada de serviço (`AlunosService.php`) e camada de modelo (`Alunos.php`)
- Configuração do banco por variáveis de ambiente

## 🛠️ Tecnologias

![PHP](https://img.shields.io/badge/-PHP-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/-MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white)

Requer PHP 7.4 ou superior com a extensão `pdo_mysql`, e MySQL ou MariaDB.

## 📁 Estrutura

```
index.php            roteador: lê a URL, escolhe o serviço e monta a resposta JSON
AlunosService.php    regras e validação do recurso alunos
Alunos.php           consultas SQL na tabela alunos
Conexao.php          conexão PDO compartilhada
ApiException.php     erro com status HTTP
config.php           configuração do banco (variáveis de ambiente)
.htaccess            reescrita de URL para o Apache
database/schema.sql  criação do banco, da tabela e dados de exemplo
tests/smoke.sh       teste rápido de todas as rotas com curl
```

## 🔗 Endpoints

| Método | Rota               | Descrição                 | Sucesso         | Erros                    |
|--------|--------------------|---------------------------|-----------------|--------------------------|
| GET    | `/api/alunos`      | Lista todos os alunos     | 200             | 500                      |
| GET    | `/api/alunos/{id}` | Busca um aluno            | 200             | 400, 404, 500            |
| POST   | `/api/alunos`      | Cadastra um aluno         | 201 + `Location` | 400, 415, 422, 500      |
| PUT    | `/api/alunos/{id}` | Atualiza um aluno (todos os campos) | 200   | 400, 404, 415, 422, 500  |
| DELETE | `/api/alunos/{id}` | Remove um aluno           | 200             | 400, 404, 500            |

Significado dos códigos de erro:

- **400**: `id` que não é um inteiro positivo, ou JSON inválido no corpo
- **404**: rota inexistente ou aluno não encontrado
- **405**: método não aceito na rota (o cabeçalho `Allow` informa os métodos válidos; `HEAD` funciona como `GET` sem corpo e `OPTIONS` responde 204 com o `Allow`)
- **415**: `Content-Type` não suportado
- **422**: dados inválidos (a mensagem lista os campos com problema)
- **500**: erro interno, por exemplo falha no banco (os detalhes vão só para o log do servidor)

### Campos do aluno

| Campo      | Regra                                                   |
|------------|---------------------------------------------------------|
| `codigo`   | gerado pelo banco                                       |
| `nome`     | obrigatório, de 1 a 50 caracteres (espaços nas pontas são removidos) |
| `email`    | obrigatório, email válido, até 50 caracteres            |
| `telefone` | opcional, até 25 caracteres (vazio ou ausente vira `null`) |

Os campos são texto em UTF-8, sem caracteres de controle. Em JSON também é aceito número inteiro (por exemplo `"telefone": 123456789`); número decimal é recusado.

O corpo pode ser enviado em JSON (`Content-Type: application/json`) ou como formulário (`application/x-www-form-urlencoded`). No `PUT` a atualização é completa: `nome`, `email` e `telefone` são substituídos, então um `telefone` não enviado fica `null`.

## 💡 Exemplos com curl

Listar:

```bash
curl http://localhost:8000/api/alunos
```

```json
{"status":"success","data":[{"codigo":1,"nome":"Lucio","email":"lucio@teste.com","telefone":"123456789"},{"codigo":2,"nome":"Nelson","email":"Nelson@teste.com","telefone":"323456788"},{"codigo":3,"nome":"Cao Ji Kan","email":"Caojikan@teste.com","telefone":"988456788"}]}
```

Buscar um aluno:

```bash
curl http://localhost:8000/api/alunos/1
```

```json
{"status":"success","data":{"codigo":1,"nome":"Lucio","email":"lucio@teste.com","telefone":"123456789"}}
```

Cadastrar (JSON):

```bash
curl -i -X POST http://localhost:8000/api/alunos \
  -H "Content-Type: application/json" \
  -d '{"nome":"Ana","email":"ana@teste.com","telefone":"11 99999-0000"}'
```

```
HTTP/1.1 201 Created
Content-Type: application/json; charset=UTF-8
Location: /api/alunos/4

{"status":"success","data":{"codigo":4,"nome":"Ana","email":"ana@teste.com","telefone":"11 99999-0000"}}
```

Cadastrar (formulário):

```bash
curl -X POST http://localhost:8000/api/alunos -d "nome=Maria&email=maria@teste.com"
```

Atualizar:

```bash
curl -X PUT http://localhost:8000/api/alunos/4 \
  -H "Content-Type: application/json" \
  -d '{"nome":"Ana Souza","email":"ana.souza@teste.com","telefone":"11 98888-0000"}'
```

```json
{"status":"success","data":{"codigo":4,"nome":"Ana Souza","email":"ana.souza@teste.com","telefone":"11 98888-0000"}}
```

Remover:

```bash
curl -X DELETE http://localhost:8000/api/alunos/4
```

```json
{"status":"success","data":"Aluno removido com sucesso."}
```

Exemplos de erro:

```bash
curl -X POST http://localhost:8000/api/alunos \
  -H "Content-Type: application/json" \
  -d '{"nome":"","email":"email-invalido"}'
# 422 {"status":"error","data":"Dados inválidos: nome é obrigatório; email inválido."}

curl http://localhost:8000/api/alunos/999
# 404 {"status":"error","data":"Aluno não encontrado."}

curl http://localhost:8000/api/alunos/abc
# 400 {"status":"error","data":"O id deve ser um número inteiro positivo."}
```

## 🚀 Como rodar

1. Crie o banco, a tabela e os dados de exemplo (o script pode ser rodado de novo sem duplicar os dados):

```bash
mysql -u root -p < database/schema.sql
```

2. Configure o acesso ao banco. O `config.php` lê as variáveis de ambiente abaixo; as que não forem definidas usam o valor padrão:

| Variável  | Padrão            |
|-----------|-------------------|
| `DB_HOST` | `localhost`       |
| `DB_PORT` | `3306`            |
| `DB_NAME` | `projwebservices` |
| `DB_USER` | `root`            |
| `DB_PASS` | (vazio)           |

3. Suba a API com o servidor embutido do PHP, usando o `index.php` como roteador:

```bash
php -S localhost:8000 index.php
```

Com variáveis de ambiente:

```bash
DB_USER=meu_usuario DB_PASS=minha_senha php -S localhost:8000 index.php
```

**Apache:** coloque a pasta dentro do diretório do servidor (por exemplo `htdocs/projwebservices`) com o `mod_rewrite` ativo e `AllowOverride All`. O `.htaccess` manda para o `index.php` tudo o que não for um arquivo real e bloqueia as pastas `database`, `tests` e `.git`; as rotas ficam em `http://localhost/projwebservices/api/alunos`. As variáveis do banco podem ser definidas no VirtualHost com `SetEnv`, por exemplo `SetEnv DB_USER meu_usuario` (no PHP-FPM use `env[DB_USER] = meu_usuario` no pool).

Observação: em algumas instalações (Debian/Ubuntu) o usuário `root` do MariaDB só entra pelo `unix_socket`, sem senha pelo PHP. Nesse caso crie um usuário próprio e use `DB_USER`/`DB_PASS`.

**Sem reescrita de URL:** a rota também pode ser passada direto no parâmetro `url`, por exemplo `http://localhost:8000/index.php?url=api/alunos/1`. Esse parâmetro só é lido quando o caminho é o próprio `index.php`; em `/api/alunos/...` vale o caminho.

## ✅ Testes

O `tests/smoke.sh` usa `bash` e `curl` para chamar todas as rotas e os principais casos de erro. Ele cria, altera e apaga alunos, então use um banco de testes. Termina com código diferente de zero se algum teste falhar.

```bash
# em um terminal: banco de testes + servidor
sed 's/projwebservices/projwebservices_test/g' database/schema.sql | mysql -u root -p
DB_NAME=projwebservices_test php -S localhost:8000 index.php

# em outro terminal
bash tests/smoke.sh
```

Para testar outro endereço, use a variável `BASE_URL`:

```bash
BASE_URL=http://localhost/projwebservices bash tests/smoke.sh
```
