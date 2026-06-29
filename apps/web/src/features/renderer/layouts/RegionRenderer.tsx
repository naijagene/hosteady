import type { UiRegion } from '@/api/types/ui'
import {
  getRegionClassName,
  getRegionComponents,
  safeMetadataClasses,
} from '../core/renderer-utils'
import { useOptionalRendererContext } from '../hooks/useRendererContext'
import { ComponentRenderer } from './ComponentRenderer'

interface RegionRendererProps {
  region: UiRegion
  components?: UiRegion['components']
}

export function RegionRenderer({ region }: RegionRendererProps) {
  const context = useOptionalRendererContext()
  const allComponents = context?.payload?.components ?? []
  const regionComponents = getRegionComponents(region, allComponents)
  const devMode = context?.devMode ?? false

  return (
    <section
      className={`${getRegionClassName(region.region_type)} ${safeMetadataClasses(region.metadata)}`}
      data-region-key={region.region_key}
      data-testid={`region-${region.region_key}`}
    >
      {region.label ? (
        <header className="mb-2">
          <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {region.label}
          </h3>
        </header>
      ) : null}
      <div className="space-y-3">
        {regionComponents.length === 0 ? (
          devMode ? (
            <p
              className="rounded-md border border-dashed border-border p-3 text-xs text-muted-foreground"
              data-testid="empty-region-placeholder"
            >
              Empty region: {region.region_key}
            </p>
          ) : null
        ) : (
          regionComponents.map((component) => (
            <ComponentRenderer
              key={component.public_id || component.component_key}
              component={component}
            />
          ))
        )}
      </div>
    </section>
  )
}
