# HEOS Platform Roadmap

Last updated: 2026-06-23

---

## Current Status

| Layer | Version | Status |
|-------|---------|--------|
| Backend platform | **v1.0** | **Locked** — see [`releases/HEOS_BACKEND_PLATFORM_V1.md`](releases/HEOS_BACKEND_PLATFORM_V1.md) |
| Live experience (frontend) | P1 | In progress |
| AI platform | M8 | Paused |

**Backend validation:** 1,875 tests passed · 5,273 assertions

---

## Completed — Backend (M1–M7)

### M1 — Platform Core
Auth (Sanctum), tenant context middleware, module system, `heos:doctor`

### M2 — Organization Domain
Organizations, memberships, invitations, provisioning

### M3 — Workspace & Applications
Workspaces, application catalog, installations, settings, workspace runtime

### M4 — Enterprise Services (Phase 1)
Audit, notifications, reference data

### M5 — Enterprise Services (Phase 2)
Files, jobs, scheduled tasks, search, workflows

### M6 — Integration & Rules
Enterprise event bus, business rules engine

### M7 — Experience Framework (Backend)
- UI metadata & layout
- Navigation menu designer
- Theme, branding & design system
- Personalization & user experience framework

---

## Active — P1 HEOS Live Experience Platform

Build the production frontend that consumes the v1.0 backend API.

| ID | Scope | Status |
|----|-------|--------|
| **P1-001** | Frontend foundation & application shell | In progress |
| P1-002+ | Auth, runtime hydration, navigation, theming, modules | Planned |

**Stack (P1):** React, TypeScript, Vite, Tailwind, TanStack Router, TanStack Query, Zustand, Axios, React Hook Form, shadcn/ui-ready structure

**Location:** `apps/web`

---

## Paused

### M8 — AI Platform
Paused until the P1 live platform experience is validated end-to-end.

---

## Principles

1. Backend v1.0 is locked; frontend work integrates via published API contracts.
2. No hardcoded business data where an API contract exists.
3. Table health guards and safe defaults remain required for all runtime paths.
4. AI/M8 does not start until P1 foundation is validated.

---

## Release Documents

| Release | Document |
|---------|----------|
| Backend v1.0 | [`HEOS_BACKEND_PLATFORM_V1.md`](releases/HEOS_BACKEND_PLATFORM_V1.md) |
