import type { UiComponent } from '@/api/types/ui'

interface UnknownComponentProps {
  component: UiComponent
}

export function UnknownComponent({ component }: UnknownComponentProps) {
  return (
    <div
      className="rounded-md border border-dashed border-border bg-muted/30 p-4 text-sm text-muted-foreground"
      data-component-type={component.component_type}
      data-testid="unknown-component"
    >
      <p className="font-medium text-foreground">{component.name}</p>
      <p>Unsupported component type: {component.component_type || 'unknown'}</p>
    </div>
  )
}
