import { useFocusEffect } from 'expo-router';
import { useCallback, useRef, useState } from 'react';
import { ApiError } from './api';

export function useLoad<T>(fetcher: () => Promise<T>) {
  const [data, setData] = useState<T | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const fetcherRef = useRef(fetcher);
  fetcherRef.current = fetcher;
  const hasData = useRef(false);

  const run = useCallback(async (mode: 'focus' | 'refresh') => {
    if (mode === 'refresh') setRefreshing(true);
    else if (!hasData.current) setLoading(true);
    try {
      const res = await fetcherRef.current();
      setData(res);
      hasData.current = true;
      setError(null);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Něco se nepovedlo.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      void run('focus');
    }, [run]),
  );

  return {
    data,
    error,
    loading,
    refreshing,
    setData,
    reload: () => run('focus'),
    refresh: () => run('refresh'),
  };
}
