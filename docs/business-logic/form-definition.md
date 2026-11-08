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

## Основания

- [Маршруты и привязка родителей](../../routes/api.php), [policy формы](../../app/Modules/FormDefinition/Infrastructure/Policies/FormPolicy.php).
- [Создание проекта](../../app/Modules/FormDefinition/Presentation/Http/Requests/StoreProjectRequest.php), [формы](../../app/Modules/FormDefinition/Presentation/Http/Requests/StoreFormRequest.php), [поля](../../app/Modules/FormDefinition/Presentation/Http/Requests/StoreFormFieldRequest.php), [обновление поля](../../app/Modules/FormDefinition/Presentation/Http/Requests/UpdateFormFieldRequest.php), [типы](../../app/Modules/FormDefinition/Domain/Form/FieldType.php).
- [Сохранение формы](../../app/Modules/FormDefinition/Infrastructure/Repository/FormRepository.php).
- [Тесты проектов](../../tests/Feature/Modules/FormDefinition/Presentation/Http/Controller/ProjectControllerTest.php), [форм](../../tests/Feature/Modules/FormDefinition/Presentation/Http/Controller/FormControllerTest.php) и [полей](../../tests/Feature/Modules/FormDefinition/Presentation/Http/Controller/FormFieldControllerTest.php).
