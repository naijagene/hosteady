import type { FormSubmissionResult } from '@/api/types/forms'

interface FormSuccessStateProps {
  message?: string
  result?: FormSubmissionResult | null
}

export function FormSuccessState({
  message = 'Form submitted successfully.',
  result,
}: FormSuccessStateProps) {
  return (
    <div
      className="rounded-md border border-border bg-card p-4 text-sm text-foreground"
      data-testid="form-success-state"
      role="status"
    >
      <p className="font-medium">{message}</p>
      {result?.entity_public_id ? (
        <p className="mt-2 text-xs text-muted-foreground">
          Reference: {result.entity_public_id}
        </p>
      ) : null}
      {result?.submission_id ? (
        <p className="mt-1 text-xs text-muted-foreground">
          Submission: {result.submission_id}
        </p>
      ) : null}
    </div>
  )
}
