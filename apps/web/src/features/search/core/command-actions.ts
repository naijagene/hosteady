import { useAuthStore } from '@/stores/auth-store'

export interface CommandExecutionResult {
  success: boolean
  message?: string
}

function applyTheme(mode: 'light' | 'dark' | 'system'): CommandExecutionResult {
  const resolved =
    mode === 'system'
      ? window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light'
      : mode

  document.documentElement.dataset.theme = resolved
  document.documentElement.classList.toggle('dark', resolved === 'dark')
  return { success: true, message: `Theme set to ${mode}` }
}

export async function executeCommand(commandKey: string): Promise<CommandExecutionResult> {
  switch (commandKey) {
    case 'theme-light':
      return applyTheme('light')
    case 'theme-dark':
      return applyTheme('dark')
    case 'theme-system':
      return applyTheme('system')
    case 'refresh-runtime':
      await useAuthStore.getState().restore()
      return { success: true, message: 'Runtime refreshed' }
    case 'switch-workspace':
      return { success: true, message: 'Workspace switcher is not available yet.' }
    case 'open-profile':
      return { success: true, message: 'Profile settings are not available yet.' }
    case 'open-help':
      return { success: true, message: 'Help center is not available yet.' }
    default:
      return { success: false, message: 'Unsupported command.' }
  }
}
