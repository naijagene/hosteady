import { ApiError } from '@/api/errors'

export type BootstrapFailureKind = 'unauthorized' | 'forbidden' | 'recoverable'

export class BootstrapError extends Error {
  readonly kind: BootstrapFailureKind

  constructor(message: string, kind: BootstrapFailureKind, cause?: unknown) {
    super(message, { cause })
    this.name = 'BootstrapError'
    this.kind = kind
  }

  static fromUnknown(error: unknown): BootstrapError {
    if (error instanceof BootstrapError) {
      return error
    }

    if (error instanceof ApiError) {
      if (error.kind === 'unauthorized') {
        return new BootstrapError(error.message, 'unauthorized', error)
      }

      if (error.kind === 'forbidden') {
        return new BootstrapError(error.message, 'forbidden', error)
      }

      if (error.kind === 'network' || error.kind === 'server') {
        return new BootstrapError(error.message, 'recoverable', error)
      }
    }

    if (error instanceof Error && error.message === 'Session expired') {
      return new BootstrapError(error.message, 'unauthorized', error)
    }

    const message =
      error instanceof Error ? error.message : 'Unable to initialize HEOS.'

    return new BootstrapError(message, 'recoverable', error)
  }
}
