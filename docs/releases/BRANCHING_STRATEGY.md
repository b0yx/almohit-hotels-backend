# Almohit Hotels — Branching Strategy

## Branches

### `main`

- Production-ready only
- Receives merges from `dev`
- Tagged releases only (e.g. `v1.0.0-beta`, `v1.0.1`, `v1.1.0`, `v2.0.0`)

### `dev`

- Active development / integration branch
- Receives feature branches (`feature/*`)
- Merged into `main` when ready for release

### `feature/*`

- Created from `dev`
- One branch per feature
- Merged back into `dev` when complete

## Workflow

```
feature/hotel-images
    ↓
    dev
    ↓
    release (tag)
    ↓
    main
```

## Creating a Feature

```bash
git checkout dev
git pull origin dev
git checkout -b feature/<feature-name>
```

Work normally on the feature branch. When complete, merge back into `dev`.

## Creating a Release

```bash
git checkout main
git pull origin main
git merge dev
git tag -a v<major>.<minor>.<patch> -m "Release v<major>.<minor>.<patch>"
git push origin main --tags
```

## Release Tagging Convention

| Version | Description |
|---------|-------------|
| `v1.0.0-beta` | Initial staging release |
| `v1.0.1` | Patch (bug fixes) |
| `v1.1.0` | Minor (new features, backward compatible) |
| `v2.0.0` | Major (breaking changes) |
