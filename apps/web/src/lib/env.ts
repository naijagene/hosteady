const apiBaseUrl = import.meta.env.VITE_API_BASE_URL

export function getApiBaseUrl(): string {
  if (typeof apiBaseUrl !== 'string' || apiBaseUrl.trim() === '') {
    return 'http://localhost:8000/api/v1'
  }

  return apiBaseUrl.replace(/\/$/, '')
}
