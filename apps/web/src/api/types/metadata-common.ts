export type MetadataRecord = Record<string, unknown>

export function asRecord(value: unknown): MetadataRecord {
  return value !== null && typeof value === 'object' && !Array.isArray(value)
    ? (value as MetadataRecord)
    : {}
}

export function asArray<T = MetadataRecord>(value: unknown): T[] {
  return Array.isArray(value) ? (value as T[]) : []
}

export function asString(value: unknown, fallback = ''): string {
  return typeof value === 'string' ? value : fallback
}

export function asNumber(value: unknown, fallback = 0): number {
  return typeof value === 'number' && Number.isFinite(value) ? value : fallback
}

export function asBoolean(value: unknown, fallback = false): boolean {
  return typeof value === 'boolean' ? value : fallback
}
