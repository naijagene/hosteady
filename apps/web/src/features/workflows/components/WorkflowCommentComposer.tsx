import { useState } from 'react'

interface WorkflowCommentComposerProps {
  onSubmit: (body: string) => Promise<void>
  disabled?: boolean
  isSubmitting?: boolean
  error?: { message: string } | null
}

export function WorkflowCommentComposer({
  onSubmit,
  disabled = false,
  isSubmitting = false,
  error,
}: WorkflowCommentComposerProps) {
  const [body, setBody] = useState('')

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault()
    const trimmed = body.trim()
    if (!trimmed) {
      return
    }

    await onSubmit(trimmed)
    setBody('')
  }

  return (
    <form className="space-y-2" data-testid="workflow-comment-composer" onSubmit={handleSubmit}>
      <label htmlFor="workflow-comment-input" className="text-xs font-medium text-foreground">
        Add comment
      </label>
      <textarea
        id="workflow-comment-input"
        className="min-h-20 w-full rounded-md border border-border bg-background px-3 py-2 text-sm"
        value={body}
        onChange={(event) => setBody(event.target.value)}
        disabled={disabled || isSubmitting}
        aria-label="Comment text"
      />
      {error ? <WorkflowCommentError message={error.message} /> : null}
      <button
        type="submit"
        className="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground disabled:opacity-50"
        disabled={disabled || isSubmitting || !body.trim()}
        aria-busy={isSubmitting}
      >
        {isSubmitting ? 'Posting…' : 'Post comment'}
      </button>
    </form>
  )
}

function WorkflowCommentError({ message }: { message: string }) {
  return (
    <p className="text-xs text-destructive" role="alert">
      {message}
    </p>
  )
}
