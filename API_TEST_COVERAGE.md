# API Test Coverage Report — Almohit Hotels

This document summarizes the coverage status of the test suite and flags security/architectural risks.

## 1. Coverage Metrics

### Tested Endpoints
* **Auth**: `POST /api/auth/signup/`, `POST /api/auth/login/`, `GET /api/auth/me/`, `PATCH /api/auth/me/`, `POST /api/auth/logout/`
* **Users**: `GET /api/auth/users/` (and admin role locks)
* **Properties**: `GET/POST /api/properties/`, `PATCH/DELETE /api/properties/{id}/`
* **Bookings**: `POST /api/bookings/`, `POST /api/bookings/confirm/`, `POST /api/bookings/{id}/cancel/`
* **Reviews**: `POST /api/properties/{id}/reviews/`, `GET /api/reviews/`

### Untested / Partially Covered Endpoints
* `PATCH /api/properties/{id}/autosave/` (wizard step details)
* `GET /api/properties/{id}/readiness/` (validation warnings check)
* `GET /api/properties/{id}/workspace/` (summary calculations)
* `GET /api/properties/{id}/rooms/search/` (public search)
* `GET /api/properties/{id}/availability/` (date-range availability calculations)
* `GET /api/properties/{id}/rates/` (rates configurations)
* `GET /api/properties/{property}/reviews/summary/` (rating categories totals)
* `api/room-types`, `api/room-prices`, `api/availability-blocks` (Generic CRUD endpoints via CrudController)

---

## 2. Security Risks & Recommendations

* **Autosave and Workspace Validation**: In onboarding wizards, make sure that `autosave` parameters are checked and sanitized to prevent malformed wizard index values.
* **Role/Permission Middleware Scaling**: Ensure that the new `RequirePermission` middleware is applied to core data modification endpoints to restrict non-authorized staff actions.
* **Test Seeding Isolation**: Use factory seeds rather than inline arrays to scale the testing framework efficiently.
