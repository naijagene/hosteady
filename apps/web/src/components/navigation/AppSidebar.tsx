import { Link, useRouterState } from '@tanstack/react-router'
import { useMemo, useState } from 'react'
import { useNavigationContext } from '@/app/providers/use-navigation-context'
import { resolveNavigationItemRoute } from '@/features/renderer/navigation-route'
import { cn } from '@/lib/utils'
import type { NavigationGroupResponse, NavigationItemResponse } from '@/api/types/runtime'

function NavigationItemLink({ item }: { item: NavigationItemResponse }) {
  const target = resolveNavigationItemRoute(item)
  const pathname = useRouterState({ select: (state) => state.location.pathname })
  const href =
    target?.params
      ? `/app/${target.params.moduleKey}/${target.params.pageKey}`
      : target?.to ?? null
  const isActive = href ? pathname === href : false

  if (!target) {
    return (
      <button
        type="button"
        className="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm text-muted-foreground"
        aria-disabled="true"
      >
        <span>{item.label}</span>
      </button>
    )
  }

  return (
    <Link
      to={target.to}
      params={target.params}
      className={cn(
        'flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm hover:bg-muted',
        isActive ? 'bg-muted font-medium text-foreground' : 'text-foreground',
      )}
    >
      <span>{item.label}</span>
      {item.badge ? (
        <span className="rounded-full bg-primary/10 px-2 py-0.5 text-xs text-primary">
          {item.badge}
        </span>
      ) : null}
    </Link>
  )
}

function NavigationGroupSection({
  group,
  collapsed,
}: {
  group: NavigationGroupResponse
  collapsed: boolean
}) {
  const [open, setOpen] = useState(true)

  return (
    <section className="space-y-1">
      <button
        type="button"
        className="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground"
        onClick={() => setOpen((value) => !value)}
      >
        <span>{group.label}</span>
        {!collapsed ? <span>{open ? '−' : '+'}</span> : null}
      </button>
      {open || collapsed
        ? group.items.map((item) => (
            <NavigationItemLink key={item.item_key} item={item} />
          ))
        : null}
    </section>
  )
}

export function AppSidebar() {
  const { menus, overrides } = useNavigationContext()
  const collapsed = overrides.collapsed === true

  const groups = useMemo(() => menus.flatMap((menu) => menu.groups), [menus])

  return (
    <aside
      className={cn(
        'flex shrink-0 flex-col border-r border-border bg-card',
        collapsed ? 'w-16' : 'w-64',
      )}
    >
      <div className="border-b border-border px-4 py-3">
        <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          Navigation
        </p>
      </div>
      <nav aria-label="Primary navigation" className="flex flex-1 flex-col gap-3 overflow-auto p-2">
        {groups.length === 0 ? (
          <p className="px-3 py-2 text-sm text-muted-foreground">
            Navigation will appear when runtime metadata is available.
          </p>
        ) : (
          groups.map((group) => (
            <NavigationGroupSection
              key={group.group_key}
              group={group}
              collapsed={collapsed}
            />
          ))
        )}
        <div className="mt-auto border-t border-border px-3 py-3">
          <p className="text-xs text-muted-foreground">Favorites placeholder</p>
        </div>
      </nav>
    </aside>
  )
}
