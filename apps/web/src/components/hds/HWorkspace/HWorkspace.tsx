import type { ReactNode } from 'react'

export interface HWorkspaceProps {
  children?: ReactNode
  className?: string
}

export function HWorkspace({ children, className = '' }: HWorkspaceProps) {
  return (
    <main
      className={`flex flex-1 flex-col overflow-auto bg-hds-surface p-6 ${className}`}
    >
      {children ?? (
        <div className="flex flex-1 flex-col items-center justify-center rounded-lg border border-dashed border-hds-border bg-hds-surface-muted/50">
          <p className="text-base font-medium text-hds-text">Workspace ready</p>
          <p className="mt-1 text-sm text-hds-text-muted">
            Application content will render here
          </p>
        </div>
      )}
    </main>
  )
}
