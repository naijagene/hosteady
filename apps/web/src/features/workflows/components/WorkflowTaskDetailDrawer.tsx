import { useQuery } from '@tanstack/react-query'
import { fetchHumanTaskHistory } from '@/api/endpoints/workflows'
import type { HumanTask } from '@/api/types/workflows'
import { formatWorkflowDate, getTaskDisplayTitle } from '../core/workflow-normalizer'
import { useTaskActions } from '../hooks/useTaskActions'
import { useWorkflowComments } from '../hooks/useWorkflowComments'
import { WorkflowCommentComposer } from './WorkflowCommentComposer'
import { WorkflowCommentList } from './WorkflowCommentList'
import { WorkflowErrorState } from './WorkflowErrorState'
import { WorkflowLoadingState } from './WorkflowLoadingState'
import { WorkflowStatusBadge } from './WorkflowStatusBadge'
import { WorkflowTaskActions } from './WorkflowTaskActions'
import { WorkflowTimeline } from './WorkflowTimeline'

interface WorkflowTaskDetailDrawerProps {
  task: HumanTask | null
  open: boolean
  permissions: string[]
  actionsEnabled?: boolean
  commentsEnabled?: boolean
  onClose: () => void
}

export function WorkflowTaskDetailDrawer({
  task,
  open,
  permissions,
  actionsEnabled = true,
  commentsEnabled = true,
  onClose,
}: WorkflowTaskDetailDrawerProps) {
  const taskActions = useTaskActions()
  const comments = useWorkflowComments(task?.public_id)

  const historyQuery = useQuery({
    queryKey: ['human-task-history', task?.public_id],
    queryFn: () => fetchHumanTaskHistory(task!.public_id),
    enabled: Boolean(task?.public_id && open),
  })

  if (!open || !task) {
    return null
  }

  return (
    <aside
      className="fixed inset-y-0 right-0 z-40 w-full max-w-xl border-l border-border bg-background p-5 shadow-lg"
      data-testid="workflow-task-detail-drawer"
      aria-label={`Task detail ${getTaskDisplayTitle(task)}`}
    >
      <div className="flex items-start justify-between gap-3">
        <div>
          <h2 className="text-lg font-semibold text-foreground">{getTaskDisplayTitle(task)}</h2>
          <p className="text-sm text-muted-foreground">{task.description ?? task.task_type}</p>
        </div>
        <button type="button" className="text-sm text-muted-foreground" onClick={onClose} aria-label="Close task detail">
          Close
        </button>
      </div>

      <div className="mt-4">
        <WorkflowStatusBadge status={task.status} />
      </div>

      <dl className="mt-4 grid gap-2 text-xs text-muted-foreground">
        <div>
          <dt className="font-medium text-foreground">Workflow instance</dt>
          <dd>{task.workflow_instance_public_id ?? '—'}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Assignee</dt>
          <dd>{task.assignee_user_public_id || task.assignee_role_key || '—'}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Created</dt>
          <dd>{formatWorkflowDate(task.created_at)}</dd>
        </div>
      </dl>

      <div className="mt-4">
        <WorkflowTaskActions
          task={task}
          permissions={permissions}
          actionsEnabled={actionsEnabled}
          onOpen={async () => {
            await taskActions.openTask(task.public_id)
          }}
          onComplete={async () => {
            await taskActions.completeTask({ publicId: task.public_id })
          }}
          onCancel={async () => {
            await taskActions.cancelTask(task.public_id)
          }}
          isOpening={taskActions.isOpening}
          isCompleting={taskActions.isCompleting}
          isCancelling={taskActions.isCancelling}
        />
      </div>

      {commentsEnabled ? (
        <section className="mt-6 space-y-3">
          <h3 className="text-sm font-medium text-foreground">Comments</h3>
          <WorkflowCommentList
            comments={comments.comments}
            isLoading={comments.query.isLoading}
            error={comments.error}
          />
          <WorkflowCommentComposer
            onSubmit={async (body) => {
              await comments.addComment(body)
            }}
            disabled={!actionsEnabled}
            isSubmitting={comments.isAdding}
            error={comments.addError}
          />
        </section>
      ) : null}

      <section className="mt-6 space-y-3">
        <h3 className="text-sm font-medium text-foreground">History</h3>
        {historyQuery.isLoading ? (
          <WorkflowLoadingState />
        ) : historyQuery.error ? (
          <WorkflowErrorState message="Unable to load task history." />
        ) : (
          <WorkflowTimeline history={historyQuery.data ?? []} comments={comments.comments} />
        )}
      </section>
    </aside>
  )
}
