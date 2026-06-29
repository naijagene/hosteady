# HEOS Web — P1-001 Frontend Foundation

React application shell for the HEOS Live Experience Platform.

## Stack

- React 19 + TypeScript
- Vite 8
- Tailwind CSS 4
- TanStack Router
- TanStack Query
- Zustand
- Axios
- React Hook Form
- Lucide icons (via `@/components/icons`)
- shadcn/ui-ready structure (`components.json`, `src/components/ui`)

## Structure

```
src/
  api/              API client, tenant headers, endpoint modules, contract types
  app/
    providers/      Query, theme, navigation providers
    router/         TanStack Router tree
  components/
    hds/            Hosteady design shell primitives
    icons/          Lucide icon re-exports
    ui/             shadcn/ui target directory
  features/
    auth/           Auth layout + login placeholder
    runtime/        Runtime loader + context
    shell/          Application shell + home placeholder
  hooks/
  lib/              env + cn utility
  stores/           Auth + tenant session (Zustand)
  styles/
```

## Environment

Copy `.env.example` to `.env.local`:

```
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

Tenant requests send `X-HEOS-Organization-Id`, `X-HEOS-Workspace-Id`, and optional `X-HEOS-Application-Id` from the session store.

## Validation

```bash
npm run lint
npm run typecheck
npm run build
npm run validate
```

## Scope (P1-001)

Foundation only — no business modules, no AI, no committed backend mock data. Runtime and auth flows connect to existing `/api/v1` contracts in later P1 tasks.
