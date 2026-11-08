# Отправленные ответы

Статус: текущее поведение (`as-is`). Модуль `Submissions`. [Общий обзор](overview.md). Перед каждым запросом выполняются [аутентификация](identity.md) и [проверка membership и всей цепочки родителей](tenancy.md).

## Создание submission и значения

```mermaid
flowchart TD
    Access["Вход, вложенность URL и доступ проверены"] --> Operation{"Какой запрос выполняется?"}
    Operation -->|Создать submission| Submission["Создать пустой submission для формы из URL"]
    Submission --> CreatedSubmission["Submission создан"]
    Operation -->|Добавить значение в существующий submission| Field{"Поле существует и принадлежит той же форме?"}
    Field -->|Нет| Invalid["Значение не записывается"]
    Field -->|Да| Unique{"Для этого поля ещё нет значения в submission?"}
    Unique -->|Нет| Invalid
    Unique -->|Да| Save["Сохранить значение для submission и поля этой формы"]
    Save --> CreatedValue["Значение создано"]
```

Точная форма запроса и ответа принадлежит [OpenAPI](../api/openapi.yaml).

## Изменение значения

```mermaid
flowchart TD
    Access["Вход, вложенность URL и доступ к значению проверены"] --> Save["Обновить значение, не меняя поле или родителей"]
    Save --> Response["Значение обновлено"]
```

Диаграммы не добавляют проверок по типу поля или его флагам `active`/`required`: такой путь сейчас не реализован. Формат значений и HTTP-результаты описывает [OpenAPI](../api/openapi.yaml); модели — [архитектурная спецификация](../architecture.md).

## Основания

- [Маршруты](../../routes/api.php), [контроллер submission](../../app/Modules/Submissions/Presentation/Http/Controller/SubmissionController.php), [создание submission](../../app/Modules/Submissions/Presentation/Http/Requests/StoreSubmissionRequest.php).
- [Валидация создания значения](../../app/Modules/Submissions/Presentation/Http/Requests/StoreSubmissionValueRequest.php) и [обновления](../../app/Modules/Submissions/Presentation/Http/Requests/UpdateSubmissionValueRequest.php).
- [Репозиторий submission](../../app/Modules/Submissions/Infrastructure/Repository/SubmissionRepository.php) и [значений](../../app/Modules/Submissions/Infrastructure/Repository/SubmissionValueRepository.php).
- [Тесты submission](../../tests/Feature/Modules/Submissions/Presentation/Http/Controller/SubmissionControllerTest.php) и [значений](../../tests/Feature/Modules/Submissions/Presentation/Http/Controller/SubmissionValueControllerTest.php).
