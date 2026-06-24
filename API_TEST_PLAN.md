# API Test Plan — Almohit Hotels

This document defines the test cases, authentication requirements, request schemas, and expected responses for the API endpoints.

## 1. Authentication Endpoints

### 1.1 Customer Signup
* **Endpoint**: `POST /api/auth/signup/`
* **Auth**: None (Public)
* **Expected Request**:
  ```json
  {
    "email": "customer_test@almohit.com",
    "full_name": "Test Customer",
    "password": "customerpass123",
    "password_confirm": "customerpass123"
  }
  ```
* **Expected Response**: Status `201 Created` with confirmation details.

### 1.2 User Login
* **Endpoint**: `POST /api/auth/login/`
* **Auth**: None (Public)
* **Expected Request**:
  ```json
  {
    "email": "customer_test@almohit.com",
    "password": "customerpass123"
  }
  ```
* **Expected Response**: Status `200 OK` returning `token`, `access`, and `user` (with `permissions`).

### 1.3 User Profile
* **Endpoint**: `GET /api/auth/me/`
* **Auth**: Bearer Token
* **Expected Response**: Status `200 OK` returning user profile.

---

## 2. Property Endpoints

### 2.1 Create Property (Hotel)
* **Endpoint**: `POST /api/properties/`
* **Auth**: Staff or Admin Bearer Token
* **Expected Request**:
  ```json
  {
    "name": "Luxury Test Hotel",
    "slug": "luxury-test-hotel",
    "subdomain": "luxurytest",
    "country": "Egypt",
    "city": "Cairo",
    "email": "luxurytest@almohit.com",
    "phone": "+201000000000"
  }
  ```
* **Expected Response**: Status `201 Created` returning the hotel object.

---

## 3. Booking Endpoints

### 3.1 Create Booking
* **Endpoint**: `POST /api/bookings/`
* **Auth**: Bearer Token
* **Expected Request**:
  ```json
  {
    "customer_name": "John Doe",
    "phone": "+1234567890",
    "email": "johndoe@example.com",
    "property": 1,
    "room_type": 1,
    "check_in": "2026-07-01",
    "check_out": "2026-07-05",
    "adults": 2,
    "children": 0
  }
  ```
* **Expected Response**: Status `201 Created` returning the booking inquiry object.

---

## 4. Review Endpoints

### 4.1 Submit Review
* **Endpoint**: `POST /api/properties/{property_id}/reviews/`
* **Auth**: None (Public)
* **Expected Request**:
  ```json
  {
    "guest_name": "John Doe",
    "guest_email": "johndoe@example.com",
    "rating": 5,
    "title": "Amazing Stay!",
    "comment": "Had a wonderful time here. Staff was very friendly.",
    "cleanliness": 5,
    "location": 5,
    "staff": 5,
    "comfort": 5,
    "value_for_money": 5
  }
  ```
* **Expected Response**: Status `201 Created` returning the review object.
