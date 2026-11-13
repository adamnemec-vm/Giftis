import { router, useLocalSearchParams } from 'expo-router';
import React, { useEffect, useState } from 'react';
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Banner, Button, Field, Text } from '../../../components/ui';
import { api, ApiError } from '../../../lib/api';
import { colors, fonts } from '../../../lib/theme';
import { OCCASIONS, type Wishlist } from '../../../lib/types';
import { Text as RNText } from 'react-native';

export default function WishlistFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const editing = !!id;
  const insets = useSafeAreaInsets();

  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [occasion, setOccasion] = useState<Wishlist['occasion']>('christmas');
  const [eventDate, setEventDate] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);

  useEffect(() => {
    if (!editing) return;
    api
      .get<{ wishlist: Wishlist }>(`/wishlists/${id}`)
      .then(({ wishlist }) => {
        setTitle(wishlist.title);
        setDescription(wishlist.description ?? '');
        setOccasion(wishlist.occasion);
        setEventDate(wishlist.event_date ?? '');
      })
      .catch(() => {});
  }, [editing, id]);

  async function save() {
    setBusy(true);
    setError(null);
    const body = {
      title: title.trim(),
      description: description.trim() || null,
      occasion,
      event_date: eventDate.trim() || null,
    };
    try {
      if (editing) {
        await api.put(`/wishlists/${id}`, body);
        router.back();
      } else {
        const res = await api.post<{ wishlist: Wishlist }>('/wishlists', body);
        router.replace({ pathname: '/wishlist/[id]', params: { id: String(res.wishlist.id) } });
      }
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
          <Text variant="title">{editing ? 'Upravit seznam' : 'Nový seznam'}</Text>
          <View style={{ width: 70 }} />
        </View>

        <Field label="Název seznamu" value={title} onChangeText={setTitle} placeholder="např. Vánoce 2026" error={error?.field('title')} />

        <View style={{ gap: 8 }}>
          <RNText style={{ fontFamily: fonts.semi, fontSize: 13, color: colors.text }}>Příležitost</RNText>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
            {OCCASIONS.map((o) => {
              const active = o.value === occasion;
              return (
                <Pressable
                  key={o.value}
                  accessibilityRole="button"
                  accessibilityState={{ selected: active }}
                  onPress={() => setOccasion(o.value)}
                  style={{
                    paddingHorizontal: 14,
                    height: 40,
                    borderRadius: 20,
                    justifyContent: 'center',
                    backgroundColor: active ? colors.burgundy : colors.cream,
                    borderWidth: 1,
                    borderColor: active ? colors.burgundy : colors.inputBorder,
                  }}
                >
                  <RNText style={{ fontFamily: fonts.semi, fontSize: 14, color: active ? colors.white : colors.textSoft }}>{o.label}</RNText>
                </Pressable>
              );
            })}
          </View>
        </View>

        <Field
          label="Datum události (volitelné)"
          value={eventDate}
          onChangeText={setEventDate}
          placeholder="RRRR-MM-DD, např. 2026-12-24"
          autoCapitalize="none"
          error={error?.field('event_date')}
        />
        <Field label="Poznámka (volitelné)" value={description} onChangeText={setDescription} multiline error={error?.field('description')} />

        {error && !Object.keys(error.errors).length ? <Banner tone="danger" icon="alert-circle">{error.message}</Banner> : null}

        <Button label={editing ? 'Uložit změny' : 'Vytvořit seznam'} onPress={save} loading={busy} />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
