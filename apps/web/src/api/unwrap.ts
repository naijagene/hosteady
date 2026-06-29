export function unwrapData<T>(payload: T | { data: T }): T {
  if (
    payload !== null &&
    typeof payload === 'object' &&
    'data' in payload &&
    (payload as { data: T }).data !== undefined
  ) {
    return (payload as { data: T }).data
  }

  return payload as T
}
