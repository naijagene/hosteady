# RFC-0001: Organization Domain Model

| Field | Value |
|-------|-------|
| **RFC** | 0001 |
| **Title** | Organization Domain Model |
| **Status** | Accepted |
| **Milestone** | M3 — Identity, Organizations & Multi-Tenancy |
| **Author** | HE-ARCH-001 (Chief Software Architect) |
| **Accepted** | 2026-06-23 |
| **Supersedes** | — |
| **Superseded by** | — |

---

## Context

HEOS is a Business Operating System designed to power multiple industry-specific business suites from a single enterprise platform. Before business applications can run, the platform requires a shared model for:

- **Identity** — who uses the platform
- **Tenancy** — which enterprise customer owns data and configuration
- **Operational context** — where users work within a tenant
- **Authorization** — what users may do (roles and permissions)
- **Onboarding** — how users join organizations

Without a formal organization domain, each suite would reinvent tenancy, membership, and access control — creating inconsistency, security risk, and technical debt.

The HEOS UI `WorkspaceShell` is presentation chrome. The domain **`Workspace`** is a tenant-scoped operational partition (team, property, business unit), distinct from the React shell component.

---

## Decision

Hosteady adopts a **shared-schema, row-level multi-tenant organization domain** with the following principles:

1. **`Organization` is the tenant root** — all tenant-owned data carries `organization_id`.
2. **`User` is global** — one identity across multiple organizations via `OrganizationMembership`.
3. **`Workspace` subdivides an organization** — default workspace provisioned at org creation.
4. **Applications** use a global catalog plus per-tenant `OrganizationApplication` installations.
5. **RBAC** uses a global permission catalog and organization-scoped roles assigned to memberships.
6. **Invitations** are first-class entities with lifecycle, expiry, and role pre-assignment.
7. **Identifiers** use UUID v7 for tenant entities; users retain Laravel bigint `id` with a separate `public_id` UUID for external references (see Amendments).
8. **Audit and soft delete** apply consistently to business entities; catalogs use status flags.

This RFC is the authoritative decision record. Detailed table definitions remain in [docs/architecture/m3-organization-domain-model.md](../architecture/m3-organization-domain-model.md).

---

## Scope

### In scope (M3)

| Concern | Entity |
|---------|--------|
| Platform identity | `User` |
| Tenant boundary | `Organization` |
| Operational context | `Workspace` |
| App enablement | `Application`, `OrganizationApplication` |
| Authorization | `Role`, `Permission`, junction tables |
| Onboarding | `Invitation` |

### Out of scope (documented for future RFCs or slices)

- SSO / OAuth provider linkage
- Billing and subscription entities
- API keys and service accounts
- Immutable audit event log
- Database-level row security policies
- Cross-organization data sharing
- `organization_settings` extensible config table (deferred — see Future slices)

---

## Core model

### Architectural decisions

| # | Decision | Rationale |
|---|----------|-----------|
| 1 | Organization is the tenant root | Billing, isolation, app installs, and RBAC belong to an enterprise customer. |
| 2 | Global User + OrganizationMembership | One email/password identity across multiple organizations. |
| 3 | Workspace as org subdivision | Departments, properties, or units without separate tenants. |
| 4 | Application catalog vs installation | Shared catalog; per-tenant installs and config. |
| 5 | Global permissions, org-scoped roles | Stable permission vocabulary; tenant-customizable roles. |
| 6 | Shared schema, row-level tenancy | `organization_id` on tenant data; upgrade path to stronger isolation later. |
| 7 | UUID v7 identifiers | Time-ordered, globally unique, no sequential leakage. |
| 8 | Standard audit mixin | Compliance and forensics from day one. |
| 9 | Soft delete on business entities | Recoverable operations; catalogs use `status` flags. |
| 10 | Invitation as first-class entity | Pending state, expiry, role pre-assignment, audit trail. |

### Bounded contexts

