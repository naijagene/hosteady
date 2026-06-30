import { useActivityFeed } from '../hooks/useActivityFeed'
import { useActivityTimeline } from '../hooks/useActivityTimeline'
import { ActivityTimelineView } from '../components/ActivityTimeline'

export function SystemHistoryWidget() {
  const feed = useActivityFeed({ per_page: 8, category: 'system' })
  const timeline = useActivityTimeline(feed.items, 'compact')

  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="system-history-widget">
      <h2 className="mb-3 text-sm font-semibold text-foreground">System history</h2>
      <ActivityTimelineView groups={timeline.slice(0, 2)} />
    </section>
  )
}
