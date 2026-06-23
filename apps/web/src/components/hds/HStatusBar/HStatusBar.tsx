export interface HStatusBarProps {
  status?: string
  version?: string
  className?: string
}

export function HStatusBar({
  status = 'Ready',
  version = 'v0.1.0',
  className = '',
}: HStatusBarProps) {
  return (
    <footer
      className={`flex h-7 shrink-0 items-center justify-between border-t border-hds-border bg-hds-surface-muted px-4 text-xs text-hds-text-muted ${className}`}
    >
      <div className="flex items-center gap-2">
        <span
          className="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"
          aria-hidden="true"
        />
        <span>{status}</span>
      </div>

      <div className="flex items-center gap-4">
        <span>{version}</span>
        <span>&copy; Hosteady Enterprise</span>
      </div>
    </footer>
  )
}
