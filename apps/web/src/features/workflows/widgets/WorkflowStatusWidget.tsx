import { useQuery } from '@tanstack/react-query'
import { fetchWorkflowInstances } from '@/api/endpoints/workflows'
import type { DashboardWidgetComponentProps } from '@/features/dashboards/widgets/types'
import { DashboardMetricCard } from '@/features/dashboards'
import { WorkflowInstanceList } from '../components/WorkflowInstanceList'
import { WorkflowLoadingState } from '../components/WorkflowLoadingState'

export function WorkflowStatusWidget({ widget }: DashboardWidgetComponentProps) {
  const query = useQuery({
    queryKey: ['workflow-status-widget'],
    queryFn: () => fetchWorkflowInstances({ status: 'running', per_page: 5 }),
  })

  const runningCount = query.data?.length ?? 0
  const failedQuery = useQuery({
    queryKey: ['workflow-status-widget-failed'],
    queryFn: () => fetchWorkflowInstances({ status: 'failed', per_page: 5 }),
  })

  return (
    <section className="space-y-4 rounded-lg border border-border bg-card p-4" data-testid="workflow-status-widget">
      <h3 className="text-sm font-medium text-foreground">{widget.label ?? 'Workflow status'}</h3>
      <div className="grid gap-3 sm:grid-cols-2">
        <DashboardMetricCard title="Running" value={query.isLoading ? '…' : String(runningCount)} />
        <DashboardMetricCard title="Failed" value={failedQuery.isLoading ? '…' : String(failedQuery.data?.length ?? 0)} />
      </div>
      {query.isLoading ? <WorkflowLoadingState /> : <WorkflowInstanceList instances={query.data ?? []} />}
    </section>
  )
}
