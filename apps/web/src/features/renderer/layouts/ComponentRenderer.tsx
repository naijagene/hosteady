import { createElement } from 'react'
import { ErrorBoundary } from '@/components/errors/ErrorBoundary'
import type { UiComponent } from '@/api/types/ui'
import { isBindingType, resolveComponentBinding } from '../core/binding-resolver'
import { has, resolve } from '../core/ComponentRegistry'
import { hasPermission } from '../core/renderer-utils'
import { useOptionalRendererContext } from '../hooks/useRendererContext'
import { UnknownComponent } from '../components/UnknownComponent'
import { FormBindingRenderer } from '../bindings/FormBindingRenderer'
import { TableBindingRenderer } from '../bindings/TableBindingRenderer'
import { DashboardBindingRenderer } from '../bindings/DashboardBindingRenderer'
import { ReportBindingRenderer } from '../bindings/ReportBindingRenderer'
import { DocumentBindingRenderer } from '../bindings/DocumentBindingRenderer'
import { WorkflowBindingRenderer } from '../bindings/WorkflowBindingRenderer'
import { NotificationBindingRenderer } from '../bindings/NotificationBindingRenderer'
import { ActivityBindingRenderer } from '../bindings/ActivityBindingRenderer'
import { AdministrationBindingRenderer } from '../bindings/AdministrationBindingRenderer'

interface ComponentRendererProps {
  component: UiComponent | null | undefined
}

function BindingRenderer({ component }: { component: UiComponent }) {
  const binding = resolveComponentBinding(component)

  if (isBindingType(binding, 'form')) {
    return <FormBindingRenderer component={component} />
  }

  if (isBindingType(binding, 'table')) {
    return <TableBindingRenderer component={component} />
  }

  if (isBindingType(binding, 'dashboard')) {
    return <DashboardBindingRenderer component={component} />
  }

  if (isBindingType(binding, 'report')) {
    return <ReportBindingRenderer component={component} />
  }

  if (
    isBindingType(binding, 'document_list', 'document') ||
    component.component_type === 'document_list'
  ) {
    return <DocumentBindingRenderer component={component} />
  }

  if (
    isBindingType(binding, 'workflow_queue', 'approval_queue', 'workflow') ||
    component.component_type === 'workflow_queue' ||
    component.component_type === 'approval_queue'
  ) {
    return <WorkflowBindingRenderer component={component} />
  }

  if (
    isBindingType(binding, 'notification', 'notification_list', 'notification_center') ||
    component.component_type === 'notification' ||
    component.component_type === 'notification_list'
  ) {
    return <NotificationBindingRenderer component={component} />
  }

  if (
    isBindingType(binding, 'activity_feed', 'activity', 'audit', 'history') ||
    component.component_type === 'activity_feed' ||
    component.component_type === 'activity'
  ) {
    return <ActivityBindingRenderer component={component} />
  }

  if (
    isBindingType(binding, 'platform_overview', 'runtime_summary', 'organization_summary', 'permission_browser', 'role_browser') ||
    component.component_type === 'platform_overview'
  ) {
    return <AdministrationBindingRenderer component={component} />
  }

  return <UnknownComponent component={component} />
}

export function ComponentRenderer({ component }: ComponentRendererProps) {
  const context = useOptionalRendererContext()

  if (!component) {
    return null
  }

  const allowed = hasPermission(context?.permissions ?? [], component.permission)

  if (!allowed) {
    if (context?.devMode) {
      return (
        <div
          className="rounded-md border border-dashed border-border bg-muted/20 p-3 text-xs text-muted-foreground"
          data-testid="restricted-component"
        >
          Restricted: {component.name}
        </div>
      )
    }

    return null
  }

  const binding = resolveComponentBinding(component, context?.moduleKey)
  const bindingTypes = new Set([
    'form',
    'table',
    'dashboard',
    'report',
    'document_list',
    'document',
    'workflow_queue',
    'approval_queue',
    'workflow',
    'notification',
    'notification_list',
    'notification_center',
    'activity_feed',
    'activity',
    'audit',
    'history',
    'platform_overview',
    'runtime_summary',
    'organization_summary',
    'permission_browser',
    'role_browser',
  ])

  const usesBinding =
    Boolean(binding?.bindingType && bindingTypes.has(binding.bindingType)) ||
    bindingTypes.has(component.component_type)

  if (usesBinding) {
    return (
      <ErrorBoundary
        fallback={
          <div data-testid="component-error-boundary" className="text-sm text-muted-foreground">
            Component failed to render
          </div>
        }
      >
        <BindingRenderer component={component} />
      </ErrorBoundary>
    )
  }

  const type = component.component_type || 'custom'
  const Registered = resolve(type)

  if (!Registered) {
    return (
      <ErrorBoundary>
        <UnknownComponent component={component} />
      </ErrorBoundary>
    )
  }

  return (
    <ErrorBoundary
      fallback={
        <div data-testid="component-error-boundary" className="text-sm text-muted-foreground">
          Component failed to render
        </div>
      }
    >
      <div data-component-type={type} data-registry-hit={has(type) ? 'true' : 'false'}>
        {createElement(Registered, { component })}
      </div>
    </ErrorBoundary>
  )
}
