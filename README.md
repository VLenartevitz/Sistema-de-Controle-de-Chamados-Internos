# Sistema de Controle de Chamados Internos - Codificar (v1 - Etapa 2.0)

Primeira versão do sistema de chamados internos solicitado pela Codificar. Esta entrega cobre a **Etapa 2.0 - Cadastro de Chamados** (CRUD completo) com Docker e testes. Etapas 3.0-6.0 (distribuição automática, filtros avançados) serão evoluções futuras.

## Stack e Justificativas (PDF 1.2, SDD 4,17)

| Tecnologia | Uso | Justificativa |
|---|---|---|
| Laravel 11 | Backend | Framework maduro, MVC, validação, ORM, migrations, testes. Recomendado pela Codificar. |
| Inertia.js | Integração | Reduz atrito front/back sem SPA/API separadas. Produtividade para time pequeno (dica PDF). |
| Vue 3 | Frontend | Componentização simples e produtiva. |
| Tailwind CSS 4 | UI | Utilitário para interface funcional rápida, sem reinventar roda. |
| MySQL 8 (Docker) | Banco | Substituí SQLite do SDD por MySQL para ambiente Docker mais realista e fácil de testar em equipe. Testes continuam com SQLite :memory: (rápido/isolado). |
| PHPUnit 12 | Testes | Padrão Laravel, cobertura das regras principais. Escolha alternativa a Pest (SDD previa Pest/PHPUnit). |
| Docker + docker-compose | Infra | Requisito da entrega: `use Docker` e `construir de maneira que consigam testar facilmente`. 1 comando sobe app+db, outro roda testes. |

Decisões arquiteturais:
- **Monólito modular single-repo** (SDD 5): projeto pequeno, único domínio. Evita custo de separar front/back.
- **Sem auth v1** (escolha 3): PDF/SDD não exigem login; adicionaria complexidade sem valor para 2.0.
- **Sem Service de distribuição nesta etapa**: RN01-RN02 cobertos, RN03-RN06 adiados para 4.0.
- **Enums PHP 8.4** para `priority/status` (SDD 8): evita strings mágicas, validação explícita.

## Funcionalidades Entregues (2.0)

- [x] `2.1` Cadastro, edição, listagem e visualização de chamados
- [x] `2.2` Campos: título (255), descrição (text), prioridade (low/medium/high), status (open/in_progress/resolved/closed), responsável (FK users), opened_at (datetime)
- [x] Rotas: `GET / (redirect)`, `GET /tickets`, `GET /tickets/create`, `POST /tickets`, `GET /tickets/{id}`, `GET /tickets/{id}/edit`, `PUT /tickets/{id}`
- [x] Validação via FormRequest + mensagens em PT-BR
- [x] Seeder com 3 responsáveis (João Silva, Maria Souza, Carlos Oliveira) para `assigned_to`
- [x] Frontend Inertia/Vue: `Tickets/Index`, `Create`, `Show`, `Edit` + `Layouts/AppLayout`
- [x] Paginação 10/it
- [x] Testes automatizados (ver abaixo)

**Fora do escopo v1 (SDD 3, adiado):** distribuição automática, filtros/busca, dashboard, notificações, anexos, SLA.

## Pré-requisitos

- Docker e Docker Compose v2
- Git
- (Opcional sem Docker) PHP 8.4+, Composer 2, Node 20+, MySQL 8

## Instalação e Execução com Docker (Recomendado)

```bash
git clone <repo> && cd Sistema-de-Controle-de-Chamados-Internos
cp .env.example .env

# Sobe app (porta 8000) + db (3306) + roda migrations automaticamente
docker compose up --build -d

# Acompanhe logs (opcional)
docker compose logs -f app

# Instale dependências se precisar (primeira vez já faz build)
docker compose exec app composer install
docker compose exec app npm install

# Gere key se necessário (entrypoint já gera)
docker compose exec app php artisan key:generate

# Popule os 3 responsáveis
docker compose exec app php artisan migrate:fresh --seed --force
docker compose exec app npm run build
```

Acesse: **http://localhost:8000** -> redireciona para `/tickets`.

### Makefile (atalhos para testar facilmente)

```bash
make up          # docker compose up --build -d
make down        # docker compose down
make logs        # logs do app
make shell       # bash dentro do app
make test        # roda php artisan test --testdox dentro do container
make fresh       # migrate:fresh --seed
make migrate     # migrate --force
make npm-build   # npm run build
```

**Exigência 4 atendida:** `make test` ou `docker compose exec app php artisan test --testdox` roda toda a suíte sem dependência externa (usa SQLite :memory: configurado em `phpunit.xml`). Não precisa de MySQL para testes.

## Execução sem Docker (alternativa)

```bash
composer install
cp .env.example .env
# Ajuste DB_CONNECTION=sqlite ou mysql local
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev    # ou npm run build
php artisan serve
```

## Testes

```bash
# Dentro do Docker (recomendado - fácil, isolado)
docker compose exec app php artisan test --testdox
# ou
make test

# Local
php artisan test --testdox
```

Suíte atual (23 testes, 88 asserções):
- `tests/Unit/TicketModelTest` (4): casts enums, relação assignedUser, factory, labels
- `tests/Feature/TicketCrudTest` (8): list, create form, store válido, store default opened_at, show, edit, update, redirect /
- `tests/Feature/TicketValidationTest` (9): title/description obrigatórios, descrição curta, priority/status inválidos, assigned_to obrigatório/inexistente, title >255, update requer campos
- `tests/Feature/ExampleTest` (1) + `tests/Unit/ExampleTest` (1)

Todos verdes com `withoutVite()` em `tests/TestCase.php:8` para evitar necessidade de manifest em ambiente de teste.

## Estrutura

```
app/Enums/TicketPriority, TicketStatus
app/Models/Ticket (fillable, casts, belongsTo assignedUser, scopeSearch)
app/Http/Requests/StoreTicketRequest, UpdateTicketRequest
app/Http/Controllers/TicketController (index, create, store, show, edit, update)
app/Http/Middleware/HandleInertiaRequests
resources/js/Layouts/AppLayout.vue
resources/js/Pages/Tickets/{Index,Create,Show,Edit}.vue
database/migrations/*_create_tickets_table.php
database/factories/TicketFactory, UserFactory
database/seeders/DatabaseSeeder (3 users)
tests/Feature/TicketCrudTest, TicketValidationTest
tests/Unit/TicketModelTest
Dockerfile (php:8.4-cli + node20 + composer)
docker-compose.yml (app, db mysql:8.0)
Makefile
```

## Modelo de Dados (SDD 7)

`tickets(id, title string 255, description text, priority string, status string, assigned_to FK users.id, opened_at datetime, timestamps)` + index em status/priority/assigned_to.

`users(id, name, email, password, timestamps)` reaproveitada do Laravel.

## Rotas

| Método | Rota | Ação |
|---|---|---|
| GET | / | redirect -> /tickets |
| GET | /tickets | index |
| GET | /tickets/create | create |
| POST | /tickets | store |
| GET | /tickets/{id} | show |
| GET | /tickets/{id}/edit | edit |
| PUT/PATCH | /tickets/{id} | update |

## Próximos Passos (fora desta entrega)

- 3.0/4.0: `TicketAssignmentService` + atribuição automática (menor carga open/in_progress, desempate ID)
- 5.0: filtros por status/prioridade/responsável + busca
- 6.0: Dashboard simples

## Licença

MIT (Laravel).
