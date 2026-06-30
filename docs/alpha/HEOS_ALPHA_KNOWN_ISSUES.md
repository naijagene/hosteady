# HEOS Alpha Known Issues

Tracked placeholders and deviations from P1 milestones. Use during smoke test and demo prep.

| ID | Area | Issue | Impact | Workaround | Target phase |
|----|------|-------|--------|------------|--------------|
| ALPHA-001 | AI | AI platform not implemented | No AI features | N/A — out of scope | M8 (paused) |
| ALPHA-002 | Business modules | HR, CRM, BarSoft, ScentMaker, AutoFarm, NollySoft not built | No vertical apps | Demo platform capabilities only | Post-Alpha modules |
| ALPHA-003 | Charts | Chart libraries not installed; chart placeholders only | Dashboard/report charts are stubs | Describe data via metrics/tables | P1-Beta |
| ALPHA-004 | API resilience | Some backend endpoints defensively optional | UI may show empty/fallback states | Use `/alpha/health` and admin diagnostics | Alpha stabilization |
| ALPHA-005 | Documents | File preview is metadata-only | No inline PDF/image preview | Download/open externally | P1-Beta |
| ALPHA-006 | Workflows | Workflow visual designer frontend not implemented | Design-time UX missing | Use backend/API workflow tools | Post-Alpha |
| ALPHA-007 | Actions | Some UI actions are placeholders | Button may no-op or show stub | Document in demo script | Alpha stabilization |
| ALPHA-008 | Admin | Some admin settings are read-only | Cannot edit org/workspace in UI | Use backend/API for changes | P1-Beta |
| ALPHA-009 | Export | Some export flows are placeholders | Export may not produce files | Manual export via API if needed | P1-Beta |
| ALPHA-010 | Activity | Some activity/history endpoints use fallbacks | Sparse timeline on fresh DB | Seed audit events or perform actions | Alpha demo seed |
| ALPHA-011 | Notifications | Some notification endpoints fallback to unified feed | Reduced channel granularity | Use notification center list | Alpha stabilization |
| ALPHA-012 | Demo data | `AlphaDemoSeeder` provisions org/users/app; metadata/workflow/document gaps remain | Partial demo coverage | Run seeder + manual steps in provisioning plan | Alpha stabilization |
| ALPHA-013 | Admin health | API latency on admin/alpha pages is placeholder | No live ping metrics | Manual network tab check | P1-Beta |
| ALPHA-014 | Roles | Role member counts in admin are placeholders | Counts not authoritative | Treat as browse-only | P1-Beta |
| ALPHA-015 | Search | Search index depends on backend data | Empty results on fresh DB | Create records/workflows first | Alpha demo seed |
| ALPHA-016 | Personalization | Optional personalization tables may be missing locally | Doctor warnings; degraded personalization | Run migrations; review doctor | Alpha setup |
| ALPHA-017 | Seeder gaps | Document/workflow/notification not fully auto-seeded | Manual demo setup required | Follow HEOS_ALPHA_PROVISIONING_PLAN.md | ALPHA-001 sprint |
| ALPHA-018 | Bundle size | Frontend production bundle >500 kB | Slower first load | Accept for Alpha; split later | P1-Beta |
| ALPHA-019 | Alpha health | Feature flags derived from permissions; may show warning for viewer | Accurate, not faked | Expected for read-only roles | Alpha validation |

---

## Reporting new issues

During Alpha validation, append rows with:

- Reproduction steps
- Expected vs actual
- Screenshot or console error
- Severity: blocker / major / minor

Do not commit secrets or real credentials in issue notes.
