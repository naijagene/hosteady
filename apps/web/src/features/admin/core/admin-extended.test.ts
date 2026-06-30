import { describe, expect, it } from 'vitest'
import { deriveRuntimeHealth, mergePlatformHealth } from './admin-health'
import { buildRuntimeDiagnostics } from './admin-diagnostics'
import { resolveActivityRoute } from '@/features/activity/core/activity-actions'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

describe('extended admin health', () => {
  it('marks runtime warning when warnings exist', () => {
    const runtime = {
      warnings: ['Personalization tables missing'],
      personalizationRuntime: { runtime_context: { missing_tables: ['favorites'] } },
    } as unknown as HydratedRuntimeBundle
    expect(deriveRuntimeHealth(runtime).status).toBe('warning')
  })

  it('prefers runtime warning over healthy backend health', () => {
    const merged = mergePlatformHealth(
      { status: 'healthy', source: 'backend' },
      { status: 'warning', summary: 'Warnings', source: 'runtime' },
    )
    expect(merged.status).toBe('warning')
  })
})

describe('extended runtime diagnostics', () => {
  it('includes all required diagnostic keys', () => {
    const keys = buildRuntimeDiagnostics({ source: 'heos_runtime' } as HydratedRuntimeBundle).map((item) => item.key)
    expect(keys).toContain('notifications')
    expect(keys).toContain('activity')
  })
})

describe('admin accessibility labels', () => {
  it('uses semantic route labels for entity history fallback', () => {
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
