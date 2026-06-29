interface TableErrorStateProps {
  message: string
}

export function TableErrorState({ message }: TableErrorStateProps) {
  return (
    <div
      className="rounded-md border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
      data-testid="table-error-state"
      role="alert"
    >
      {message}
    </div>
  )
}
