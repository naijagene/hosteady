import { useEffect, useState } from 'react'

interface TableSearchBoxProps {
  value: string
  onChange: (value: string) => void
  loading?: boolean
}

export function TableSearchBox({ value, onChange, loading = false }: TableSearchBoxProps) {
  const [draft, setDraft] = useState(value)

  useEffect(() => {
    const timer = window.setTimeout(() => {
      if (draft !== value) {
        onChange(draft)
      }
    }, 300)

    return () => window.clearTimeout(timer)
  }, [draft, onChange, value])

  return (
    <div className="flex items-center gap-2">
      <label htmlFor="table-search" className="sr-only">
        Search records
      </label>
      <input
        id="table-search"
        type="search"
        value={draft}
        placeholder="Search records"
        className="w-full max-w-xs rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
        onChange={(event) => setDraft(event.target.value)}
      />
      {draft ? (
        <button
          type="button"
          className="text-xs text-muted-foreground underline"
          onClick={() => {
            setDraft('')
            onChange('')
          }}
        >
          Clear
        </button>
      ) : null}
      {loading ? <span className="text-xs text-muted-foreground">Searching…</span> : null}
    </div>
  )
}
