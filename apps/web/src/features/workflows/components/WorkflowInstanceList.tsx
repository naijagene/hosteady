import type { WorkflowInstance } from '@/api/types/workflows'
import { WorkflowEmptyState } from './WorkflowEmptyState'
import { WorkflowInstanceCard } from './WorkflowInstanceCard'

interface WorkflowInstanceListProps {
  instances: WorkflowInstance[]
  onOpen?: (instance: WorkflowInstance) => void
  emptyMessage?: string
}

export function WorkflowInstanceList({ instances, onOpen, emptyMessage }: WorkflowInstanceListProps) {
  if (instances.length === 0) {
    return <WorkflowEmptyState message={emptyMessage} />
  }

  return (
    <div className="grid gap-3" data-testid="workflow-instance-list">
      {instances.map((instance) => (
        <WorkflowInstanceCard key={instance.public_id} instance={instance} onOpen={onOpen} />
      ))}
    </div>
  )
}
