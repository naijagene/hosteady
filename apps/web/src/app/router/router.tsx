import {
  createRootRoute,
  createRoute,
  createRouter,
  Outlet,
  redirect,
} from '@tanstack/react-router'
import { AppProviders } from '@/app/providers/AppProviders'
import {
  ApplicationLayout,
  AuthLayout,
  ErrorLayout,
  ForbiddenLayout,
  LoadingLayout,
  UnauthorizedLayout,
} from '@/layouts'
import {
  ApplicationGuard,
  AuthGuard,
  GuestGuard,
  PermissionGuard,
  WorkspaceGuard,
} from '@/features/guards'
import { AuthLoginPage } from '@/features/auth/pages/AuthLoginPage'
import { LogoutPage } from '@/features/auth/pages/LogoutPage'
import { MetadataPage } from '@/features/renderer/pages/MetadataPage'
import { ShellHomePage } from '@/features/shell/pages/ShellHomePage'
import { SettingsPage } from '@/features/shell/pages/SettingsPage'

const rootRoute = createRootRoute({
  component: () => (
    <AppProviders>
      <Outlet />
    </AppProviders>
  ),
})

const loginRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/login',
  validateSearch: (search: Record<string, unknown>) => ({
    redirect: typeof search.redirect === 'string' ? search.redirect : undefined,
  }),
  component: () => (
    <GuestGuard>
      <AuthLayout>
        <AuthLoginPage />
      </AuthLayout>
    </GuestGuard>
  ),
})

const logoutRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/logout',
  component: LogoutPage,
})

const unauthorizedRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/unauthorized',
  component: UnauthorizedLayout,
})

const forbiddenRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/forbidden',
  component: ForbiddenLayout,
})

const loadingRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/loading',
  component: LoadingLayout,
})

const appRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/',
  component: () => (
    <AuthGuard>
      <WorkspaceGuard>
        <ApplicationGuard>
          <ApplicationLayout />
        </ApplicationGuard>
      </WorkspaceGuard>
    </AuthGuard>
  ),
})

const homeRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/',
  component: ShellHomePage,
})

const settingsRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/settings',
  component: () => (
    <PermissionGuard permission="settings.read">
      <SettingsPage />
    </PermissionGuard>
  ),
})

const metadataPageRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/app/$moduleKey/$pageKey',
  component: MetadataPage,
})

const routeTree = rootRoute.addChildren([
  loginRoute,
  logoutRoute,
  unauthorizedRoute,
  forbiddenRoute,
  loadingRoute,
  appRoute.addChildren([homeRoute, settingsRoute, metadataPageRoute]),
])

export const router = createRouter({
  routeTree,
  defaultPreload: 'intent',
  defaultNotFoundComponent: ErrorLayout,
})

declare module '@tanstack/react-router' {
  interface Register {
    router: typeof router
  }
}

export function redirectToLogin(): never {
  throw redirect({ to: '/login', search: { redirect: undefined } })
}
