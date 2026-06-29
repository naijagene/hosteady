import type { SearchCommand } from '@/api/types/search'

export const defaultCommands: SearchCommand[] = [
  {
    command_key: 'go-home',
    title: 'Go to Home',
    description: 'Navigate to the home page',
    category: 'Navigation',
    action: { action_type: 'navigate', route: '/' },
    keywords: ['home', 'dashboard'],
  },
  {
    command_key: 'go-documents',
    title: 'Go to Documents',
    description: 'Open the documents manager',
    category: 'Navigation',
    permission: 'documents.read',
    action: { action_type: 'navigate', route: '/documents' },
    keywords: ['documents', 'files'],
  },
  {
    command_key: 'go-reports',
    title: 'Go to Reports',
    description: 'Browse available reports',
    category: 'Navigation',
    permission: 'reports.read',
    action: { action_type: 'navigate', route: '/reports/platform/summary' },
    keywords: ['reports'],
  },
  {
    command_key: 'go-dashboards',
    title: 'Go to Dashboards',
    description: 'Browse dashboards',
    category: 'Navigation',
    permission: 'dashboards.read',
    action: { action_type: 'navigate', route: '/dashboards/platform/home' },
    keywords: ['dashboards'],
  },
  {
    command_key: 'go-workflows',
    title: 'Go to Workflows',
    description: 'Open workflow inbox',
    category: 'Navigation',
    permission: 'workflow.runtime.read',
    action: { action_type: 'navigate', route: '/workflows' },
    keywords: ['workflows', 'tasks', 'approvals'],
  },
  {
    command_key: 'go-notifications',
    title: 'Go to Notifications',
    description: 'Open notification center',
    category: 'Navigation',
    permission: 'notifications.read',
    action: { action_type: 'navigate', route: '/notifications' },
    keywords: ['notifications', 'alerts'],
  },
  {
    command_key: 'go-settings',
    title: 'Go to Settings',
    description: 'Open settings page',
    category: 'Navigation',
    permission: 'settings.read',
    action: { action_type: 'navigate', route: '/settings' },
    keywords: ['settings', 'preferences'],
  },
  {
    command_key: 'theme-light',
    title: 'Switch to light theme',
    description: 'Use light theme',
    category: 'Theme',
    action: { action_type: 'execute_command', command_key: 'theme-light' },
    keywords: ['theme', 'light'],
  },
  {
    command_key: 'theme-dark',
    title: 'Switch to dark theme',
    description: 'Use dark theme',
    category: 'Theme',
    action: { action_type: 'execute_command', command_key: 'theme-dark' },
    keywords: ['theme', 'dark'],
  },
  {
    command_key: 'theme-system',
    title: 'Use system theme',
    description: 'Follow system theme preference',
    category: 'Theme',
    action: { action_type: 'execute_command', command_key: 'theme-system' },
    keywords: ['theme', 'system'],
  },
  {
    command_key: 'refresh-runtime',
    title: 'Refresh runtime',
    description: 'Reload hydrated runtime data',
    category: 'Workspace',
    action: { action_type: 'execute_command', command_key: 'refresh-runtime' },
    keywords: ['refresh', 'runtime'],
  },
  {
    command_key: 'switch-workspace',
    title: 'Switch workspace',
    description: 'Open workspace switcher placeholder',
    category: 'Workspace',
    action: { action_type: 'open_dialog', command_key: 'switch-workspace' },
    keywords: ['workspace', 'switch'],
  },
  {
    command_key: 'open-profile',
    title: 'Open profile',
    description: 'Profile settings placeholder',
    category: 'Actions',
    action: { action_type: 'open_dialog', command_key: 'open-profile' },
    keywords: ['profile', 'account'],
  },
  {
    command_key: 'open-help',
    title: 'Open help',
    description: 'Help and documentation placeholder',
    category: 'Actions',
    action: { action_type: 'open_dialog', command_key: 'open-help' },
    keywords: ['help', 'support'],
  },
  {
    command_key: 'go-search',
    title: 'Open full search page',
    description: 'Navigate to search page',
    category: 'Navigation',
    action: { action_type: 'navigate', route: '/search' },
    keywords: ['search', 'find'],
  },
]

export function filterCommands(commands: SearchCommand[], query: string, permissions: string[]): SearchCommand[] {
  const normalized = query.trim().toLowerCase()

  return commands.filter((command) => {
    if (command.permission && permissions.length > 0 && !permissions.includes(command.permission)) {
      return false
    }

    if (!normalized) {
      return true
    }

    const haystack = [command.title, command.description ?? '', ...(command.keywords ?? [])]
      .join(' ')
      .toLowerCase()

    return haystack.includes(normalized)
  })
}

export function commandToSearchResult(command: SearchCommand) {
  return {
    id: command.command_key,
    title: command.title,
    description: command.description ?? command.category ?? 'Command',
    type: 'command' as const,
    icon: 'command',
    route: command.action.route ?? null,
    source: 'command' as const,
    permission: command.permission ?? null,
    metadata: { command_key: command.command_key, category: command.category },
    action: command.action,
  }
}
