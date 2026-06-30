const BLOCKED_REDIRECT_PATHS = new Set([
  '/login',
  '/logout',
  '/unauthorized',
  '/forbidden',
  '/loading',
])

export function sanitizeRedirectTarget(
  raw: string | undefined | null,
): string | undefined {
  if (!raw) {
    return undefined
  }

  const path = raw.trim()

  if (!path.startsWith('/') || path.startsWith('//')) {
    return undefined
  }

  if (path.includes('://') || path.includes('\\')) {
    return undefined
  }

  const pathname = path.split(/[?#]/)[0] ?? path

  if (BLOCKED_REDIRECT_PATHS.has(pathname)) {
    return undefined
  }

  return path
}
