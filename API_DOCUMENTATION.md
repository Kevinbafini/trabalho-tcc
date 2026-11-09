# API REST — Sistema de Controle de Atividades Agrícolas

Documentação da **API REST v1** do sistema. A API permite que aplicações externas (um app de celular, uma planilha, outro sistema ou o Postman) consultem e gerenciem os dados agrícolas por requisições HTTP, recebendo respostas em JSON.

A API **faz parte do próprio projeto Laravel**. Ela usa o mesmo banco MySQL, os mesmos models Eloquent, as mesmas validações e os mesmos cálculos de custo da aplicação web. Não é um sistema separado.

## Sumário

1. [Visão geral](#1-visão-geral)
2. [Como uma requisição é processada](#2-como-uma-requisição-é-processada)
3. [Preparação (uma única vez)](#3-preparação-uma-única-vez)
4. [Autenticação: como gerar e usar o token](#4-autenticação-como-gerar-e-usar-o-token)
5. [Endpoints implementados](#5-endpoints-implementados)
6. [Atividades agrícolas (CRUD)](#6-atividades-agrícolas-crud)
7. [Propriedades e talhões](#7-propriedades-e-talhões)
8. [Recursos: insumos, máquinas, funcionários e implementos](#8-recursos-insumos-máquinas-funcionários-e-implementos)
9. [Relatório de custos](#9-relatório-de-custos)
10. [Paginação, busca e filtros](#10-paginação-busca-e-filtros)
11. [Códigos de status HTTP e formato dos erros](#11-códigos-de-status-http-e-formato-dos-erros)
12. [Segurança e permissões](#12-segurança-e-permissões)
13. [Testes automatizados](#13-testes-automatizados)
14. [Postman: collection e roteiro de demonstração](#14-postman-collection-e-roteiro-de-demonstração)
15. [Arquivos da API no projeto](#15-arquivos-da-api-no-projeto)

---

## 1. Visão geral

| Item | Valor |
| --- | --- |
| Endereço base (local, Laravel Herd) | `https://projeto-tcc.test/api/v1` |
| Endereço base (servidor) | `{APP_URL}/api/v1` |
| Formato | JSON (requisição e resposta) |
| Versão | `v1`, no próprio endereço (`/api/v1/...`) |
| Autenticação | Token Bearer (token pessoal do Laravel Passport) |
| Idioma das mensagens | Português |

Cabeçalhos usados nas requisições:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

O `Content-Type` só é necessário quando a requisição tem corpo (POST e PUT). O `Authorization` é necessário em todas as rotas, menos no login.

**Por que versionar (`/v1`)?** Se um dia o formato das respostas mudar, uma `v2` pode ser criada sem quebrar as aplicações que já usam a `v1`.

---

## 2. Como uma requisição é processada

Exemplo: `POST /api/v1/atividades` (cadastrar uma atividade).

```text
 Aplicação externa (Postman, app, outro sistema)
        │  POST /api/v1/atividades
        │  Authorization: Bearer eyJ0eXAi...
        │  { "talhao_id": 1, "tipo": "adubacao", ... }
        ▼
 ┌──────────────────────────────────────────────────────────────┐
 │ routes/api.php            qual controller atende o endereço  │
 ├──────────────────────────────────────────────────────────────┤
 │ Middlewares (proteções, em ordem)                             │
 │   auth:api      o token é válido?             não → 401      │
 │   throttle:api  passou de 60 req/minuto?      sim → 429      │
 │   CheckToken    o token tem o escopo?         não → 403      │
 │   bindings      a atividade da URL é sua?     não → 404      │
 ├──────────────────────────────────────────────────────────────┤
 │ Form Request              os dados são válidos?   não → 422  │
 │   AtividadeAgricolaRequest (o mesmo da tela de lançamento)   │
 ├──────────────────────────────────────────────────────────────┤
 │ Controller                coordena a operação                │
 │   Api\V1\AtividadeAgricolaController@store                   │
 ├──────────────────────────────────────────────────────────────┤
 │ Service + Model (Eloquent)    regra de negócio e banco MySQL │
 │   CustoAtividadeService calcula os custos e grava tudo       │
 │   numa transação                                             │
 ├──────────────────────────────────────────────────────────────┤
 │ API Resource              formata o JSON de saída            │
 │   AtividadeAgricolaResource                                  │
 └──────────────────────────────────────────────────────────────┘
        │
        ▼
 201 Created + Location: /api/v1/atividades/15
 { "data": { "id": 15, "custo_total": 1335.5, ... } }
```

| Camada | Papel | Onde está |
| --- | --- | --- |
| Rotas | Ligam método HTTP + endereço a um controller | `routes/api.php` |
| Middlewares | Autenticação, limite de requisições e escopos | `routes/api.php`, `bootstrap/app.php` |
| Form Requests | Validação dos dados recebidos | `app/Http/Requests/` |
| Controllers | Recebem a requisição e devolvem a resposta | `app/Http/Controllers/Api/V1/` |
| Services | Regras de negócio (cálculo de custos, indicadores) | `app/Services/` |
| Models | Acesso ao banco com Eloquent | `app/Models/` |
| API Resources | Formato padronizado do JSON | `app/Http/Resources/` |
| Tratamento de erros | Erros sempre em JSON e em português | `bootstrap/app.php` |

**Reaproveitamento:** a API não duplica regras. Ela usa as mesmas classes da aplicação web:

| Regra | Classe reaproveitada |
| --- | --- |
| Validação do lançamento de atividade | `AtividadeAgricolaRequest` |
| Cálculo e gravação dos custos (snapshot) | `CustoAtividadeService` |
| Filtros dos relatórios | `FiltroCustosRequest` |
| Totais, custo por hectare, custos por talhão/mês/operação/cultura | `IndicadoresCustoService` |
| Isolamento entre usuários | `PertenceAPropriedade` (scope `doUsuario` e route model binding) |

---

## 3. Preparação (uma única vez)

A API usa o **Laravel Passport**, que já estava instalado no projeto para o servidor MCP. Antes do primeiro login, o Passport precisa de:

```bash
# 1. Chaves que assinam os tokens (pule se storage/oauth-private.key já existir)
php artisan passport:keys

# 2. Cliente de "tokens pessoais", usado pelo login da API
php artisan passport:client --personal --name="API REST" --provider=users --no-interaction
```

Nenhuma migration nova é necessária: as tabelas `oauth_*` já existem.

> Sem o passo 2, o login responde erro 500 ("Personal access client not found"). No servidor de produção, os dois comandos também precisam ser executados uma vez.

---

## 4. Autenticação: como gerar e usar o token

A API não usa sessão nem cookies. Cada requisição se identifica sozinha pelo token (API **stateless**).

### 4.1 Gerar o token — `POST /api/v1/login`

O login usa o **mesmo e-mail e a mesma senha** do sistema web.

| Campo | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `email` | texto | sim | E-mail da conta |
| `password` | texto | sim | Senha da conta |
| `nome_dispositivo` | texto | não | Nome que identifica o token (padrão: `API REST`) |
| `escopos` | lista | não | `["api:leitura"]`, `["api:escrita"]` ou os dois (padrão: os dois) |
| `codigo_2fa` | texto | só com 2FA | Código de 6 dígitos do app autenticador, se a conta usar autenticação em duas etapas |

Requisição:

```http
POST /api/v1/login
Accept: application/json
Content-Type: application/json

{
  "email": "admin@admin.com",
  "password": "password",
  "nome_dispositivo": "Postman"
}
```

Resposta `200 OK`:

```json
{
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOi...",
    "tipo": "Bearer",
    "expira_em": "2026-11-16T08:56:54-03:00",
    "escopos": ["api:leitura", "api:escrita"],
    "usuario": {
      "id": 1,
      "nome": "admin user",
      "email": "admin@admin.com"
    }
  }
}
```

E-mail ou senha incorretos (`422`):

```json
{
  "message": "Os dados enviados são inválidos.",
  "errors": {
    "email": ["E-mail ou senha incorretos."]
  }
}
```

### 4.2 Usar o token

Envie o token no cabeçalho `Authorization` de todas as requisições:

```bash
curl https://projeto-tcc.test/api/v1/usuario \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
```

```json
{ "data": { "id": 1, "nome": "admin user", "email": "admin@admin.com" } }
```

### 4.3 Escopos (permissões do token)

Os escopos separam **autenticação** (quem é você) de **autorização** (o que este token pode fazer).

| Escopo | Permite | Sem o escopo |
| --- | --- | --- |
| `api:leitura` | Todas as consultas (GET) | `403 Forbidden` |
| `api:escrita` | Cadastrar, alterar e excluir atividades (POST, PUT, DELETE) | `403 Forbidden` |

Exemplo: um token criado com `"escopos": ["api:leitura"]` lista atividades, mas ao tentar cadastrar recebe:

```json
{ "message": "Este token não tem permissão para esta operação. Escopo necessário: api:escrita." }
```

### 4.4 Validade e logout

- O token vale **7 dias**. Depois disso, basta fazer login de novo.
- `POST /api/v1/logout` revoga o token usado na requisição (`204 No Content`). A partir daí, ele responde `401`.

### 4.5 Contas com autenticação em duas etapas (2FA)

Se a conta tiver o 2FA ligado (Configurações → Segurança), o login exige também o `codigo_2fa`, do mesmo app autenticador usado no login web. Assim, a API não vira um caminho para contornar essa proteção.

```json
{
  "message": "Os dados enviados são inválidos.",
  "errors": {
    "codigo_2fa": ["Esta conta usa autenticação em duas etapas. Informe o código de 6 dígitos do aplicativo autenticador."]
  }
}
```

### 4.6 Limites de requisições

| Rota | Limite | Ao passar do limite |
| --- | --- | --- |
| `POST /login` | 5 tentativas por minuto por e-mail e IP (o mesmo do login web) | `429` |
| Demais rotas | 60 requisições por minuto por usuário | `429` |

---

## 5. Endpoints implementados

| Método | Endereço | Escopo | Descrição | Sucesso |
| --- | --- | --- | --- | --- |
| POST | `/api/v1/login` | — (público) | Gera o token de acesso | 200 |
| POST | `/api/v1/logout` | qualquer token | Revoga o token atual | 204 |
| GET | `/api/v1/usuario` | `api:leitura` | Usuário dono do token | 200 |
| GET | `/api/v1/propriedades` | `api:leitura` | Lista as propriedades | 200 |
| GET | `/api/v1/propriedades/{id}` | `api:leitura` | Consulta uma propriedade (com resumo de custos) | 200 |
| GET | `/api/v1/talhoes` | `api:leitura` | Lista os talhões | 200 |
| GET | `/api/v1/talhoes/{id}` | `api:leitura` | Consulta um talhão (com custos e custo/ha) | 200 |
| GET | `/api/v1/insumos` | `api:leitura` | Lista os insumos | 200 |
| GET | `/api/v1/maquinas` | `api:leitura` | Lista as máquinas | 200 |
| GET | `/api/v1/funcionarios` | `api:leitura` | Lista os funcionários | 200 |
| GET | `/api/v1/implementos` | `api:leitura` | Lista os implementos | 200 |
| GET | `/api/v1/atividades` | `api:leitura` | Lista as atividades | 200 |
| GET | `/api/v1/atividades/{id}` | `api:leitura` | Consulta uma atividade (com itens e custos) | 200 |
| POST | `/api/v1/atividades` | `api:escrita` | Cadastra uma atividade | 201 |
| PUT | `/api/v1/atividades/{id}` | `api:escrita` | Atualiza uma atividade | 200 |
| DELETE | `/api/v1/atividades/{id}` | `api:escrita` | Exclui uma atividade | 204 |
| GET | `/api/v1/relatorios/custos` | `api:leitura` | Relatório de custos agrícolas | 200 |

Para conferir as rotas no terminal:

```bash
php artisan route:list --path=api
```

**Por que só as atividades têm CRUD completo?** É o foco do trabalho. Propriedades, talhões e recursos continuam sendo cadastrados pela aplicação web, e a API os expõe para consulta. Os recursos (insumos, máquinas, funcionários, implementos) estão na API porque o cliente precisa dos IDs deles para cadastrar uma atividade.

---

## 6. Atividades agrícolas (CRUD)

### 6.1 Listar — `GET /api/v1/atividades`

Ordem: das mais recentes para as mais antigas.

| Parâmetro (query string) | Descrição |
| --- | --- |
| `propriedade_id` | Só as atividades da propriedade |
| `talhao_id` | Só as atividades do talhão |
| `tipo` | Tipo de operação (valores na seção 6.3) |
| `busca` | Procura na descrição, na safra, no nome do talhão e no nome do tipo (`pulveriz` encontra Pulverização) |
| `por_pagina` | Itens por página, de 1 a 100 (padrão: 15) |
| `page` | Número da página |

```http
GET /api/v1/atividades?talhao_id=1&tipo=adubacao&por_pagina=15
```

```json
{
  "data": [
    {
      "id": 15,
      "propriedade_id": 1,
      "talhao_id": 1,
      "propriedade": { "id": 1, "nome": "Fazenda Santa Helena" },
      "talhao": { "id": 1, "nome": "Talhão 01", "cultura": "Soja", "area": 12.5 },
      "data": "2026-03-10",
      "tipo": "adubacao",
      "tipo_rotulo": "Adubação",
      "safra": "2025/2026",
      "descricao": "Adubação de cobertura",
      "custo_insumos": 800,
      "custo_maquinas": 450,
      "custo_mao_obra": 85.5,
      "custo_total": 1335.5,
      "created_at": "2026-03-10T09:15:00-03:00",
      "updated_at": "2026-03-10T09:15:00-03:00"
    }
  ],
  "links": { "first": "...?page=1", "last": "...?page=1", "prev": null, "next": null },
  "meta": { "current_page": 1, "from": 1, "last_page": 1, "per_page": 15, "to": 1, "total": 1, "path": "...", "links": [] }
}
```

### 6.2 Consultar — `GET /api/v1/atividades/{id}`

Traz também os itens da atividade, com os **valores gravados no lançamento**.

```json
{
  "data": {
    "id": 15,
    "propriedade_id": 1,
    "talhao_id": 1,
    "propriedade": { "id": 1, "nome": "Fazenda Santa Helena" },
    "talhao": { "id": 1, "nome": "Talhão 01", "cultura": "Soja", "area": 12.5 },
    "data": "2026-03-10",
    "tipo": "adubacao",
    "tipo_rotulo": "Adubação",
    "safra": "2025/2026",
    "descricao": "Adubação de cobertura",
    "custo_insumos": 800,
    "custo_maquinas": 450,
    "custo_mao_obra": 85.5,
    "custo_total": 1335.5,
    "insumos": [
      { "insumo_id": 3, "nome": "Ureia", "unidade": "kg", "quantidade": 250, "custo_unitario": 3.2, "custo_total": 800 }
    ],
    "maquinas": [
      { "maquina_id": 2, "nome": "Trator 01", "horas": 2.5, "custo_hora": 180, "custo_total": 450 }
    ],
    "funcionarios": [
      { "funcionario_id": 4, "nome": "João Silva", "funcao": "Operador", "horas": 3, "custo_hora": 28.5, "custo_total": 85.5 }
    ],
    "implementos": [
      { "implemento_id": 1, "nome": "Grade aradora" }
    ],
    "created_at": "2026-03-10T09:15:00-03:00",
    "updated_at": "2026-03-10T09:15:00-03:00"
  }
}
```

> **Regra do snapshot:** o custo unitário/hora de cada recurso é gravado no momento do lançamento. Se o preço da ureia mudar depois, esta atividade continua mostrando R$ 3,20/kg. Essa regra vem do `CustoAtividadeService` e vale igualmente para a web e para a API.

### 6.3 Cadastrar — `POST /api/v1/atividades`

| Campo | Tipo | Obrigatório | Regras |
| --- | --- | --- | --- |
| `propriedade_id` | inteiro | sim | Precisa ser uma propriedade sua |
| `talhao_id` | inteiro | sim | Precisa ser um talhão da propriedade |
| `data` | data `AAAA-MM-DD` | sim | Não pode ser futura |
| `tipo` | texto | sim | `preparo_solo`, `plantio`, `adubacao`, `pulverizacao`, `irrigacao`, `tratos_culturais`, `colheita` ou `outra` |
| `descricao` | texto | não | Até 1000 caracteres |
| `insumos` | lista | * | `[{ "insumo_id": 3, "quantidade": 250 }]`, quantidade maior que 0 |
| `maquinas` | lista | * | `[{ "maquina_id": 2, "horas": 2.5 }]`, horas maiores que 0 |
| `funcionarios` | lista | * | `[{ "funcionario_id": 4, "horas": 3 }]`, horas maiores que 0 |
| `implementos` | lista | não | `[1]` (IDs dos implementos, sem custo próprio) |

\* É obrigatório informar **pelo menos um** insumo, uma máquina ou um funcionário. Todos os recursos precisam ser da mesma propriedade da atividade, e cada um só pode aparecer uma vez.

**Os custos não são enviados.** O sistema calcula sempre: custo do insumo = quantidade × custo unitário; custo da máquina e da mão de obra = horas × custo por hora; custo total = soma dos três grupos. Se a requisição trouxer `custo_total`, o campo é ignorado. A safra também é copiada do talhão.

```http
POST /api/v1/atividades
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json

{
  "propriedade_id": 1,
  "talhao_id": 1,
  "data": "2026-03-10",
  "tipo": "adubacao",
  "descricao": "Adubação de cobertura",
  "insumos": [{ "insumo_id": 3, "quantidade": 250 }],
  "maquinas": [{ "maquina_id": 2, "horas": 2.5 }],
  "funcionarios": [{ "funcionario_id": 4, "horas": 3 }],
  "implementos": [1]
}
```

Resposta `201 Created`, com o cabeçalho `Location: https://projeto-tcc.test/api/v1/atividades/15` e o mesmo corpo da consulta (6.2).

Conta do exemplo:

| Item | Cálculo | Custo |
| --- | --- | --- |
| Ureia | 250 kg × R$ 3,20 | R$ 800,00 |
| Trator 01 | 2,5 h × R$ 180,00 | R$ 450,00 |
| Operador | 3 h × R$ 28,50 | R$ 85,50 |
| **Total** | | **R$ 1.335,50** |

Erro de validação (`422`), por exemplo com o corpo vazio:

```json
{
  "message": "Os dados enviados são inválidos.",
  "errors": {
    "propriedade_id": ["O campo propriedade é obrigatório."],
    "talhao_id": ["O campo talhão é obrigatório."],
    "data": ["O campo data é obrigatório."],
    "tipo": ["O campo tipo de operação é obrigatório."],
    "recursos": ["Adicione pelo menos um insumo, uma máquina ou um funcionário para calcular o custo da atividade."]
  }
}
```

Recurso de outra propriedade (`422`):

```json
{
  "message": "Os dados enviados são inválidos.",
  "errors": {
    "insumos.0.insumo_id": ["O insumo selecionado não pertence a esta propriedade."]
  }
}
```

### 6.4 Atualizar — `PUT /api/v1/atividades/{id}`

O corpo é o mesmo do cadastro, **completo** (como no formulário de edição da web). O `PATCH` também é aceito e se comporta igual. A propriedade da atividade não pode ser trocada: o campo `propriedade_id` é ignorado.

Na edição, os itens que já pertenciam à atividade mantêm o valor gravado no lançamento original (mesmo que a quantidade ou as horas mudem). Os itens novos usam o valor atual do cadastro.

```http
PUT /api/v1/atividades/15
Content-Type: application/json

{
  "talhao_id": 1,
  "data": "2026-03-10",
  "tipo": "adubacao",
  "descricao": "Adubação corrigida",
  "insumos": [{ "insumo_id": 3, "quantidade": 250 }],
  "maquinas": [{ "maquina_id": 2, "horas": 4 }],
  "funcionarios": [{ "funcionario_id": 4, "horas": 3 }],
  "implementos": [1]
}
```

Resposta `200 OK` com a atividade atualizada. Neste exemplo, `custo_maquinas` passa a `720` (4 h × R$ 180,00) e `custo_total` a `1605.5`.

### 6.5 Excluir — `DELETE /api/v1/atividades/{id}`

Resposta `204 No Content`, sem corpo. Os vínculos da atividade com insumos, máquinas, funcionários e implementos são removidos junto, em cascata.

---

## 7. Propriedades e talhões

### 7.1 `GET /api/v1/propriedades`

Parâmetros: `busca` (nome, proprietário ou município), `por_pagina`, `page`. Ordem alfabética.

```json
{
  "data": [
    {
      "id": 1,
      "nome": "Fazenda Santa Helena",
      "proprietario": "Maria Oliveira",
      "municipio": "Bebedouro",
      "uf": "SP",
      "area_total": 50,
      "observacoes": null,
      "demonstracao": false,
      "quantidade_talhoes": 5,
      "created_at": "2026-10-05T10:00:00-03:00",
      "updated_at": "2026-10-05T10:00:00-03:00"
    }
  ],
  "links": { "...": "..." },
  "meta": { "...": "..." }
}
```

### 7.2 `GET /api/v1/propriedades/{id}`

A propriedade (em `data`) e o resumo dos custos das atividades dela (em `custos`):

```json
{
  "data": { "id": 1, "nome": "Fazenda Santa Helena", "area_total": 50, "quantidade_talhoes": 5, "...": "..." },
  "custos": {
    "quantidade_atividades": 1,
    "custo_insumos": 800,
    "custo_maquinas": 450,
    "custo_mao_obra": 85.5,
    "custo_total": 1335.5,
    "area": 12.5,
    "custo_por_hectare": 106.84
  }
}
```

`area` é a soma das áreas dos talhões que receberam as atividades. `custo_por_hectare` = custo total ÷ área (fica `null` quando não há área).

### 7.3 `GET /api/v1/talhoes`

Parâmetros: `propriedade_id`, `busca` (nome, cultura ou safra), `por_pagina`, `page`.

```json
{
  "data": [
    {
      "id": 1,
      "propriedade_id": 1,
      "propriedade": { "id": 1, "nome": "Fazenda Santa Helena" },
      "nome": "Talhão 01",
      "area": 12.5,
      "cultura": "Soja",
      "safra": "2025/2026",
      "status": "em_cultivo",
      "status_rotulo": "Em cultivo",
      "created_at": "2026-10-05T10:05:00-03:00",
      "updated_at": "2026-10-05T10:05:00-03:00"
    }
  ],
  "links": { "...": "..." },
  "meta": { "...": "..." }
}
```

Situações (`status`): `em_cultivo`, `em_pousio`, `inativo`.

### 7.4 `GET /api/v1/talhoes/{id}`

O talhão (em `data`) e os custos acumulados nele (em `custos`, no mesmo formato da seção 7.2). No exemplo: R$ 1.335,50 em 12,5 ha = **R$ 106,84 por hectare**.

---

## 8. Recursos: insumos, máquinas, funcionários e implementos

Somente consulta (o cadastro continua na aplicação web). Todos aceitam `propriedade_id`, `busca`, `por_pagina` e `page`, em ordem alfabética.

| Endereço | Busca em | Campos principais |
| --- | --- | --- |
| `GET /api/v1/insumos` | nome | `categoria`, `unidade`, `valor_unitario`, `quantidade_estoque`, `status` |
| `GET /api/v1/maquinas` | nome, tipo, modelo | `tipo`, `modelo`, `ano`, `custo_hora`, `status` |
| `GET /api/v1/funcionarios` | nome, função | `funcao`, `custo_hora`, `status` |
| `GET /api/v1/implementos` | nome, tipo, modelo | `tipo`, `modelo`, `status` |

Exemplo — `GET /api/v1/insumos?busca=ureia`:

```json
{
  "data": [
    {
      "id": 3,
      "propriedade_id": 1,
      "nome": "Ureia",
      "categoria": "fertilizante",
      "categoria_rotulo": "Fertilizante",
      "unidade": "kg",
      "unidade_rotulo": "Quilograma",
      "valor_unitario": 3.2,
      "quantidade_estoque": 1200,
      "status": "ativo",
      "status_rotulo": "Ativo",
      "created_at": "2026-10-05T10:10:00-03:00",
      "updated_at": "2026-10-05T10:10:00-03:00"
    }
  ],
  "links": { "...": "..." },
  "meta": { "...": "..." }
}
```

---

## 9. Relatório de custos

### `GET /api/v1/relatorios/custos`

Os mesmos números das telas **Custos por talhão** e **Relatórios**, calculados pelo `IndicadoresCustoService`. Os valores saem como números (`1335.5`), e quem consome a API decide como formatar.

| Filtro (opcional) | Descrição |
| --- | --- |
| `propriedade_id` | Precisa ser uma propriedade sua (senão, `422`) |
| `talhao_id` | Precisa ser um talhão seu (senão, `422`) |
| `safra` | Ex.: `2025/2026` |
| `data_inicio` | `AAAA-MM-DD` |
| `data_fim` | `AAAA-MM-DD`, igual ou posterior a `data_inicio` |

```http
GET /api/v1/relatorios/custos?safra=2025/2026
```

```json
{
  "data": {
    "filtros": {
      "propriedade_id": null,
      "talhao_id": null,
      "safra": "2025/2026",
      "data_inicio": null,
      "data_fim": null
    },
    "resumo": {
      "quantidade_atividades": 1,
      "custo_insumos": 800,
      "custo_maquinas": 450,
      "custo_mao_obra": 85.5,
      "custo_total": 1335.5,
      "area": 12.5,
      "custo_por_hectare": 106.84
    },
    "por_talhao": [
      {
        "talhao_id": 1,
        "talhao": "Talhão 01",
        "propriedade_id": 1,
        "propriedade": "Fazenda Santa Helena",
        "cultura": "Soja",
        "area": 12.5,
        "quantidade_atividades": 1,
        "custo_insumos": 800,
        "custo_maquinas": 450,
        "custo_mao_obra": 85.5,
        "custo_total": 1335.5,
        "custo_por_hectare": 106.84
      }
    ],
    "por_operacao": [
      { "tipo": "adubacao", "tipo_rotulo": "Adubação", "quantidade_atividades": 1, "custo_total": 1335.5 }
    ],
    "por_mes": [
      { "mes": "2026-03", "rotulo": "Mar/26", "custo_insumos": 800, "custo_maquinas": 450, "custo_mao_obra": 85.5, "custo_total": 1335.5 }
    ],
    "por_cultura": [
      { "cultura": "Soja", "area": 12.5, "custo_total": 1335.5, "custo_por_hectare": 106.84 }
    ]
  }
}
```

| Bloco | Conteúdo |
| --- | --- |
| `resumo` | Totais por grupo de custo e custo por hectare |
| `por_talhao` | Só os talhões com atividades no filtro |
| `por_operacao` | Custo por tipo de operação, do maior para o menor |
| `por_mes` | Do primeiro ao último mês com atividades (meses sem atividade aparecem com zero) |
| `por_cultura` | Custo por hectare de cada cultura, do maior para o menor |

---

## 10. Paginação, busca e filtros

Todas as listagens são paginadas, no formato padrão do Laravel:

- `data`: os registros da página;
- `links`: endereços da primeira, da última, da anterior e da próxima página;
- `meta`: `current_page`, `last_page`, `per_page`, `total` (total de registros encontrados), entre outros.

```http
GET /api/v1/talhoes?por_pagina=1&page=2
```

```json
{
  "data": [ { "id": 12, "nome": "Quadra 02", "...": "..." } ],
  "links": {
    "first": "https://projeto-tcc.test/api/v1/talhoes?por_pagina=1&page=1",
    "last": "https://projeto-tcc.test/api/v1/talhoes?por_pagina=1&page=8",
    "prev": "https://projeto-tcc.test/api/v1/talhoes?por_pagina=1&page=1",
    "next": "https://projeto-tcc.test/api/v1/talhoes?por_pagina=1&page=3"
  },
  "meta": { "current_page": 2, "from": 2, "last_page": 8, "per_page": 1, "to": 2, "total": 8, "path": "https://projeto-tcc.test/api/v1/talhoes", "links": [ "..." ] }
}
```

Filtros inválidos respondem `422`. Por exemplo, `?tipo=capina&por_pagina=500`:

```json
{
  "message": "Os dados enviados são inválidos.",
  "errors": {
    "tipo": ["O valor selecionado em tipo de operação é inválido."],
    "por_pagina": ["O campo itens por página deve estar entre 1 e 100."]
  }
}
```

---

## 11. Códigos de status HTTP e formato dos erros

| Código | Significado | Quando acontece nesta API |
| --- | --- | --- |
| `200 OK` | Sucesso | Consultas, login e atualização |
| `201 Created` | Registro criado | Cadastro de atividade (com cabeçalho `Location`) |
| `204 No Content` | Sucesso, sem corpo | Exclusão de atividade e logout |
| `401 Unauthorized` | Não autenticado | Sem token, token inválido, expirado ou revogado |
| `403 Forbidden` | Sem permissão | O token não tem o escopo exigido pela rota |
| `404 Not Found` | Não encontrado | Registro inexistente **ou de outro usuário**, ou endereço inexistente |
| `405 Method Not Allowed` | Método não aceito | Ex.: `DELETE /api/v1/talhoes` |
| `422 Unprocessable Content` | Dados inválidos | Falha de validação ou login incorreto |
| `429 Too Many Requests` | Excesso de requisições | Passou do limite (seção 4.6) |
| `500 Internal Server Error` | Erro inesperado | Falha no servidor |

Todo erro volta em JSON, com a chave `message`. A validação (`422`) acrescenta `errors`, com as mensagens de cada campo:

```json
{ "message": "Não autenticado. Envie um token válido no cabeçalho Authorization: Bearer {token}." }
```

```json
{ "message": "Este token não tem permissão para esta operação. Escopo necessário: api:escrita." }
```

```json
{ "message": "Registro não encontrado." }
```

```json
{ "message": "Endereço não encontrado na API." }
```

```json
{ "message": "Método HTTP não permitido neste endereço." }
```

```json
{ "message": "Muitas requisições. Aguarde um momento e tente novamente." }
```

As respostas são JSON mesmo quando o cliente não envia `Accept: application/json` (regra configurada em `bootstrap/app.php` para todos os endereços `api/*`).

---

## 12. Segurança e permissões

1. **Cada usuário só vê os próprios dados.** Todas as consultas partem das propriedades do usuário do token (scope `doUsuario`). Um registro de outro usuário responde **404**, e não 403, para não revelar que ele existe. É o mesmo comportamento da aplicação web.
2. **Escopos.** O token só faz o que foi permitido quando foi criado (`api:leitura` e/ou `api:escrita`). O escopo é conferido antes de qualquer busca no banco.
3. **Custos calculados no servidor.** O cliente nunca envia valores de custo. Se enviar, eles são ignorados.
4. **Validação no servidor.** As mesmas regras da tela de lançamento: recursos da mesma propriedade, data não futura, quantidades positivas e sem itens repetidos.
5. **Mesmo login da web, inclusive o 2FA.** Contas com autenticação em duas etapas precisam do código do app autenticador.
6. **Tokens com prazo.** Valem 7 dias e podem ser revogados no logout.
7. **Limite de requisições.** 5 tentativas de login por minuto e 60 requisições por minuto por usuário.
8. **Separação entre API e MCP.** O sistema também emite tokens OAuth para apps de IA conectados ao servidor MCP (`/mcp`). Esses tokens **não recebem os escopos da API**, mesmo que peçam (`app/Services/EscoposDosTokens.php`). Assim, um app de IA nunca altera nem exclui atividades pela API.
9. **Senhas protegidas.** A senha e os dados do 2FA nunca aparecem nas respostas.

---

## 13. Testes automatizados

Os testes usam **Pest** e um banco SQLite em memória, criado do zero a cada teste. **O banco MySQL de desenvolvimento não é tocado.**

```bash
# Só os testes da API
php artisan test --compact tests/Feature/Api

# Um teste específico
php artisan test --compact --filter="cadastra a atividade"

# Todo o sistema (web, MCP e API)
php artisan test --compact

# Verificações completas do CI: formatação (Pint), análise estática (PHPStan) e testes
composer test
```

| Arquivo | O que cobre |
| --- | --- |
| `tests/Feature/Api/AutenticacaoDaApiTest.php` | Login, validade de 7 dias, senha errada, campos obrigatórios, 2FA, escopos (403), logout, 401 sem token ou com token inválido, token do MCP bloqueado, 404 e 405 em JSON, limite de tentativas (429) |
| `tests/Feature/Api/AtividadesDaApiTest.php` | Listagem paginada, os 4 filtros, filtros inválidos, cadastro (201 + `Location` + custos), custos enviados ignorados, validação (422), recurso de outra propriedade, propriedade de outro usuário, snapshot dos valores, atualização, exclusão (204), isolamento (404) |
| `tests/Feature/Api/ConsultasDaApiTest.php` | Propriedades e talhões (lista, detalhes com custo por hectare, filtro, 404 de outro usuário), recursos (só do usuário, busca) e relatório de custos (valores, filtro de período, filtros inválidos) |

Resultado atual: **55 testes da API** e **266 testes no total**, todos passando.

Os testes usam um par de chaves do Passport próprio, gerado numa pasta temporária (`tests/TestCase.php`). Por isso funcionam no CI sem as chaves reais, que ficam fora do repositório.

---

## 14. Postman: collection e roteiro de demonstração

Arquivo: **`API_REST.postman_collection.json`** (na raiz do projeto).

1. No Postman: **Import** → selecione o arquivo.
2. Na collection, aba **Variables**, confira `base_url`, `email` e `senha`.
3. Execute **"1. Autenticação → Login"**. O token é salvo sozinho na variável `token`, e as demais requisições já o usam.
4. Siga as pastas em ordem. As listagens guardam os IDs (propriedade, talhão, insumo, máquina, funcionário) que o cadastro de atividade usa.

| Variável | Valor inicial | Preenchida por |
| --- | --- | --- |
| `base_url` | `https://projeto-tcc.test/api/v1` | você |
| `email` / `senha` | `admin@admin.com` / `password` | você |
| `token` | — | Login |
| `token_leitura` | — | Login só leitura |
| `propriedade_id`, `talhao_id` | — | Listar propriedades / Listar talhões |
| `insumo_id`, `maquina_id`, `funcionario_id`, `implemento_id` | — | Listagens de recursos |
| `atividade_id` | — | Cadastrar atividade |

Cada requisição tem testes do Postman (aba **Tests**) que conferem o código de status. Com o **Collection Runner**, a collection inteira roda de uma vez, como numa demonstração automática (24 requisições, cerca de 2 segundos). A atividade cadastrada é excluída no final, e os tokens são revogados.

Também é possível rodar pelo terminal, com o newman (o executor de linha de comando do Postman):

```bash
npx newman run API_REST.postman_collection.json --insecure
```

O `--insecure` aceita o certificado local do Laravel Herd. No servidor, ajuste a variável `base_url` com `--env-var "base_url=https://seu-dominio/api/v1"`.

> A collection usa a primeira propriedade do usuário (em ordem alfabética). Para o cadastro funcionar, essa propriedade precisa ter ao menos um talhão, um insumo, uma máquina e um funcionário, como nos dados de demonstração.

### Roteiro sugerido para a apresentação

1. **Sem token → 401**: mostra que a API é protegida.
2. **Login → 200**: o token é gerado, e a resposta mostra o tipo, a validade e os escopos.
3. **Listar propriedades e talhões → 200**: JSON paginado, só com os dados do usuário.
4. **Cadastrar atividade → 201**: o corpo não tem custos, e a resposta traz os custos calculados pelo sistema e o cabeçalho `Location`.
5. **Consultar a atividade → 200**: itens com os valores gravados no lançamento.
6. **Atualizar → 200**: o custo é recalculado.
7. **Relatório de custos → 200**: os mesmos números da tela de Relatórios.
8. **Validação → 422**: mensagens de erro em português, por campo.
9. **Token só leitura tentando cadastrar → 403**: diferença entre autenticação e autorização.
10. **Atividade inexistente → 404**.
11. **Excluir → 204** e **Logout → 204**; depois do logout, o mesmo token recebe **401**.

### Testando com `curl` (alternativa ao Postman)

```bash
# Login: guarda o token numa variável do terminal (precisa do jq)
TOKEN=$(curl -s https://projeto-tcc.test/api/v1/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"admin@admin.com","password":"password"}' | jq -r .data.token)

# Consultar
curl -s https://projeto-tcc.test/api/v1/talhoes \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" | jq

# Logout
curl -s -X POST https://projeto-tcc.test/api/v1/logout \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

---

## 15. Arquivos da API no projeto

```text
routes/api.php                                  rotas /api/v1 e proteções
bootstrap/app.php                               registro das rotas da API e erros em JSON
app/Providers/AppServiceProvider.php            escopos, validade do token e limite de requisições
app/Enums/EscopoDaApi.php                       escopos api:leitura e api:escrita
app/Services/EscoposDosTokens.php               escopos da API só nos tokens pessoais (não nos do MCP)
app/Http/Controllers/Api/V1/
    AutenticacaoController.php                  login, logout e usuário
    AtividadeAgricolaController.php             CRUD de atividades
    PropriedadeController.php                   propriedades
    TalhaoController.php                        talhões
    InsumoController.php                        insumos
    MaquinaController.php                       máquinas
    FuncionarioController.php                   funcionários
    ImplementoController.php                    implementos
    RelatorioDeCustosController.php             relatório de custos
app/Http/Requests/Api/
    LoginRequest.php                            validação e conferência do login (inclui 2FA)
    ListagemRequest.php                         busca, filtro por propriedade e paginação
    ListagemDeAtividadesRequest.php             + filtros por talhão e tipo
app/Http/Resources/                             formato JSON de cada recurso
tests/Feature/Api/                              testes automatizados da API
API_REST.postman_collection.json                collection do Postman
```

Reaproveitados sem alteração: `AtividadeAgricolaRequest`, `FiltroCustosRequest`, `CustoAtividadeService`, `IndicadoresCustoService` e todos os models.
