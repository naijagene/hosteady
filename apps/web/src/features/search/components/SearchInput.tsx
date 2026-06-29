import { Search } from '@/components/icons'

interface SearchInputProps {
  id?: string
  value: string
  onChange: (value: string) => void
  placeholder?: string
  autoFocus?: boolean
  ariaControls?: string
  ariaActiveDescendant?: string
  onKeyDown?: (event: React.KeyboardEvent<HTMLInputElement>) => void
}

export function SearchInput({
  id = 'global-search-input',
  value,
  onChange,
  placeholder = 'Search pages, documents, commands…',
  autoFocus = false,
  ariaControls,
  ariaActiveDescendant,
  onKeyDown,
}: SearchInputProps) {
  return (
    <div className="relative">
      <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" aria-hidden />
      <input
        id={id}
        type="search"
        role="combobox"
        aria-expanded="true"
        aria-controls={ariaControls}
        aria-activedescendant={ariaActiveDescendant}
        aria-label="Search HEOS"
        autoFocus={autoFocus}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        onKeyDown={onKeyDown}
        placeholder={placeholder}
        className="w-full rounded-md border border-input bg-background py-2 pl-9 pr-3 text-sm text-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring"
        data-testid="search-input"
      />
    </div>
  )
}
