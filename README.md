# FormFlow API

Backend API для управления рабочими пространствами, проектами, формами и отправленными ответами. Приложение построено на Laravel и разделено на модули по областям ответственности.

## Технологии

- PHP 8.5 в Docker runtime Sail; ограничение Composer — PHP `^8.3`.
- Laravel 13.
- PostgreSQL 18 и Redis.
- Pest для тестов.
- OpenAPI 3.1 для описания API.

## Локальный запуск

Нужны Docker Compose, Composer, Node.js и npm. В корне проекта выполните:

```bash
test -f .env || cp .env.example .env
composer install
npm ci
npm run build
```

Если `.env` уже существует, не копируйте поверх него `.env.example`: сохраните текущие локальные настройки и проверьте только необходимые значения.

В `.env` задайте параметры приложения и сервисов Compose. Для стандартных локальных портов можно использовать:

```dotenv
APP_NAME="FormFlow API"
APP_URL=http://localhost:8000
APP_PORT=8000

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=formflow
DB_USERNAME=formflow
DB_PASSWORD=secret

REDIS_HOST=redis
```

`secret` здесь — только пароль для локальной разработки. Для другого окружения задайте собственный пароль и не публикуйте файл `.env`.

Запустите контейнеры, создайте ключ приложения и примените миграции:

```bash
./vendor/bin/sail up -d --build
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

При необходимости создайте локального тестового пользователя:

```bash
./vendor/bin/sail artisan db:seed
```

Сидер создаёт пользователя `test@example.com` с паролем `password`. Используйте эту учётную запись только локально; в production не запускайте сидер без изменения его данных.

При значении `APP_PORT=8000` приложение доступно по адресу `http://localhost:8000`. Остановить контейнеры, сохранив данные в Docker volumes, можно командой:

```bash
./vendor/bin/sail down
```

## API

Базовый префикс API — `/api/v1`. Основные реализованные маршруты для пространств:

| Метод | Путь | Назначение |
| --- | --- | --- |
| `GET` | `/api/v1/workspaces` | Список пространств, участником которых является пользователь |
| `POST` | `/api/v1/workspaces` | Создание пространства и добавление создателя в участники |
| `GET` | `/api/v1/workspaces/{workspace}` | Детальная информация о пространстве |
| `PUT`, `PATCH` | `/api/v1/workspaces/{workspace}` | Обновление пространства |
| `DELETE` | `/api/v1/workspaces/{workspace}` | Удаление пространства и связанных данных согласно каскадным связям БД |

Тип пространства `kind`: `personal` или `team`. Успешные ответы API используют JSON-обёртку `data`.

Все API-маршруты требуют HTTP Basic Authentication: имя пользователя — email, пароль — пароль учётной записи. За пределами локальной разработки передавайте учётные данные только по HTTPS. Доступ к пространству разрешён его участникам. Ошибки аутентификации возвращают `401`, отказ в доступе — `403`, ошибки валидации — `422`. Третья неудачная попытка в пределах трёхчасового окна блокирует пару IP-адреса и Basic username на три часа и возвращает `429`.

Пример получения списка пространств с локальным тестовым пользователем:

```bash
curl --user 'test@example.com:password' \
  --header 'Accept: application/json' \
  http://localhost:8000/api/v1/workspaces
```

Пример создания пространства:

```bash
curl --user 'test@example.com:password' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{"name":"Product team","kind":"team"}' \
  http://localhost:8000/api/v1/workspaces
```

### Документация API

- Swagger UI: [`/doc/api`](http://localhost:8000/doc/api)
- OpenAPI YAML: [`/doc/api/openapi.yaml`](http://localhost:8000/doc/api/openapi.yaml)
- Исходная спецификация: [`docs/api/openapi.yaml`](docs/api/openapi.yaml)

Спецификация сейчас описывает API пространств. Вложенные маршруты проектов зарегистрированы как `/api/v1/workspaces/{workspace}/projects`, но методы `ProjectController` пока являются каркасом и ещё не реализуют операции.

## Тесты и проверки

Запустить тесты внутри Docker:

```bash
./vendor/bin/sail artisan test --compact
```

Запустить один feature-тест:

```bash
./vendor/bin/sail artisan test --compact tests/Feature/Modules/Tenancy/Presentation/Http/Controller/WorkspaceControllerTest.php
```

Посмотреть зарегистрированные маршруты:

```bash
./vendor/bin/sail artisan route:list --path=api/v1
```

## Структура приложения

Основной код расположен в `app/Modules` и организован по модулям и слоям:

- `Identity` — пользователь, Basic Authentication и ограничение неудачных попыток входа.
- `Tenancy` — workspace, участники, политики доступа, HTTP-ресурсы, репозитории и кэширование.
- `FormDefinition` — проекты, формы, поля и типы полей.
- `Submissions` — отправленные формы и значения ответов.

Внутри модулей используются слои `Domain`, `Application`, `Infrastructure` и `Presentation` там, где они нужны. Маршруты API находятся в `routes/api.php`, миграции и фабрики — в `database`, OpenAPI-спецификация — в `docs/api/openapi.yaml`.
