import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link, useNavigate, useSearch } from '@tanstack/react-router'
import { ApiError } from '@/api/errors'
import type { LoginRequest } from '@/api/types/auth'
import { performLogin } from '@/features/auth/services/session-service'
import { Spinner } from '@/components/loading/Spinner'

export function AuthLoginPage() {
  const navigate = useNavigate()
  const search = useSearch({ strict: false }) as { redirect?: string }
  const [submitError, setSubmitError] = useState<string | null>(null)
  const {
    register,
    handleSubmit,
    formState: { isSubmitting, errors },
  } = useForm<LoginRequest>({
    defaultValues: {
      email: '',
      password: '',
      remember: true,
    },
  })

  const onSubmit = handleSubmit(async (values) => {
    setSubmitError(null)

    try {
      await performLogin(values)
      await navigate({ to: search.redirect ?? '/' })
    } catch (error) {
      setSubmitError(
        error instanceof ApiError
          ? error.message
          : error instanceof Error
            ? error.message
            : 'Unable to sign in.',
      )
    }
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
          {...register('email', { required: 'Email is required' })}
        />
        {errors.email ? (
          <p className="text-xs text-destructive">{errors.email.message}</p>
        ) : null}
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
          {...register('password', { required: 'Password is required' })}
        />
        {errors.password ? (
          <p className="text-xs text-destructive">{errors.password.message}</p>
        ) : null}
      </div>
      <label className="flex items-center gap-2 text-sm">
        <input type="checkbox" {...register('remember')} />
        Remember me
      </label>
      {submitError ? (
        <p className="text-sm text-destructive">{submitError}</p>
      ) : null}
      <button
        type="submit"
        disabled={isSubmitting}
        className="inline-flex w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
      >
        {isSubmitting ? <Spinner className="h-4 w-4" /> : null}
        Sign in
      </button>
      <p className="text-center text-xs text-muted-foreground">
        Need access? Contact your HEOS administrator.
      </p>
      <p className="text-center text-xs">
        <Link to="/unauthorized" className="text-primary">
          Unauthorized help
        </Link>
      </p>
    </form>
  )
}
