import { describe, expect, it } from 'vitest'
import type { ActivityEntry } from '@/api/types/activity'
import { createInitialActivityQuery, mergeActivityQuery, shouldQueryActivity } from './activity-query'
import { applyActivityFilters, getTabQueryPatch, serializeActivityFilters } from './activity-filters'
import { applyActivitySort, getDefaultActivitySort } from './activity-sorts'
import { resolveActivityIcon } from './activity-icons'
import { getSeverityLabel, getSeverityTone } from './activity-severity'
import { resolveActivityAction, resolveActivityRoute } from './activity-actions'
import { canReadActivity, canReadAudit, filterActivityByPermission } from './activity-permissions'
import { activityRoutes } from './activity-navigation'
import { buildTimeline, groupActivityByDate, sortActivityEntries } from './activity-timeline'
import { getChangeSetSummary, sanitizeChangeValue } from './activity-diff'
import { formatActivityDateKey, getActivityTitle } from './activity-normalizer'
import { buildRuntimeActivityPlaceholders } from './activity-runtime'
import { toActivityQueryError } from './activity-errors'
import { ApiError } from '@/api/errors'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

const entry = (overrides: Partial<ActivityEntry> = {}): ActivityEntry => ({
  public_id: '1',
  action: 'updated',
  summary: 'Document updated',
  severity: 'info',
  entity: { type: 'document', public_id: 'doc-1', label: 'Invoice' },
  source: 'backend',
  occurred_at: '2024-01-01T10:00:00.000Z',
  ...overrides,
})

describe('activity query core', () => {
  it('creates initial query payload', () => {
    expect(createInitialActivityQuery().per_page).toBe(25)
  })

  it('merges query patches', () => {
    expect(mergeActivityQuery(createInitialActivityQuery(), { search: 'doc' }).search).toBe('doc')
  })

  it('detects searchable queries', () => {
    expect(shouldQueryActivity('doc')).toBe(true)
    expect(shouldQueryActivity('   ')).toBe(false)
  })
})

describe('activity filters and sorts', () => {
  it('applies filters to payload', () => {
    const payload = applyActivityFilters(createInitialActivityQuery(), [{ key: 'severity', value: 'critical' }])
    expect(payload.filters).toHaveLength(1)
  })

  it('serializes filters', () => {
    expect(serializeActivityFilters([{ key: 'action', value: 'updated' }])).toEqual({ action: 'updated' })
  })

  it('returns tab query patches', () => {
    expect(getTabQueryPatch('documents').entity_type).toBe('document')
    expect(getTabQueryPatch('security').severity).toBe('critical')
  })

  it('applies default sort', () => {
    expect(getDefaultActivitySort()?.[0]?.key).toBe('occurred_at')
    expect(applyActivitySort(createInitialActivityQuery(), 'occurred_at').sorts?.[0]?.direction).toBe('desc')
  })
})

describe('activity icons and severity', () => {
  it('maps icons by entity type', () => {
    expect(resolveActivityIcon('document')).toBe('document')
    expect(resolveActivityIcon('unknown')).toBe('activity')
  })

  it('maps severity labels and tones', () => {
    expect(getSeverityLabel('critical')).toBe('Critical')
    expect(getSeverityTone('warning')).toBe('warning')
  })
})

describe('activity actions and routes', () => {
  it('resolves document routes', () => {
    expect(resolveActivityRoute(entry())).toBe('/documents/doc-1')
  })

  it('resolves workflow task routes', () => {
    expect(resolveActivityRoute(entry({ entity: { type: 'task', public_id: 'task-1' } }))).toBe('/workflows/tasks/task-1')
  })

  it('resolves entity history routes', () => {
    expect(activityRoutes.entity('document', 'doc-1')).toBe('/activity/document/doc-1')
  })

  it('returns navigate action', () => {
    expect(resolveActivityAction(entry()).action_key).toBe('open_resource')
  })
})

describe('activity permissions', () => {
  it('allows read when permissions empty', () => {
    expect(canReadActivity([])).toBe(true)
    expect(canReadAudit([])).toBe(true)
  })

  it('requires audit.read when permissions present', () => {
    expect(canReadAudit(['activity.read'])).toBe(false)
    expect(canReadAudit(['audit.read'])).toBe(true)
  })

  it('filters entries by permission metadata', () => {
    const filtered = filterActivityByPermission(
      [entry({ permission: 'documents.read' }), entry({ public_id: '2', permission: 'admin.only' })],
      ['documents.read'],
    )
    expect(filtered).toHaveLength(1)
  })
})

describe('activity timeline', () => {
  it('sorts entries chronologically', () => {
    const sorted = sortActivityEntries([
      entry({ public_id: 'old', occurred_at: '2024-01-01T00:00:00.000Z' }),
      entry({ public_id: 'new', occurred_at: '2024-01-02T00:00:00.000Z' }),
    ])
    expect(sorted[0].public_id).toBe('new')
  })

  it('groups entries by date', () => {
    const groups = groupActivityByDate([entry(), entry({ public_id: '2', occurred_at: '2024-01-02T00:00:00.000Z' })])
    expect(groups.length).toBeGreaterThan(0)
  })

  it('builds compact timeline', () => {
    const groups = buildTimeline(Array.from({ length: 8 }, (_, index) => entry({ public_id: String(index) })), 'compact')
    expect(groups[0].items.length).toBeLessThanOrEqual(5)
  })

  it('formats date keys safely', () => {
    expect(formatActivityDateKey(undefined)).toBe('Unknown date')
  })
})

describe('activity diff viewer helpers', () => {
  it('sanitizes sensitive values', () => {
    expect(sanitizeChangeValue('secret', true)).toBe('[redacted]')
  })

  it('summarizes change sets', () => {
    expect(getChangeSetSummary([{ field: 'title' }])).toBe('1 field changed')
  })
})

describe('activity runtime placeholders', () => {
  it('builds notification placeholder from runtime', () => {
    const runtime = {
      unreadNotificationCount: 2,
      personalizationRuntime: { recent_items: [] },
    } as unknown as HydratedRuntimeBundle
    const items = buildRuntimeActivityPlaceholders(runtime)
    expect(items.some((item) => item.action === 'notifications.unread')).toBe(true)
  })
})

describe('activity errors', () => {
  it('maps ApiError', () => {
    const error = toActivityQueryError(new ApiError('Forbidden', { status: 403 }))
    expect(error.status).toBe(403)
  })
})

describe('activity normalizer', () => {
  it('returns activity title', () => {
    expect(getActivityTitle(entry())).toBe('Document updated')
  })
})
