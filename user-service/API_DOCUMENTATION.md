# User Service API Documentation

## Overview

User Service CRUD API with Repository Pattern, Service Layer, and Request Validation.

## Architecture

```
├── Controller Layer (UserController)
│   └── Handles HTTP requests/responses
│       └── Uses ApiResponse trait
│
├── Request Layer (UserRequest)
│   └── Validates incoming data
│       └── Auto-returns JSON errors
│
├── Service Layer (UserService)
│   └── Business logic & transactions
│       └── Uses BaseException for errors
│
├── Repository Layer (UserRepository)
│   └── Database operations
│       └── Extends BaseRepository
│
└── Model Layer (User)
    └── Eloquent ORM
```

## API Endpoints

### Base URL

```
http://localhost:8000/api
```

### 1. Health Check

```http
GET /health
```

**Response:**

```json
{
    "success": true,
    "message": "Service is running",
    "timestamp": "2025-11-24T12:00:00+00:00"
}
```

---

### 2. List Users (with pagination & search)

```http
GET /users?per_page=15&search=john
```

**Query Parameters:**

-   `per_page` (optional): Number of items per page (default: 15)
-   `search` (optional): Search keyword for name or email

**Response:**

```json
{
    "success": true,
    "message": "Users retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com",
                "email_verified_at": "2025-11-24T12:00:00+00:00",
                "created_at": "2025-11-24T12:00:00+00:00"
            }
        ],
        "per_page": 15,
        "total": 100
    }
}
```

---

### 3. Create User

```http
POST /users
Content-Type: application/json
```

**Request Body:**

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePass123",
    "password_confirmation": "SecurePass123"
}
```

**Validation Rules:**

-   `name`: required, string, min:2, max:255
-   `email`: required, email, unique
-   `password`: required, string, min:8, confirmed

**Success Response (201):**

```json
{
    "success": true,
    "message": "User created successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2025-11-24T12:00:00+00:00"
    }
}
```

**Error Response (422):**

```json
{
    "success": false,
    "message": "Validation failed",
    "error_code": "VALIDATION_ERROR",
    "errors": {
        "email": ["The email has already been taken."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

---

### 4. Get User by ID

```http
GET /users/{id}
```

**Response:**

```json
{
    "success": true,
    "message": "User retrieved successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": "2025-11-24T12:00:00+00:00",
        "created_at": "2025-11-24T12:00:00+00:00",
        "updated_at": "2025-11-24T12:00:00+00:00"
    }
}
```

**Error Response (404):**

```json
{
    "success": false,
    "message": "User not found",
    "error_code": "USER_NOT_FOUND",
    "errors": {
        "user_id": 999
    }
}
```

---

### 5. Update User

```http
PUT /users/{id}
Content-Type: application/json
```

**Request Body:**

```json
{
    "name": "John Updated",
    "email": "john.updated@example.com",
    "password": "NewPassword123",
    "password_confirmation": "NewPassword123"
}
```

**Notes:**

-   Password is optional for updates
-   If password is empty, it won't be updated
-   Email uniqueness is checked excluding current user

**Success Response:**

```json
{
    "success": true,
    "message": "User updated successfully",
    "data": {
        "id": 1,
        "name": "John Updated",
        "email": "john.updated@example.com",
        "updated_at": "2025-11-24T13:00:00+00:00"
    }
}
```

---

### 6. Delete User

```http
DELETE /users/{id}
```

**Success Response:**

```json
{
    "success": true,
    "message": "User deleted successfully"
}
```

---

### 7. Get Active Users

```http
GET /users/active/list
```

**Description:** Returns only users with verified email addresses.

**Response:**

```json
{
    "success": true,
    "message": "Active users retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "email_verified_at": "2025-11-24T12:00:00+00:00"
        }
    ]
}
```

---

### 8. Bulk Delete Users

```http
POST /users/bulk-delete
Content-Type: application/json
```

**Request Body:**

```json
{
    "ids": [1, 2, 3, 4, 5]
}
```

**Success Response:**

```json
{
    "success": true,
    "message": "Successfully deleted 5 users",
    "data": {
        "deleted_count": 5
    }
}
```

---

### 9. User Statistics

```http
GET /users/stats/overview
```

**Response:**

```json
{
    "success": true,
    "message": "Statistics retrieved successfully",
    "data": {
        "total_users": 1000,
        "verified_users": 850,
        "unverified_users": 150
    }
}
```

---

### 10. Check Email Availability

```http
POST /users/check-email
Content-Type: application/json
```

**Request Body:**

```json
{
    "email": "test@example.com"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Email already exists",
    "data": {
        "exists": true
    }
}
```

---

## Error Codes

| Code                       | Description                  |
| -------------------------- | ---------------------------- |
| `VALIDATION_ERROR`         | Request validation failed    |
| `USER_NOT_FOUND`           | User with given ID not found |
| `EMAIL_EXISTS`             | Email already registered     |
| `CREATE_USER_FAILED`       | Failed to create user        |
| `UPDATE_USER_FAILED`       | Failed to update user        |
| `DELETE_USER_FAILED`       | Failed to delete user        |
| `FETCH_USERS_FAILED`       | Failed to fetch users list   |
| `SEARCH_USERS_FAILED`      | Failed to search users       |
| `BULK_DELETE_USERS_FAILED` | Failed to bulk delete users  |
| `FETCH_STATISTICS_FAILED`  | Failed to fetch statistics   |

---

## Testing with cURL

### Create User

```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Get All Users

```bash
curl http://localhost:8000/api/users
```

### Get User by ID

```bash
curl http://localhost:8000/api/users/1
```

### Update User

```bash
curl -X PUT http://localhost:8000/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Name",
    "email": "updated@example.com"
  }'
```

### Delete User

```bash
curl -X DELETE http://localhost:8000/api/users/1
```

### Search Users

```bash
curl "http://localhost:8000/api/users?search=john&per_page=10"
```

---

## Features

✅ **Repository Pattern** - Clean separation of data access logic
✅ **Service Layer** - Business logic isolation
✅ **Request Validation** - Automatic validation with custom error messages
✅ **Exception Handling** - Custom BaseException with error codes
✅ **API Response Trait** - Standardized JSON responses
✅ **Transaction Management** - Automatic rollback on errors
✅ **Logging** - Comprehensive error and activity logging
✅ **Password Hashing** - Automatic bcrypt hashing
✅ **Email Validation** - Unique email enforcement
✅ **Pagination** - Built-in pagination support
✅ **Search Functionality** - Name and email search
✅ **Bulk Operations** - Bulk delete support
✅ **Statistics** - User statistics endpoint

---

## Running the Service

### Start the service

```bash
cd user-service
php artisan serve
```

### Run migrations

```bash
php artisan migrate
```

### Run with specific port

```bash
php artisan serve --port=8000
```

---

## Code Structure Best Practices

### 1. Controller Layer

-   Handles HTTP requests only
-   Uses dependency injection
-   Returns consistent JSON responses via ApiResponse trait
-   Catches and handles exceptions

### 2. Service Layer

-   Contains business logic
-   Manages transactions
-   Throws BaseException with proper error codes
-   Logs important operations

### 3. Repository Layer

-   Database operations only
-   Extends BaseRepository for common operations
-   Custom methods for specific queries
-   No business logic

### 4. Request Layer

-   Validation rules
-   Custom error messages
-   Data preparation/normalization
-   Returns JSON validation errors

---

## Environment Configuration

```env
APP_NAME="User Service"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=user_service
DB_USERNAME=root
DB_PASSWORD=
```
