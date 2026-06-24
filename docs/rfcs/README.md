# Hosteady Request for Comments (RFCs)

Hosteady uses RFCs to document significant architectural and platform decisions for the Hosteady Enterprise Operating System (HEOS).

---

## What is an RFC?

A **Request for Comments (RFC)** is a written proposal that captures:

- The problem or context being addressed
- The decision taken (or proposed)
- The scope and constraints of that decision
- Consequences for engineering, operations, and future work

RFCs exist so that architectural choices are **discussable, reviewable, and durable**. Once accepted, an RFC becomes the reference point for implementation, code review, and onboarding.

RFCs are not user documentation or runbooks. They are **engineering decision records** for the platform.

---

## How Hosteady uses RFCs

| Stage | Purpose |
|-------|---------|
| **Before implementation** | Align architects, leads, and reviewers on intent and boundaries |
| **During implementation** | Guide migrations, models, APIs, and tests |
| **After acceptance** | Archive the decision; link from code, PRs, and milestone plans |
| **When superseded** | Preserve history; point readers to the replacement RFC |

### Workflow

1. **Author** drafts an RFC with status `Draft`.
2. **Review** moves the RFC to `Proposed` and collects feedback from engineering and leadership.
3. **Approval** sets status to `Accepted` — implementation may proceed.
4. **Changes** to an accepted RFC require either an amendment section in the same document (minor) or a new RFC that supersedes the original (major).

Implementation plans, migration slices, and ADRs may reference RFCs but do not replace them.

---

## RFC status values

| Status | Meaning |
|--------|---------|
| **Draft** | Work in progress. Not ready for review. Must not drive production implementation. |
| **Proposed** | Ready for review and comment. Implementation may be planned but should not merge without acceptance. |
| **Accepted** | Approved by Hosteady engineering leadership. Authoritative for the stated scope. |
| **Superseded** | Replaced by a newer RFC. Retained for history; readers must follow the superseding document. |
| **Rejected** | Considered and declined. Retained to avoid revisiting the same decision without new context. |

Each RFC file must declare its status in the document header. Status changes are recorded in the revision history.

---

## Naming convention

RFC files live in `docs/rfcs/` and follow this pattern:

```
RFC-{NNNN}-{short-kebab-title}.md
```

| Part | Rule | Example |
|------|------|---------|
| `NNNN` | Zero-padded sequence, starting at `0001` | `0001`, `0002`, `0010` |
| `short-kebab-title` | Lowercase words separated by hyphens | `organization-domain` |
| Extension | Always `.md` | |

**Examples:**

- `RFC-0001-organization-domain.md`
- `RFC-0002-tenant-context-middleware.md`

### Numbering rules

- Numbers are assigned **sequentially** and never reused.
- A superseding RFC receives a **new number**; the old RFC is marked `Superseded` with a link to the replacement.
- Related documents (implementation plans, slice specs) reference the RFC number in prose but use their own naming elsewhere under `docs/`.

---

## Index

| RFC | Title | Status |
|-----|-------|--------|
| [RFC-0001](./RFC-0001-organization-domain.md) | Organization Domain Model | Accepted |

---

## Related documentation

- [M3 Organization Domain Model (architecture blueprint)](../architecture/m3-organization-domain-model.md) — detailed schema reference aligned with RFC-0001
- [Company engineering principles](../company/engineering-principles.md)
