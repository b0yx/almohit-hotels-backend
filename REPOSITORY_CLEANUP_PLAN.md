# Repository Cleanup Plan — Almohit Hotels Laravel

**Phase 1 Audit** | **Date:** 2026-06-23

---

## Classification Summary

| Category | File Count | Action |
|----------|-----------|--------|
| Standard project files | ~30 | **KEEP** — no changes |
| Documentation (docs/api/, docs/deployment/, docs/handoff/) | 9 | **KEEP** — improve structure |
| **Archive reports (docs/archive/)** | 31 | **DELETE** — all internal development artifacts |
| **Root temporary documents** | 2 | **DELETE** — CHATGPT_PROJECT_BRIEFING.md, DEPLOYMENT_INVESTIGATION.md |
| Documentation to **MOVE** | 3 | .env.example.production, PROJECT_KNOWLEDGE_TRANSFER.md |

---

## Full File Inventory

### KEEP — Standard Project Files (no changes)

These are the essential project files that must remain untouched:

| File | Purpose |
|------|---------|
| `app/`, `bootstrap/`, `config/`, `database/` | Application source code |
| `public/`, `resources/`, `routes/` | Web layer |
| `storage/`, `tests/` | Runtime & test infrastructure |
| `.env`, `.env.example` | Environment configuration |
| `.editorconfig`, `.gitattributes`, `.gitignore`, `.npmrc` | Standard dotfiles |
| `artisan`, `composer.json`, `composer.lock` | PHP tooling |
| `package.json`, `vite.config.js` | Node build tooling |
| `phpunit.xml` | Test configuration |
| `LICENSE` | MIT license |
| `railway.json` | Railway deployment config |

### DELETE — Archive Reports (31 files in docs/archive/)

**Safety verification:** No PHP file, test file, config file, or deployment file references any of these. Cross-references only exist within other archive files (which are also being deleted) and two markdown files (which will be updated).

| # | File | Reason for Deletion |
|---|------|-------------------|
| 1 | `API_COMPATIBILITY_MAP.md` | Migration planning doc, obsolete |
| 2 | `API_STABILITY_REPORT.md` | AI-generated audit, one-time |
| 3 | `AUTH_VERIFICATION_REPORT.md` | AI-generated audit, one-time |
| 4 | `BACKEND_FREEZE_REPORT.md` | Freeze verification, obsolete |
| 5 | `BACKEND_MIGRATION_AUDIT.md` | Migration audit, one-time |
| 6 | `CODE_QUALITY_REPORT.md` | AI-generated audit, one-time |
| 7 | `DATABASE_SCHEMA.md` | Schema reference (superseded by migration files) |
| 8 | `DEMO_ACCOUNTS.md` | Demo credentials (listed in README.md) |
| 9 | `DOCKER_DATABASE_CONNECTION_REPORT.md` | Investigation report, obsolete |
| 10 | `DOCUMENTATION_STRUCTURE_REPORT.md` | This audit's predecessor |
| 11 | `FINAL_BACKEND_AUDIT.md` | Audit report, one-time |
| 12 | `FINAL_REPOSITORY_STRUCTURE.md` | Structure snapshot, obsolete |
| 13 | `FRONTEND_API_MAPPING.md` | Migration planning, obsolete |
| 14 | `GITHUB_PUSH_REPORT.md` | One-time push report |
| 15 | `GITHUB_RELEASE_REVIEW.md` | Release review, one-time |
| 16 | `HIGH_PRIORITY_FIX_REPORT.md` | Bug fix report, one-time |
| 17 | `IMAGE_UPLOAD_VERIFICATION_REPORT.md` | Verification report, one-time |
| 18 | `LARAVEL_REBUILD_PLAN.md` | Rebuild plan, obsolete |
| 19 | `MIGRATION_PROGRESS.md` | Progress tracker, obsolete |
| 20 | `MVP_SCOPE_REPORT.md` | MVP scope audit, obsolete |
| 21 | `NEXTJS_MVP_HANDOFF.md` | Superseded by FRONTEND_HANDOFF.md |
| 22 | `PRODUCTION_CHECKLIST.md` | Internal ops, superseded |
| 23 | `RAILWAY_502_ANALYSIS.md` | Incident analysis, obsolete |
| 24 | `RAILWAY_BUILD_FAILURE_ANALYSIS.md` | Build analysis, obsolete |
| 25 | `RAILWAY_HEALTHCHECK_REPORT.md` | Healthcheck report, obsolete |
| 26 | `REPOSITORY_CLEANUP_AUDIT.md` | Prior cleanup audit, superseded |
| 27 | `REPOSITORY_CLEANUP_REPORT.md` | Prior cleanup report, superseded |
| 28 | `REPOSITORY_READINESS_REPORT.md` | Readiness report, one-time |
| 29 | `SAFE_MIGRATION_REPO_REPORT.md` | Migration report, obsolete |
| 30 | `SECURITY_AUDIT.md` | Security audit (superseded) |

