/**
 * Placeholder for future refresh-token rotation.
 * Currently Sanctum personal access tokens do not expose refresh endpoints.
 */
export async function refreshAccessTokenPlaceholder(): Promise<string | null> {
  return null
}

export function shouldAttemptRefresh(): boolean {
  return false
}
