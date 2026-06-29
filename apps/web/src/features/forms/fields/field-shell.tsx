import type { ReactNode } from 'react'
import { cn } from '@/lib/utils'

interface FieldShellProps {
  fieldKey: string
  label: string
  required?: boolean
  error?: string
  description?: string | null
  hidden?: boolean
  className?: string
  children: ReactNode
}

export function FieldShell({
  fieldKey,
  label,
  required = false,
  error,
  description,
  hidden = false,
  className,
  children,
}: FieldShellProps) {
  const errorId = `${fieldKey}-error`
  const descriptionId = `${fieldKey}-description`

  if (hidden) {
    return <div className="hidden">{children}</div>
  }

  return (
    <div className={cn('space-y-1', className)} data-field-key={fieldKey}>
      <label htmlFor={fieldKey} className="text-sm font-medium text-foreground">
        {label}
        {required ? <span className="text-destructive"> *</span> : null}
      </label>
      {description ? (
        <p id={descriptionId} className="text-xs text-muted-foreground">
          {description}
        </p>
      ) : null}
      {children}
      {error ? (
        <p id={errorId} className="text-xs text-destructive" role="alert">
          {error}
        </p>
      ) : null}
    </div>
  )
}
