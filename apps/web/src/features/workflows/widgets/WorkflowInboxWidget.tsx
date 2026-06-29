import type { DashboardWidgetComponentProps } from '@/features/dashboards/widgets/types'
import { WorkflowInbox } from '../components/WorkflowInbox'

export function WorkflowInboxWidget({ widget }: DashboardWidgetComponentProps) {
  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="workflow-inbox-widget">
      <WorkflowInbox
        title={widget.label ?? 'Assigned tasks'}
        binding={{
          mode: 'compact',
          inbox_type: 'assigned',
          show_counts: true,
          actions_enabled: true,
          comments_enabled: false,
          per_page: 5,
        }}
      />
    </section>
  )
}
