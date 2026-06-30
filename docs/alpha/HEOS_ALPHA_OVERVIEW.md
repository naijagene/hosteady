# HEOS Platform Alpha Overview

**Tag target:** `v1.0.0-alpha.1`  
**Status:** Internal validation — not a public production release  
**Last updated:** 2026-06-23

---

## What is HEOS Alpha?

HEOS Alpha is the first end-to-end internal validation of the completed **HEOS Backend Platform v1.0** and **HEOS Live Experience (P1)** frontend. The goal is to confirm that a real user can log in, hydrate tenant runtime, navigate the platform shell, and exercise every core platform capability before tagging an Alpha release for internal demo and hands-on testing.

Alpha is **not** a feature milestone. No new business modules, AI layer, or major architecture changes are in scope.

---

## Platform scope at Alpha

### Backend (locked v1.0)

| Milestone | Domain |
|-----------|--------|
| M1 | Platform core, auth, tenant context |
| M2 | Organization & membership |
| M3 | Workspace & application runtime |
| M4 | Enterprise services (audit, notifications, reference data) |
| M5 | Files, jobs, scheduler, search, workflows |
| M6 | Event bus & business rules |
| M7 | UI metadata, navigation, themes, personalization |

### Frontend (P1 complete)

| ID | Capability |
|----|------------|
| P1-001 | Foundation & application shell |
| P1-002 | Authentication & runtime hydration |
| P1-003 | Metadata renderer |
| P1-004 | Dynamic forms |
| P1-005 | Dynamic tables |
| P1-006 | Dynamic dashboards |
| P1-007 | Dynamic reports |
| P1-008 | Document manager |
| P1-009 | Workflow center |
| P1-010 | Notification center |
| P1-011 | Enterprise search & command palette |
| P1-012 | Activity & audit |
| P1-013 | Administration console |

---

## Alpha validation goals

A validator should be able to:

1. Start backend and frontend locally
2. Log in and restore session
3. Select organization and workspace
4. Load hydrated runtime (theme, navigation, personalization, permissions)
5. Navigate the platform shell
6. Use the administration console
7. Render metadata-driven pages
8. Submit forms, browse tables, view dashboards and reports
9. Manage documents
10. Use workflow inbox, tasks, and approvals
11. Use notifications
12. Use global search / command palette
13. View activity and audit timeline
14. Confirm permissions behave correctly
15. Confirm UX is usable for an internal demo

---

## Alpha package contents

| Document | Purpose |
|----------|---------|
| [HEOS_ALPHA_SETUP.md](./HEOS_ALPHA_SETUP.md) | Local environment setup (Windows PowerShell) |
| [HEOS_ALPHA_SMOKE_TEST.md](./HEOS_ALPHA_SMOKE_TEST.md) | Manual pass/fail checklist |
| [HEOS_ALPHA_DEMO_GUIDE.md](./HEOS_ALPHA_DEMO_GUIDE.md) | Recommended demo storyline |
| [HEOS_ALPHA_KNOWN_ISSUES.md](./HEOS_ALPHA_KNOWN_ISSUES.md) | Placeholders and limitations |
| [HEOS_ALPHA_RELEASE_CHECKLIST.md](./HEOS_ALPHA_RELEASE_CHECKLIST.md) | Pre-tag checklist |
| [../architecture/HEOS_PLATFORM_ARCHITECTURE.md](../architecture/HEOS_PLATFORM_ARCHITECTURE.md) | Developer architecture reference |

---

## Internal health surface

The frontend exposes an internal readiness page at **`/alpha/health`** (authenticated). It summarizes runtime hydration, feature availability, and API context without requiring backend changes.

The home page includes a compact **Platform Alpha** card linking to `/alpha/health` and the administration console.

---

## Out of scope for Alpha

- AI / M8 platform
- Vertical business modules (HR, CRM, BarSoft, ScentMaker, AutoFarm, NollySoft, etc.)
- Workflow visual designer (frontend)
- Production deployment hardening
- Full charting libraries (placeholders only)

---

## Next steps after Alpha

1. Complete smoke test checklist with pass/fail notes
2. Commit pending P1-013 work (administration console)
3. Tag `v1.0.0-alpha.1`
4. Run internal demo sessions and collect feedback
5. Prioritize stabilization fixes before Beta
