# HEOS Alpha Provisioning Plan

Step-by-step plan to provision the Alpha validation tenant **Moondew Group / Production** with sample metadata and enterprise artifacts.

**Password policy:** Never commit real passwords. Set `ALPHA_DEMO_PASSWORD` in local `.env` only.

---

## Automated path (recommended)

```powershell
cd C:\Projects\Hosteady\apps\api
php artisan migrate:fresh --seed
# Ensure ALPHA_DEMO_PASSWORD is set in .env
php artisan db:seed --class=AlphaDemoSeeder
```

### What `AlphaDemoSeeder` creates

| # | Artifact | Status |
|---|----------|--------|
| 1 | Organization — Moondew Group | ✅ Automated |
| 2 | Workspace — Production | ✅ Automated (renamed from default) |
| 3 | Administrator — BIGJYDE | ✅ Automated |
| 4 | Manager user | ✅ Automated |
| 5 | Viewer user | ✅ Automated |
| 6 | Sample application — Hosteady Platform Preview | ✅ Automated (catalog key `demo`) |
| 7 | Navigation definition | ⚠️ Optional (attempted; may warn if tables/contracts differ) |
| 8 | Theme definition | ⚠️ Optional |
| 9 | Personalization | ⚠️ Requires runtime usage / follow-up |
| 10 | UI page — Alpha Preview Home | ⚠️ Optional |
| 11 | Form — `alpha.preview/sample` | ⚠️ Optional |
| 12 | Table — `alpha.preview/sample` | ⚠️ Optional |
| 13 | Dashboard — `alpha.preview/sample` | ⚠️ Optional |
| 14 | Report — `alpha.preview/sample` | ⚠️ Optional |
| 15 | Document record | ❌ Manual — see § Manual steps |
| 16 | Workflow definition | ❌ Manual — see § Manual steps |
| 17 | Human task | ❌ Manual — depends on workflow execution |
| 18 | Approval | ❌ Manual — depends on workflow execution |
| 19 | Notification | ❌ Manual — see § Manual steps |
| 20 | Activity/audit entry | ⚠️ Partial — org provisioning emits domain audit events |

---

## Prerequisites

1. `PlatformBootstrapSeeder` completes (129 permissions, application catalog)
2. All migrations applied (`php artisan migrate`)
3. `ALPHA_DEMO_PASSWORD` set in `apps/api/.env`
4. Backend tests pass

---

## Manual steps (gaps)

### 15. Document metadata record

Use document manager UI after login, or enterprise document development service in tinker with tenant context for Moondew Group / Production.

Minimum: one document list entry visible at `/documents`.

### 16–18. Workflow, task, approval

Alpha seeder intentionally skips workflow execution (brittle, environment-dependent).

Recommended manual path:

1. Use existing workflow development/publish APIs or backend tests as reference
2. Publish a minimal human-task workflow definition
3. Trigger instance creation via API
4. Verify `/workflows` inbox shows assigned task and pending approval

Document module keys and public IDs in stabilization notes.

### 19. Notification

Options:

- Trigger notification via enterprise notification API
- Perform an action that emits notification (form submit, workflow event)
- Verify `/notifications` unified feed

### 9. Personalization

Personalization populates through runtime usage:

1. Log in as BIGJYDE
2. Add favorites/shortcuts via platform UI where available
3. Confirm home personalization sections update

---

## Service reuse map

| Step | Preferred backend path |
|------|------------------------|
| Org/workspace/users | `OrganizationProvisioningService` |
| Roles | `SystemRoleProvisioner` (via org provision) |
| Application install | `ApplicationInstallationService` + `WorkspaceApplicationService` |
| Theme | `ThemeDevelopmentService::registerDefinition` |
| Navigation | `NavigationDevelopmentService::registerDefinition` |
| UI page | `UiDevelopmentService::registerPage` |
| Form | `DynamicFormDevelopmentService::registerDefinition` |
| Table | `DynamicTableDevelopmentService::registerDefinition` |
| Dashboard | `DynamicDashboardDevelopmentService::registerDefinition` |
| Report | `DynamicReportDevelopmentService::registerDefinition` |
| Document | `EnterpriseDocumentDevelopmentService` |
| Workflow | Workflow designer/publish services |
| Notification | `NotificationDevelopmentService` |
| Audit | Domain actions + `AuditLog` via normal platform operations |

---

## Idempotency

`AlphaDemoSeeder` skips when organization slug `moondew-group` already exists. Safe to re-run on fresh DB only.

To reprovision:

```powershell
php artisan migrate:fresh --seed
php artisan db:seed --class=AlphaDemoSeeder
```

---

## Placeholder credentials (docs only)

| User | Email | Role |
|------|-------|------|
| BIGJYDE | `bigjyde@alpha.demo.local` | Administrator |
| Alpha Manager | `manager@alpha.demo.local` | Manager |
| Alpha Viewer | `viewer@alpha.demo.local` | Viewer |

Password: value of `ALPHA_DEMO_PASSWORD` in your local `.env` — **never commit or share in documentation repositories**.

---

## Validation after provisioning

1. `php artisan heos:doctor` — review warnings
2. Login as BIGJYDE
3. `/alpha/health` — confirm runtime checks
4. Complete [HEOS_ALPHA_OPERATIONAL_VALIDATION.md](./HEOS_ALPHA_OPERATIONAL_VALIDATION.md)

---

## Future: full AlphaDemoSeeder v2

Planned enhancements (post-Alpha stabilization):

- Idempotent document metadata seed
- Published workflow with one task + approval
- Sample notification row
- Navigation items wired to Alpha Preview Home
- Personalization bootstrap via `PersonalizationDevelopmentService`
