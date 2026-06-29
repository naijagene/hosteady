import { Spinner } from './Spinner'
import { cn } from '@/lib/utils'

interface LoadingOverlayProps {
  label?: string
  className?: string
}

export function LoadingOverlay({
  label = 'Loading…',
  className,
}: LoadingOverlayProps) {
  return (
    <div
      className={cn(
        'flex h-full min-h-[12rem] flex-col items-center justify-center gap-3 bg-background p-8',
        className,
      )}
    >
      <Spinner />
      <p className="text-sm text-muted-foreground">{label}</p>
    </div>
  )
}
