
# Translation Management Service (Laravel 12)

A scalable **API-driven Translation Management Service** built with **Laravel 12** and **Sanctum authentication**.  
Supports:
- Multi-locale translations
- Tag-based filtering
- JSON export for frontends
- Swagger UI documentation
- Unit Test Cases

---

## Features
- Store translations for multiple locales (`en`, `fr`, `es`, etc.)
- Add context tags (e.g., `web`, `mobile`)
- Endpoints:
    - Create translation
    - Update translation
    - Get Translation by ID
    - Search Translations by key/value/locale/tag
    - JSON export for Vue.js/React frontends
- **Sanctum token-based authentication**
- **Swagger UI API documentation**
- **Docker setup**

---

## Tech Stack
- **Laravel 12**
- **PHP 8.2**
- **Xdebugger**
- **MySQL 8**
- **Nginx**
- **Docker & Docker Compose**
- **Laravel Sanctum**
- **Swagger (OpenAPI 3)**

---

## Installation & Setup

### **1. Clone Repository**
```bash
git clone https://github.com/your-repo/translation-management.git
cd translation-management
```

### **2. Configure Environment**
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

Update `.env`:
```
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=laravel

L5_SWAGGER_CONST_HOST=http://localhost:8000
```

Generate app key:
```bash
php artisan key:generate
```

### **3. Build & Start Docker**
```bash
docker-compose build
docker-compose up -d
```

### **4. Install Dependencies**
```bash
docker exec -it laravel-app composer install
```

### **5. Run Migrations**
```bash
docker exec -it laravel-app php artisan migrate
```

### **6. Seed 100K+ Records (Optional for Performance Test)**
```bash
docker exec -it laravel-app php artisan translations:generate 10000 {or desired count for test data}
```

---

##  Authentication
- Login to get **Bearer Token**:
```
POST /api/login
{
  "email": "test@test.com",
  "password": "Pakistan@1"
}
```
- Add token to **Authorization** header:
```
Authorization: Bearer YOUR_TOKEN
```

---

##  API Endpoints

| Method | Endpoint                         | Description                      | Auth |
|--------|---------------------------------|---------------------------------|------|
| POST   | `/api/login`                   | User login                     | No   |
| POST   | `/api/logout`                  | Logout user                    | Yes  |
| POST   | `/api/createTranslation`       | Create new translation         | Yes    |
| PUT    | `/api/updateTranslation/{id}`  | Update translation by ID       | Yes    |
| GET    | `/api/getTranslationById/{id}` | Fetch translation by ID        | Yes    |
| GET    | `/api/getTranslations`         | Search translations by filters | Yes    |
| GET    | `/api/translationsJsonExport`  | Export translations as JSON    | Yes    |

###  Search Filters
```
/api/getTranslations?locale=en&tag=web&key=welcome&value=hello
```

---

##  Swagger API Docs
Access Swagger UI:
```
http://localhost:8000/api/documentation
```

Features:
- **Authorize** button for Sanctum token
- Try out endpoints directly from UI

---

##  Performance Optimization
- Uses **indexed queries**
- **Chunked export for large data**
- Cache for JSON export (`translations_export`)
- Performance-tested with **100K+ records**

---

##  Run Tests
```bash
docker exec -it laravel-app php artisan test
```

---

##  Docker Services
| Service | Port |
|---------|------|
| App     | 9000 |
| Nginx   | 8000 |
| MySQL   | 3306 |

---

##  Development Tools
- Xdebug configured for PhpStorm
- Debug via:
```
?XDEBUG_SESSION=PHPSTORM
```

---

##  Swagger Annotation Example
```php
/**
 * @OA\Post(
 *     path="/api/login",
 *     tags={"Authentication"},
 *     summary="Login user",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(property="email", type="string", example="test@test.com"),
 *             @OA\Property(property="password", type="string", example="Pakistan@1")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Login successful"),
 *     @OA\Response(response=401, description="Invalid credentials")
 * )
 */
```

### **Quick Start**
```
docker-compose up -d
php artisan migrate
php artisan l5-swagger:generate
```
Then open:
```
http://localhost:8000/api/documentation
```
