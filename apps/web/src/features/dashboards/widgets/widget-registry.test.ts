import { describe, expect, it } from 'vitest'
import {
  hasWidgetType,
  listRegisteredWidgetTypes,
  registerWidget,
  resolveWidgetComponent,
} from '@/features/dashboards/widgets/widget-registry'
import { PlaceholderWidget } from '@/features/dashboards/widgets/PlaceholderWidget'
import { MetricWidget } from '@/features/dashboards/widgets/MetricWidget'

describe('dashboard widget registry', () => {
  it('lists registered widget types', () => {
    const types = listRegisteredWidgetTypes()
    expect(types).toContain('metric')
    expect(types).toContain('chart')
    expect(hasWidgetType('metric')).toBe(true)
  })

  it('resolves known and unknown widget components', () => {
    expect(resolveWidgetComponent('metric')).toBe(MetricWidget)
    expect(resolveWidgetComponent('unknown-widget')).toBe(PlaceholderWidget)
  })

  it('registers custom widget types safely', () => {
    registerWidget('demo_widget', PlaceholderWidget)
    expect(hasWidgetType('demo_widget')).toBe(true)
    expect(resolveWidgetComponent('demo_widget')).toBe(PlaceholderWidget)
  })
})
