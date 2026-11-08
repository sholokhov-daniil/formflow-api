# Workspace, участники и доступ

Статус: текущее поведение (`as-is`). Модуль `Tenancy`. [Общий обзор](overview.md). Вход выполняется по [общей схеме аутентификации](identity.md).

## Создание workspace

```mermaid
flowchart TD
    Actor["Аутентифицированный пользователь"] --> Allowed{"Policy разрешает создание?"}
    Allowed -->|Нет| Forbidden["Отказать в создании"]
    Allowed -->|Да| Validate{"name и kind корректны?"}
    Validate -->|Нет| Invalid["Не создавать workspace"]
    Validate -->|Да| Transaction["Начать транзакцию"]
    Transaction --> Workspace["Создать workspace вида personal или team"]
    Workspace --> Membership["Создать membership для текущего пользователя"]
    Membership --> Success{"Обе записи сохранены?"}
    Success -->|Нет| Rollback["Откатить обе записи"]
    Success -->|Да| Commit["Зафиксировать транзакцию"]
    Commit --> Created["Workspace создан; создатель стал участником"]
```

Поля запроса и HTTP-результаты определены в [OpenAPI](../api/openapi.yaml). Техническая атомарность и обновление кеша относятся к [архитектуре и реализации](../architecture.md).

## Работа с существующими данными

```mermaid
flowchart TD
    Request["Аутентифицированный запрос внутри workspace"] --> Bound{"Ресурсы существуют и вложенность URL верна?"}
    Bound -->|Нет| NotFound["Подходящая вложенная сущность не найдена"]
    Bound -->|Да| Member{"Текущий пользователь состоит в workspace?"}
    Member -->|Нет| Forbidden["Доступ запрещён; данные не меняются"]
    Member -->|Да| Operation{"Операция"}
    Operation -->|Чтение| Read["Вернуть запрошенные данные в границах workspace"]
    Operation -->|Добавить или заменить membership| Validate{"Пользователь существует и не дублируется в workspace?"}
    Validate -->|Нет| Invalid["Membership не меняется"]
    Validate -->|Да| SaveMembership["Сохранить membership в workspace из URL"]
    Operation -->|Удалить membership| DeleteMembership["Удалить указанный membership"]
    Operation -->|Обновить workspace| UpdateWorkspace["Проверить входные данные и сохранить изменения"]
    Operation -->|Удалить workspace| DeleteWorkspace["Удалить workspace и зависимые данные каскадом"]
```

Правила доступа и HTTP-результаты описывает [OpenAPI](../api/openapi.yaml). Кеш и эффекты после commit — технические детали; см. [правила кеша workspace](../../.agents/skills/formflow-tenancy/references/workspace-cache.md).

## Основания

- [Реализация и политики доступа](../../app/Modules/Tenancy/Infrastructure/Policies/WorkspacePolicy.php), [атомарное создание](../../app/Modules/Tenancy/Infrastructure/Repository/WorkspaceRepository.php).
- [Feature-тесты сценария](../../tests/Feature/Modules/Tenancy/Presentation/Http/Controller/WorkspaceControllerTest.php) и [membership](../../tests/Feature/Modules/Tenancy/Presentation/Http/Controller/WorkspaceMembershipControllerTest.php).
