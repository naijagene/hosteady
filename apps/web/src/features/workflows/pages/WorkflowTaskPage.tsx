import { useParams } from '@tanstack/react-router'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { WorkflowTaskDetailDrawer } from '../components/WorkflowTaskDetailDrawer'
import { WorkflowErrorState } from '../components/WorkflowErrorState'
import { WorkflowLoadingState } from '../components/WorkflowLoadingState'
import { useHumanTask } from '../hooks/useHumanTasks'

export function WorkflowTaskPage() {
  const params = useParams({ strict: false })
  const taskPublicId = typeof params.taskPublicId === 'string' ? params.taskPublicId : null
  const runtime = useHydratedRuntime()
  const { task, query, error } = useHumanTask(taskPublicId)

  if (query.isLoading) {
    return <WorkflowLoadingState />
  }

  if (error) {
    return <WorkflowErrorState message={error.message} />
  }

  return (
    <div className="mx-auto w-full max-w-5xl">
      <WorkflowTaskDetailDrawer
        task={task}
        open={Boolean(task)}
        permissions={runtime?.permissions ?? []}
        onClose={() => undefined}
      />
    </div>
  )
}