```mermaid
flowchart LR
  subgraph Identity["Identity Context"]
    User
  end

  subgraph Organization["Organization Context"]
    Organization
    Workspace
    OrganizationMembership
    Invitation
    Role
    OrganizationApplication
  end

  subgraph Platform["Platform Catalog Context"]
    Application
    Permission
  end

  subgraph Authorization["Authorization"]
    RolePermission
    OrganizationMemberRole
  end

  User --> OrganizationMembership
  Organization --> Workspace
  Organization --> OrganizationMembership
  Organization --> Invitation
  Organization --> Role
  Organization --> OrganizationApplication
  Application --> OrganizationApplication
  Role --> RolePermission
  Permission --> RolePermission
  OrganizationMembership --> OrganizationMemberRole
  Role --> OrganizationMemberRole
  Invitation -.->|accepts into| OrganizationMembership
```

### Aggregate roots

| Aggregate | Entities | Invariants |
|-----------|----------|------------|
| **User** | User | Email unique among active (non-deleted) users |
| **Organization** | Organization, Workspace, OrganizationMembership, Invitation, Role, OrganizationApplication | ≥1 workspace; ≥1 active Owner membership; slug unique globally |
| **Application** | Application | Key immutable after publish |
| **Permission** | Permission | Key immutable |

### Identifier strategy

| Entity | Internal PK | External API identifier | Notes |
|--------|-------------|---------------------------|-------|
| `users` | `bigint` `id` (Laravel) | `public_id` UUID v7, unique | Preserves Laravel auth and `sessions.user_id` compatibility |
| `organizations` | `uuid` `id` | `public_id` UUID v7, unique | Tenant root |
| `workspaces` | `uuid` `id` | `public_id` UUID v7, unique | Org subdivision |
| All other M3 entities | `uuid` `id` | `public_id` where exposed | Per full schema in architecture doc |

HEOS APIs and integrations resolve entities by **`public_id`**, not internal `users.id`.

### Organization fields (accepted amendments)

Beyond the base RFC model, the following fields are part of the accepted design:

| Field | Entity | Purpose |
|-------|--------|---------|
| `public_id` | User, Organization, Workspace | Stable external identifier |
| `organization_code` | Organization | Human/system reference (support, billing); **nullable during provisioning** |
| `owner_user_id` | Organization | Denormalized owner pointer; nullable during `provisioning`, required before `active` |
| `plan_tier` | Organization | Commercial tier: `free`, `starter`, `business`, `enterprise` (default: `free`) |

### Audit mixin

**Full audit + soft delete** (business entities):

`created_at`, `created_by_user_id`, `updated_at`, `updated_by_user_id`, `deleted_at`, `deleted_by_user_id`

Audit user FKs reference `users.id` (bigint). Default reads exclude `deleted_at IS NOT NULL`.

### Multi-tenancy runtime context

```
TenantContext {
  organization_id: UUID      // required for tenant operations
  workspace_id: UUID         // optional; defaults from membership
  membership_id: UUID        // caller's OrganizationMembership
  user_id: UUID              // user's public_id or resolved identity
}
```

### Organization provisioning flow

1. Insert `organization` (`status = provisioning`, `organization_code` may be NULL)
2. Insert default `workspace` (`is_default = true`)
3. Seed system roles: `owner`, `admin`, `member`, `viewer`
4. Create `organization_membership` for creator (`status = active`)
5. Assign `owner` role; set `owner_user_id`
6. Set `organization_code`; set `status = active`

### Permission vocabulary (summary)

