import type { UiComponent } from '@/api/types/ui'
import { extractMetricValue } from '../core/renderer-utils'

interface MetricComponentProps {
  component: UiComponent
}

export function MetricComponent({ component }: MetricComponentProps) {
  return (
    <div
      className="rounded-lg border border-border bg-card p-4"
      data-testid="metric-component"
    >
      <p className="text-xs text-muted-foreground">{component.name}</p>
      <p className="mt-2 text-2xl font-semibold text-foreground">
        {extractMetricValue(component)}
      </p>
    </div>
  )
}
