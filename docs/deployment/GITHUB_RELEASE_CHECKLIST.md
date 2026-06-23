# GitHub Release Checklist — Almohit Hotels API

## Pre-Publish Checks

### Security
- [x] No secrets/credentials in tracked files
- [x] `.env` in `.gitignore`
- [x] No hardcoded IPs or internal URLs
- [x] License file present (MIT)

### Repository Structure
- [x] Clean root directory (no temp files)
- [x] `README.md` with badges, install, API docs, contributing
- [x] `LICENSE` file
- [x] `.gitignore` covering all generated artifacts
- [x] `.env.example` for required vars

### Documentation
- [x] OpenAPI spec at `docs/api/OPENAPI_SPEC.yaml`
- [x] Postman collection at `docs/api/POSTMAN_COLLECTION.json`
- [x] Deployment guide at `docs/deployment/DEPLOYMENT.md`
- [x] Development guide at `docs/deployment/DEVELOPMENT.md`
- [x] Frontend handoff at `docs/handoff/FRONTEND_HANDOFF.md`

### Code Quality
- [x] No `dd()`, `dump()`, `var_dump()` in tracked files
- [x] No commented-out code blocks
- [x] No `TODO` or `FIXME` left as blockers
- [x] Tests pass: `php artisan test`

### Git Hygiene
- [x] Clean commit history (no merge commits, no temp commits)
- [x] Commit messages follow conventional commits
- [x] No large binary files tracked
- [ ] Tag release: `git tag v1.0.0`

## GitHub Repository Setup

1. Create new repository on GitHub (no template, no README)
2. Push: `git remote add origin git@github.com:your-org/almohit-hotels-backend.git && git push -u origin main`
3. Add topics: `laravel`, `api`, `rest-api`, `hotel-booking`, `php`
4. Enable Issues for bug tracking
5. Protect `main` branch (require PRs for direct pushes)

## Repository Settings

- [ ] Default branch: `main`
- [ ] Merge strategy: Squash merge preferred
- [ ] Allow auto-merge for dependency updates
- [ ] Enable Dependabot for `composer.json` + `package.json`
- [ ] Add `CODEOWNERS` file (optional)
- [ ] Create GitHub Pages site from `/docs` (optional)

## Post-Publish

- [ ] Verify `https://github.com/your-org/almohit-hotels-backend` loads correctly
- [ ] Check README renders with badges
- [ ] Clone fresh from GitHub and run `composer install` to verify
- [ ] Test Railway deploy from GitHub source

## First Release (`v1.0.0`)

- [ ] Write release notes summarizing features
- [ ] Attach Postman collection as release asset
- [ ] Mark as "Latest Release"