Permissions are globally defined (e.g. `organization.read`, `members.invite`, `applications.install`, `workspace.create`). System roles `owner`, `admin`, `member`, and `viewer` map to subsets of this vocabulary. Full list: [architecture doc §9](../architecture/m3-organization-domain-model.md#9-m3-permission-vocabulary).

---

## M3 Slice 1 implementation reference

**Slice:** M3-002 Slice 1 — Identity foundation tables  
**Engineering lead:** HE-ENG-002  
**Scope:** Database migrations only (no models or API in this slice)

### Principles

- Laravel foundation migration `0001_01_01_000000_create_users_table.php` remains **unchanged**.
- HEOS user requirements applied via a **separate alter migration**.
- `organization_settings` **deferred** from Slice 1.
- Production database target: **PostgreSQL** (partial unique indexes).

### Migration files

| Order | File | Action |
|-------|------|--------|
| 0 | `0001_01_01_000000_create_users_table.php` | Unchanged (Laravel foundation) |
| 1 | `2026_06_24_100005_alter_users_table_for_heos_identity.php` | Alter `users` |
| 2 | `2026_06_24_100010_create_organizations_table.php` | Create `organizations` |
| 3 | `2026_06_24_100030_create_workspaces_table.php` | Create `workspaces` |

Path: `apps/api/database/migrations/`

### Slice 1 tables

#### `users` (altered)

Adds: `public_id`, `display_name`, `status`, audit columns, soft delete.  
Preserves: bigint `id`, `name`, `email`, `password`, `remember_token`, Laravel timestamps.  
Partial unique on `email` where `deleted_at IS NULL`.

#### `organizations` (created)

Includes: `id`, `public_id`, `organization_code` (nullable), `name`, `slug`, `status`, `timezone`, `locale`, `plan_tier`, `owner_user_id`, audit + soft delete.

#### `workspaces` (created)

Includes: `id`, `public_id`, `organization_id`, `name`, `slug`, `is_default`, `status`, audit + soft delete.  
Partial unique: one `is_default = true` workspace per active organization.

### Slice 1 relationships

```
User (bigint id, uuid public_id)
  ↑ owner_user_id, audit FKs
Organization (uuid id, uuid public_id)
  ↓ organization_id
Workspace (uuid id, uuid public_id)
```

---

## Future slices

Planned work under RFC-0001. Each slice may have its own implementation plan; none supersede this RFC unless explicitly noted.

| Slice | Focus | Key deliverables |
|-------|--------|------------------|
| **M3-002 Slice 2** | Membership & RBAC | `organization_memberships`, `roles`, `permissions`, `role_permissions`, `organization_member_roles` |
| **M3-002 Slice 3** | Onboarding | `invitations`, `invitation_roles` |
| **M3-002 Slice 4** | Applications | `applications`, `organization_applications` |
| **M3-002 Slice 5** | Organization settings | `organization_settings` (extensible tenant config JSON or key-value) |
| **M3-003** | Domain models & Eloquent | `HasUuids`, audit trait, soft deletes, `public_id` generation, factories |
| **M3-004** | Tenant context | Middleware, request scoping, `organization_id` enforcement |
| **M3-005** | Provisioning service | Org + default workspace + roles + owner in one transaction |
| **M3-006** | Permission seeders | M3 vocabulary and system role templates |
| **M3-007** | API layer | External routes bound by `public_id` |

Future platform concerns (SSO, billing, audit log) require separate RFCs or RFC amendments.

---

## Consequences

### Positive

- Single tenancy model for all Hosteady suites
- Clear separation of global identity vs tenant membership
- External APIs stable via `public_id`
- Laravel auth compatibility preserved for `users`

### Trade-offs

- Dual identifiers (`id` vs `public_id`) require discipline in API and review
- Mixed FK types (bigint user, uuid org) in migrations and models
- Partial unique indexes require PostgreSQL in production (MySQL not supported for full constraint set)
- `owner_user_id` must stay in sync with Owner role assignment (Slice 2)

---

## Revision history

| Date | Version | Change |
|------|---------|--------|
| 2026-06-23 | 1.0 | Initial acceptance (HE-ARCH-001 domain model) |
| 2026-06-23 | 1.1 | CTO amendments: dual identifiers, `organization_code`, `owner_user_id`, `plan_tier` values, defer `organization_settings`, Slice 1 migration reference |
