import Constants from 'expo-constants';
import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

const TOKEN_KEY = 'giftis_token';
const API_PORT = 8001;

function resolveBaseUrl(): string {
  const fromEnv = process.env.EXPO_PUBLIC_API_URL;
  if (fromEnv) return fromEnv.replace(/\/$/, '');

  let host = 'localhost';
  if (Platform.OS === 'web') {
    if (typeof window !== 'undefined' && window.location?.hostname) host = window.location.hostname;
  } else {
    const hostUri = Constants.expoConfig?.hostUri; // např. "192.168.1.10:8081"
    if (hostUri) host = hostUri.split(':')[0];
    else if (Platform.OS === 'android') host = '10.0.2.2';
  }

  return `http://${host}:${API_PORT}/api/v1`;
}

export const API_URL = resolveBaseUrl();

export const storage = {
  async get(): Promise<string | null> {
    if (Platform.OS === 'web') {
      try {
        return window.localStorage.getItem(TOKEN_KEY);
      } catch {
        return null;
      }
    }
    return SecureStore.getItemAsync(TOKEN_KEY);
  },
  async set(token: string): Promise<void> {
    if (Platform.OS === 'web') {
      try {
        window.localStorage.setItem(TOKEN_KEY, token);
      } catch {}
      return;
    }
    await SecureStore.setItemAsync(TOKEN_KEY, token);
  },
  async clear(): Promise<void> {
    if (Platform.OS === 'web') {
      try {
        window.localStorage.removeItem(TOKEN_KEY);
      } catch {}
      return;
    }
    await SecureStore.deleteItemAsync(TOKEN_KEY);
  },
};

export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]>;

  constructor(message: string, status: number, errors: Record<string, string[]> = {}) {
    super(message);
    this.status = status;
    this.errors = errors;
  }

  field(name: string): string | undefined {
    return this.errors[name]?.[0];
  }
}

let token: string | null = null;
let onUnauthorized: (() => void) | null = null;

export function setToken(value: string | null) {
  token = value;
}

export function setUnauthorizedHandler(handler: (() => void) | null) {
  onUnauthorized = handler;
}

type Method = 'GET' | 'POST' | 'PUT' | 'DELETE';

async function request<T>(method: Method, path: string, body?: unknown): Promise<T> {
  let res: Response;
  const isForm = typeof FormData !== 'undefined' && body instanceof FormData;
  try {
    res = await fetch(`${API_URL}${path}`, {
      method,
      headers: {
        Accept: 'application/json',
        // U multipart necháváme Content-Type na fetch (doplní boundary).
        ...(isForm ? {} : { 'Content-Type': 'application/json' }),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: body === undefined ? undefined : isForm ? (body as FormData) : JSON.stringify(body),
    });
  } catch {
    throw new ApiError(`Nelze se spojit se serverem (${API_URL}). Běží Laravel?`, 0);
  }

  let data: any = null;
  try {
    data = await res.json();
  } catch {}

  if (!res.ok) {
    if (res.status === 401 && token && onUnauthorized) onUnauthorized();
    const message =
      data?.message ??
      (res.status === 403 ? 'K této akci nemáte oprávnění.' : res.status === 404 ? 'Nenalezeno.' : 'Něco se nepovedlo.');
    throw new ApiError(message, res.status, data?.errors ?? {});
  }

  return data as T;
}

export const api = {
  get: <T>(path: string) => request<T>('GET', path),
  post: <T>(path: string, body?: unknown) => request<T>('POST', path, body ?? {}),
  put: <T>(path: string, body?: unknown) => request<T>('PUT', path, body ?? {}),
  delete: <T>(path: string) => request<T>('DELETE', path),
};
