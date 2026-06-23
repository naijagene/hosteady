export type HLogoSize = 'sm' | 'md' | 'lg'

export interface HLogoProps {
  size?: HLogoSize
  showWordmark?: boolean
  className?: string
}

const markSizes: Record<HLogoSize, string> = {
  sm: 'h-6 w-6',
  md: 'h-8 w-8',
  lg: 'h-10 w-10',
}

const wordmarkSizes: Record<HLogoSize, string> = {
  sm: 'text-sm',
  md: 'text-base',
  lg: 'text-lg',
}

export function HLogo({
  size = 'md',
  showWordmark = true,
  className = '',
}: HLogoProps) {
  return (
    <div className={`flex items-center gap-2.5 ${className}`}>
      <svg
        className={markSizes[size]}
        viewBox="0 0 32 32"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
      >
        <rect width="32" height="32" rx="6" fill="#0c2d57" />
        <path
          d="M8 22V10h3.2l4.8 7.2V10H19v12h-3.2l-4.8-7.2V22H8z"
          fill="#c9a227"
        />
        <path d="M21 10h3v12h-3V10z" fill="#ffffff" />
      </svg>
      {showWordmark && (
        <span
          className={`font-semibold tracking-tight text-white ${wordmarkSizes[size]}`}
        >
          Hosteady
        </span>
      )}
    </div>
  )
}
