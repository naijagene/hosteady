import { Link } from '@tanstack/react-router'

export function UnauthorizedLayout() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-4 p-8 text-center">
      <h1 className="text-2xl font-semibold">Unauthorized</h1>
      <p className="max-w-md text-sm text-muted-foreground">
        You must sign in to access this page.
      </p>
      <Link to="/login" search={{ redirect: undefined }} className="text-sm font-medium text-primary">
        Go to login
      </Link>
    </div>
  )
}
