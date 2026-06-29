export function fieldAriaProps(fieldKey: string, error?: string) {
  return {
    id: fieldKey,
    name: fieldKey,
    'aria-invalid': error ? true : undefined,
    'aria-describedby': error ? `${fieldKey}-error` : undefined,
  } as const
}

export const inputClassName =
  'w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-60'
