# Проекты, формы и поля

Статус: текущее поведение (`as-is`). Модуль `FormDefinition`. [Общий обзор](overview.md). Все операции проходят [аутентификацию](identity.md) и [проверку membership и вложенности](tenancy.md).

## Создание определения

```mermaid
flowchart TD
    Access["Вход, вложенность URL и доступ проверены"] --> Entity{"Что создаём?"}
    Entity -->|Проект| Project{"name уникален в workspace, name и description корректны?"}
    Project -->|Да| SaveProject["Создать проект в workspace из URL"]
    Entity -->|Форму| Form{"name, active и необязательные settings корректны?"}
    Form -->|Да| SaveForm["Создать форму в проекте из URL; created_by взять из текущего пользователя"]
    Entity -->|Поле| Field{"code уникален в форме, тип и остальные свойства корректны?"}
    Field -->|Да| SaveField["Создать поле в форме из URL"]
    Project -->|Нет| Invalid["Проект не создаётся"]
    Form -->|Нет| Invalid
    Field -->|Нет| Invalid
    SaveProject --> Created["Проект создан"]
    SaveForm --> Created
    SaveField --> Created
```

Точные JSON-поля, допустимые значения, вложенность ресурсов и HTTP-результаты описаны в [OpenAPI](../api/openapi.yaml). Технические границы модулей и модели — в [архитектурной спецификации](../architecture.md).

## Публичная форма и версии определений (R02 и R05 реализованы)

```mermaid
flowchart TD
    Member["Участник workspace"] --> Publish{"Выпустить или перевыпустить токен?"}
    Publish -->|Да| Token["Выдать новый токен одной формы; прежний заменить"]
    Publish -->|Отозвать| Revoke["Удалить хэш токена"]
    Client["Внешний backend или SPA"] --> ReadSchema["Запросить публичную схему по токену"]
    ReadSchema --> Valid{"Токен действителен и forms.active = true?"}
    Valid -->|Нет| Denied["Вернуть одинаковый 404"]
    Valid -->|Да| Schema["Вернуть текущую схему только с активными полями"]
    Token --> ReadSchema
    Revoke --> Denied
    Member --> Edit{"Изменить или удалить поле?"}
    Edit -->|Изменить| Revision["Создать новую версию; прежние версии и ответы оставить"]
    Edit -->|Удалить| Archive["Деактивировать поле; сохранить определение и ответы"]
```

Публичный токен — отзывной маркер доступа к одной форме, а не секрет. Защищённые операции выдачи и отзыва доступны участнику workspace; raw токен возвращается один раз, сервер хранит SHA-256 hash. Только `forms.active = true` делает форму доступной публично; это состояние достаточно для первой версии, отдельное `published` не используется. Внешняя схема содержит название и активные поля без служебных идентификаторов и `settings`. Изменения поля создают неизменяемую версию; DELETE архивирует поле как неактивное. Разрешённые CORS origin задаются deployment-конфигурацией. HTTP-поля и статусы зафиксированы в [OpenAPI](../api/openapi.yaml).

## Основания

- [Маршруты и привязка родителей](../../routes/api.php), [policy формы](../../app/Modules/FormDefinition/Infrastructure/Policies/FormPolicy.php).
- [Создание проекта](../../app/Modules/FormDefinition/Presentation/Http/Requests/StoreProjectRequest.php), [формы](../../app/Modules/FormDefinition/Presentation/Http/Requests/StoreFormRequest.php), [поля](../../app/Modules/FormDefinition/Presentation/Http/Requests/StoreFormFieldRequest.php), [обновление поля](../../app/Modules/FormDefinition/Presentation/Http/Requests/UpdateFormFieldRequest.php), [типы](../../app/Modules/FormDefinition/Domain/Form/FieldType.php).
- [Сохранение формы](../../app/Modules/FormDefinition/Infrastructure/Repository/FormRepository.php).
- [Тесты проектов](../../tests/Feature/Modules/FormDefinition/Presentation/Http/Controller/ProjectControllerTest.php), [форм](../../tests/Feature/Modules/FormDefinition/Presentation/Http/Controller/FormControllerTest.php) и [полей](../../tests/Feature/Modules/FormDefinition/Presentation/Http/Controller/FormFieldControllerTest.php).
