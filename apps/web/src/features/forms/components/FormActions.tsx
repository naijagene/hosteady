interface FormActionsProps {
  submitLabel?: string
  isSubmitting?: boolean
  disabled?: boolean
}

export function FormActions({
  submitLabel = 'Submit',
  isSubmitting = false,
  disabled = false,
}: FormActionsProps) {
  return (
    <div className="flex items-center gap-3">
      <button
        type="submit"
        disabled={disabled || isSubmitting}
        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
      >
        {isSubmitting ? 'Submitting…' : submitLabel}
      </button>
    </div>
  )
}
