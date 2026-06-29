import { useParams } from '@tanstack/react-router'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { WorkflowInstanceDetail } from '../components/WorkflowInstanceDetail'
import { useWorkflowInstance } from '../hooks/useWorkflowInstances'

export function WorkflowInstancePage() {
  const params = useParams({ strict: false })
  const instancePublicId = typeof params.instancePublicId === 'string' ? params.instancePublicId : null
  const runtime = useHydratedRuntime()
  const { instance, history, error, instanceQuery, refresh } = useWorkflowInstance(instancePublicId)

  return (
    <div className="mx-auto w-full max-w-5xl rounded-lg border border-border bg-card p-5">
      <WorkflowInstanceDetail
        instance={instance}
        history={history}
        permissions={runtime?.permissions ?? []}
        isLoading={instanceQuery.isLoading}
        error={error}
        onRefresh={refresh}
      />
    </div>
  )
}
