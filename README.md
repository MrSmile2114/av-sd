# Прототип API сервиса курьерской доставки
Разработан прототип JSON API для сервиса курьерской доставки

[![Build Status](https://travis-ci.org/s-shiryaev/av-sd.svg?branch=master)](https://travis-ci.org/s-shiryaev/av-sd)
[![codecov](https://codecov.io/gh/s-shiryaev/av-sd/branch/master/graph/badge.svg)](https://codecov.io/gh/s-shiryaev/av-sd)

#### Основной функционал
Предполагается, что на стороне frontend адрес клиента преобразуется в координаты места доставки. 
(Например, с помощью API Yandex) 

По координатам с помощью метода `GET /api/delivery_price` вычисляется стоимость и возможность доставки в зависимости от 
расстояния.

При согласии клиента с ценой, с помощью метода `POST /api/order/` создается заказ на доставку.

С помощью метода `GET /api/order/{id}` курьер получает информацию о заказе. 
Методом `PATCH /api/order/{id}/delivered` сообщает об успешной доставке.

Метод `GET /api/orders` возвращает список заказов.

Авто-собираемая документация со всеми методами доступна по адресу `/api/doc`.

### Особенности
- [x] Реализован RateLimit с ограничением 10 RPM.
- [x] Автоматически генерируемая из аннотаций OpenAPI-спецификация.
- [x] Реализация покрыта тестами.
- [x] Контейнеризация.
- [x] Для ускорения ответа БД используется кеширование Redis.
- [x] Пагинация.


