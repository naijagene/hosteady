import { useNavigate } from '@tanstack/react-router'
import { resetSession } from '@/features/auth/core/session-reset'
import { useAuthStore } from '@/stores/auth-store'

interface BootstrapRecoveryPanelProps {
  message?: string | null
  technicalMessage?: string | null
}

export function BootstrapRecoveryPanel({
  message,
  technicalMessage,
}: BootstrapRecoveryPanelProps) {
  const navigate = useNavigate()
  const retryBootstrap = useAuthStore((state) => state.retryBootstrap)
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated())

  return (
    <div className="flex min-h-[20rem] flex-col items-center justify-center gap-4 p-8 text-center">
      <h1 className="text-lg font-semibold">Unable to initialize HEOS</h1>
      <p className="max-w-md text-sm text-muted-foreground">
        {message ??
          'Something prevented HEOS from starting. You can retry or reset your session.'}
      </p>
      <div className="flex flex-wrap items-center justify-center gap-2">
        <button
          type="button"
          className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
          onClick={() => {
            void retryBootstrap()
          }}
        >
          Retry
        </button>
        <button
          type="button"
          className="rounded-md border border-input px-4 py-2 text-sm font-medium"
          onClick={() => {
            void resetSession().then(() => {
              void navigate({ to: '/login', replace: true, search: { redirect: undefined } })
            })
          }}
        >
          Reset Session
        </button>
        {!isAuthenticated ? (
          <button
            type="button"
            className="rounded-md border border-input px-4 py-2 text-sm font-medium"
            onClick={() => {
              void navigate({ to: '/login', replace: true, search: { redirect: undefined } })
            }}
          >
            Sign In again
          </button>
        ) : null}
      </div>
      {technicalMessage && technicalMessage !== message ? (
        <p className="max-w-lg text-xs text-muted-foreground">{technicalMessage}</p>
      ) : null}
    </div>
  )
}
