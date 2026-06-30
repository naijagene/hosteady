# HEOS Platform Architecture

**Version:** Backend v1.0 (locked) · Frontend P1 (Alpha validation)  
**Last updated:** 2026-06-23  
**Audience:** Developers integrating with or extending HEOS

---

## 1. HEOS vision

HEOS is an enterprise platform foundation: a multi-tenant backend and metadata-driven live experience that powers organization-specific applications without rebuilding core infrastructure for each product. Business verticals (HR, CRM, industry modules) sit **above** the platform layer; AI (M8) sits **adjacent** and is paused until Alpha validation completes.

Design principles:

- API-first, contract-driven integration
- Tenant isolation at organization/workspace scope
- Metadata-driven UI where possible
- Safe defaults and table-health resilience (`heos:doctor`)
- No hardcoded business data where an API contract exists

---

## 2. Platform phase status

| Layer | Phase | Status |
|-------|-------|--------|
| Backend M1–M7 | v1.0 | **Locked** |
| Frontend P1-001–P1-013 | Live Experience | **Feature-complete** (Alpha validation) |
| Platform Alpha | Internal validation | **In progress** |
| M8 AI | Paused | Not started |
| Business modules | Future | Not in Alpha scope |

See [`docs/releases/HEOS_BACKEND_PLATFORM_V1.md`](../releases/HEOS_BACKEND_PLATFORM_V1.md) and [`docs/roadmap.md`](../roadmap.md).

---

## 3. Backend architecture

**Stack:** Laravel (PHP 8.2+), Sanctum auth, modular SDK under `app/Modules/Sdk/`.

**Layers:**

```
HTTP (Controllers, Resources, Policies)
    ↓
Services (domain orchestration)
    ↓
SDK Modules (Form, Table, Dashboard, Report, Workflow, Theme, Personalization, …)
    ↓
Models / Repositories / Enterprise infrastructure
```

**Key capabilities:**

- Auth & tenant context middleware
- Organization, membership, workspace, application runtime
- Dynamic metadata (forms, tables, dashboards, reports, UI pages)
- Enterprise services: audit, notifications, files, jobs, search, workflows
- Event bus & business rules (M6)
- Theme, navigation, personalization runtime (M7)
- `heos:doctor` diagnostics CLI

**Location:** `apps/api`

---

## 4. Frontend architecture

**Stack:** React 19, TypeScript, Vite, Tailwind CSS 4, TanStack Router, TanStack Query, Zustand, Axios, React Hook Form.

**Layers:**

```
app/ (router, providers)
    ↓
features/ (domain modules: auth, runtime, renderer, forms, tables, …)
    ↓
api/ (typed endpoints + normalizers)
    ↓
stores/ (auth/session state)
    ↓
components/ + layouts/
```

Each feature follows: `types.ts`, `core/`, `hooks/`, `components/`, `pages/`, optional `widgets/`.

**Location:** `apps/web`

---

## 5. Monorepo structure

```
Hosteady/
├── apps/
│   ├── api/                 # Laravel backend
│   └── web/                 # React frontend
├── docs/
│   ├── alpha/               # Alpha validation package
│   ├── architecture/        # Architecture references
│   ├── releases/            # Release notes
│   └── roadmap.md
└── (company docs, RFCs)
```

Backend and frontend deploy independently but share API contracts via `/api/v1`.

---

## 6. Tenant model

- **Authentication** is user-scoped (Sanctum token).
- **Tenant context** is organization-scoped; requests carry `X-HEOS-Organization-Id`.
- **Workspace context** scopes runtime: `X-HEOS-Workspace-Id`.
- Optional **application context**: `X-HEOS-Application-Id`.

Middleware resolves tenant membership and policies before enterprise module access.

---

## 7. Organization / workspace / application model

