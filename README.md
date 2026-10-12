# Laravel API Logger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/euginepj/laravel-api-logger.svg?style=flat-square)](https://packagist.org/packages/euginepj/laravel-api-logger)
[![Total Downloads](https://img.shields.io/packagist/dt/euginepj/laravel-api-logger.svg?style=flat-square)](https://packagist.org/packages/euginepj/laravel-api-logger)
[![License](https://img.shields.io/packagist/l/euginepj/laravel-api-logger.svg?style=flat-square)](https://github.com/euginepj/laravel-api-logger/blob/main/LICENSE)

A high-performance API request and response logging middleware with visual analytics and admin dashboard for Laravel.

---

## ✨ Features

- **Asynchronous Lifecycle Logging**: Runs during Laravel's `terminate` lifecycle step to avoid slowing down API response times.
- **Visual Admin Dashboard**: Inspect API calls, status codes (2xx, 4xx, 5xx), request/response headers, payload bodies, and duration.
- **Sensitive Data Masking**: Automatically masks passwords, bearer tokens, API keys, and credit cards with `PayloadSanitizer`.
- **Flexible Storage Drivers**: Supports `database`, `file` (daily logs), or `both`.
- **Automatic Pruning**: Artisan command (`php artisan api-logger:prune`) to automatically clean old logs.
- **Selective Logging**: Option to log only errors (`>= 400`) or all requests, with customizable path exclusion.

---

## 📦 Installation

Install via Composer:

```bash
composer require euginepj/laravel-api-logger
```

Publish configuration and run database migrations:

```bash
php artisan vendor:publish --tag=api-logger-config
php artisan vendor:publish --tag=api-logger-migrations
php artisan migrate
```

---

## 🚀 Usage

### 1. Register Middleware

Add the middleware to your `api` middleware group:

**Laravel 11+ (`bootstrap/app.php`):**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(append: [
        \Euginepj\ApiLogger\Http\Middleware\LogApiRequests::class,
    ]);
})
```

**Laravel 10 (`app/Http/Kernel.php`):**
```php
protected $middlewareGroups = [
    'api' => [
        // ...
        \Euginepj\ApiLogger\Http\Middleware\LogApiRequests::class,
    ],
];
```

### 2. Access Admin Dashboard

Visit `/admin/api-logs` in your browser.

> You can customize route prefix, enabled status, and middleware in `config/api-logger.php`.

### 3. Automatic Log Pruning

Schedule log cleanup in your `routes/console.php` or `app/Console/Kernel.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('api-logger:prune')->daily();
```

---

## ⚙️ Configuration

Published config file: `config/api-logger.php`:

```php
return [
    'enabled' => env('API_LOGGER_ENABLED', true),
    'only_errors' => env('API_LOGGER_ONLY_ERRORS', false),
    'driver' => env('API_LOGGER_DRIVER', 'database'), // database | file | both
    'prune_days' => env('API_LOGGER_PRUNE_DAYS', 30),
    'dashboard' => [
        'enabled' => env('API_LOGGER_DASHBOARD_ENABLED', true),
        'prefix' => 'admin/api-logs',
        'middleware' => ['web', 'auth'],
    ],
];
```

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
