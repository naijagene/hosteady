interface FormErrorSummaryProps {
  title?: string
  messages: string[]
}

export function FormErrorSummary({
  title = 'Please fix the following errors:',
  messages,
}: FormErrorSummaryProps) {
  if (messages.length === 0) {
    return null
  }

  return (
    <div
      className="rounded-md border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
      role="alert"
      data-testid="form-error-summary"
    >
      <p className="font-medium">{title}</p>
      <ul className="mt-2 list-disc space-y-1 pl-5">
        {messages.map((message) => (
          <li key={message}>{message}</li>
        ))}
      </ul>
    </div>
  )
}
