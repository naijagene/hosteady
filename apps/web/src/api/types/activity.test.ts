import { describe, expect, it } from 'vitest'
import {
  buildActivityQueryRequest,
  normalizeActivityBindingContext,
  normalizeActivityEntry,
  normalizeActivityQueryResult,
  normalizeAuditEntry,
  normalizeEntityType,
  normalizeHistoryEntry,
  normalizeSeverity,
} from '@/api/types/activity'

describe('activity type normalization', () => {
  it('normalizes snake_case audit payloads', () => {
    const entry = normalizeActivityEntry({
      public_id: 'evt-1',
      occurred_at: '2024-01-01T00:00:00.000Z',
      action: 'document.updated',
      summary: 'Document updated',
      severity: 'warning',
      actor: { display_name: 'Alex', user_public_id: 'u-1' },
      entity: { type: 'document', public_id: 'doc-1', label: 'Invoice' },
      changes: { before: { title: 'A' }, after: { title: 'B' } },
    })
    expect(entry.public_id).toBe('evt-1')
    expect(entry.entity?.type).toBe('document')
    expect(entry.changes).toHaveLength(1)
  })

  it('normalizes camelCase activity payloads', () => {
    const entry = normalizeActivityEntry({
      publicId: 'evt-2',
      occurredAt: '2024-01-02',
      action: 'updated',
      displayName: 'Ignored',
      entityType: 'workflow',
      entityPublicId: 'wf-1',
    })
    expect(entry.public_id).toBe('evt-2')
    expect(entry.entity?.type).toBe('workflow')
  })

  it('normalizes audit entries with context', () => {
    const entry = normalizeAuditEntry({
      public_id: 'audit-1',
      action: 'login',
      context: { ip_address: '127.0.0.1', user_agent: 'TestAgent' },
    })
    expect(entry.ip_address).toBe('127.0.0.1')
    expect(entry.user_agent).toBe('TestAgent')
  })

  it('normalizes history entries', () => {
    const entry = normalizeHistoryEntry({ public_id: 'h-1', change_type: 'version', version: 3, action: 'updated' })
    expect(entry.version).toBe(3)
  })

  it('normalizes query results', () => {
    const result = normalizeActivityQueryResult({
      data: [{ public_id: '1', action: 'created', summary: 'Created' }],
      meta: { total: 1, per_page: 25 },
    })
    expect(result.items).toHaveLength(1)
    expect(result.total).toBe(1)
  })

  it('builds activity query request', () => {
    const request = buildActivityQueryRequest({
      page: 2,
      per_page: 10,
      search: 'invoice',
      entity_type: 'document',
      metadata: { source: 'web', binding: 'activity_center' },
    })
    expect(request.page).toBe(2)
    expect(request.entity_type).toBe('document')
  })

  it('normalizes binding context', () => {
    const binding = normalizeActivityBindingContext({ mode: 'audit', showFilters: false, perPage: 10 })
    expect(binding.mode).toBe('audit')
    expect(binding.show_filters).toBe(false)
    expect(binding.per_page).toBe(10)
  })

  it('normalizes entity and severity helpers', () => {
    expect(normalizeEntityType('DOCUMENT')).toBe('document')
    expect(normalizeSeverity('CRITICAL')).toBe('critical')
  })

  it('marks sensitive change fields', () => {
    const entry = normalizeActivityEntry({
      public_id: 's-1',
      action: 'updated',
      changes: { before: { password: 'old' }, after: { password: 'new' } },
    })
    expect(entry.changes?.[0]?.sensitive).toBe(true)
  })
})
