import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import type { AdminUserProfile } from '@/api/types/admin'
import { normalizeAdminUserProfile } from '@/api/types/admin'

export function buildUserProfile(
  runtime: HydratedRuntimeBundle | null | undefined,
  sessionExpiresAt?: string | null,
): AdminUserProfile {
  const user = runtime?.user ?? runtime?.tenantContext?.user ?? null
  const membership = runtime?.membership ?? runtime?.workspaceRuntime?.membership ?? runtime?.tenantContext?.membership ?? null

  return normalizeAdminUserProfile(user ?? {}, {
    roles: runtime?.roles ?? [],
    permissions: runtime?.permissions ?? [],
    membership: membership ? ({ ...membership } as AdminUserProfile['membership']) : undefined,
    session_expires_at: sessionExpiresAt ?? null,
    avatar_url: null,
  })
}

export function getUserProfileFields(profile: AdminUserProfile): Array<{ label: string; value: string }> {
  return [
    { label: 'User', value: profile.display_name ?? '—' },
    { label: 'Email', value: profile.email ?? '—' },
    { label: 'Status', value: profile.status ?? '—' },
    { label: 'Roles', value: profile.roles?.join(', ') || '—' },
    { label: 'Permissions', value: String(profile.permissions?.length ?? 0) },
    { label: 'Membership', value: typeof profile.membership?.public_id === 'string' ? profile.membership.public_id : '—' },
    { label: 'Avatar', value: profile.avatar_url ?? 'Placeholder' },
    { label: 'Session expires', value: profile.session_expires_at ?? '—' },
  ]
}
