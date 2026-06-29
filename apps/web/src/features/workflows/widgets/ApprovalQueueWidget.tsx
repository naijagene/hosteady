import type { DashboardWidgetComponentProps } from '@/features/dashboards/widgets/types'
import { WorkflowInbox } from '../components/WorkflowInbox'

export function ApprovalQueueWidget({ widget }: DashboardWidgetComponentProps) {
  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="approval-queue-widget">
      <WorkflowInbox
        title={widget.label ?? 'Pending approvals'}
        binding={{
          mode: 'approvals',
          show_counts: true,
          actions_enabled: true,
          comments_enabled: false,
          per_page: 5,
        }}
      />
    </section>
  )
}
