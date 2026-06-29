import { describe, expect, it } from 'vitest'
import {
  hasWidgetType,
  listRegisteredWidgetTypes,
  registerWidget,
  resolveWidgetComponent,
} from '@/features/dashboards/widgets/widget-registry'
import { ApprovalQueueWidget, WorkflowInboxWidget, WorkflowStatusWidget } from '@/features/workflows'
import { PlaceholderWidget } from '@/features/dashboards/widgets/PlaceholderWidget'
import { MetricWidget } from '@/features/dashboards/widgets/MetricWidget'

describe('dashboard widget registry', () => {
  it('lists registered widget types', () => {
    const types = listRegisteredWidgetTypes()
    expect(types).toContain('metric')
    expect(types).toContain('chart')
    expect(types).toContain('document_list')
    expect(types).toContain('workflow_queue')
    expect(types).toContain('approval_queue')
    expect(types).toContain('workflow_status')
    expect(hasWidgetType('metric')).toBe(true)
    expect(hasWidgetType('document_list')).toBe(true)
  })

  it('resolves known and unknown widget components', () => {
    expect(resolveWidgetComponent('metric')).toBe(MetricWidget)
    expect(resolveWidgetComponent('workflow_queue')).toBe(WorkflowInboxWidget)
    expect(resolveWidgetComponent('approval_queue')).toBe(ApprovalQueueWidget)
    expect(resolveWidgetComponent('workflow_status')).toBe(WorkflowStatusWidget)
    expect(resolveWidgetComponent('unknown-widget')).toBe(PlaceholderWidget)
  })

  it('registers custom widget types safely', () => {
    registerWidget('demo_widget', PlaceholderWidget)
    expect(hasWidgetType('demo_widget')).toBe(true)
    expect(resolveWidgetComponent('demo_widget')).toBe(PlaceholderWidget)
  })
})
