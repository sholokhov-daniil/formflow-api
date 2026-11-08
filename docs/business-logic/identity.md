# Вход и ограничение попыток

Статус: текущее поведение (`as-is`). Модуль `Identity`. [Общий обзор](overview.md).

## Проверка запроса

```mermaid
flowchart TD
    Request["Запрос к защищённому API"] --> Key["Определить пару IP и Basic username"]
    Key --> Blocked{"Блокировка ещё действует?"}
    Blocked -->|Да| TooMany["Отклонить запрос до конца блокировки"]
    Blocked -->|Нет| Next["Выполнить HTTP Basic и последующую обработку"]
    Next --> User{"Пользователь определён?"}
    User -->|Да| Reset["Сбросить счётчик ошибок входа"]
    Reset --> Response["Вернуть результат обработки, включая ошибки приложения"]
    User -->|Нет| Unauthorized{"Получен ответ 401?"}
    Unauthorized -->|Нет| Response
    Unauthorized -->|Да| Count["Учесть неудачную попытку в течение окна"]
    Count --> Limit{"Накопилось 3 ошибки?"}
    Limit -->|Нет| Return401["Отклонить вход"]
    Limit -->|Да| Lock["Заблокировать пару на 3 часа и очистить счётчик ошибок"]
    Lock --> TooMany
```

Диаграмма — единственное описание последовательности бизнес-сценария входа. Состав HTTP-ответов и заголовки описаны в [OpenAPI](../api/openapi.yaml); реализация и подтверждающие тесты приведены ниже.

## Основания

- [Middleware](../../app/Modules/Identity/Presentation/Http/Middleware/ThrottleAuthenticationFailures.php), [resolver](../../app/Modules/Identity/Presentation/Http/Middleware/RequestAuthenticationAttemptIdentityResolver.php) и [limiter](../../app/Modules/Identity/Infrastructure/Security/AuthenticationAttemptLimiter.php).
- [Регистрация зависимостей](../../app/Providers/PresentationServiceProvider.php).
- [Тесты HTTP-поведения](../../tests/Feature/Modules/Identity/Presentation/Http/Middleware/ThrottleAuthenticationFailuresTest.php) и [тесты ограничителя](../../tests/Feature/Modules/Identity/Infrastructure/Security/AuthenticationAttemptLimiterTest.php).
