import { useParams } from '@tanstack/react-router'
import { useActivityActions } from '../hooks/useActivityActions'
import { useActivityFilters } from '../hooks/useActivityFilters'
import { useEntityHistory } from '../hooks/useEntityHistory'
import { useActivityTimeline } from '../hooks/useActivityTimeline'
import { ActivityDetailDrawer } from '../components/ActivityDetailDrawer'
import { ActivityEmptyState } from '../components/ActivityEmptyState'
import { ActivityErrorState } from '../components/ActivityErrorState'
import { ActivityLoadingState } from '../components/ActivityLoadingState'
import { ActivityTimelineView } from '../components/ActivityTimeline'
import { useState } from 'react'
import type { ActivityEntry } from '@/api/types/activity'
import { labelActivitySource } from '@/api/endpoints/activity'

export function EntityHistoryPage() {
  const params = useParams({ strict: false }) as { entityType?: string; entityPublicId?: string }
  const entityType = params.entityType ?? 'custom'
  const entityPublicId = params.entityPublicId ?? ''
  const { query } = useActivityFilters({ entity_type: entityType, entity_public_id: entityPublicId })
  const history = useEntityHistory(entityType, entityPublicId, query)
  const timeline = useActivityTimeline(history.items, 'full')
  const { openEntry } = useActivityActions()
  const [selected, setSelected] = useState<ActivityEntry | null>(null)

  return (
    <div className="space-y-4" data-testid="entity-history-page">
      <div>
        <h1 className="text-2xl font-semibold text-foreground">Entity history</h1>
        <p className="mt-1 text-sm text-muted-foreground">
          Timeline for {entityType} · {entityPublicId || 'unknown'} · {labelActivitySource(history.source)}
        </p>
      </div>

      {history.isLoading ? <ActivityLoadingState /> : null}
      {history.error ? <ActivityErrorState message={history.error.message} /> : null}
      {!history.isLoading && !history.error && history.items.length === 0 ? (
        <ActivityEmptyState title="No history for this resource" />
      ) : null}
      {!history.isLoading && !history.error && history.items.length > 0 ? (
        <ActivityTimelineView groups={timeline} onSelect={setSelected} />
      ) : null}

      <ActivityDetailDrawer
        entry={selected}
        open={Boolean(selected)}
        onClose={() => setSelected(null)}
        onOpenResource={(entry) => void openEntry(entry)}
      />
    </div>
  )
}
