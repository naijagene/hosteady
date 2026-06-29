import type { UiLayout, UiRegion } from '@/api/types/ui'
import { getLayoutClassName, sortRegions } from '../core/renderer-utils'
import { RegionRenderer } from './RegionRenderer'

interface LayoutRendererProps {
  layout: UiLayout | null | undefined
  regions?: UiRegion[]
}

export function LayoutRenderer({ layout, regions = [] }: LayoutRendererProps) {
  if (!layout) {
    return (
      <div
        className="rounded-md border border-dashed border-border p-4 text-sm text-muted-foreground"
        data-testid="layout-fallback"
      >
        Layout metadata unavailable
      </div>
    )
  }

  const layoutRegions =
    regions.length > 0 ? sortRegions(regions) : sortRegions(layout.regions ?? [])

  return (
    <div
      className={getLayoutClassName(layout.layout_type)}
      data-layout-type={layout.layout_type}
      data-testid="layout-renderer"
    >
      {layoutRegions.length === 0 ? (
        <p className="text-sm text-muted-foreground" data-testid="layout-empty">
          No regions configured
        </p>
      ) : (
        layoutRegions.map((region) => (
          <RegionRenderer key={region.region_key} region={region} />
        ))
      )}
    </div>
  )
}
