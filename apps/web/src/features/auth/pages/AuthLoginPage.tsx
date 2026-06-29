import { useForm } from 'react-hook-form'
import type { LoginRequest } from '@/api/types/auth'

export function AuthLoginPage() {
  const {
    register,
    handleSubmit,
    formState: { isSubmitting },
  } = useForm<LoginRequest>({
    defaultValues: {
      email: '',
      password: '',
    },
  })

  const onSubmit = handleSubmit(async () => {
    // P1-002 will wire POST /auth/login and session bootstrap.
  })

  return (
    <form className="space-y-4" onSubmit={onSubmit}>
      <div className="space-y-2">
        <label className="text-sm font-medium" htmlFor="email">
          Email
        </label>
        <input
          id="email"
          type="email"
          autoComplete="email"
          className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
          {...register('email', { required: true })}
        />
      </div>
      <div className="space-y-2">
        <label className="text-sm font-medium" htmlFor="password">
          Password
        </label>
        <input
          id="password"
          type="password"
          autoComplete="current-password"
          className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
          {...register('password', { required: true })}
        />
      </div>
      <button
        type="submit"
        disabled={isSubmitting}
        className="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
      >
        Continue
      </button>
      <p className="text-center text-xs text-muted-foreground">
        Placeholder only — no auth request is sent in P1-001.
      </p>
    </form>
  )
}
