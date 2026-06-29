import { commandToSearchResult, defaultCommands, filterCommands } from '../core/command-registry'

export function useCommandRegistry(query: string, permissions: string[]) {
  const commands = filterCommands(defaultCommands, query, permissions)
  return {
    commands,
    commandResults: commands.map(commandToSearchResult),
  }
}
