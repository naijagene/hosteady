import { useMemo, useState } from 'react'
import { buildPermissionBrowser, filterPermissions, groupPermissionsByCategory } from '../core/admin-permissions'

export function usePermissionBrowser(permissions: string[]) {
  const [search, setSearch] = useState('')
  const all = useMemo(() => buildPermissionBrowser(permissions), [permissions])
  const items = useMemo(() => filterPermissions(all, search), [all, search])
  const groups = useMemo(() => groupPermissionsByCategory(items), [items])
  return { search, setSearch, items, groups }
}
