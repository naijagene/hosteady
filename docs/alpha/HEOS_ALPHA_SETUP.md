# HEOS Alpha Setup Guide

Windows PowerShell commands are included because current development targets Windows.

---

## Prerequisites

| Tool | Version | Notes |
|------|---------|-------|
| PHP | 8.2+ | Backend |
| Composer | 2.x | PHP dependencies |
| Node.js | 20+ | Frontend |
| npm | 10+ | Frontend package manager |
| SQLite | bundled | Default local DB (`apps/api/.env.example`) |

Optional: MySQL/PostgreSQL if you change `DB_CONNECTION` in backend `.env`.

---

## Repository layout

```
Hosteady/
├── apps/
│   ├── api/          # Laravel backend (HEOS Platform v1.0)
│   └── web/          # React frontend (P1 Live Experience)
└── docs/
    ├── alpha/        # Alpha validation docs
    └── architecture/ # Platform architecture
```

---

## Backend setup

```powershell
cd C:\Projects\Hosteady\apps\api
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan heos:doctor
php artisan serve
```

Default API URL: `http://localhost:8000`  
API base path: `http://localhost:8000/api/v1`

### Expected `heos:doctor` behavior

On a fresh local database, `heos:doctor` may report **warnings** such as:

- Missing optional personalization tables
- Unmigrated optional enterprise modules
- Empty organization/workspace context until provisioned

These warnings are informational for local dev. Review output; do not ignore crashes or migration failures.

### Default seeded user (placeholder)

`php artisan migrate --seed` creates:

| Field | Value |
|-------|-------|
| Email | `test@example.com` |
| Name | `Test User` |

**Note:** Seeders do **not** create a full demo organization/workspace graph. You may need to provision org/workspace via API or internal tooling before runtime hydration succeeds. See [Demo seed data review](./HEOS_ALPHA_OVERVIEW.md) and smoke test section 5.

---

## Frontend setup

```powershell
cd C:\Projects\Hosteady\apps\web
npm install
copy .env.example .env
npm run validate
npm run dev
```

Default dev URL: `http://localhost:5173`

### Environment

`apps/web/.env`:

```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

Ensure this matches the backend `php artisan serve` host/port.

---

## Validation commands

### Backend

```powershell
cd C:\Projects\Hosteady\apps\api
php artisan test
php artisan heos:doctor
```

### Frontend

```powershell
cd C:\Projects\Hosteady\apps\web
npm run lint
npm run typecheck
npm run test
npm run build
npm run validate
```

---

## Typical Alpha session flow

1. Start backend (`php artisan serve`)
2. Start frontend (`npm run dev`)
3. Open `http://localhost:5173/login`
4. Log in with seeded or provisioned credentials
5. Select organization and workspace (if prompted)
6. Confirm home page loads with runtime metrics
7. Open `/alpha/health` for readiness snapshot
8. Run through [HEOS_ALPHA_SMOKE_TEST.md](./HEOS_ALPHA_SMOKE_TEST.md)

---

## Troubleshooting

| Symptom | Likely cause | Action |
|---------|--------------|--------|
| 401 on API calls | Missing/ expired token | Log out and log in again |
| Empty runtime | Org/workspace not selected | Complete organization select flow |
| CORS errors | API URL mismatch | Check `VITE_API_BASE_URL` |
| Doctor warnings | Optional tables absent | Run migrations; review doctor output |
| Blank metadata pages | No UI metadata seeded | Provision workspace UI metadata via backend tooling |

---

## Related documents

- [Alpha Overview](./HEOS_ALPHA_OVERVIEW.md)
- [Smoke Test](./HEOS_ALPHA_SMOKE_TEST.md)
- [Demo Guide](./HEOS_ALPHA_DEMO_GUIDE.md)
- [Platform Architecture](../architecture/HEOS_PLATFORM_ARCHITECTURE.md)
