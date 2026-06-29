import { useCallback, useEffect, useMemo, useState } from 'react'
import { useNavigate } from '@tanstack/react-router'
import type { SearchResult } from '@/api/types/search'
import { executeCommand } from '../core/command-actions'
import { resolveSearchAction } from '../core/search-actions'
import { writeRecentSearch } from '../core/recent-searches-storage'
import { shouldSearch } from '../core/search-query'
import { useUniversalFinder } from './useUniversalFinder'

function isMacPlatform(): boolean {
  return typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform)
}

export function useCommandPalette() {
  const [open, setOpenState] = useState(false)
  const [query, setQueryState] = useState('')
  const [activeIndex, setActiveIndex] = useState(0)
  const navigate = useNavigate()
  const finder = useUniversalFinder(query, {
    include_backend: true,
    include_runtime: true,
    include_personalization: true,
    include_commands: true,
    limit: 20,
  })
  const defaultFinder = useUniversalFinder('', {
    include_backend: false,
    include_runtime: true,
    include_personalization: true,
    include_commands: true,
    limit: 12,
  })

  const setOpen = useCallback((value: boolean | ((current: boolean) => boolean)) => {
    setOpenState((current) => {
      const next = typeof value === 'function' ? value(current) : value
      if (next) {
        setActiveIndex(0)
      }
      return next
    })
  }, [])

  const setQuery = useCallback((value: string) => {
    setQueryState(value)
    setActiveIndex(0)
  }, [])

  const flatResults = useMemo(() => {
    if (shouldSearch(query)) {
      return finder.results
    }
    return defaultFinder.defaultItems
  }, [defaultFinder.defaultItems, finder.results, query])

  const activateResult = useCallback(
    async (result: SearchResult) => {
      if (query.trim()) {
        writeRecentSearch(query)
      }

      const action = resolveSearchAction(result)

      if (action.action_type === 'navigate' && action.route) {
        setOpen(false)
        await navigate({ to: action.route })
        return
      }

      if (action.action_type === 'execute_command' && action.command_key) {
        await executeCommand(action.command_key)
        setOpen(false)
        return
      }

      if (action.action_type === 'open_dialog') {
        setOpen(false)
      }
    },
    [navigate, query, setOpen],
  )

  useEffect(() => {
    const onKeyDown = (event: KeyboardEvent) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault()
        setOpen((current) => !current)
        return
      }

      if (!open) {
        return
      }

      if (event.key === 'Escape') {
        event.preventDefault()
        setOpen(false)
        return
      }

      if (event.key === 'ArrowDown') {
        event.preventDefault()
        setActiveIndex((current) => Math.min(current + 1, Math.max(flatResults.length - 1, 0)))
        return
      }

      if (event.key === 'ArrowUp') {
        event.preventDefault()
        setActiveIndex((current) => Math.max(current - 1, 0))
        return
      }

      if (event.key === 'Enter' && flatResults[activeIndex]) {
        event.preventDefault()
        void activateResult(flatResults[activeIndex])
      }
    }

    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [activateResult, activeIndex, flatResults, open, setOpen])

  return {
    open,
    setOpen,
    query,
    setQuery,
    activeIndex,
    setActiveIndex,
    flatResults,
    isLoading: finder.isLoading,
    error: finder.error,
    source: finder.source,
    shortcutHint: isMacPlatform() ? '⌘K' : 'Ctrl+K',
    activateResult,
  }
}
