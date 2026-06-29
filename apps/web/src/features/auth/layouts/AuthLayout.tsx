import { Outlet } from '@tanstack/react-router'
import { LogIn } from '@/components/icons'

export function AuthLayout() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-background p-6">
      <div className="w-full max-w-md space-y-6 rounded-xl border border-border bg-card p-8 shadow-sm">
        <div className="space-y-2 text-center">
          <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
            <LogIn className="h-6 w-6" aria-hidden />
          </div>
          <h1 className="text-xl font-semibold tracking-tight">HEOS Sign In</h1>
          <p className="text-sm text-muted-foreground">
            Authentication flows will connect to{' '}
            <code className="rounded bg-muted px-1 py-0.5 text-xs">POST /auth/login</code>.
          </p>
        </div>
        <Outlet />
      </div>
    </div>
  )
}
