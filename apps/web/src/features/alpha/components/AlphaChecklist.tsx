import { AlphaStatusBadge } from './AlphaStatusBadge'
import type { AlphaFeatureCheck, AlphaRuntimeCheck } from '../types'

interface AlphaChecklistProps {
  items: AlphaRuntimeCheck[] | AlphaFeatureCheck[]
  mode: 'runtime' | 'features'
}

export function AlphaChecklist({ items, mode }: AlphaChecklistProps) {
  return (
    <ul className="space-y-2" data-testid={mode === 'runtime' ? 'alpha-runtime-checklist' : 'alpha-feature-checklist'}>
      {items.map((item) => {
        const status =
          mode === 'runtime'
            ? (item as AlphaRuntimeCheck).status
            : (item as AlphaFeatureCheck).available
              ? 'ready'
              : 'unavailable'

        return (
          <li key={item.key} className="flex items-start justify-between gap-3 text-xs">
            <div>
              <p className="font-medium text-foreground">{item.label}</p>
              {'detail' in item && item.detail ? <p className="text-muted-foreground">{item.detail}</p> : null}
            </div>
            <AlphaStatusBadge status={status} />
          </li>
        )
      })}
    </ul>
  )
}