### DELETE — Root Temporary Files (2 files)

| File | Reason |
|------|--------|
| `CHATGPT_PROJECT_BRIEFING.md` | Temporary briefing, superseded by PROJECT_KNOWLEDGE_TRANSFER.md |
| `docs/deployment/DEPLOYMENT_INVESTIGATION.md` | Temporary investigation, superseded by DEPLOYMENT.md |

### MOVE — Files to Relocate (3 files)

| File | From | To | Reason |
|------|------|----|--------|
| `.env.example.production` | `docs/archive/` | `docs/deployment/` | Production env template belongs with deployment docs |
| `PROJECT_KNOWLEDGE_TRANSFER.md` | Root | `docs/architecture/` | Comprehensive reference doc, not a root-level file |
| `HANDOFF_PACKAGE.md` | `docs/handoff/` | `docs/archive/` | Superseded by FRONTEND_HANDOFF.md (or keep in place) |

### KEEP — Documentation (no changes needed)

| File | Location | Status |
|------|----------|--------|
| `FRONTEND_HANDOFF.md` | `docs/handoff/` | ✅ Core handoff doc |
| `NEXTJS.env.example` | `docs/handoff/` | ✅ Frontend env template |
| `API.md` | `docs/api/` | ✅ API reference |
| `OPENAPI_SPEC.yaml` | `docs/api/` | ✅ OpenAPI spec |
| `OPENAPI_SPEC.json` | `docs/api/` | ✅ OpenAPI spec (JSON) |
| `POSTMAN_COLLECTION.json` | `docs/api/` | ✅ Postman collection |
| `DEPLOYMENT.md` | `docs/deployment/` | ✅ Deployment guide |
| `DEVELOPMENT.md` | `docs/deployment/` | ✅ Developer guide |

---

## External Reference Updates Required

Before deleting, these external references to archive files must be fixed:

| File | Line | Current Reference | Action |
|------|------|-------------------|--------|
| `PROJECT_KNOWLEDGE_TRANSFER.md` | 578 | `docs/archive/DEMO_ACCOUNTS.md` | Update to reference README.md §Seed Data instead |
| `FRONTEND_HANDOFF.md` | 521 | `DEMO_ACCOUNTS.md` in `docs/archive/` | Update or remove row |
| `HANDOFF_PACKAGE.md` | 18 | `DEMO_ACCOUNTS.md` | Update reference or remove row |

---

## Phase Execution Order

1. **Fix external references** — Update PROJECT_KNOWLEDGE_TRANSFER.md and FRONTEND_HANDOFF.md first
2. **Move files** — Relocate .env.example.production and PROJECT_KNOWLEDGE_TRANSFER.md
3. **Delete archive files** — Remove all 30+ internal reports
4. **Improve README.md** — Polish, add Procfile, fix deployment instructions
5. **Organize docs** — Create `docs/architecture/` for PROJECT_KNOWLEDGE_TRANSFER.md
6. **Update .gitignore** — Add Railway-specific entries
7. **Create deployment artifacts** — Procfile (if missing), DEPLOYMENT_READY.md
8. **Git commit** — Final status check and commit

---

## Safety Checklist

- [x] No PHP file references any file in `docs/archive/`
- [x] No test file references any file in `docs/archive/`
- [x] No config file references any file in `docs/archive/`
- [x] No deployment file references any file in `docs/archive/`
- [x] No database migration references any file in `docs/archive/`
- [x] All archive references are only in markdown documentation files
- [x] Demo credentials are already documented in README.md §Seed Data
- [x] OpenAPI spec and API docs are in `docs/api/` (undeleted)
- [x] Deployment guides are in `docs/deployment/` (undeleted)
- [x] Frontend handoff is in `docs/handoff/` (undeleted)
