import type { HumanTask } from '@/api/types/workflows'
import { canManageTasks } from '../core/workflow-permissions'

interface WorkflowTaskActionsProps {
  task: HumanTask
  permissions: string[]
  actionsEnabled?: boolean
  onOpen?: () => Promise<void>
  onComplete?: () => Promise<void>
  onCancel?: () => Promise<void>
  isOpening?: boolean
  isCompleting?: boolean
  isCancelling?: boolean
}

export function WorkflowTaskActions({
  task,
  permissions,
  actionsEnabled = true,
  onOpen,
  onComplete,
  onCancel,
  isOpening,
  isCompleting,
  isCancelling,
}: WorkflowTaskActionsProps) {
  const canManage = canManageTasks(permissions)
  const disabled = !actionsEnabled || !canManage

  return (
    <div className="flex flex-wrap gap-2" data-testid="workflow-task-actions">
      <button
        type="button"
        className="rounded-md border border-border px-3 py-1.5 text-xs font-medium disabled:opacity-50"
        disabled={disabled || isOpening}
        onClick={() => onOpen?.()}
        aria-label={`Open task ${task.title}`}
      >
        {isOpening ? 'Opening…' : 'Open'}
      </button>
      <button
        type="button"
        className="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground disabled:opacity-50"
        disabled={disabled || isCompleting || task.status === 'completed'}
        onClick={() => onComplete?.()}
        aria-label={`Complete task ${task.title}`}
      >
        {isCompleting ? 'Completing…' : 'Complete'}
      </button>
      <button
        type="button"
        className="rounded-md border border-destructive px-3 py-1.5 text-xs font-medium text-destructive disabled:opacity-50"
        disabled={disabled || isCancelling || task.status === 'cancelled'}
        onClick={() => onCancel?.()}
        aria-label={`Cancel task ${task.title}`}
      >
        {isCancelling ? 'Cancelling…' : 'Cancel'}
      </button>
    </div>
  )
}
