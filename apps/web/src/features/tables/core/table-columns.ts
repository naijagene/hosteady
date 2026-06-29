
export function toggleColumnVisibility(
  hiddenColumnKeys: Set<string>,
  columnKey: string,
): Set<string> {
  const next = new Set(hiddenColumnKeys)

  if (next.has(columnKey)) {
    next.delete(columnKey)
  } else {
    next.add(columnKey)
  }

  return next
}

export function setAllColumnsVisible(): Set<string> {
  return new Set()
}

export function getHiddenColumnCount(hiddenColumnKeys: Set<string>): number {
  return hiddenColumnKeys.size
}
