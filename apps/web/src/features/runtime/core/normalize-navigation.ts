import type {
  NavigationGroupResponse,
  NavigationItemResponse,
  NavigationMenuResponse,
} from '@/api/types/runtime'
import type { ApiRecord } from '@/api/types/api'

export const DEFAULT_NAVIGATION_GROUP_KEY = 'default'
export const DEFAULT_NAVIGATION_GROUP_LABEL = 'Main'

function isRecord(value: unknown): value is ApiRecord {
  return value !== null && typeof value === 'object'
}

function readString(record: ApiRecord, ...keys: string[]): string | null {
  for (const key of keys) {
    const value = record[key]
    if (typeof value === 'string' && value.trim() !== '') {
      return value
    }
  }

  return null
}

function isNavigationItemRecord(record: ApiRecord): boolean {
  return readString(record, 'item_key', 'itemKey') !== null
}

export function normalizeNavigationItem(raw: unknown): NavigationItemResponse | null {
  if (!isRecord(raw)) {
    return null
  }

  const itemKey = readString(raw, 'item_key', 'itemKey')
  const label = readString(raw, 'label')

  if (!itemKey || !label) {
    return null
  }

  return {
    item_key: itemKey,
    label,
    item_type: readString(raw, 'item_type', 'itemType') ?? undefined,
    route: isRecord(raw.route) || typeof raw.route === 'string' ? (raw.route as NavigationItemResponse['route']) : undefined,
    badge: typeof raw.badge === 'string' ? raw.badge : null,
    sort_order: typeof raw.sort_order === 'number' ? raw.sort_order : undefined,
    required_permission: readString(raw, 'required_permission', 'requiredPermission'),
    metadata: isRecord(raw.metadata) ? raw.metadata : undefined,
  }
}

export function normalizeNavigationGroup(
  raw: unknown,
  _index = 0,
  fallbackLabel = DEFAULT_NAVIGATION_GROUP_LABEL,
): NavigationGroupResponse | null {
  if (!isRecord(raw)) {
    return null
  }

  if (isNavigationItemRecord(raw)) {
    const item = normalizeNavigationItem(raw)
    if (!item) {
      return null
    }

    return {
      group_key: DEFAULT_NAVIGATION_GROUP_KEY,
      label: fallbackLabel,
      items: [item],
    }
  }

  const items = Array.isArray(raw.items)
    ? raw.items
        .map((item) => normalizeNavigationItem(item))
        .filter((item): item is NavigationItemResponse => item !== null)
    : []

  const groupKey = readString(raw, 'group_key', 'groupKey') ?? DEFAULT_NAVIGATION_GROUP_KEY
  const label = readString(raw, 'label') ?? fallbackLabel

  return {
    group_key: groupKey,
    label,
    sort_order: typeof raw.sort_order === 'number' ? raw.sort_order : undefined,
    items,
    metadata: isRecord(raw.metadata) ? raw.metadata : undefined,
  }
}

function normalizeNavigationMenu(raw: unknown, index = 0): NavigationMenuResponse | null {
  if (!isRecord(raw)) {
    return null
  }

  const menuKey = readString(raw, 'menu_key', 'menuKey') ?? `menu-${index}`
  const label = readString(raw, 'label') ?? DEFAULT_NAVIGATION_GROUP_LABEL
  const fallbackGroupLabel = label

  let groups: NavigationGroupResponse[] = []

  if (Array.isArray(raw.groups)) {
    groups = raw.groups
      .map((group, groupIndex) =>
        normalizeNavigationGroup(group, groupIndex, fallbackGroupLabel),
      )
      .filter((group): group is NavigationGroupResponse => group !== null)
  } else if (Array.isArray(raw.items)) {
    groups = [
      {
        group_key: DEFAULT_NAVIGATION_GROUP_KEY,
        label: DEFAULT_NAVIGATION_GROUP_LABEL,
        items: raw.items
          .map((item) => normalizeNavigationItem(item))
          .filter((item): item is NavigationItemResponse => item !== null),
      },
    ]
  } else if (isNavigationItemRecord(raw)) {
    const item = normalizeNavigationItem(raw)
    if (item) {
      groups = [
        {
          group_key: DEFAULT_NAVIGATION_GROUP_KEY,
          label: DEFAULT_NAVIGATION_GROUP_LABEL,
          items: [item],
        },
      ]
    }
  }

  return {
    menu_key: menuKey,
    label,
    groups,
    metadata: isRecord(raw.metadata) ? raw.metadata : {},
  }
}

function isNavigationMenuRecord(record: ApiRecord): boolean {
  return (
    readString(record, 'menu_key', 'menuKey') !== null ||
    Array.isArray(record.groups) ||
    Array.isArray(record.items)
  )
}

function normalizeFlatNavigationItems(items: unknown[]): NavigationMenuResponse[] {
  const navigationItems = items
    .map((item) => normalizeNavigationItem(item))
    .filter((item): item is NavigationItemResponse => item !== null)

  if (navigationItems.length === 0) {
    return []
  }

  return [
    {
      menu_key: 'main',
      label: DEFAULT_NAVIGATION_GROUP_LABEL,
      groups: [
        {
          group_key: DEFAULT_NAVIGATION_GROUP_KEY,
          label: DEFAULT_NAVIGATION_GROUP_LABEL,
          items: navigationItems,
        },
      ],
      metadata: { source: 'workspace_runtime' },
    },
  ]
}

export function normalizeNavigationMenus(input: unknown): NavigationMenuResponse[] {
  if (input === null || input === undefined) {
    return []
  }

  if (Array.isArray(input)) {
    if (input.length === 0) {
      return []
    }

    if (input.some((entry) => isRecord(entry) && isNavigationMenuRecord(entry))) {
      return input
        .map((menu, index) => normalizeNavigationMenu(menu, index))
        .filter((menu): menu is NavigationMenuResponse => menu !== null)
    }

    return normalizeFlatNavigationItems(input)
  }

  if (!isRecord(input)) {
    return []
  }

  if (Array.isArray(input.menus)) {
    return normalizeNavigationMenus(input.menus)
  }

  if (Array.isArray(input.groups)) {
    const groups = input.groups
      .map((group, index) => normalizeNavigationGroup(group, index))
      .filter((group): group is NavigationGroupResponse => group !== null)

    if (groups.length === 0) {
      return []
    }

    return [
      {
        menu_key: readString(input, 'menu_key', 'menuKey') ?? 'main',
        label: readString(input, 'label') ?? DEFAULT_NAVIGATION_GROUP_LABEL,
        groups,
        metadata: isRecord(input.metadata) ? input.metadata : {},
      },
    ]
  }

  const menu = normalizeNavigationMenu(input)
  return menu ? [menu] : []
}

export function collectNavigationGroups(
  menus: NavigationMenuResponse[],
): NavigationGroupResponse[] {
  return menus.flatMap((menu) =>
    (menu.groups ?? [])
      .map((group, index) => normalizeNavigationGroup(group, index))
      .filter((group): group is NavigationGroupResponse => group !== null),
  )
}
