# Hosteady Admin Reference Application

Hosteady Admin (`hosteady.admin`) is the first real HEOS reference application. It validates the metadata-driven runtime for forms, tables, dashboards, reports, navigation, permissions, notifications, activity, and search.

## Purpose

- Prove HEOS can host real enterprise-style administration software
- Exercise metadata renderer bindings without custom React pages
- Provide operational controls for Alpha/demo validation
- Serve as the blueprint for future applications

## Installation

Prerequisites:

1. Platform bootstrap (`PlatformBootstrapSeeder` or migrations + permission catalog)
2. Alpha demo tenant (`AlphaDemoSeeder` with `ALPHA_DEMO_PASSWORD` in `apps/api/.env`)

Seed Hosteady Admin:

```bash
cd apps/api
php artisan db:seed --class=HosteadyAdminApplicationSeeder
```

Target tenant:

- Organization: **Moondew Group** (`moondew-group`)
- Workspace: **Production** (`production`)

## Application registration

| Field | Value |
|-------|-------|
| Name | Hosteady Admin |
| Catalog key | `hosteady-admin` |
| Metadata / runtime key | `hosteady.admin` |
| Version | `0.1.0-alpha` |
| Category | `platform` |
| Status | `active` |

Registration path:

- Module registry key: `hosteady-admin` (SDK key pattern constraint; used by `heos:doctor` sync checks)
- Catalog application key: `hosteady-admin` (must match module registry key after `heos:sync-modules`)
- Metadata module key: `hosteady.admin` (routes, forms, tables, pages, navigation)
- Runtime application key: `hosteady.admin` (enterprise runtime registry label)
- Provider: registered in `config/heos.php`
- Catalog sync: `php artisan heos:sync-modules` (also invoked by `HosteadyAdminApplicationSeeder` when drift is detected)

### Legacy catalog rows

Earlier seeds renamed the synced catalog key from `hosteady-admin` to `hosteady.admin`, which caused `heos:doctor` to report one missing module sync. Re-running `HosteadyAdminApplicationSeeder` normalizes the catalog key back to `hosteady-admin` and removes duplicate catalog rows keyed as `hosteady.admin` when they are not the canonical module UUID row. Orphan enterprise runtime rows with UUID-like `application_key` values and the generic name `Application` are removed when they belong to the Hosteady Admin module.

## Installed routes

### Metadata pages

| Page | Route |
|------|-------|
| Overview | `/app/hosteady.admin/overview` |
| Organizations | `/app/hosteady.admin/organizations` |
| Workspaces | `/app/hosteady.admin/workspaces` |
| Applications | `/app/hosteady.admin/applications` |
| Users | `/app/hosteady.admin/users` |
| Roles & Permissions | `/app/hosteady.admin/roles-permissions` |
| Runtime Diagnostics | `/app/hosteady.admin/runtime` |
| Activity & Audit | `/app/hosteady.admin/activity-audit` |
| Reports | `/app/hosteady.admin/reports` |

### Forms

| Form | Route |
|------|-------|
| Organization Profile | `/forms/hosteady.admin/organization-profile` |
| Workspace Profile | `/forms/hosteady.admin/workspace-profile` |
| User Invite | `/forms/hosteady.admin/user-invite` |
| Application Settings | `/forms/hosteady.admin/application-settings` |

### Tables

| Table | Route |
|-------|-------|
| Organizations | `/tables/hosteady.admin/organizations` |
| Workspaces | `/tables/hosteady.admin/workspaces` |
| Applications | `/tables/hosteady.admin/applications` |
| Users | `/tables/hosteady.admin/users` |
| Permissions | `/tables/hosteady.admin/permissions` |
| Activity | `/tables/hosteady.admin/activity` |

### Dashboard

| Dashboard | Route |
|-----------|-------|
| Overview | `/dashboards/hosteady.admin/overview` |

### Reports

| Report | Route |
|--------|-------|
| Platform Health | `/reports/hosteady.admin/platform-health` |
| Permission Coverage | `/reports/hosteady.admin/permission-coverage` |
| Activity Summary | `/reports/hosteady.admin/activity-summary` |

## Seeded metadata

- Navigation definition: `hosteady.admin` / `admin-primary` (group **Hosteady Admin**, 9 items)
- UI pages: 9 metadata pages with renderer bindings
- Forms: 4 placeholder definitions
- Tables: 6 placeholder definitions (empty-state metadata)
- Dashboard: `overview` with 9 widgets
- Reports: 3 tabular placeholder definitions
- Notification: “Hosteady Admin installed” (category `system`)
- Audit sample: `hosteady_admin.installed` metadata on application install event
- Search index entries for app name and primary navigation targets

## Permissions

| Permission | Description |
|------------|-------------|
| `hosteady.admin.read` | Read Hosteady Admin |
| `hosteady.admin.manage` | Manage Hosteady Admin operations |
| `hosteady.admin.configure` | Configure Hosteady Admin settings |
| `hosteady.admin.reports.read` | Read Hosteady Admin reports |
| `hosteady.admin.audit.read` | Read Hosteady Admin audit events |

Role assignments (Alpha system roles):

| Role | Permissions |
|------|-------------|
| owner | all five |
| administrator | all five |
| manager | read, reports.read, audit.read |
| viewer | read only |

Global permission catalog count: **139** (was 134).

## Application context behavior

`active_application` in workspace runtime is **request-scoped**. It is resolved only when the client sends `X-HEOS-Application-Id` (workspace application public id). Without that header, diagnostics correctly show no active application even when multiple apps are installed.

Admin Runtime Diagnostics now labels this **Active application** and reports `N installed (none selected)` when apps are enabled but no header is present.

## Current limitations

- Tables use placeholder metadata; no live entity repository bindings yet
- Forms are minimal/read-only placeholders
- Report output is tabular placeholder metadata
- Dashboard widgets use static/KPI placeholders where live counts are unavailable
- Search indexing is best-effort during seed; depends on `platform_search_indexes` table
- No full platform management workflows (install/configure/uninstall from UI)
- Active application selection is not persisted automatically on install

## Validation checklist

### Automated

```bash
cd apps/api
php artisan test --filter=HosteadyAdminApplicationSeederTest
php artisan test
php artisan db:seed --class=AlphaDemoSeeder
php artisan db:seed --class=HosteadyAdminApplicationSeeder
php artisan heos:doctor

cd apps/web
npm run validate
```

### Manual browser

1. Login as `bigjyde@alpha.demo.local`
2. Confirm **Hosteady Admin** appears in navigation (group Hosteady Admin) or application registry
3. Open `/app/hosteady.admin/overview`
4. Open `/dashboards/hosteady.admin/overview`
5. Open `/forms/hosteady.admin/organization-profile`
6. Open `/tables/hosteady.admin/organizations`
7. Open `/reports/hosteady.admin/platform-health`
8. Search for **Hosteady Admin**
9. Confirm no crashes; Runtime Diagnostics shows installed app count when none selected

## Future roadmap

- Bind tables to organization/workspace/application/user repositories
- Wire forms to real mutation APIs with validation
- Connect reports to platform health and permission coverage services
- Application switcher setting `X-HEOS-Application-Id` from UI
- Expand Hosteady Admin into full platform operations (tenants, modules, jobs, integrations)
- Document as template module for NollySoft, Scent Maker, BarSoft, and other vertical apps