| Entity | Purpose |
|--------|---------|
| **Organization** | Top-level tenant; memberships, roles, settings |
| **Workspace** | Operational unit within org; hosts runtime |
| **Application** | Installed catalog app (core, workspace, demo, …) |
| **Membership** | Links user to org with default workspace |

Runtime composer merges org + workspace + active applications into a **hydrated runtime bundle** consumed by the frontend.

---

## 8. Authentication flow

```
User → POST /api/v1/auth/login
     → Receive access token
     → Store in auth store (Zustand)
     → GET /api/v1/tenant/context (organizations, workspaces)
     → User selects org/workspace
     → Subsequent requests include Bearer token + tenant headers
     → AuthGuard + WorkspaceGuard + ApplicationGuard on frontend routes
```

Logout clears token and hydrated runtime.

---

## 9. Runtime hydration flow

```
Frontend boot (authenticated)
    → GET tenant/workspace/runtime
    → GET tenant/themes/runtime
    → GET tenant/personalization/runtime
    → GET tenant/application-runtime/navigation
    → Compose HydratedRuntimeBundle
    → HydratedRuntimeProvider supplies context to shell + features
```

Home, admin, and alpha health pages read from this bundle. Warnings (missing tables) surface as health warnings, not silent failure.

---

## 10. Metadata rendering pipeline

```
Navigation item → route /pages/:key
    → fetch UI page definition
    → ComponentRenderer resolves component_type
    → Binding renderers (form, table, dashboard, report, activity, admin, …)
    → Layout components (grid, section, tabs, card, …)
```

Unknown components render safe fallback (`UnknownComponent`).

---

## 11. Dynamic form flow

```
Route /forms/:key
    → fetch form definition
    → FormBindingRenderer + React Hook Form
    → Client validation from metadata
    → POST submission to form API
    → Audit/activity hooks on backend
```

---

## 12. Dynamic table flow

```
Route /tables/:key
    → fetch table definition + records
    → TableBindingRenderer
    → Pagination, filters, row actions from metadata
    → Record drill-down links
```

---

## 13. Dashboard / report flow

**Dashboards:** widget registry maps types (metric, chart placeholder, table, admin widgets, activity widgets) → data from runtime/API.

**Reports:** report definition → grouping/aggregation on backend → ReportBindingRenderer → export placeholder where not fully wired.

---

## 14. Document flow

```
/documents → DocumentManagerPage
/documents/:id → DirectDocumentPage
    → List/upload/metadata from enterprise document API
    → Preview: metadata-only at Alpha
    → Version history via activity integration
```

---

## 15. Workflow / task / approval flow

```
/workflows → inbox (human tasks)
/workflows/instances/:id → instance detail
/workflows/tasks/:id → task detail
/workflows/approvals/:id → approval actions
```

Backend workflow engine executes definitions; frontend is runtime/inbox focused (no visual designer at Alpha).

---

## 16. Notification flow

```
/notifications → unified feed
/notifications/:id → detail
    → Preferences referenced from personalization runtime
    → Fallback to unified feed when channel endpoints unavailable
```

Shell shows unread count from hydrated runtime.

---

## 17. Search / command palette flow

```
/search or palette shortcut
    → Local runtime routes (admin, activity, modules)
    → Backend search API when available
    → Navigate to entity/route on selection
```

Universal finder builds searchable index from navigation + static platform routes.

---

## 18. Activity / audit flow

```
/activity → activity center (feed, filters)
/audit → audit viewer
/activity/:type/:id → entity history
    → Backend audit/events API
    → Fallback empty states on fresh DB
```

Activity widgets embedded on home and dashboards.

---

## 19. Administration console flow

```
/admin/* → AdminConsoleLayout + section panels
    → useAdminConsole() aggregates runtime + selective API calls
    → Read-only org/workspace settings at Alpha
    → Permission/role browsers from hydrated permissions
    → Health: merge backend health + runtime warnings
```

Routes protected by `PermissionGuard`; empty permissions array = allow.

---

## 20. Permission model

