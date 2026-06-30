import { register } from './core/ComponentRegistry'
import { CardComponent } from './components/CardComponent'
import { ChartPlaceholderComponent } from './components/ChartPlaceholderComponent'
import { DocumentListPlaceholder } from './components/DocumentListPlaceholder'
import { GridComponent } from './components/GridComponent'
import { MetricComponent } from './components/MetricComponent'
import { SectionComponent } from './components/SectionComponent'
import { StaticTextComponent } from './components/StaticTextComponent'
import { TabsComponent } from './components/TabsComponent'
import { UnknownComponent } from './components/UnknownComponent'
import { WorkflowQueuePlaceholder } from './components/WorkflowQueuePlaceholder'
import { FormBindingRenderer } from './bindings/FormBindingRenderer'
import { TableBindingRenderer } from './bindings/TableBindingRenderer'
import { DashboardBindingRenderer } from './bindings/DashboardBindingRenderer'
import { ReportBindingRenderer } from './bindings/ReportBindingRenderer'
import { ActivityBindingRenderer } from './bindings/ActivityBindingRenderer'

let initialized = false

export function registerDefaultComponents(): void {
  if (initialized) {
    return
  }

  register('static_text', StaticTextComponent)
  register('card', CardComponent)
  register('section', SectionComponent)
  register('grid', GridComponent)
  register('tabs', TabsComponent)
  register('metric', MetricComponent)
  register('chart', ChartPlaceholderComponent)
  register('document_list', DocumentListPlaceholder)
  register('workflow_queue', WorkflowQueuePlaceholder)
  register('approval_queue', WorkflowQueuePlaceholder)
  register('notification_list', StaticTextComponent)
  register('activity_feed', ActivityBindingRenderer)
  register('navigation_menu', StaticTextComponent)
  register('form', FormBindingRenderer)
  register('table', TableBindingRenderer)
  register('dashboard', DashboardBindingRenderer)
  register('report', ReportBindingRenderer)
  register('custom', UnknownComponent)

  initialized = true
}

export function resetDefaultComponentsForTests(): void {
  initialized = false
}

registerDefaultComponents()
