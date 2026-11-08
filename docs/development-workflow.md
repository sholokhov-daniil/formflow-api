# Процесс разработки

## CI

Workflow [Laravel CI](../.github/workflows/ci.yml) запускает Laravel-тесты при открытии pull request в `main`, добавлении новых коммитов в pull request и его повторном открытии. Обычный push без pull request тесты не запускает.

## Условия слияния

Чтобы GitHub блокировал слияние pull request в `main` до успешного завершения CI, в настройках защиты ветки `main` нужно включить обязательные status checks и выбрать `Laravel tests` (`Settings → Branches` либо ruleset для `main`). Эта настройка хранится на GitHub и пока не включена: для неё требуется доступ с правами администратора репозитория.
