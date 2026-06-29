import type { DashboardWidgetComponentProps } from './types'

export function TableWidget({ widget }: DashboardWidgetComponentProps) {
  const rows = widget.data?.rows ?? []

  return (
    <div className="space-y-3" data-testid="table-widget">
      <h4 className="text-sm font-medium text-foreground">{widget.label}</h4>
      {rows.length === 0 ? (
        <p className="text-xs text-muted-foreground">No table rows available.</p>
      ) : (
        <div className="overflow-x-auto">
          <table className="min-w-full text-left text-xs">
            <tbody>
              {rows.map((row, index) => (
                <tr key={index} className="border-t border-border">
                  {Object.entries(row).map(([key, value]) => (
                    <td key={key} className="px-2 py-1 text-foreground">
                      {String(value ?? '')}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
