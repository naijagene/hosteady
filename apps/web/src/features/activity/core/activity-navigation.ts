export function buildActivityCenterRoutes() {
  return {
    center: '/activity',
    audit: '/activity/audit',
    history: '/activity/history',
    entity: (entityType: string, entityPublicId: string) => `/activity/${encodeURIComponent(entityType)}/${encodeURIComponent(entityPublicId)}`,
  }
}

export const activityRoutes = buildActivityCenterRoutes()
