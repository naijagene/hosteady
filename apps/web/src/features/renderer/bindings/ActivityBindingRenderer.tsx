import type { UiComponent } from '@/api/types/ui'
import { normalizeActivityBindingContext } from '@/api/types/activity'
import { ActivityCenter } from '@/features/activity/components/ActivityCenter'
import { ActivityFeedWidget } from '@/features/activity/widgets/ActivityFeedWidget'
import { AuditViewer } from '@/features/activity/components/AuditViewer'
import { useAuditLog } from '@/features/activity/hooks/useAuditLog'
import { useActivityFeed } from '@/features/activity/hooks/useActivityFeed'
import { useEntityHistory } from '@/features/activity/hooks/useEntityHistory'
import { useActivityTimeline } from '@/features/activity/hooks/useActivityTimeline'
import { ActivityTimelineView } from '@/features/activity/components/ActivityTimeline'

interface ActivityBindingRendererProps {
  component: UiComponent
}

export function ActivityBindingRenderer({ component }: ActivityBindingRendererProps) {
  const binding = normalizeActivityBindingContext(component.binding_config)
  const compact = binding.mode === 'compact'
  const feed = useActivityFeed({
    per_page: binding.per_page,
    entity_type: binding.entity_type,
    entity_public_id: binding.entity_public_id,
    severity: binding.severity_filter,
    action: binding.action_filter,
  })
  const audit = useAuditLog({
    per_page: binding.per_page,
    severity: binding.severity_filter,
    action: binding.action_filter,
  })
  const history = useEntityHistory(binding.entity_type ?? 'custom', binding.entity_public_id ?? '', {
    per_page: binding.per_page,
  })
  const timeline = useActivityTimeline(
    binding.mode === 'history' ? history.items : feed.items,
    compact ? 'compact' : 'full',
  )

  if (binding.mode === 'audit') {
    return (
      <div data-testid="activity-binding-renderer">
        <AuditViewer items={audit.items} isLoading={audit.isLoading} error={audit.error?.message ?? null} />
      </div>
    )
  }

  if (binding.mode === 'history' && binding.entity_public_id) {
    return (
      <div data-testid="activity-binding-renderer">
        <ActivityTimelineView groups={timeline} compact={compact} />
      </div>
    )
  }

  if (binding.mode === 'timeline') {
    return (
      <div data-testid="activity-binding-renderer">
        <ActivityTimelineView groups={timeline} compact={compact} />
      </div>
    )
  }

  if (binding.mode === 'compact' || binding.mode === 'feed') {
    return (
      <div data-testid="activity-binding-renderer">
        <ActivityFeedWidget title={component.name} binding={binding} />
      </div>
    )
  }

  return (
    <div data-testid="activity-binding-renderer">
      <ActivityCenter title={component.name} />
    </div>
  )
}
