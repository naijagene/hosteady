import type { UiComponent } from '@/api/types/ui'
import { safeMetadataClasses } from '../core/renderer-utils'

interface SectionComponentProps {
  component: UiComponent
  children?: React.ReactNode
}

export function SectionComponent({ component, children }: SectionComponentProps) {
  return (
    <section
      className={`space-y-3 ${safeMetadataClasses(component.metadata)}`}
      data-testid="section-component"
    >
      <header>
        <h3 className="text-sm font-semibold text-foreground">{component.name}</h3>
      </header>
      {children}
    </section>
  )
}
