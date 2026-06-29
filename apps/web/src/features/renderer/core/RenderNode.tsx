import type { UiComponent } from '@/api/types/ui'
import { ComponentRenderer } from '../layouts/ComponentRenderer'

interface RenderNodeProps {
  component: UiComponent
}

export function RenderNode({ component }: RenderNodeProps) {
  return <ComponentRenderer component={component} />
}
