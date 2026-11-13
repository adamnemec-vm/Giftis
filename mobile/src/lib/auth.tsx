import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { api, setToken, setUnauthorizedHandler, storage } from './api';
import type { User } from './types';

type AuthState = {
  user: User | null;
  ready: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string, confirmation: string) => Promise<void>;
  loginWithGoogleCode: (code: string) => Promise<void>;
  logout: () => Promise<void>;
};

const AuthContext = createContext<AuthState | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [ready, setReady] = useState(false);

  const clear = useCallback(async () => {
    setToken(null);
    await storage.clear();
    setUser(null);
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(() => {
      void clear();
    });
    (async () => {
      const saved = await storage.get();
      if (saved) {
        setToken(saved);
        try {
          const res = await api.get<{ user: User }>('/me');
          setUser(res.user);
        } catch {
          await clear();
        }
      }
      setReady(true);
    })();
    return () => setUnauthorizedHandler(null);
  }, [clear]);

  const accept = useCallback(async (res: { token: string; user: User }) => {
    setToken(res.token);
    await storage.set(res.token);
    setUser(res.user);
  }, []);

  const value = useMemo<AuthState>(
    () => ({
      user,
      ready,
      login: async (email, password) => {
        await accept(await api.post('/auth/login', { email, password, device_name: 'Giftis app' }));
      },
      register: async (name, email, password, confirmation) => {
        await accept(
          await api.post('/auth/register', {
            name,
            email,
            password,
            password_confirmation: confirmation,
            device_name: 'Giftis app',
          }),
        );
      },
      loginWithGoogleCode: async (code) => {
        await accept(await api.post('/auth/google/exchange', { code, device_name: 'Giftis app' }));
      },
      logout: async () => {
        try {
          await api.post('/auth/logout');
        } catch {}
        await clear();
      },
    }),
    [user, ready, accept, clear],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth musí být uvnitř AuthProvider');
  return ctx;
}
