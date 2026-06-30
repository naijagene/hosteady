import { describe, expect, it } from 'vitest'
import { normalizeAuditEntry, normalizeHistoryEntry } from '@/api/types/activity'
import { resolveActivityRoute } from './activity-actions'
import { buildTimeline } from './activity-timeline'
import { sanitizeChangeValue } from './activity-diff'

describe('extended audit normalization', () => {
  it('normalizes workflow history style payloads', () => {
    const entry = normalizeHistoryEntry({
      event_type: 'task.completed',
      occurred_at: '2024-01-01',
      summary: 'Task completed',
      metadata: { task_public_id: 'task-1' },
    })
    expect(entry.action).toBe('task.completed')
    expect(entry.summary).toBe('Task completed')
  })

  it('normalizes audit entries with module context', () => {
    const entry = normalizeAuditEntry({
      public_id: 'a-1',
      action: 'security.login',
      module_key: 'platform',
      severity: 'critical',
      context: { request_id: 'req-1' },
    })
    expect(entry.module_key).toBe('platform')
    expect(entry.request_id).toBe('req-1')
  })
})

describe('extended route resolution', () => {
  it('resolves report routes from metadata', () => {
    expect(
      resolveActivityRoute({
        public_id: '1',
        action: 'viewed',
        entity: { type: 'report' },
        metadata: { module_key: 'platform', report_key: 'summary' },
        source: 'backend',
      }),
    ).toBe('/reports/platform/summary')
  })

  it('resolves notification routes', () => {
    expect(
      resolveActivityRoute({
        public_id: '1',
        action: 'sent',
        entity: { type: 'notification', public_id: 'n-1' },
        source: 'backend',
      }),
    ).toBe('/notifications/n-1')
  })

  it('falls back to entity history route', () => {
    expect(
      resolveActivityRoute({
        public_id: '1',
        action: 'updated',
        entity: { type: 'custom', public_id: 'x-1' },
        source: 'backend',
      }),
    ).toBe('/activity/custom/x-1')
  })
})

describe('extended timeline and diff', () => {
  it('handles malformed entries without crashing', () => {
    const groups = buildTimeline([
      { public_id: 'ok', action: 'updated', source: 'backend' } as never,
      null as never,
      undefined as never,
    ])
    expect(groups.length).toBeGreaterThan(0)
  })

  it('stringifies object change values safely', () => {
    expect(sanitizeChangeValue({ title: 'A' })).toContain('title')
  })
})
