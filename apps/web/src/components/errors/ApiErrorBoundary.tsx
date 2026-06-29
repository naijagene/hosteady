import { Component, type ReactNode } from 'react'
import { ApiError } from '@/api/errors'

interface ApiErrorBoundaryProps {
  children: ReactNode
  onUnauthorized?: () => void
  onForbidden?: () => void
}

interface ApiErrorBoundaryState {
  error: ApiError | null
}

export class ApiErrorBoundary extends Component<
  ApiErrorBoundaryProps,
  ApiErrorBoundaryState
> {
  state: ApiErrorBoundaryState = { error: null }

  static getDerivedStateFromError(error: unknown): ApiErrorBoundaryState {
    if (error instanceof ApiError) {
      return { error }
    }

    if (error instanceof Error) {
      return {
        error: new ApiError(error.message),
      }
    }

    return { error: new ApiError('An unexpected API error occurred.') }
  }

  componentDidCatch(error: unknown): void {
    if (!(error instanceof ApiError)) {
      return
    }

    if (error.kind === 'unauthorized') {
      this.props.onUnauthorized?.()
    }

    if (error.kind === 'forbidden') {
      this.props.onForbidden?.()
    }
  }

  render() {
    if (this.state.error) {
      return (
        <div className="flex h-full flex-col items-center justify-center gap-2 p-8">
          <h1 className="text-lg font-semibold">Request failed</h1>
          <p className="text-sm text-muted-foreground">
            {this.state.error.message}
          </p>
        </div>
      )
    }

    return this.props.children
  }
}
