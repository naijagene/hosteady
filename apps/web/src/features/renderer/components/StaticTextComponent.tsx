import type { UiComponent } from '@/api/types/ui'
import { extractStaticText } from '../core/renderer-utils'

interface StaticTextComponentProps {
  component: UiComponent
}

export function StaticTextComponent({ component }: StaticTextComponentProps) {
  return (
    <p className="text-sm text-foreground" data-testid="static-text-component">
      {extractStaticText(component)}
    </p>
  )
}
