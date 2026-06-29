import { useState } from 'react'
import { Search } from '@/components/icons'

export function SearchDialog() {
  const [open, setOpen] = useState(false)

  return (
    <>
      <button
        type="button"
        className="inline-flex items-center gap-2 rounded-md border border-primary-foreground/20 px-3 py-1.5 text-sm text-primary-foreground hover:bg-primary-foreground/10"
        onClick={() => setOpen(true)}
      >
        <Search className="h-4 w-4" aria-hidden />
        <span className="hidden md:inline">Search</span>
      </button>
      {open ? (
        <div className="fixed inset-0 z-50 flex items-start justify-center bg-black/40 p-6">
          <div className="mt-24 w-full max-w-lg rounded-xl border border-border bg-card p-4 shadow-xl">
            <div className="mb-3 flex items-center justify-between">
              <h2 className="text-sm font-medium">Global search</h2>
              <button
                type="button"
                className="text-sm text-muted-foreground"
                onClick={() => setOpen(false)}
              >
                Close
              </button>
            </div>
            <input
              autoFocus
              type="search"
              placeholder="Search HEOS…"
              className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            />
            <p className="mt-3 text-xs text-muted-foreground">
              Placeholder only — backend search integration arrives in a later milestone.
            </p>
          </div>
        </div>
      ) : null}
    </>
  )
}
