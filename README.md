# News Aggregator

Тестовое задание: новостной агрегатор на Symfony 8.

**Dev-окружение — через Docker Compose. Продакшен (этап 7) — нативно без Docker (nginx + php-fpm + MySQL + RabbitMQ через apt/systemd), как требует ТЗ.**

## Быстрый старт (локальная разработка)

```bash
# 1. Скопировать и заполнить .env.local
cp .env.local.dist .env.local

# 2. Поднять контейнеры
make up

# 3. Установить зависимости
docker compose exec php-fpm composer install

# 4. Применить миграции
make migrate

# 5. Открыть в браузере
open http://localhost:8080
```

## Полезные команды

```bash
make up       # поднять контейнеры
make down     # остановить контейнеры
make sh       # войти в php-fpm контейнер
make test     # запустить тесты
make migrate  # применить миграции
make lint     # проверить twig/yaml/schema
```

## Проверки

- Главная: http://localhost:8080/
- RabbitMQ UI: http://localhost:15672/ (guest/guest)

## Стек

| Компонент | Версия |
|---|---|
| PHP | 8.5-fpm-alpine |
| Symfony | 8.0 |
| MySQL | 8.4 LTS |
| RabbitMQ | 4.x |
| nginx | 1.27 (stable) |

## Тестирование

```bash
make test
# или внутри контейнера:
docker compose exec php-fpm composer test
```

Тесты запускаются против базы `news_test`. Миграции применяются автоматически в `tests/bootstrap.php`.
