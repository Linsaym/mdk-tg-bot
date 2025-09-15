# MDK Telegram Bot 🤖✈️

**Telegram-бот на Laravel, который анализирует совместимость друзей в путешествиях.**

[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Telegram](https://img.shields.io/badge/Telegram-2CA5E0?style=for-the-badge&logo=telegram&logoColor=white)](https://core.telegram.org/bots/api)

---

## 🔥 Возможности

- **Анкетирование** — отвечаешь на вопросы о своих предпочтениях в путешествиях
- **Сравнение** — бот анализирует ответы тебя и твоего друга
- **Советы** — показывает, насколько вы совместимы и как избежать конфликтов
- **Реферальные ссылки** — удобный обмен тестами
- **Административная панель** - позволяет добавлять вопросы и смотреть статистику

---

## 🛠 Технологии

- **Backend**: Laravel 12
- **Telegram API**: [irazasyed/telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk)
- **Хранение данных**: MySQL + Redis (кеширование)

---

## ⚡️ Быстрый старт

### 1. Установка

```bash
    git clone https://github.com/Linsaym/mdk-tg-bot
    cd mdk-tg-bot
    composer install
    cp .env.example .env
    php artisan key:generate
```

### 2. Настройка Telegram бота

1. Создай бота через [@BotFather](https://t.me/BotFather)
2. Добавь токен в `.env`:

```dotenv
TELEGRAM_BOT_TOKEN=your_token_here
TELEGRAM_BOT_NAME=your_bot_name
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook
```

### 3. Установка вебхука

Чтобы команда сработала - проверьте указанны ли у вас в env
TELEGRAM_BOT_TOKEN и TELEGRAM_WEBHOOK_URL. А так же настройки в config/telegram.php

```bash
    php artisan telegram:set-webhook
```

---

## 🔧 Локальное тестирование

Для локальной разработки можете использовать tuna.aw или ngrock. После активации tuna, не забудьте перейти по
сгенерированной ссылке, чтобы активировать её.
В файле env, нужно указать ссылку, сгенерированную tuna, с припиской api/telegram-webhook

```bash
    php artisan serve --port=8000
    tuna http 8000
    php artisan telegram:set-webhook
```

## 📊 Пример работы

### 1. Пользователь начинает диалог:

...

### 2. Проходит тест:

```  
Бот: "Как ты относишься к спонтанным изменениям планов?"  
1) Ненавижу  
2) Нормально  
3) Обожаю  
```  

### 3. Получает результат:

...

# Тестовая среда

(у телеграм бота есть тестовая бд, которая указывается в env, для неё создан отдельный контроллер)