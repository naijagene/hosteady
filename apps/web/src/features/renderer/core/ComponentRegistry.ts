import type { ComponentType } from 'react'
import type { UiComponent } from '@/api/types/ui'

export type RegistryComponentProps = {
  component: UiComponent
}

export type RegistryComponent = ComponentType<RegistryComponentProps>

const registry = new Map<string, RegistryComponent>()

export function register(type: string, component: RegistryComponent): void {
  if (!type.trim()) {
    return
  }

  registry.set(type.toLowerCase(), component)
}

export function resolve(type: string | null | undefined): RegistryComponent | undefined {
  if (!type) {
    return undefined
  }

  return registry.get(type.toLowerCase())
}

export function has(type: string): boolean {
  return registry.has(type.toLowerCase())
}

export function listRegisteredTypes(): string[] {
  return Array.from(registry.keys())
}

export function clearRegistryForTests(): void {
  registry.clear()
}

export function getRegistrySize(): number {
  return registry.size
}
