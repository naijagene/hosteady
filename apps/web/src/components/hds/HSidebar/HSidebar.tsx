const navItems = [
  { label: 'Overview', active: true },
  { label: 'Modules', active: false },
  { label: 'Settings', active: false },
] as const

export interface HSidebarProps {
  className?: string
}

export function HSidebar({ className = '' }: HSidebarProps) {
  return (
    <aside
      className={`flex w-60 shrink-0 flex-col border-r border-hds-border bg-hds-surface-muted ${className}`}
    >
      <div className="border-b border-hds-border px-4 py-3">
        <p className="text-xs font-semibold uppercase tracking-wider text-hds-text-muted">
          Navigation
        </p>
      </div>

      <nav aria-label="Primary navigation" className="flex flex-col gap-0.5 p-2">
        {navItems.map((item) => (
          <button
            key={item.label}
            type="button"
            aria-disabled="true"
            className={`flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium transition-colors ${
              item.active
                ? 'border-l-2 border-hds-brand-gold bg-white text-hds-brand-blue shadow-sm'
                : 'border-l-2 border-transparent text-hds-text-muted'
            }`}
          >
            {item.label}
          </button>
        ))}
      </nav>
    </aside>
  )
}
