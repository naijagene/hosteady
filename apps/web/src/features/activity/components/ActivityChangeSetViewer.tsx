import type { ActivityChangeSet } from '@/api/types/activity'
import { getChangeSetSummary, sanitizeChangeValue } from '../core/activity-diff'

interface ActivityChangeSetViewerProps {
  changes?: ActivityChangeSet[]
}

export function ActivityChangeSetViewer({ changes = [] }: ActivityChangeSetViewerProps) {
  if (changes.length === 0) {
    return <p className="text-xs text-muted-foreground">No visible changes recorded.</p>
  }

  return (
    <div className="space-y-2" data-testid="activity-change-set-viewer">
      <p className="text-xs font-medium text-foreground">{getChangeSetSummary(changes)}</p>
      <div className="overflow-hidden rounded-md border border-border">
        <table className="w-full text-left text-xs">
          <thead className="bg-muted/50">
            <tr>
              <th className="px-3 py-2 font-medium">Field</th>
              <th className="px-3 py-2 font-medium">Before</th>
              <th className="px-3 py-2 font-medium">After</th>
            </tr>
          </thead>
          <tbody>
            {changes.map((change, index) => (
              <tr key={`${change.field ?? 'field'}-${index}`} className="border-t border-border">
                <td className="px-3 py-2 align-top font-medium">{change.field ?? 'value'}</td>
                <td className="px-3 py-2 align-top text-muted-foreground">
                  {sanitizeChangeValue(change.before, change.sensitive)}
                </td>
                <td className="px-3 py-2 align-top text-foreground">
                  {sanitizeChangeValue(change.after, change.sensitive)}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
