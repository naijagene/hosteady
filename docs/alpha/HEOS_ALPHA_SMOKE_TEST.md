# HEOS Alpha Smoke Test

Manual validation checklist for Platform Alpha. Record **Pass / Fail** and notes for each section.

**Tester:** _______________  
**Date:** _______________  
**Environment:** Local / Staging  
**Backend commit:** _______________  
**Frontend commit:** _______________

---

## 1. Backend boot

| | |
|---|---|
| **Objective** | Confirm API starts and responds |
| **Steps** | Run `php artisan serve`; open `http://localhost:8000` or hit `/api/v1` health/context endpoint |
| **Expected** | Server starts without crash; API reachable |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 2. Frontend boot

| | |
|---|---|
| **Objective** | Confirm Vite dev server starts |
| **Steps** | Run `npm run dev`; open `http://localhost:5173` |
| **Expected** | App loads login or redirects appropriately |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 3. Login / logout

| | |
|---|---|
| **Objective** | Authenticate and terminate session |
| **Steps** | Log in with test credentials; navigate app; log out |
| **Expected** | Login succeeds; logout clears session and redirects to login |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 4. Session restore

| | |
|---|---|
| **Objective** | Session persists across refresh |
| **Steps** | Log in; refresh browser; confirm still authenticated |
| **Expected** | User remains logged in; no redirect loop |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 5. Organization / workspace context

| | |
|---|---|
| **Objective** | Tenant context is selected |
| **Steps** | Complete org/workspace selection if shown; verify home shows org and workspace names |
| **Expected** | Organization and workspace names visible; tenant headers sent on API calls |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 6. Runtime hydration

| | |
|---|---|
| **Objective** | Workspace runtime bundle loads |
| **Steps** | Open home or `/alpha/health`; check runtime status |
| **Expected** | Runtime loaded; permissions count > 0 or empty-array allow policy documented |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 7. Navigation rendering

| | |
|---|---|
| **Objective** | Navigation menus render from runtime |
| **Steps** | Inspect shell sidebar/top nav; click a menu item |
| **Expected** | Menus visible; links navigate without crash |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 8. Theme rendering

| | |
|---|---|
| **Objective** | Theme runtime applies |
| **Steps** | Verify branded colors/typography; check admin runtime diagnostics |
| **Expected** | Theme source shown; UI uses theme tokens |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 9. Personalization

| | |
|---|---|
| **Objective** | Favorites, recents, shortcuts load |
| **Steps** | View home personalization sections |
| **Expected** | Sections render (may be empty); no crash on missing tables |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 10. Administration console

| | |
|---|---|
| **Objective** | Admin sections accessible |
| **Steps** | Navigate `/admin`, `/admin/permissions`, `/admin/runtime` |
| **Expected** | Overview, org, workspace, roles, permissions, health panels render |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 11. Metadata page rendering

| | |
|---|---|
| **Objective** | UI metadata pages render components |
| **Steps** | Open a metadata page from navigation |
| **Expected** | Page layout renders; unknown components show safe fallback |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 12. Dynamic forms

| | |
|---|---|
| **Objective** | Form loads and submits |
| **Steps** | Open a form route; fill required fields; submit |
| **Expected** | Validation works; submission succeeds or shows API error clearly |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 13. Dynamic tables

| | |
|---|---|
| **Objective** | Table loads records |
| **Steps** | Open a table route; paginate/filter if available |
| **Expected** | Columns and rows render; empty state if no data |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 14. Dashboards

| | |
|---|---|
| **Objective** | Dashboard widgets render |
| **Steps** | Open dashboard route or home widgets |
| **Expected** | Metrics/widgets visible; chart placeholders acceptable |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 15. Reports

| | |
|---|---|
| **Objective** | Report viewer loads |
| **Steps** | Open report route; attempt export if available |
| **Expected** | Report renders; export may be placeholder |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 16. Documents

| | |
|---|---|
| **Objective** | Document manager works |
| **Steps** | Open `/documents`; upload or browse if data exists |
| **Expected** | List/detail views work; preview may be metadata-only |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 17. Workflows / tasks / approvals

| | |
|---|---|
| **Objective** | Workflow center operational |
| **Steps** | Open `/workflows`; check inbox, tasks, approvals |
| **Expected** | Lists render; detail pages navigable |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 18. Notifications

| | |
|---|---|
| **Objective** | Notification center works |
| **Steps** | Open `/notifications`; read/mark items |
| **Expected** | Feed loads; detail view works |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 19. Global search / command palette

| | |
|---|---|
| **Objective** | Search discovers routes and actions |
| **Steps** | Open search (`/search` or palette shortcut); search for admin, documents, workflows |
| **Expected** | Results include platform routes; navigation works |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 20. Activity / audit timeline

| | |
|---|---|
| **Objective** | Activity center and audit views work |
| **Steps** | Open `/activity`, `/audit`; check entity history if available |
| **Expected** | Timeline renders; empty state if no events |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 21. Permissions

| | |
|---|---|
| **Objective** | Permission guards behave correctly |
| **Steps** | Access restricted route without permission; verify forbidden/unauthorized handling |
| **Expected** | Guard blocks access; empty permission array allows (platform convention) |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 22. Error handling

| | |
|---|---|
| **Objective** | Errors surface gracefully |
| **Steps** | Trigger API error (invalid route, network off briefly) |
| **Expected** | Error state shown; no white screen |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 23. Empty states

| | |
|---|---|
| **Objective** | Empty data handled |
| **Steps** | View modules with no seeded data |
| **Expected** | Empty state components/messages shown |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 24. Responsive layout

| | |
|---|---|
| **Objective** | Layout usable at common breakpoints |
| **Steps** | Resize to mobile/tablet widths |
| **Expected** | Shell usable; no major overlap |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 25. Accessibility basics

| | |
|---|---|
| **Objective** | Basic a11y checks |
| **Steps** | Tab through login, nav, search; check aria labels on key controls |
| **Expected** | Focus visible; labeled inputs on forms/search |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## 26. Performance observations

| | |
|---|---|
| **Objective** | Subjective performance check |
| **Steps** | Navigate between 5+ routes; note load times |
| **Expected** | Acceptable for internal demo; note any >3s loads |

| Pass / Fail | Notes |
|-------------|-------|
| ☐ Pass ☐ Fail | |

---

## Summary

| Section | Pass | Fail |
|---------|------|------|
| Total | ___ / 26 | ___ / 26 |

**Overall Alpha smoke test result:** ☐ Pass ☐ Fail (with exceptions) ☐ Blocked

**Blockers:**

1. ___
2. ___

**Sign-off:** _______________
