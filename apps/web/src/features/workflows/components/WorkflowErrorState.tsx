interface WorkflowErrorStateProps {
  message: string
}

export function WorkflowErrorState({ message }: WorkflowErrorStateProps) {
  return (
    <div
      className="rounded-md border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
      data-testid="workflow-error-state"
      role="alert"
    >
      {message}
    </div>
  )
}
