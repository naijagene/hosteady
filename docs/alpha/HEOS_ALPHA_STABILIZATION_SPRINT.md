# HEOS Alpha Stabilization Sprint

Bug and polish tracking for Platform Alpha operational validation (`v1.0.0-alpha.1`).

---

## Severity categories

| Category | Definition | Fix before demo? |
|----------|------------|------------------|
| **Blocker** | Platform unusable | **Yes — must fix** |
| **Critical** | Major feature broken or security issue | **Yes — must fix** |
| **Major** | Important module degraded | Fix if time allows; document if not |
| **Minor** | UX issue, missing empty state | Document; fix in sprint backlog |
| **Polish** | Visual/copy improvements | Backlog |
| **Future** | Out of Alpha scope | Do not fix in Alpha sprint |

---

## Fix-before-demo rules

### Blocker

- Cannot login
- Backend cannot boot
- Frontend cannot boot
- Runtime hydration fails for provisioned tenant
- Admin console crashes on load
- Metadata renderer crashes on valid page

### Critical

- Permissions broken (empty allow misapplied in production-like config)
- Tenant leakage across org/workspace
- Workflow actions fail globally (not just missing seed data)
- Forms cannot submit when definition exists
- Tables cannot load when definition exists

### Major

- Document manager broken
- Reports/dashboards cannot render when definitions exist
- Search unavailable
- Activity center unavailable

### Minor

- Layout issue at one breakpoint
- Missing empty state copy
- Inconsistent labels between admin and shell

### Polish

- Spacing, loading animations, icons, copy refinements

### Future (do not fix in Alpha sprint)

- AI platform (M8)
- Real business modules (HR, CRM, BarSoft, ScentMaker, AutoFarm, NollySoft, Inventory, Finance)
- Workflow visual designer frontend
- Chart libraries (replace placeholders)
- Advanced export pipelines
- Hosteady Admin product shell

---

## Issue log template

| ID | Category | Area | Issue | Impact | Owner | Decision | Status |
|----|----------|------|-------|--------|-------|----------|--------|
| STAB-001 | | | | | | | Open |

---

## Starter issues (from Alpha known gaps)

| ID | Category | Area | Issue | Workaround |
|----|----------|------|-------|------------|
| STAB-010 | Major | Demo data | Document/workflow/notification not auto-seeded | Manual provisioning per plan |
| STAB-011 | Minor | Charts | Chart widgets are placeholders | Demo with metrics/tables |
| STAB-012 | Minor | Admin | Some admin settings read-only | Use API for changes |
| STAB-013 | Minor | Documents | Preview metadata-only | Download externally |
| STAB-014 | Polish | Bundle | Frontend bundle >500 kB | Accept for Alpha |
| STAB-015 | Future | AI | Not implemented | Out of scope |

---

## Sprint workflow

1. Run [operational validation](./HEOS_ALPHA_OPERATIONAL_VALIDATION.md)
2. Log issues in this document
3. Triage: blocker/critical first
4. Fix only integration/stabilization issues (no new platform features)
5. Re-run `php artisan test` and `npm run validate`
6. Update [known issues](./HEOS_ALPHA_KNOWN_ISSUES.md)
7. Sign off via [release checklist](./HEOS_ALPHA_RELEASE_CHECKLIST.md)

---

## Sign-off

| Role | Name | Date | Notes |
|------|------|------|-------|
| Engineering | | | |
| QA | | | |
| Demo lead | | | |

**Demo-ready:** ☐ Yes ☐ No

---

## ALPHA-005 Final RC Validation — 2026-06-23

**Scope:** Release-candidate validation after `fix(alpha): harden session recovery and metadata navigation` (`501bb49`).

### Automated environment — PASS

| Check | Result |
|-------|--------|
| `php artisan test` | **1882 passed** (5312 assertions) |
| `php artisan db:seed --class=AlphaDemoSeeder` | **PASS** — Moondew Group / Production / BIGJYDE verified |
| `php artisan heos:doctor` | **PASS** — exit code 0, overall healthy |
| `npm run validate` | **PASS** — lint, typecheck, **950 tests**, build |
| Targeted Alpha suites | AlphaDemoSeeder (7), TenantAuthorization (2), runtime/navigation (158), module FE (682), stabilization (51) — all **PASS** |

### Server startup — PASS

| Service | URL | Status |
|---------|-----|--------|
| Laravel API | `http://127.0.0.1:8000/up` | HTTP 200 |
| Vite dev | `http://localhost:5173/` | HTTP 200 |
| Terminals | `php artisan serve`, `npm run dev` | Running, no crash |

### Browser validation (agent-run)

| Area | Result | Notes |
|------|--------|-------|
| Login page render | **PASS** | `/login` shows HEOS Sign In form |
| Unauthenticated redirect | **PASS** | `/` → `/login` |
| Redirect loop prevention | **PASS** | `/login?redirect=/login` stays on `/login` (sanitized) |
| Forbidden route | **PASS** | `/forbidden` static page, no bootstrap loop |
| Authenticated walkthrough (Parts 3–18) | **Pending human sign-off** | Credential automation blocked; run [smoke test](./HEOS_ALPHA_SMOKE_TEST.md) locally |

### Pass / fail summary

| Category | Status |
|----------|--------|
| Backend automated | **PASS** |
| Frontend automated | **PASS** |
| Alpha seed & doctor | **PASS** |
| Server availability | **PASS** |
| Auth UI & redirect hygiene | **PASS** |
| Full interactive RC (logged-in paths) | **Pending QA** |

### Blockers

None identified in automated validation.

### Critical issues

None identified in automated validation.

### Major issues

None new. Existing documented gaps: workflow samples manual (ALPHA-017), chart placeholders (ALPHA-003).

### Minor issues / warnings

| ID | Issue | RC impact |
|----|-------|-----------|
| ALPHA-018 | Frontend bundle >500 kB | Accept for Alpha |
| Build | Ineffective dynamic import warning (`session-service`) | Non-blocking |
| QA-RC-001 | Interactive browser checklist not signed in this run | Human demo rehearsal required |

### Polish / Beta backlog

- **APPLICATION-001:** Hosteady Admin reference app (`hosteady.admin`) — see [HOSTEADY_ADMIN_APP.md](../applications/HOSTEADY_ADMIN_APP.md)
- Code-split frontend bundle
- Live admin API latency metrics (ALPHA-013)
- Chart libraries (ALPHA-003)
- Workflow visual designer (ALPHA-006)
- Document inline preview (ALPHA-005 known issues table)

### RC recommendation

**Ready for Alpha demo** after a human completes [HEOS_ALPHA_SMOKE_TEST.md](./HEOS_ALPHA_SMOKE_TEST.md) (login → health → sidebar → modules → recovery). Automated gate is green; no blocker/critical/major fixes required from this run.

**Fixes applied in ALPHA-005:** None (validation-only milestone).

**Git at validation:** `501bb49` — working tree clean.
