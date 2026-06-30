# HEOS Alpha Demo Guide

Internal demo script for Platform Alpha. **Do not share real passwords in this document.**

---

## What is HEOS?

HEOS is an enterprise platform foundation: multi-tenant backend (M1–M7) plus a metadata-driven live experience frontend (P1). It provides authentication, organization/workspace runtime, dynamic UI rendering, enterprise services (documents, workflows, notifications, search, audit), and an administration console — without vertical business applications.

---

## What Alpha means

Alpha confirms the **platform works end-to-end** for internal teams. It is not production-ready. Expect placeholders, read-only admin settings, and manual demo data setup.

---

## What is working

- Full application shell with auth guards
- Runtime hydration (theme, navigation, personalization, permissions)
- Metadata page renderer
- Dynamic forms, tables, dashboards, reports
- Document manager
- Workflow inbox, tasks, approvals
- Notification center
- Global search / command palette
- Activity & audit timeline
- Administration console
- Alpha health page (`/alpha/health`)

---

## What is not included yet

- AI platform (M8) — paused
- Business modules (HR, CRM, BarSoft, ScentMaker, AutoFarm, NollySoft)
- Workflow visual designer (frontend)
- Real chart libraries (placeholders only)
- Full file preview (metadata-only)
- Production deployment automation

---

## Demo users (placeholders)

Use credentials provisioned in your environment. Placeholder examples:

| Role | Email placeholder | Password placeholder |
|------|-------------------|-------------------|
| Platform admin | `admin@demo.local` | *(set during provisioning)* |
| Standard user | `user@demo.local` | *(set during provisioning)* |
| Seeded test user | `test@example.com` | *(from factory — set password via tinker if needed)* |

**Never document or demo real production passwords.**

---

## Recommended demo flow (~30–45 minutes)

### 1. Login
Show Sanctum login, session persistence, and logout.

### 2. Runtime shell
Highlight organization/workspace context, theme, navigation, and home metrics.

### 3. Admin console
Navigate `/admin` → platform overview, permissions browser, runtime diagnostics.

### 4. Search / command palette
Search for admin, documents, workflows; demonstrate quick navigation.

### 5. Metadata page
Open a UI metadata page; explain component binding renderer.

### 6. Form
Open a dynamic form; show validation and submission.

### 7. Table
Browse records; show filters/pagination if configured.

### 8. Dashboard
Show dashboard widgets; note chart placeholders.

### 9. Report
Open report viewer; mention export placeholder if applicable.

### 10. Document manager
Browse/upload documents; note preview limitations.

### 11. Workflow inbox
Show assigned tasks and pending approvals.

### 12. Notification center
Show unified feed and detail view.

### 13. Activity / audit timeline
Show activity center and audit viewer.

### 14. Alpha health
Open `/alpha/health` and home Alpha card for readiness summary.

---

## Demo storyline (narrative)

> "HEOS is the platform layer beneath future business apps. We log in, hydrate tenant runtime once, and the shell drives everything — navigation, theme, permissions. Metadata defines pages without redeploying the frontend. Enterprise services (documents, workflows, notifications, search, audit) are first-class. Alpha proves the full stack is demo-ready for internal validation."

---

## Feedback questions for testers

1. Was login and org/workspace selection clear?
2. Did navigation match your expectations?
3. Were empty states understandable?
4. Did permission restrictions behave correctly?
5. Which module felt most/least polished?
6. Any blockers for daily internal use?
7. What demo data would help next iteration?

---

## Known limitations

See [HEOS_ALPHA_KNOWN_ISSUES.md](./HEOS_ALPHA_KNOWN_ISSUES.md) for the full table. Key demo callouts:

- Demo org/workspace may require manual provisioning
- Some admin panels are read-only
- Chart widgets are placeholders
- Export flows may be stubbed

---

## Related documents

- [Alpha Setup](./HEOS_ALPHA_SETUP.md)
- [Smoke Test](./HEOS_ALPHA_SMOKE_TEST.md)
- [Release Checklist](./HEOS_ALPHA_RELEASE_CHECKLIST.md)
