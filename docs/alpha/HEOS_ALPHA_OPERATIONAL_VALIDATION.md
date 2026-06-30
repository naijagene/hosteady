# HEOS Alpha Operational Validation

**Release:** `v1.0.0-alpha.1`  
**Sprint:** ALPHA-001 — Platform Operational Validation & Stabilization  
**Goal:** Confirm HEOS runs as a real multi-tenant SaaS application with a live validation tenant.

---

## Validation tenant profile

| Entity | Value |
|--------|-------|
| Organization | **Moondew Group** (`moondew-group`) |
| Workspace | **Production** (`production`) |
| Administrator | **BIGJYDE** (`bigjyde@alpha.demo.local`) |
| Manager | Alpha Manager (`manager@alpha.demo.local`) |
| Viewer | Alpha Viewer (`viewer@alpha.demo.local`) |
| Sample application | **Hosteady Platform Preview** (catalog key: `demo`) |

**Password policy:** Set `ALPHA_DEMO_PASSWORD` in `apps/api/.env` before seeding. Do not commit passwords. Document only placeholder emails in shared docs.

---

## Operational validation sequence

### 1. Environment bootstrap

```powershell
cd C:\Projects\Hosteady\apps\api
composer install
copy .env.example .env
# Set ALPHA_DEMO_PASSWORD=your-local-placeholder in .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan db:seed --class=AlphaDemoSeeder
php artisan heos:doctor
php artisan test
php artisan serve
```

```powershell
cd C:\Projects\Hosteady\apps\web
npm install
npm run validate
npm run dev
```

### 2. Login verification

1. Open `http://localhost:5173/login`
2. Log in as `bigjyde@alpha.demo.local` with your local `ALPHA_DEMO_PASSWORD`
3. Select **Moondew Group** / **Production** if prompted
4. Confirm home shell loads without console errors

### 3. Runtime verification

| Check | Route / surface | Expected |
|-------|-----------------|----------|
| Alpha health | `/alpha/health` | Overall status `ready` or `warning` (not faked) |
| Organization | Home / `/admin/organization` | Moondew Group |
| Workspace | Home / `/admin/workspaces` | Production |
| Application | `/admin/applications` | Hosteady Platform Preview enabled |
| Navigation | Shell sidebar | Menus present or documented empty state |
| Theme | Admin runtime diagnostics | Theme source reported |
| Permissions | Home metrics / admin | Owner/administrator permissions loaded |

### 4. Metadata rendering verification

After optional metadata seeding (see provisioning plan):

| Artifact | Frontend route pattern | Pass criteria |
|----------|------------------------|---------------|
| UI page | `/pages/alpha-preview-home` | Page renders without crash |
| Form | `/forms/alpha.preview/sample` | Form fields visible |
| Table | `/tables/alpha.preview/sample` | Table shell loads |
| Dashboard | `/dashboards/alpha.preview/sample` | Widgets render (chart placeholders OK) |
| Report | `/reports/alpha.preview/sample` | Report viewer loads |

Gaps documented in [HEOS_ALPHA_PROVISIONING_PLAN.md](./HEOS_ALPHA_PROVISIONING_PLAN.md) for document, workflow, notification, and audit samples.

### 5. Platform module verification

| Module | Route | Admin user | Manager | Viewer |
|--------|-------|------------|---------|--------|
| Admin console | `/admin` | Full access | Limited | Read-only sections |
| Search | `/search` | Works | Works | Works |
| Documents | `/documents` | Opens | Opens | Read-only |
| Workflows | `/workflows` | Opens | Opens | Read-only |
| Notifications | `/notifications` | Opens | Opens | Read-only |
| Activity | `/activity` | Opens | Opens | Read-only |

### 6. Permission verification

1. Log in as **manager** — confirm admin sections filtered; write actions allowed where permitted
2. Log in as **viewer** — confirm read-only behavior; restricted routes show forbidden/unauthorized
3. Confirm no cross-tenant data visible when switching context

### 7. Stabilization handoff

Record findings in [HEOS_ALPHA_STABILIZATION_SPRINT.md](./HEOS_ALPHA_STABILIZATION_SPRINT.md) using severity categories. Blockers must be resolved before internal demo sign-off.

---

## Pass / fail criteria

**Operational validation passes when:**

- Alpha demo seeder completes without error
- Admin user can log in and hydrate runtime
- `/alpha/health` reflects real state (no fake success)
- Core platform routes open without crash
- Permission differences observable across admin/manager/viewer
- Smoke test checklist completed with notes

**Does not require:** AI, business modules, workflow designer UI, production chart libraries, or full document/workflow seed automation (documented gaps acceptable for Alpha).

---

## Related documents

- [Provisioning Plan](./HEOS_ALPHA_PROVISIONING_PLAN.md)
- [Stabilization Sprint](./HEOS_ALPHA_STABILIZATION_SPRINT.md)
- [Smoke Test](./HEOS_ALPHA_SMOKE_TEST.md)
- [Setup Guide](./HEOS_ALPHA_SETUP.md)
