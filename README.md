# Sistema de Controle de Chamados Internos - Codificar

Sistema de chamados internos solicitado pela Codificar como desafio técnico. Monólito modular em Laravel + Inertia.js + Vue, com Docker, seeders e suíte de testes automatizados.

Escopo entregue: cadastro, edição, listagem e visualização de chamados, distribuição automática por menor carga e listagem com busca, filtros e ordenação.

## Stack e Justificativas (PDF 1.2)

| Tecnologia | Uso | Justificativa |
|---|---|---|
| Laravel 13 | Backend | Framework maduro, MVC, validação, ORM, migrations, testes. Recomendado pela Codificar. |
| Inertia.js | Integração | Reduz atrito front/back sem SPA/API separadas. Produtividade para time pequeno (dica PDF). |
| Vue 3 | Frontend | Componentização simples e produtiva. |
| Tailwind CSS 4 | UI | Utilitário para interface funcional rápida, sem reinventar roda. |
| MySQL 8 (Docker) | Banco | Banco relacional em container, realista e fácil de testar em equipe. A suíte de testes roda com banco em memória, sem depender do container. |
| PHPUnit 12 | Testes | Padrão Laravel, cobertura das regras principais. Escolha alternativa a Pest, que não é exigido. |
| Docker + docker-compose | Infra | Requisito da entrega: `use Docker` e `construir de maneira que consigam testar facilmente`. 1 comando sobe app+db, outro roda testes. |

### Referências e recursos externos

O PDF pede que referências e bibliotecas externas sejam registradas aqui. O
projeto usa:

