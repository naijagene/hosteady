import type { NavigationMenuResponse } from '@/api/types/runtime'

export const PLATFORM_FALLBACK_MENU_KEY = 'platform-fallback'
export const PLATFORM_FALLBACK_SOURCE = 'platform_fallback'

export function buildPlatformFallbackNavigation(): NavigationMenuResponse[] {
  return [
    {
      menu_key: PLATFORM_FALLBACK_MENU_KEY,
      label: 'Platform (runtime fallback)',
      metadata: { source: PLATFORM_FALLBACK_SOURCE },
      groups: [
        {
          group_key: 'default',
          label: 'Main',
          items: [
            {
              item_key: 'fallback-home',
              label: 'Home',
              route: { path: '/' },
              metadata: { fallback: true, icon: 'home' },
            },
            {
              item_key: 'fallback-alpha-health',
              label: 'Alpha Health',
              route: { path: '/alpha/health' },
              metadata: { fallback: true, icon: 'health' },
            },
            {
              item_key: 'fallback-admin',
              label: 'Administration',
              route: { path: '/admin' },
              metadata: { fallback: true, icon: 'admin' },
            },
            {
              item_key: 'fallback-documents',
              label: 'Documents',
              route: { path: '/documents' },
              metadata: { fallback: true, icon: 'document' },
            },
            {
              item_key: 'fallback-workflows',
              label: 'Workflows',
              route: { path: '/workflows' },
              metadata: { fallback: true, icon: 'workflow' },
            },
            {
              item_key: 'fallback-notifications',
              label: 'Notifications',
              route: { path: '/notifications' },
              metadata: { fallback: true, icon: 'notification' },
            },
            {
              item_key: 'fallback-search',
              label: 'Search',
              route: { path: '/search' },
              metadata: { fallback: true, icon: 'search' },
            },
            {
              item_key: 'fallback-activity',
              label: 'Activity',
              route: { path: '/activity' },
              metadata: { fallback: true, icon: 'activity' },
            },
          ],
        },
      ],
    },
  ]
}
