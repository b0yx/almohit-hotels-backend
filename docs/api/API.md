# API Documentation — Almohit Hotels

The API is documented via OpenAPI 3.0.3 specification and a Postman collection.

## Quick Reference

| Resource | File | Contents |
|----------|------|----------|
| Full API Spec | [`OPENAPI_SPEC.yaml`](OPENAPI_SPEC.yaml) | Complete OpenAPI 3.0.3 spec (60 paths, 15 schemas) |
| JSON version | [`OPENAPI_SPEC.json`](OPENAPI_SPEC.json) | JSON format for tool imports |
| Postman Collection | [`POSTMAN_COLLECTION.json`](POSTMAN_COLLECTION.json) | 82 endpoints across 11 folders |
| Frontend Integration | [`FRONTEND_HANDOFF.md`](FRONTEND_HANDOFF.md) | Auth flow, uploads, pagination, errors |
| MVP Handoff | [`NEXTJS_MVP_HANDOFF.md`](NEXTJS_MVP_HANDOFF.md) | MVP-scoped endpoint reference |

## API Basics

| Property | Value |
|----------|-------|
| Base URL | `http://localhost:8000/api/` |
| Auth | `Authorization: Bearer <token>` |
| Response format | JSON, snake_case keys |
| Pagination | `{ count, next, previous, results }` |
| Errors | `{ detail: string, code: string }` |
| Page size | Default 20, max 100 (`?page_size=N`) |

## Authentication Flow

1. **Signup** → `POST /api/auth/signup/`
2. **Verify OTP** → `POST /api/auth/verify-otp/`
3. **Login** → `POST /api/auth/login/` → returns `{ access: "token", user: {...} }`
4. **Use token** → `Authorization: Bearer <access>` on all authenticated requests
5. **Logout** → `POST /api/auth/logout/`

## Navigation

- [OpenAPI Spec (YAML)](OPENAPI_SPEC.yaml)
- [OpenAPI Spec (JSON)](OPENAPI_SPEC.json)
- [Postman Collection](POSTMAN_COLLECTION.json)
- [Frontend Handoff Guide](FRONTEND_HANDOFF.md)
- [MVP Handoff Guide](NEXTJS_MVP_HANDOFF.md)
