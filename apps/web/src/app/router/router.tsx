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
import { DirectFormPage } from '@/features/forms/pages/DirectFormPage'
import { DirectTablePage } from '@/features/tables/pages/DirectTablePage'
import { DirectDashboardPage } from '@/features/dashboards/pages/DirectDashboardPage'
import { DirectReportPage } from '@/features/reports/pages/DirectReportPage'
import { DocumentManagerPage } from '@/features/documents/pages/DocumentManagerPage'
import { DirectDocumentPage } from '@/features/documents/pages/DirectDocumentPage'
import {
  ApprovalPage,
  WorkflowInboxPage,
  WorkflowInstancePage,
  WorkflowTaskPage,
} from '@/features/workflows'
import {
  NotificationCenterPage,
  NotificationDetailPage,
} from '@/features/notifications'
import {
  ActivityCenterPage,
  AuditViewerPage,
  EntityHistoryPage,
} from '@/features/activity'
import { MetadataPage } from '@/features/renderer/pages/MetadataPage'
import { ShellHomePage } from '@/features/shell/pages/ShellHomePage'
import { SettingsPage } from '@/features/shell/pages/SettingsPage'
import { SearchPage } from '@/features/search/pages/SearchPage'

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

const directFormRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/forms/$moduleKey/$formKey',
  component: DirectFormPage,
})

const directTableRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/tables/$moduleKey/$tableKey',
  component: DirectTablePage,
})

const directDashboardRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/dashboards/$moduleKey/$dashboardKey',
  component: DirectDashboardPage,
})

const directReportRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/reports/$moduleKey/$reportKey',
  component: DirectReportPage,
})

const documentsRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/documents',
  component: DocumentManagerPage,
})

const directDocumentRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/documents/$documentPublicId',
  component: DirectDocumentPage,
})

const workflowsRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/workflows',
  component: WorkflowInboxPage,
})

const workflowInstanceRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/workflows/instances/$instancePublicId',
  component: WorkflowInstancePage,
})

const workflowTaskRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/workflows/tasks/$taskPublicId',
  component: WorkflowTaskPage,
})

const workflowApprovalRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/workflows/approvals/$approvalPublicId',
  component: ApprovalPage,
})

const notificationsRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/notifications',
  component: NotificationCenterPage,
})

const notificationDetailRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/notifications/$publicId',
  component: NotificationDetailPage,
})

const searchRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/search',
  component: SearchPage,
})

const activityRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/activity',
  component: ActivityCenterPage,
})

const auditRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/activity/audit',
  component: AuditViewerPage,
})

const entityHistoryRoute = createRoute({
  getParentRoute: () => appRoute,
  path: '/activity/$entityType/$entityPublicId',
  component: EntityHistoryPage,
})

const routeTree = rootRoute.addChildren([
  loginRoute,
  logoutRoute,
  unauthorizedRoute,
  forbiddenRoute,
  loadingRoute,
  appRoute.addChildren([
    homeRoute,
    settingsRoute,
    metadataPageRoute,
    directFormRoute,
    directTableRoute,
    directDashboardRoute,
    directReportRoute,
    documentsRoute,
    directDocumentRoute,
    workflowsRoute,
    workflowInstanceRoute,
    workflowTaskRoute,
    workflowApprovalRoute,
    notificationsRoute,
    notificationDetailRoute,
    searchRoute,
    activityRoute,
    auditRoute,
    entityHistoryRoute,
  ]),
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
