import type { UiComponent } from '@/api/types/ui'
import { safeMetadataClasses } from '../core/renderer-utils'

interface GridComponentProps {
  component: UiComponent
  children?: React.ReactNode
}

export function GridComponent({ component, children }: GridComponentProps) {
  return (
    <div
      className={`grid gap-4 sm:grid-cols-2 ${safeMetadataClasses(component.metadata)}`}
      data-testid="grid-component"
    >
      {children ?? (
        <p className="text-sm text-muted-foreground">Grid placeholder</p>
      )}
    </div>
  )
}
