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
