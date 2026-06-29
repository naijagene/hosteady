import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { AuthUser } from '@/api/types/auth'

interface AuthState {
  token: string | null
  expiresAt: string | null
  user: AuthUser | null
  setSession: (session: {
    token: string
    expiresAt: string
    user: AuthUser
  }) => void
  setUser: (user: AuthUser) => void
  clearSession: () => void
  isAuthenticated: () => boolean
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      token: null,
      expiresAt: null,
      user: null,
      setSession: ({ token, expiresAt, user }) =>
        set({ token, expiresAt, user }),
      setUser: (user) => set({ user }),
      clearSession: () => set({ token: null, expiresAt: null, user: null }),
      isAuthenticated: () => Boolean(get().token),
    }),
    {
      name: 'heos-auth',
      partialize: (state) => ({
        token: state.token,
        expiresAt: state.expiresAt,
        user: state.user,
      }),
    },
  ),
)
