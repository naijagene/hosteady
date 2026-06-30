import type { ActivityBindingContext } from '@/api/types/activity'
import { useActivityFeed } from '../hooks/useActivityFeed'
import { ActivityFeed } from '../components/ActivityFeed'

interface ActivityFeedWidgetProps {
  title?: string
  binding?: ActivityBindingContext
}

export function ActivityFeedWidget({ title = 'Recent activity', binding }: ActivityFeedWidgetProps) {
  const feed = useActivityFeed({ per_page: binding?.per_page ?? 6, entity_type: binding?.entity_type, entity_public_id: binding?.entity_public_id })

  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="activity-feed-widget">
      <h2 className="mb-3 text-sm font-semibold text-foreground">{title}</h2>
      <ActivityFeed items={feed.items.slice(0, binding?.per_page ?? 6)} isLoading={feed.isLoading} error={feed.error?.message ?? null} />
    </section>
  )
}
