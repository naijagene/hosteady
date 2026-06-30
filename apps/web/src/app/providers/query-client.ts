import type { QueryClient } from '@tanstack/react-query'

let registeredQueryClient: QueryClient | null = null

export function registerQueryClient(client: QueryClient): void {
  registeredQueryClient = client
}

export function clearQueryCache(): void {
  registeredQueryClient?.clear()
}

export function getRegisteredQueryClient(): QueryClient | null {
  return registeredQueryClient
}
