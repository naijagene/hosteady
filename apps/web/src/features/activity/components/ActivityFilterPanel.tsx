import type { ActivityQueryPayload } from '@/api/types/activity'

interface ActivityFilterPanelProps {
  query: ActivityQueryPayload
  onChange: (patch: Partial<ActivityQueryPayload>) => void
}

const severities = ['info', 'warning', 'critical']
const entityTypes = ['document', 'workflow', 'record', 'notification', 'security']

export function ActivityFilterPanel({ query, onChange }: ActivityFilterPanelProps) {
  return (
    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4" data-testid="activity-filter-panel">
      <label className="text-xs">
        <span className="mb-1 block font-medium text-foreground">Entity type</span>
        <select
          aria-label="Entity type filter"
          className="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm"
          value={query.entity_type ?? ''}
          onChange={(event) => onChange({ entity_type: event.target.value || undefined, page: 1 })}
        >
          <option value="">All types</option>
          {entityTypes.map((type) => (
            <option key={type} value={type}>
              {type}
            </option>
          ))}
        </select>
      </label>

      <label className="text-xs">
        <span className="mb-1 block font-medium text-foreground">Severity</span>
        <select
          aria-label="Severity filter"
          className="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm"
          value={query.severity ?? ''}
          onChange={(event) => onChange({ severity: event.target.value || undefined, page: 1 })}
        >
          <option value="">All severities</option>
          {severities.map((severity) => (
            <option key={severity} value={severity}>
              {severity}
            </option>
          ))}
        </select>
      </label>

      <label className="text-xs">
        <span className="mb-1 block font-medium text-foreground">Action</span>
        <input
          aria-label="Action filter"
          className="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm"
          value={query.action ?? ''}
          onChange={(event) => onChange({ action: event.target.value || undefined, page: 1 })}
          placeholder="e.g. updated"
        />
      </label>

      <label className="text-xs">
        <span className="mb-1 block font-medium text-foreground">Date range</span>
        <input
          aria-label="Date range placeholder"
          className="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm"
          placeholder="Date range placeholder"
          disabled
        />
      </label>
    </div>
  )
}
