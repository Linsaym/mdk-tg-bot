<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка reCAPTCHA</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const code = urlParams.get('code');

            if (code) {
                document.getElementById('hiddenCode').value = code;
            } else {
                document.getElementById('telegramMessage').classList.remove('hidden');
                document.getElementById('codeForm').classList.add('hidden');
                document.querySelector('.g-recaptcha').style.display = 'none';
            }
        });
    </script>
    <style>
        body {
            background-color: #1b1b18;
            color: #ffffff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: #2a2a28;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #e0e0e0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #b0b0b0;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #3a3a38;
            color: #ffffff;
            font-size: 16px;
            box-sizing: border-box;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #666;
            background-color: #4a4a48;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #45a049;
        }

        button:disabled {
            background-color: #666;
            cursor: not-allowed;
        }

        .recaptcha-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .hidden {
            display: none;
        }

        .error {
            color: #ff6b6b;
            text-align: center;
            margin-top: 10px;
        }

        .success {
            color: #4CAF50;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Проверка безопасности</h1>
    <div id="telegramMessage" class="hidden">
        <p style="text-align: center; color: #ff6b6b;">
            Пожалуйста, откройте эту страницу через Telegram-бота для получения кода доступа.
        </p>
    </div>
    <div class="recaptcha-container">
        <div class="g-recaptcha"
             data-sitekey="6Ld7S9ErAAAAAMkcWLORzZSwPKr4W3jvVZQLngF2"
             data-callback="onCaptchaSuccess"
             data-expired-callback="onCaptchaExpired"
             data-error-callback="onCaptchaError"></div>
    </div>

    <form id="codeForm" class="hidden" method="POST" action="{{ route('verify.code') }}">
        @csrf
        <input type="hidden" id="hiddenCode" name="code">
        <button type="submit">Подтвердить человечность</button>
    </form>

    @if(session('error'))
        <div class="error">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="success">
            {{ session('success') }}
        </div>
    @endif
</div>

<script>
    function onCaptchaSuccess(response) {
        document.getElementById('codeForm').classList.remove('hidden');
    }

    function onCaptchaExpired() {
        document.getElementById('codeForm').classList.add('hidden');
        grecaptcha.reset();
    }

    function onCaptchaError() {
        document.getElementById('codeForm').classList.add('hidden');
    }
</script>
</body>
</html>