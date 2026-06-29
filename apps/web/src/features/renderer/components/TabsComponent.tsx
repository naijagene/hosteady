import type { UiComponent } from '@/api/types/ui'
import { asArray, asRecord, asString } from '@/api/types/metadata-common'
import { safeMetadataClasses } from '../core/renderer-utils'

interface TabsComponentProps {
  component: UiComponent
}

export function TabsComponent({ component }: TabsComponentProps) {
  const tabs = asArray(component.metadata?.tabs).map((tab) => {
    const item = asRecord(tab)
    return {
      key: asString(item.key ?? item.tab_key, 'tab'),
      label: asString(item.label, 'Tab'),
    }
  })

  return (
    <div
      className={`rounded-lg border border-border bg-card ${safeMetadataClasses(component.metadata)}`}
      data-testid="tabs-component"
    >
      <div className="flex gap-2 border-b border-border px-3 py-2">
        {(tabs.length > 0 ? tabs : [{ key: 'default', label: component.name }]).map(
          (tab) => (
            <span
              key={tab.key}
              className="rounded-md bg-muted px-2 py-1 text-xs text-muted-foreground"
            >
              {tab.label}
            </span>
          ),
        )}
      </div>
      <div className="p-4 text-sm text-muted-foreground">Tab content placeholder</div>
    </div>
  )
}
