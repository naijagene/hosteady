import type { UiComponent } from '@/api/types/ui'
import { normalizeNotificationBindingContext } from '@/api/types/notifications'
import { NotificationCenter } from '@/features/notifications/components/NotificationCenter'

interface NotificationBindingRendererProps {
  component: UiComponent
}

export function NotificationBindingRenderer({ component }: NotificationBindingRendererProps) {
  const binding = normalizeNotificationBindingContext(component.binding_config)

  return (
    <div data-testid="notification-binding-renderer">
      <NotificationCenter title={component.name} binding={binding} />
    </div>
  )
}
