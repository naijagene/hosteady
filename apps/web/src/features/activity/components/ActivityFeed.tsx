import type { ActivityEntry } from '@/api/types/activity'
import { ActivityEmptyState } from './ActivityEmptyState'
import { ActivityEntryCard } from './ActivityEntryCard'
import { ActivityErrorState } from './ActivityErrorState'
import { ActivityLoadingState } from './ActivityLoadingState'

interface ActivityFeedProps {
  items: ActivityEntry[]
  isLoading?: boolean
  error?: string | null
  onSelect?: (entry: ActivityEntry) => void
}

export function ActivityFeed({ items, isLoading = false, error = null, onSelect }: ActivityFeedProps) {
  if (isLoading) return <ActivityLoadingState />
  if (error) return <ActivityErrorState message={error} />
  if (items.length === 0) return <ActivityEmptyState />

  return (
    <div className="space-y-3" data-testid="activity-feed">
      {items.map((entry) => (
        <ActivityEntryCard key={entry.public_id} entry={entry} onSelect={onSelect} />
      ))}
    </div>
  )
}
