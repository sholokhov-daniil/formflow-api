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

## Публичная отправка (R02 реализован; правила значений продолжаются в R03)

```mermaid
flowchart TD
    Client["Внешний backend или SPA с публичным токеном"] --> Token{"Bearer-токен действителен и форма active?"}
    Token -->|Нет| Denied["Отказать; отправка не создаётся"]
    Token -->|Да| Schema["Получить актуальную публичную схему формы"]
    Schema --> Submit["Передать имя, email и сообщение"]
    Submit --> Validate["Проверить, что коды относятся к активным полям"]
    Validate -->|Ошибка| Invalid["Вернуть ошибку проверки"]
    Validate -->|Успех| Save["Одной транзакцией сохранить отправку и значения"]
    Save --> Stored["Отправка доступна участникам workspace"]
    Operator["Участник workspace"] --> Read["Вручную запросить отправки через защищённый API"]
    Stored --> Read
```

Публичный Bearer-токен не даёт права читать отправки; такие маршруты остаются только под HTTP Basic и membership. Текущая внешняя операция отклоняет неизвестные и неактивные коды, атомарно сохраняет полученные значения и не доверяет parent/owner полям тела. Проверка типов, обязательных значений и версий схемы уточняется в R03–R05. Оператору не обещаются уведомления или срок чтения.

## Основания

- [Маршруты](../../routes/api.php), [контроллер submission](../../app/Modules/Submissions/Presentation/Http/Controller/SubmissionController.php), [создание submission](../../app/Modules/Submissions/Presentation/Http/Requests/StoreSubmissionRequest.php).
- [Валидация создания значения](../../app/Modules/Submissions/Presentation/Http/Requests/StoreSubmissionValueRequest.php) и [обновления](../../app/Modules/Submissions/Presentation/Http/Requests/UpdateSubmissionValueRequest.php).
- [Репозиторий submission](../../app/Modules/Submissions/Infrastructure/Repository/SubmissionRepository.php) и [значений](../../app/Modules/Submissions/Infrastructure/Repository/SubmissionValueRepository.php).
- [Тесты submission](../../tests/Feature/Modules/Submissions/Presentation/Http/Controller/SubmissionControllerTest.php) и [значений](../../tests/Feature/Modules/Submissions/Presentation/Http/Controller/SubmissionValueControllerTest.php).
