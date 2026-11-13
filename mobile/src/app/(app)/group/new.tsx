import { router } from 'expo-router';
import React, { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Banner, Button, Field, Text } from '../../../components/ui';
import { api, ApiError } from '../../../lib/api';
import { colors } from '../../../lib/theme';
import type { Group } from '../../../lib/types';

export default function NewGroupScreen() {
  const insets = useSafeAreaInsets();
  const [name, setName] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);

  async function save() {
    setBusy(true);
    setError(null);
    try {
      const res = await api.post<{ group: Group }>('/groups', { name: name.trim() });
      router.replace({ pathname: '/group/[id]', params: { id: String(res.group.id) } });
    } catch (e) {
      setError(e as ApiError);
    } finally {
      setBusy(false);
    }
  }

  return (
    <KeyboardAvoidingView style={{ flex: 1, backgroundColor: colors.white }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={{ padding: 20, paddingBottom: insets.bottom + 24, gap: 16 }} keyboardShouldPersistTaps="handled">
        <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
          <Button label="Zrušit" kind="ghost" small onPress={() => router.back()} />
          <Text variant="title">Nová skupina</Text>
          <View style={{ width: 70 }} />
        </View>
        <Field label="Název skupiny" value={name} onChangeText={setName} placeholder="např. Naše rodina" error={error?.field('name')} />
        {error && !Object.keys(error.errors).length ? <Banner tone="danger" icon="alert-circle">{error.message}</Banner> : null}
        <Button label="Vytvořit skupinu" onPress={save} loading={busy} disabled={!name.trim()} />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
