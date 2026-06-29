import type { UiComponent } from '@/api/types/ui'
import { safeMetadataClasses } from '../core/renderer-utils'

interface CardComponentProps {
  component: UiComponent
  children?: React.ReactNode
}

export function CardComponent({ component, children }: CardComponentProps) {
  return (
    <section
      className={`rounded-lg border border-border bg-card p-4 text-card-foreground ${safeMetadataClasses(component.metadata)}`}
      data-testid="card-component"
    >
      <h3 className="text-sm font-medium">{component.name}</h3>
      {component.description ? (
        <p className="mt-1 text-xs text-muted-foreground">{component.description}</p>
      ) : null}
      {children ? <div className="mt-3">{children}</div> : null}
    </section>
  )
}
