import { Link } from '@tanstack/react-router'

export function ErrorLayout() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-4 p-8 text-center">
      <h1 className="text-2xl font-semibold">Something went wrong</h1>
      <p className="max-w-md text-sm text-muted-foreground">
        The application encountered an unexpected error.
      </p>
      <Link to="/" className="text-sm font-medium text-primary">
        Return home
      </Link>
    </div>
  )
}
