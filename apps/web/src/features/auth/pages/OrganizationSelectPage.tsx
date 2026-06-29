import { switchOrganization } from '@/features/auth/services/session-service'
import { useAuthStore } from '@/stores/auth-store'

export function OrganizationSelectPage() {
  const organizations = useAuthStore((state) => state.organizations)
  const organization = useAuthStore((state) => state.organization)

  if (organizations.length <= 1) {
    return null
  }

  return (
    <div className="mx-auto max-w-lg space-y-4">
      <div>
        <h1 className="text-lg font-semibold">Select organization</h1>
        <p className="text-sm text-muted-foreground">
          Choose the organization context for this session.
        </p>
      </div>
      <div className="space-y-2">
        {organizations.map((entry) => (
          <button
            key={entry.public_id}
            type="button"
            className={`flex w-full items-center justify-between rounded-lg border px-4 py-3 text-left ${
              organization?.public_id === entry.public_id
                ? 'border-primary bg-primary/5'
                : 'border-border'
            }`}
            onClick={() => void switchOrganization(entry.public_id)}
          >
            <span>
              <span className="block text-sm font-medium">{entry.name}</span>
              <span className="text-xs text-muted-foreground">{entry.slug}</span>
            </span>
          </button>
        ))}
      </div>
    </div>
  )
}
