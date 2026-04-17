# Kindergarten School Management System - PHP Backend

REST API backend for Flutter mobile app.

## Stack
- PHP 8
- MySQL
- JWT auth (HS256)
- PHPMailer (password reset email)

## Folder Structure
```text
backend/
  config/
  controllers/
  models/
  routes/
  middleware/
  utils/
  database/
  public/
```

## Setup (XAMPP / Apache / Linux)
1. Import DB schema:
   - Run `backend/database/schema.sql` in MySQL.
2. Install dependencies:
   - `cd backend`
   - `composer install`
3. Create env file:
   - Copy `.env.example` to `.env`
   - Fill DB/JWT/SMTP values
4. Serve:
   - Apache DocumentRoot => `backend/public`
   - or `php -S localhost:8000 -t backend/public`

## Auth APIs
- `POST /api/login`
- `POST /api/forgot-password`
- `POST /api/reset-password`
- `POST /api/change-password` (Bearer token required)

## Core APIs
- Students: `POST /students/add`, `GET /students/list`, `PUT /students/update`, `DELETE /students/delete`
- Teachers: `POST /teachers/add`, `GET /teachers/list`, `PUT /teachers/update`, `DELETE /teachers/delete`
- Attendance: `POST /attendance/mark`, `GET /attendance/student?student_id=1`
- Homework: `POST /homework/add`, `GET /homework/list`
- Grades: `POST /grades/add`, `GET /grades/student?student_id=1`
- Fees: `POST /fees/add`, `GET /fees/student?student_id=1`
- Messages: `POST /messages/send`, `GET /messages/conversation?user_id=5`
- Notifications: `POST /notifications/send`, `GET /notifications/user?user_id=5`

`/api/...` versions are also available for all resource routes.

## Example Request / Response

### Login
**Request**
```http
POST /api/login
Content-Type: application/json

{
  "email": "admin@school.com",
  "password": "Admin@123"
}
```

**Response**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "<jwt_token>",
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@school.com",
      "role": "admin",
      "created_at": "2026-04-17 17:00:00"
    }
  }
}
```

### Add Student
**Request**
```http
POST /students/add
Authorization: Bearer <jwt_token>
Content-Type: application/json

{
  "name": "Aarav",
  "class_id": 1,
  "parent_id": 3,
  "date_of_birth": "2021-06-12",
  "address": "Delhi",
  "photo": "https://cdn.example.com/student.jpg"
}
```

**Response**
```json
{
  "success": true,
  "message": "Student added successfully",
  "data": {
    "id": 10
  }
}
```

### Forgot Password
**Request**
```http
POST /api/forgot-password
Content-Type: application/json

{
  "email": "parent@school.com"
}
```

**Response**
```json
{
  "success": true,
  "message": "Reset link sent to email",
  "data": null
}
```