| Recurso | Origem | Como é usado |
|---|---|---|
| [PCOV](https://github.com/krakjoe/pcov) | PECL, compilado no `Dockerfile` | Driver de cobertura. Vem com `pcov.enabled=0` e só é ligado no `make coverage`. |
| [Bunny Fonts](https://bunny.net/fonts/) — Instrument Sans | `fonts.bunny.net` | Baixada em tempo de `npm run build` pelo plugin `fonts` do `laravel-vite-plugin` e emitida como `.woff2` local em `public/build/assets/`. |
| Imagem base `php:8.4-cli`, `mysql:8.0`, Node 20 (NodeSource), `composer:2` | Docker Hub / deb.nodesource.com | Só na construção da imagem. |

⚠️ **O `npm run build` precisa de acesso à internet**, porque a fonte é
baixada do CDN durante o build. Sem rede, o `npm run build` falha e, como
`public/build` não é versionado, a aplicação sobe sem manifest do Vite e toda
página responde 500. Se você estiver offline, apague a linha `bunny(...)` em
`vite.config.js` ou aponte `input` para uma fonte local — o resto do projeto não
depende disso.

Decisões arquiteturais:
- **Monólito modular single-repo**: projeto pequeno, único domínio. Evita custo de separar front/back.
- **Sem autenticação**: o PDF não exige login, e o requisito 3.2 dispensa tela própria de cadastro de responsáveis. Introduzir auth traria middleware, policies e gestão de sessão sem valor entregue no escopo. É a primeira evolução candidata (ver "Próximos Passos").
- **Regra de distribuição isolada em Service**: `TicketAssignmentService` concentra o cálculo de carga, permitindo testar a regra de negócio sem passar por HTTP.
- **Enums PHP** para `priority/status`: evita strings mágicas, centraliza os rótulos em PT-BR e alimenta o `Rule::in()` da validação.
- **Filtros, busca e ordenação na listagem** (PDF 5.2): a especificação deixa a apresentação a critério do candidato; a implementação segue o que a pessoa solicitante precisaria no dia a dia para acompanhar a fila.

## Funcionalidades Entregues

**Cadastro de chamados (PDF 2.0)**
- [x] Cadastro, edição, listagem e visualização de chamados
- [x] Campos: título (255), descrição (text, mín. 10 caracteres), prioridade (low/medium/high), status (open/in_progress/resolved/closed), responsável (FK users), opened_at (datetime)
- [x] Validação via FormRequest + mensagens em PT-BR
- [x] `opened_at` preenchido com `now()` do servidor quando não informado

**Responsáveis (PDF 3.0)**
- [x] Seeder com 3 responsáveis (João Silva, Maria Souza, Carlos Oliveira)
- [x] Select de responsável disponível ao abrir e ao editar
- [x] Sem tela de cadastro própria, conforme o requisito 3.2
- [x] 14 chamados de demonstração, para a listagem nascer com conteúdo e a distribuição automática ter carga diferente entre os responsáveis

> O seeder é o que torna a entrega avaliável em 2 minutos: sem ele a tela
> central do sistema abre vazia. Os 14 chamados têm carga **propositalmente
> desigual** (João 4, Maria 2, Carlos 2 em aberto), porque a regra de
> distribuição só é observável quando alguém está menos sobrecarregado que os
> outros. Os status `resolved` e `closed` aparecem justamente para ilustrar que
> não entram na contagem de carga.

**Distribuição automática (PDF 4.0)**
- [x] `TicketAssignmentService` com a regra de menor carga
- [x] Atribuição manual e automática, com pré-visualização da justificativa
- [x] `GET /tickets/next-assignee` para consultar a sugestão com a contagem de carga

**Listagem e acompanhamento (PDF 5.0)**
- [x] Busca por título e descrição (debounce de 350ms)
- [x] Filtros por prioridade, status, responsável e intervalo de data de abertura
- [x] Ordenação por título, prioridade, status, data de abertura e criação
- [x] Paginação 10/it preservando os filtros ativos na URL

**Infraestrutura e qualidade**
- [x] Docker + docker-compose (app + MySQL 8) e Makefile com atalhos
- [x] Testes automatizados de CRUD, validação, filtros e regra de distribuição

**Fora do escopo** (PDF 3): tela de cadastro de responsáveis, notificações por e-mail/WhatsApp, anexos, comentários, chat, SLA, permissões avançadas e microserviços.

**Fora do escopo por ausência no PDF:** exclusão de chamados. A seção 2.0 lista cadastro, edição, listagem e visualização, sem DELETE, e um chamado resolvido é histórico. A rota de destroy foi retirada em vez de ficar registrada sem controller por trás — `DELETE /tickets/{id}` responde 405, que é o comportamento correto para um método não implementado, e há teste travando isso.

## Decisões e Trade-offs

O PDF orienta: *"Se precisar fazer trade-offs por conta do tempo, documente suas decisões no README."* Esta seção registra as decisões que afetam o comportamento do sistema.

### O que conta como "em aberto"? (requisito 4.3)

O PDF deixa a definição a critério do candidato, exigindo que ela seja explicitada e justificada.

**Apenas `OPEN` e `IN_PROGRESS` entram na contagem de carga** (`TicketStatus::openStatuses()`).

A justificativa está no significado de cada status no modelo:

| Status | Significado | Conta na carga? |
|---|---|---|
| `open` | Registrado, ainda não iniciado | Sim |
| `in_progress` | Suporte trabalhando | Sim |
| `resolved` | Suporte concluiu, aguardando confirmação do solicitante | Não |
| `closed` | Confirmado e encerrado | Não |

`RESOLVED` representa trabalho **concluído** que aguarda apenas a confirmação de quem abriu o chamado; `CLOSED` está totalmente encerrado. Nos dois casos o atendimento terminou, então nenhum dos dois deve influenciar para qual responsável um **novo** chamado será distribuído.

Incluir `IN_PROGRESS` é uma decisão deliberada: o status sugere progresso, mas o chamado representa carga real de trabalho. Excluí-lo faria a distribuição ignorar exatamente os chamados que estão em atendimento.

### Desempate determinístico

A regra completa de ordenação em `TicketAssignmentService::resolve()`:

1. Total de chamados abertos (`open` + `in_progress`) — **crescente**
2. Chamados abertos de prioridade `high` — crescente
3. Chamados abertos de prioridade `medium` — crescente
4. Chamados abertos de prioridade `low` — crescente
5. `id` do responsável — crescente

O total de abertos é o critério principal (requisito 4.1). A prioridade entra como desempate para que, em caso de empate na carga, o responsável com chamados mais graves fique com os próximos. O `id` é o critério final e garante resultado determinístico, como exige o documento de requisitos. A ordem é estável e não depende de `random()`, então o mesmo estado da base sempre produz a mesma decisão.

### Limitação conhecida: a atribuição não é atômica

**Estado atual:** o `GET /tickets/next-assignee` calcula a sugestão em uma transação própria, e o `POST /tickets` registra o `assigned_to` enviado pelo formulário. A gravação **não** ocorre na mesma transação que o cálculo.

Consequência: dois operadores que Abram chamados no mesmo instante podem receber a mesma sugestão, porque ambos leeram a mesma carga. Não há corrupção de dados — o desempate continua determinístico — mas o balanceamento pode ficar levemente otimista em cenários de alta concorrência.

O `lockForUpdate()` presente no service não corrige isso hoje: ele incide sobre a linha de `users` e a transação fecha antes de qualquer escrita em `tickets`, ou seja, o lock é liberado antes de ser útil.

Mitigação em planejamento: mover a resolução para dentro da transação que grava o chamado.

### Por que o servidor decide, e não a tela

A regra de distribuição roda **exclusivamente** no servidor, dentro de `TicketAssignmentService`, e é testável sem passar por HTTP. A tela chama `GET /tickets/next-assignee` apenas para *pré-visualizar* a sugestão acompanhada da justificativa de carga — o endpoint não altera nenhum dado. Isso evita duplicar regra de negócio no front e dá transparência ao usuário sem abrir mão do controle do servidor.


## Pré-requisitos

- Docker e Docker Compose v2
- Git
- **Acesso à internet** — a imagem baixa a fonte Instrument Sans do CDN no `npm run build` (ver "Referências e recursos externos")
- (Opcional sem Docker) PHP 8.3+, Composer 2, Node 20+, MySQL 8

## Instalação e Execução com Docker (Recomendado)

```bash
git clone <repo> && cd Sistema-de-Controle-de-Chamados-Internos
cp .env.example .env

# Sobe app (porta 8000) + db (porta 3308), roda as migrations e popula os
# dados de demonstração na primeira execução
docker compose up --build -d

# Acompanhe logs (opcional)
docker compose logs -f app

# Instale dependências se precisar (primeira vez já faz build)
docker compose exec app composer install
docker compose exec app npm install

# Gere key se necessário (entrypoint já gera)
docker compose exec app php artisan key:generate

# Só se quiser recomeçar do zero
docker compose exec app php artisan migrate:fresh --seed --force
docker compose exec app npm run build
```

Acesse: **http://localhost:8000** -> redireciona para `/tickets`.

**Você não precisa rodar `db:seed` na mão.** O entrypoint do container chama
`php artisan chamados:seed-if-empty` logo depois das migrations, que semeia os
3 responsáveis e os 14 chamados **apenas se a tabela `users` estiver vazia**.
Isso existe por dois motivos: o requisito 3.4 pede "pelo menos 3 responsáveis
disponíveis na aplicação", e como `assigned_to` é obrigatório, um clone sem
seed não permitiria abrir nem o primeiro chamado. A guarda de banco vazio
mantém o comando idempotente, então `docker compose restart` não duplica nada.

Para zerar a base e voltar ao estado inicial a qualquer momento:
`make fresh`.

### Makefile (atalhos para testar facilmente)

```bash
make up          # docker compose up --build -d
make down        # docker compose down
make logs        # logs do app
make shell       # bash dentro do app
make test        # roda php artisan test --testdox dentro do container
make coverage    # mede a cobertura de app/ (liga o PCOV só para a medição)
make coverage-min# roda a suíte e falha se a cobertura cair abaixo de 100%
make coverage-html # relatório navegável em build/coverage/index.html
make fresh       # migrate:fresh --seed
make migrate     # migrate --force
make npm-build   # npm run build
```

**Exigência 4 atendida:** `make test` ou `docker compose exec app php artisan test --testdox` roda toda a suíte sem depender do container de banco (usa banco em memória configurado em `phpunit.xml`).

### CI

`.github/workflows/ci.yml` roda em todo push e pull request para `main`, com
três jobs independentes — qualquer um deles reprova o PR:

| Job | O que roda | Por quê |
|---|---|---|
| `backend` | `php artisan test --coverage --min=100` | Mesmo gate do `make coverage-min` local. SQLite em memória, sem serviço de banco. |
| `frontend` | `npm run build` | Não há teste de template no projeto, então o build é o que garante que componentes e páginas Inertia continuam compilando. |
| `style` | `./vendor/bin/pint --test` | Estilo verificado, não corrigido: o job falha em vez de reescrever o código de quem commitou. |

## Execução sem Docker (alternativa)

```bash
composer install
cp .env.example .env
# Ajuste as credenciais do banco no .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev    # ou npm run build
php artisan serve
```

## Testes

A suíte roda com **SQLite em memória** (`phpunit.xml`), ou seja, não depende do container de banco nem de dados seedados.

### Pré-requisito

O container do app precisa estar de pé:

```bash
docker compose ps        # app deve estar "running"
make up                  # se não estiver, sobe app + db
```

### Suíte completa

```bash
make test                # equivalente a docker compose exec app php artisan test --testdox
# ou
docker compose exec app php artisan test --testdox
```

Com `--testdox` o output mostra um teste por linha (formato de narrativa); sem ele, `php artisan test` imprime o ponto puro por teste.

### Um arquivo de teste

```bash
docker compose exec app php artisan test tests/Feature/TicketAssignmentServiceTest.php
docker compose exec app php artisan test tests/Unit/TicketEnumTest.php
```

### Um teste específico

O `--filter` aceita o nome do método ou um trecho dele:

```bash
docker compose exec app php artisan test --filter=test_atribui_ao_responsavel_com_menos_chamados
docker compose exec app php artisan test --filter=TicketFilterTest
```

### Cobertura de código: 100% de `app/`

O driver de cobertura é o [PCOV](https://github.com/krakjoe/pcov), compilado na imagem do Docker. Ele fica com `pcov.enabled=0` para que `make test` e as requisições web não paguem a instrumentação; o target `coverage` o reativa via `PHP_INI_SCAN_DIR`.

```bash
make coverage      # arquivo por arquivo, sem relatório em disco
make coverage-min  # mesmo, mas sai com status de erro se ficar abaixo de 100%
make coverage-html # relatório HTML em build/coverage/index.html + clover.xml
```

O denominador é `app/`, definido em `phpunit.xml:15-19`. O estado atual é **100,0%** nos 12 arquivos de `app/`, e `make coverage-min` funciona como gate de regressão.

Suíte atual (108 testes, 416 asserções):

| Arquivo | Testes | O que fixa |
|---|---|---|
| `tests/Unit/TicketEnumTest` | 12 | rótulos PT-BR (data providers), `values()`, `openStatuses()` (a definição de "em aberto"), `options()` consumida pelo formulário |
| `tests/Feature/TicketAssignmentServiceTest` | 9 | regra de distribuição: menor carga, desempate por `high`/`medium`/`low`/`id`, exceção sem responsáveis, `preview()` nos dois caminhos |
| `tests/Feature/TicketAssignmentEndpointTest` | 3 | `GET /tickets/next-assignee`: 200 com justificativa, 422 sem responsáveis, e o endpoint não altera dados |
| `tests/Feature/TicketFilterTest` | 16 | busca em título e descrição, filtros de prioridade/status/responsável, intervalo de datas, valores desconhecidos ignorados, datas fora do formato `Y-m-d` descartadas sem 500 |
| `tests/Feature/TicketSortingTest` | 8 | ordenação por cada coluna da whitelist (data provider), ordem semântica de prioridade via `CASE`, padrões e paginação com query string |
| `tests/Feature/TicketScopeTest` | 11 | os 6 scopes do `Ticket`, incluindo os ramos de "filtro ausente" |
| `tests/Feature/TicketShowTest` | 6 | contrato da tela de detalhe, `withDefault` de "Sem responsável", 404, telas de cadastro e edição |
| `tests/Feature/UserModelTest` | 7 | relação `tickets()`, casts (`hashed`, datetime), atributos ocultos e preenchíveis |
| `tests/Feature/HandleInertiaRequestsTest` | 5 | props `flash.success`/`flash.error` e mensagens de validação em PT-BR |
| `tests/Feature/DatabaseSeederTest` | 4 | os 3 responsáveis documentados no README, os 14 chamados de exemplo, as datas passadas e a carga desigual entre eles |
| `tests/Feature/SeedDemoDataCommandTest` | 4 | `chamados:seed-if-empty` semeia base vazia, não duplica quando já há responsáveis, é idempotente em execuções repetidas e falha sem a tabela `users` |
| `tests/Unit/TicketModelTest` | 4 | casts enums, relação `assignedUser`, factory, labels |
| `tests/Feature/TicketCrudTest` | 8 | list, create form, store válido, store default `opened_at`, show, edit, update, redirect `/` |
| `tests/Feature/TicketValidationTest` | 11 | title/description obrigatórios, descrição curta (no create e no update), priority/status inválidos, `assigned_to` obrigatório/inexistente, title >255, update requer campos, `DELETE /tickets/{id}` inexistente devolve 405 |

Todos verdes com `withoutVite()` em `tests/TestCase.php:12` para evitar necessidade do manifest de assets em ambiente de teste.

**Dívida técnica conhecida:** o ramo `: null` de `app/Http/Controllers/TicketController.php:116` é inalcançável, porque `Ticket::assignedUser()` usa `withDefault()` e portanto nunca devolve `null`. Não afeta a cobertura de linhas, mas impede 100% de *branches*.

## Estrutura

```
app/Enums/TicketPriority, TicketStatus
app/Models/Ticket (fillable, casts, belongsTo assignedUser, scopes de filtro e busca)
app/Models/User
app/Services/TicketAssignmentService (regra de distribuição automática)
app/Http/Requests/StoreTicketRequest, UpdateTicketRequest
app/Http/Controllers/TicketController (index, create, store, show, edit, update, nextAssignee)
app/Http/Middleware/HandleInertiaRequests
app/Console/Commands/SeedDemoData (chamados:seed-if-empty, roda no boot do container)
resources/js/Layouts/AppLayout.vue
resources/js/Components/TicketForm.vue (corpo do form, compartilhado por Create e Edit)
resources/js/Components/AutoAssignButton.vue (pré-visualização da distribuição automática)
resources/js/Pages/Tickets/{Index,Create,Show,Edit}.vue
database/migrations/*_create_tickets_table.php
database/factories/TicketFactory, UserFactory
database/seeders/DatabaseSeeder (3 responsáveis + 14 chamados de exemplo)
tests/Feature/TicketCrudTest, TicketValidationTest
tests/Unit/TicketModelTest
Dockerfile (php:8.4-cli + node20 + composer)
docker-compose.yml (app, db mysql:8.0, test)
Makefile
.github/workflows/ci.yml (testes + cobertura, build do front, Pint)
Docs/ (PDF do desafio - ignorado no git por ser sigiloso)
```

### Componentes Vue

`Create.vue` e `Edit.vue` são irmão de ~45 linhas cada: valores iniciais do
`useForm`, destino do submit e rótulo do botão. Todo o corpo do formulário
(campos, validação, select de responsável) está em `Components/TicketForm.vue`,
e as diferenças reais entre as duas telas viraram props (`assign-placeholder`
só no cadastro, `opened-at-required` só na edição). A consequência prática é
que um ajuste no formulário é uma edição, não duas — o Vite ainda extrai
`TicketForm` como um chunk compartilhado entre as duas rotas.

A mesma disciplina vale para os rótulos: `Index.vue` não mantém uma cópia de
`"Alta"` / `"Em andamento"` em JavaScript. Os enums PHP expõem `options()` e o
controller envia as props `priorities` e `statuses`, de modo que o texto
exibido tem uma única origem e não pode divergir entre back e front.

## Modelo de Dados

`tickets(id, title string 255, description text, priority string, status string, assigned_to FK users.id nullOnDelete, opened_at datetime, timestamps)` + índice composto em `[status, priority, assigned_to]`.

`assigned_to` é nullable no banco para permitir a remoção de um responsável sem perder o histórico do chamado; `Ticket::assignedUser()` usa `withDefault` para exibir "Sem responsável" nesses casos. A camada HTTP continua exigindo o campo, conforme o requisito 2.2.

`users(id, name, email, password, timestamps)` reaproveitada do Laravel.

## Rotas

| Método | Rota | Ação |
|---|---|---|
| GET | / | redirect -> /tickets |
| GET | /tickets | index (listagem com busca, filtros e ordenação) |
| GET | /tickets/create | create |
| POST | /tickets | store |
| GET | /tickets/{id} | show |
| GET | /tickets/{id}/edit | edit |
| PUT/PATCH | /tickets/{id} | update |
| GET | /tickets/next-assignee | sugere o responsável com menor carga (JSON) |

## Próximos Passos (evoluções futuras)

- **Autenticação e autorização** por perfil — primeira evolução candidata; requer `TicketPolicy` e middleware de rota
- **Histórico de alterações** do chamado (quem mudou o quê e quando)
- **Categorias/departamentos** para além dos três responsáveis do seeder
- **Busca full-text** quando o volume justificar (`LIKE %termo%` não usa índice)
- **SLA e métricas de atendimento**

## Licença

MIT (Laravel).
