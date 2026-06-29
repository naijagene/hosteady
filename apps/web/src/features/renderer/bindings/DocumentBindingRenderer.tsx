import type { UiComponent } from '@/api/types/ui'
import { normalizeDocumentBindingContext } from '@/api/types/documents'
import { DocumentManager } from '@/features/documents'

interface DocumentBindingRendererProps {
  component: UiComponent
}

export function DocumentBindingRenderer({ component }: DocumentBindingRendererProps) {
  const binding = normalizeDocumentBindingContext({
    ...component.binding_config,
    binding: component.component_key,
    page: component.component_key,
  })

  return (
    <div data-testid="document-binding-renderer">
      <DocumentManager title={component.name} binding={binding} />
    </div>
  )
}
