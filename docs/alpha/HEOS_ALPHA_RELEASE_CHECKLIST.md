# HEOS Alpha Release Checklist

Target tag: **`v1.0.0-alpha.1`**

Complete before creating the Alpha git tag and announcing internal availability.

---

## Backend

| Item | Status |
|------|--------|
| `php artisan test` passes | ☑ ALPHA-005 (1882 tests) |
| Migrations run cleanly (`php artisan migrate`) | ☑ |
| `php artisan db:seed --class=AlphaDemoSeeder` succeeds (with `ALPHA_DEMO_PASSWORD`) | ☑ ALPHA-005 |
| `php artisan heos:doctor` reviewed (warnings documented) | ☑ ALPHA-005 exit 0 |
| API routes reviewed for Alpha demo paths | ☑ |
| Permission catalog reviewed | ☑ (134 permissions) |
| No temporary scripts committed | ☑ |
| No secrets committed (`.env`, keys, tokens) | ☑ |

---

## Frontend

| Item | Status |
|------|--------|
| `npm run lint` passes | ☑ ALPHA-005 |
| `npm run typecheck` passes | ☑ ALPHA-005 |
| `npm run test` passes | ☑ ALPHA-005 (950 tests) |
| `npm run build` passes | ☑ ALPHA-005 |
| `npm run validate` passes | ☑ ALPHA-005 |
| Routes reviewed (`/admin/*`, `/alpha/health`, core modules) | ☑ |
| `.env.example` reviewed (`VITE_API_BASE_URL`) | ☑ |
| No console errors during smoke test | ☐ Human smoke test pending |
| Responsive layout checked (home, shell, admin) | ☐ Human smoke test pending |
| Alpha health page verified (`/alpha/health`) | ☐ Human smoke test pending |

---

## Documentation

| Item | Status |
|------|--------|
| `docs/alpha/HEOS_ALPHA_OVERVIEW.md` | ☑ |
| `docs/alpha/HEOS_ALPHA_SETUP.md` | ☑ |
| `docs/alpha/HEOS_ALPHA_SMOKE_TEST.md` | ☑ |
| `docs/alpha/HEOS_ALPHA_DEMO_GUIDE.md` | ☑ |
| `docs/alpha/HEOS_ALPHA_KNOWN_ISSUES.md` | ☑ |
| `docs/alpha/HEOS_ALPHA_RELEASE_CHECKLIST.md` | ☑ |
| `docs/architecture/HEOS_PLATFORM_ARCHITECTURE.md` | ☑ |
| `docs/alpha/HEOS_ALPHA_OPERATIONAL_VALIDATION.md` | ☑ |
| `docs/alpha/HEOS_ALPHA_PROVISIONING_PLAN.md` | ☑ |
| `docs/alpha/HEOS_ALPHA_STABILIZATION_SPRINT.md` | ☑ ALPHA-005 RC section added |

---

## Git

| Item | Status |
|------|--------|
| Working tree clean (or only approved doc commits pending) | ☑ at `501bb49` (doc updates from ALPHA-005 pending commit) |
| P1-012 committed | ☑ |
| P1-013 committed | ☑ |
| Alpha validation (ALPHA-001) committed | ☑ |
| Session/navigation stabilization committed | ☑ `501bb49` |
| Commits pushed to remote | ☐ |
| Release tag ready (`v1.0.0-alpha.1`) | ☐ |

---

## Smoke test

| Item | Status |
|------|--------|
| [HEOS_ALPHA_SMOKE_TEST.md](./HEOS_ALPHA_SMOKE_TEST.md) completed | ☐ Human sign-off pending |
| Blockers documented in known issues | ☑ none new |
| Demo guide reviewed | ☑ |

---

## RC validation (ALPHA-005)

| Item | Status |
|------|--------|
| Automated backend gate | ☑ PASS |
| Automated frontend gate | ☑ PASS |
| AlphaDemoSeeder + doctor | ☑ PASS |
| Dev servers reachable | ☑ PASS |
| Auth UI / redirect / forbidden (unauthenticated) | ☑ PASS |
| Full logged-in browser walkthrough | ☐ Pending human QA |

**RC status:** **Ready for Alpha demo** pending human smoke test sign-off.

---

## Sign-off

| Role | Name | Date |
|------|------|------|
| Engineering | | 2026-06-23 (automated ALPHA-005) |
| QA / Validation | | |
| Product / Demo lead | | |

**Alpha release approved:** ☐ Yes ☐ No (with exceptions)

**Exceptions noted:**

1. Complete human smoke test before external demo.
2. ___
