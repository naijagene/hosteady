interface DocumentErrorStateProps {
  message?: string
}

export function DocumentErrorState({ message = 'Unable to load documents.' }: DocumentErrorStateProps) {
  return (
    <div
      className="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
      data-testid="document-error-state"
      role="alert"
    >
      {message}
    </div>
  )
}
