import { useState } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { LogOut } from '@/components/icons'
import { useAuthStore } from '@/stores/auth-store'

export function UserMenu() {
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)
  const user = useAuthStore((state) => state.user)
  const workspace = useAuthStore((state) => state.workspace)
  const logout = useAuthStore((state) => state.logout)

  const initials = (user?.display_name ?? user?.email ?? 'HE')
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()

  return (
    <div className="relative">
      <button
        type="button"
        className="flex items-center gap-2 rounded-md px-2 py-1 text-sm text-primary-foreground hover:bg-primary-foreground/10"
        aria-haspopup="menu"
        aria-expanded={open}
        onClick={() => setOpen((value) => !value)}
      >
        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-foreground/15 text-xs font-semibold">
          {initials}
        </span>
        <span className="hidden max-w-32 truncate md:inline">
          {user?.display_name ?? user?.email}
        </span>
      </button>
      {open ? (
        <div className="absolute right-0 top-full z-20 mt-2 w-64 rounded-lg border border-border bg-card p-3 text-card-foreground shadow-lg">
          <div className="space-y-1">
            <p className="text-sm font-medium">{user?.display_name}</p>
            <p className="text-xs text-muted-foreground">{user?.email}</p>
            <p className="text-xs text-muted-foreground">
              Workspace: {workspace?.name ?? '—'}
            </p>
          </div>
          <button
            type="button"
            className="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-md border border-border px-3 py-2 text-sm hover:bg-muted"
            onClick={async () => {
              setOpen(false)
              await logout()
              void navigate({ to: '/login', search: { redirect: undefined } })
            }}
          >
            <LogOut className="h-4 w-4" aria-hidden />
            Logout
          </button>
        </div>
      ) : null}
    </div>
  )
}
