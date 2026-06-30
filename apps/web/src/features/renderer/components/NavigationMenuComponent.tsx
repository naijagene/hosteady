import { Link } from '@tanstack/react-router'
import type { UiComponent } from '@/api/types/ui'
import { asArray } from '@/api/types/metadata-common'
import { useNavigationContext } from '@/app/providers/use-navigation-context'
import {
  collectNavigationGroups,
  normalizeNavigationMenus,
} from '@/features/runtime/core/normalize-navigation'
import {
  resolveNavigationItemHref,
  resolveNavigationItemRoute,
} from '@/features/renderer/navigation-route'
import type { NavigationItemResponse } from '@/api/types/runtime'

interface NavigationMenuComponentProps {
  component: UiComponent
}

function NavigationMenuItem({ item }: { item: NavigationItemResponse }) {
  const target = resolveNavigationItemRoute(item)
  const href = resolveNavigationItemHref(item)

  if (!target || !href) {
    return <span className="text-sm text-muted-foreground">{item.label}</span>
  }

  return (
    <Link
      to={target.to}
      params={target.params}
      className="text-sm text-primary underline-offset-4 hover:underline"
    >
      {item.label}
    </Link>
  )
}

export function NavigationMenuComponent({ component }: NavigationMenuComponentProps) {
  const { menus } = useNavigationContext()
  const bindingItems = asArray<unknown>(component.binding_config?.items)
  const items =
    bindingItems.length > 0
      ? collectNavigationGroups(normalizeNavigationMenus(bindingItems)).flatMap(
          (group) => group.items ?? [],
        )
      : collectNavigationGroups(menus).flatMap((group) => group.items ?? [])

  return (
    <nav
      aria-label={component.name ?? 'Navigation menu'}
      className="rounded-lg border border-border bg-card p-4"
      data-testid="navigation-menu-component"
    >
      {items.length === 0 ? (
        <p className="text-sm text-muted-foreground">No navigation items available.</p>
      ) : (
        <ul className="space-y-2">
          {items.map((item) => (
            <li key={item.item_key}>
              <NavigationMenuItem item={item} />
            </li>
          ))}
        </ul>
      )}
    </nav>
  )
}
