import type { HumanTaskComment } from '@/api/types/workflows'
import { formatWorkflowDate } from '../core/workflow-normalizer'
import { WorkflowEmptyState } from './WorkflowEmptyState'
import { WorkflowErrorState } from './WorkflowErrorState'
import { WorkflowLoadingState } from './WorkflowLoadingState'

interface WorkflowCommentListProps {
  comments: HumanTaskComment[]
  isLoading?: boolean
  error?: { message: string } | null
}

export function WorkflowCommentList({ comments, isLoading, error }: WorkflowCommentListProps) {
  if (isLoading) {
    return <WorkflowLoadingState />
  }

  if (error) {
    return <WorkflowErrorState message={error.message} />
  }

  if (comments.length === 0) {
    return <WorkflowEmptyState message="No comments yet." />
  }

  return (
    <ul className="space-y-3" data-testid="workflow-comment-list" aria-label="Task comments">
      {comments.map((comment) => (
        <li key={comment.public_id} className="rounded-md border border-border bg-muted/20 p-3">
          <p className="text-sm text-foreground">{comment.body}</p>
          <p className="mt-1 text-xs text-muted-foreground">{formatWorkflowDate(comment.created_at)}</p>
        </li>
      ))}
    </ul>
  )
}
