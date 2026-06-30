import { clearQueryCache } from '@/app/providers/query-client'
import { useAuthStore } from '@/stores/auth-store'

export const HEOS_AUTH_STORAGE_KEY = 'heos-auth'

export function clearPersistedAuthStorage(): void {
  useAuthStore.persist.clearStorage()
  if (typeof localStorage !== 'undefined') {
    localStorage.removeItem(HEOS_AUTH_STORAGE_KEY)
  }
}

export async function resetSession(): Promise<void> {
  clearQueryCache()
  clearPersistedAuthStorage()
  useAuthStore.getState().clearAuth()
}
