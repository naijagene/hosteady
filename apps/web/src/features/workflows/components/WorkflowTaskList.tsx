import type { HumanTask } from '@/api/types/workflows'
import { WorkflowEmptyState } from './WorkflowEmptyState'
import { WorkflowTaskCard } from './WorkflowTaskCard'

interface WorkflowTaskListProps {
  tasks: HumanTask[]
  onOpen?: (task: HumanTask) => void
  emptyMessage?: string
}

export function WorkflowTaskList({ tasks, onOpen, emptyMessage }: WorkflowTaskListProps) {
  if (tasks.length === 0) {
    return <WorkflowEmptyState message={emptyMessage} />
  }

  return (
    <div className="grid gap-3" data-testid="workflow-task-list">
      {tasks.map((task) => (
        <WorkflowTaskCard key={task.public_id} task={task} onOpen={onOpen} />
      ))}
    </div>
  )
}
