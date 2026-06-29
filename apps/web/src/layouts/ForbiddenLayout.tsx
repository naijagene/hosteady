import { Link } from '@tanstack/react-router'

export function ForbiddenLayout() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-4 p-8 text-center">
      <h1 className="text-2xl font-semibold">Forbidden</h1>
      <p className="max-w-md text-sm text-muted-foreground">
        You do not have permission to access this resource.
      </p>
      <Link to="/" className="text-sm font-medium text-primary">
        Return home
      </Link>
    </div>
  )
}