- **Backend:** Permission catalog (~129 permissions), policies per module, organization roles.
- **Frontend:** Hydrated `permissions[]` on runtime bundle; `PermissionGuard` on routes; admin nav filtering.
- **Convention:** Empty permissions array allows access (development/demo convenience).

Key admin permissions: `platform.read`, `settings.read`, `organization.read`, `workspace.read`, `roles.read`, `permissions.read`, `applications.read`, `runtime.read`, `diagnostics.read`.

---

## 21. Event Bus

Enterprise event bus (M6) publishes domain events for integrations, audit, notifications, and workflow triggers. Modules subscribe via SDK contracts; frontend consumes outcomes through API resources (activity feed, notifications) rather than direct bus access.

---

## 22. Business Rules

Rules engine evaluates conditions against enterprise scope data. Used server-side for workflow automation, validation, and policy enforcement. Frontend displays rule outcomes via standard API responses (form errors, workflow state) — no standalone rules UI at Alpha.

---

## 23. Integration Framework

SDK integration module supports connectors, mappings, and transformers. Development services assist module authors. Alpha frontend does not expose integration designer; integrations run server-side.

---

## 24. Testing strategy

| Layer | Approach |
|-------|----------|
| Backend | PHPUnit feature + unit tests (`php artisan test`); 1,875+ tests at v1.0 lock |
| Frontend | Vitest + Testing Library; `npm run validate` = lint + typecheck + test + build |
| Alpha | Manual smoke test checklist (`docs/alpha/HEOS_ALPHA_SMOKE_TEST.md`) |
| Doctor | `php artisan heos:doctor` for module/table health |

---

## 25. Release strategy

| Release | Tag pattern | Criteria |
|---------|-------------|----------|
| Backend v1.0 | locked | M1–M7 complete, tests green |
| Platform Alpha | `v1.0.0-alpha.1` | P1 complete, smoke test, docs package |
| Beta | TBD | Stabilization, demo seed, reduced placeholders |
| GA | TBD | Production hardening, vertical modules optional |

Release docs live under `docs/releases/` and `docs/alpha/`.

---

## 26. Future AI roadmap (paused)

M8 AI platform is **paused** until P1 Live Experience is validated end-to-end at Alpha. Planned direction (not implemented):

- AI agents as platform modules consuming event bus + metadata
- Guard-railed access to tenant data via existing permission model
- No AI in Alpha demo scope

Resume after Alpha sign-off and stabilization backlog is prioritized.

---

## Quick reference — key API paths

| Path | Purpose |
|------|---------|
| `POST /api/v1/auth/login` | Authentication |
| `GET /api/v1/tenant/context` | Org/workspace list |
| `GET /api/v1/tenant/workspace/runtime` | Workspace runtime |
| `GET /api/v1/tenant/themes/runtime` | Theme runtime |
| `GET /api/v1/tenant/personalization/runtime` | Personalization |
| `GET /api/v1/tenant/application-runtime/navigation` | Navigation menus |
| `GET /api/v1/tenant/workspace/runtime/health` | Runtime health |

---

## Quick reference — key frontend routes

| Route | Module |
|-------|--------|
| `/` | Home shell |
| `/admin/*` | Administration console |
| `/alpha/health` | Alpha readiness (internal) |
| `/pages/:key` | Metadata pages |
| `/forms/:key` | Dynamic forms |
| `/tables/:key` | Dynamic tables |
| `/documents` | Document manager |
| `/workflows` | Workflow center |
| `/notifications` | Notification center |
| `/search` | Enterprise search |
| `/activity` | Activity center |

---

## Related documents

- [Alpha Overview](../alpha/HEOS_ALPHA_OVERVIEW.md)
- [Alpha Setup](../alpha/HEOS_ALPHA_SETUP.md)
- [Backend v1.0 Release](../releases/HEOS_BACKEND_PLATFORM_V1.md)
- [Roadmap](../roadmap.md)
