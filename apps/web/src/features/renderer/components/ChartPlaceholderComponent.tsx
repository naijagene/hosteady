import type { UiComponent } from '@/api/types/ui'

interface ChartPlaceholderComponentProps {
  component: UiComponent
}

export function ChartPlaceholderComponent({
  component,
}: ChartPlaceholderComponentProps) {
  return (
    <div
      className="flex h-40 items-center justify-center rounded-lg border border-dashed border-border bg-muted/20 text-sm text-muted-foreground"
      data-testid="chart-placeholder"
    >
      Chart placeholder: {component.name}
    </div>
  )
}
