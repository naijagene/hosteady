# HEOS Alpha Release Checklist

Target tag: **`v1.0.0-alpha.1`**

Complete before creating the Alpha git tag and announcing internal availability.

---

## Backend

| Item | Status |
|------|--------|
| `php artisan test` passes | ☐ |
| Migrations run cleanly (`php artisan migrate`) | ☐ |
| Seeders run cleanly (`php artisan db:seed`) | ☐ |
| `php artisan heos:doctor` reviewed (warnings documented) | ☐ |
| API routes reviewed for Alpha demo paths | ☐ |
| Permission catalog reviewed | ☐ |
| No temporary scripts committed | ☐ |
| No secrets committed (`.env`, keys, tokens) | ☐ |

---

## Frontend

| Item | Status |
|------|--------|
| `npm run lint` passes | ☐ |
| `npm run typecheck` passes | ☐ |
| `npm run test` passes | ☐ |
| `npm run build` passes | ☐ |
| `npm run validate` passes | ☐ |
| Routes reviewed (`/admin/*`, `/alpha/health`, core modules) | ☐ |
| `.env.example` reviewed (`VITE_API_BASE_URL`) | ☐ |
| No console errors during smoke test | ☐ |
| Responsive layout checked (home, shell, admin) | ☐ |
| Alpha health page verified (`/alpha/health`) | ☐ |

---

## Documentation

| Item | Status |
|------|--------|
| `docs/alpha/HEOS_ALPHA_OVERVIEW.md` | ☐ |
| `docs/alpha/HEOS_ALPHA_SETUP.md` | ☐ |
| `docs/alpha/HEOS_ALPHA_SMOKE_TEST.md` | ☐ |
| `docs/alpha/HEOS_ALPHA_DEMO_GUIDE.md` | ☐ |
| `docs/alpha/HEOS_ALPHA_KNOWN_ISSUES.md` | ☐ |
| `docs/alpha/HEOS_ALPHA_RELEASE_CHECKLIST.md` | ☐ |
| `docs/architecture/HEOS_PLATFORM_ARCHITECTURE.md` | ☐ |

---

## Git

| Item | Status |
|------|--------|
| Working tree clean (or only approved doc commits pending) | ☐ |
| P1-012 committed | ☐ |
| P1-013 committed | ☐ |
| Alpha validation changes committed | ☐ |
| Commits pushed to remote | ☐ |
| Release tag ready (`v1.0.0-alpha.1`) | ☐ |

---

## Smoke test

| Item | Status |
|------|--------|
| [HEOS_ALPHA_SMOKE_TEST.md](./HEOS_ALPHA_SMOKE_TEST.md) completed | ☐ |
| Blockers documented in known issues | ☐ |
| Demo guide reviewed | ☐ |

---

## Sign-off

| Role | Name | Date |
|------|------|------|
| Engineering | | |
| QA / Validation | | |
| Product / Demo lead | | |

**Alpha release approved:** ☐ Yes ☐ No (with exceptions)

**Exceptions noted:**

1. ___
2. ___
