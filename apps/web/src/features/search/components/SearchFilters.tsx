import type { SearchResultType } from '@/api/types/search'

const filterTypes: Array<{ value: SearchResultType | 'all'; label: string }> = [
  { value: 'all', label: 'All' },
  { value: 'page', label: 'Pages' },
  { value: 'document', label: 'Documents' },
  { value: 'report', label: 'Reports' },
  { value: 'dashboard', label: 'Dashboards' },
  { value: 'workflow', label: 'Workflows' },
  { value: 'command', label: 'Commands' },
]

interface SearchFiltersProps {
  value?: SearchResultType | 'all'
  onChange?: (value: SearchResultType | 'all') => void
}

export function SearchFilters({ value = 'all', onChange }: SearchFiltersProps) {
  return (
    <div className="flex flex-wrap gap-2 px-3 py-2" data-testid="search-filters">
      {filterTypes.map((filter) => (
        <button
          key={filter.value}
          type="button"
          aria-pressed={value === filter.value}
          className={`rounded-full border px-2.5 py-1 text-xs ${
            value === filter.value
              ? 'border-primary bg-primary text-primary-foreground'
              : 'border-border text-muted-foreground hover:bg-muted'
          }`}
          onClick={() => onChange?.(filter.value)}
        >
          {filter.label}
        </button>
      ))}
    </div>
  )
}
