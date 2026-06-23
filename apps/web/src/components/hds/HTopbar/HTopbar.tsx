import { HLogo } from '../HLogo'

export interface HTopbarProps {
  title?: string
  className?: string
}

export function HTopbar({
  title = 'HEOS Workspace',
  className = '',
}: HTopbarProps) {
  return (
    <header
      className={`flex h-14 shrink-0 items-center justify-between border-b border-hds-brand-blue-light bg-hds-brand-blue px-4 ${className}`}
    >
      <div className="flex items-center gap-6">
        <HLogo size="sm" />
        <div className="hidden h-5 w-px bg-hds-brand-blue-light sm:block" />
        <h1 className="hidden text-sm font-medium text-white/90 sm:block">
          {title}
        </h1>
      </div>

      <div className="flex items-center gap-3">
        <input
          type="search"
          disabled
          placeholder="Search..."
          aria-label="Search"
          className="hidden w-48 rounded border border-hds-brand-blue-light bg-hds-brand-blue-light/40 px-3 py-1.5 text-sm text-white/70 placeholder:text-white/40 md:block"
        />
        <div
          className="flex h-8 w-8 items-center justify-center rounded-full bg-hds-brand-gold text-xs font-semibold text-hds-brand-blue"
          aria-label="User"
          role="img"
        >
          HB
        </div>
      </div>
    </header>
  )
}
