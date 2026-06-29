import { createRootRoute, createRoute, createRouter, redirect } from '@tanstack/react-router'
import { AuthLayout } from '@/features/auth'
import { ApplicationShell } from '@/features/shell'
import { AuthLoginPage } from '@/features/auth/pages/AuthLoginPage'
import { ShellHomePage } from '@/features/shell/pages/ShellHomePage'

const rootRoute = createRootRoute({})

const authLayoutRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/auth',
  component: AuthLayout,
})

const authLoginRoute = createRoute({
  getParentRoute: () => authLayoutRoute,
  path: 'login',
  component: AuthLoginPage,
})

const appLayoutRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/',
  component: ApplicationShell,
})

const appHomeRoute = createRoute({
  getParentRoute: () => appLayoutRoute,
  path: '/',
  component: ShellHomePage,
})

const routeTree = rootRoute.addChildren([
  authLayoutRoute.addChildren([authLoginRoute]),
  appLayoutRoute.addChildren([appHomeRoute]),
])

export const router = createRouter({
  routeTree,
  defaultPreload: 'intent',
})

declare module '@tanstack/react-router' {
  interface Register {
    router: typeof router
  }
}

export const authLoginPath = '/auth/login' as const

export function redirectToLogin(): never {
  throw redirect({ to: authLoginPath })
}
