import { Feather } from '@expo/vector-icons';
import { useLocalSearchParams } from 'expo-router';
import React, { useState } from 'react';
import { KeyboardAvoidingView, Platform, Pressable, Text as RNText, TextInput, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  BackBar,
  Banner,
  Button,
  Card,
  Chip,
  ErrorNote,
  Field,
  ItemThumb,
  Loading,
  Pad,
  ProgressBar,
  Screen,
  SectionLabel,
  Text,
} from '../../../../components/ui';
import { api, ApiError } from '../../../../lib/api';
import { confirmAction, notify } from '../../../../lib/dialog';
import { useLoad } from '../../../../lib/hooks';
import { colors, fonts } from '../../../../lib/theme';
import type { Item, Wishlist } from '../../../../lib/types';

type Payload = { wishlist: Wishlist; items: Item[]; is_owner: boolean; can_reserve: boolean };

function time(iso: string | null): string {
  if (!iso) return '';
  const d = new Date(iso);
  return `${d.getHours()}:${String(d.getMinutes()).padStart(2, '0')}`;
}

export default function GroupGiftScreen() {
  const { code, itemId } = useLocalSearchParams<{ code: string; itemId: string }>();
  const insets = useSafeAreaInsets();
  const { data, loading, error, reload, refresh, refreshing } = useLoad(() => api.get<Payload>(`/shared/${code}`));
  const [amount, setAmount] = useState('');
  const [amountError, setAmountError] = useState<string | undefined>();
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');
  const [sending, setSending] = useState(false);

  const item = data?.items.find((i) => String(i.id) === itemId);

  if (loading && !data) return <Screen><BackBar /><Loading /></Screen>;
  if (!data || !item) return <Screen><BackBar /><ErrorNote message={error ?? 'Dárek nenalezen.'} onRetry={reload} /></Screen>;

  const { wishlist } = data;
  const mine = item.my_contribution_total ?? 0;
  const coordination = item.coordination;
  const canContribute = item.status === 'available' && data.can_reserve;

  async function contribute() {
    setBusy(true);
    setAmountError(undefined);
    try {
      await api.post(`/shared/${code}/items/${item!.id}/contributions`, { amount: Number(amount.replace(',', '.')) });
      setAmount('');
      await reload();
    } catch (e) {
      const err = e as ApiError;
      setAmountError(err.field('amount') ?? err.message);
    } finally {
      setBusy(false);
    }
  }

  async function cancelContribution(id: number) {
    if (!(await confirmAction('Zrušit příspěvek?', 'Váš příspěvek na tento dárek bude odebrán.', 'Zrušit příspěvek', true))) return;
    try {
      await api.delete(`/shared/${code}/items/${item!.id}/contributions/${id}`);
      await reload();
    } catch (e) {
      notify('Nepovedlo se', e instanceof ApiError ? e.message : undefined);
    }
  }

  async function send() {
    const body = message.trim();
    if (!body) return;
    setSending(true);
    try {
      await api.post(`/shared/${code}/items/${item!.id}/comments`, { body });
      setMessage('');
      await reload();
    } catch (e) {
      notify('Zprávu se nepodařilo odeslat', e instanceof ApiError ? e.message : undefined);
    } finally {
      setSending(false);
    }
  }

  return (
    <KeyboardAvoidingView style={{ flex: 1, backgroundColor: colors.cream }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <Screen onRefresh={refresh} refreshing={refreshing}>
        <BackBar right={<Chip label="Skupinový dárek" tone="blue" />} />

        <Pad style={{ marginTop: 14, flexDirection: 'row', gap: 14, alignItems: 'center' }}>
          <ItemThumb item={item} size={84} />
          <View style={{ flex: 1 }}>
            <Text variant="h2">{item.title}</Text>
            {item.formatted_price ? <Text variant="title" style={{ marginTop: 2 }}>{item.formatted_price}</Text> : null}
            <Text variant="small">{wishlist.title}</Text>
          </View>
        </Pad>

        <Pad style={{ marginTop: 14 }}>
          <Card style={{ gap: 8 }}>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
              <Text variant="label">Vybráno {Math.round(item.contributed_total).toLocaleString('cs-CZ')} {item.currency}</Text>
              <Text variant="small">
                {item.status === 'reserved' ? 'Částka je vybraná' : `zbývá ${Math.round(item.remaining_amount).toLocaleString('cs-CZ')} ${item.currency}`}
              </Text>
            </View>
            <ProgressBar percent={item.contribution_percentage} height={8} />

            {(item.my_contributions ?? []).length > 0 ? (
              <View style={{ gap: 4, marginTop: 4 }}>
                {item.my_contributions!.map((c) => (
                  <View key={c.id} style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
                    <Text variant="small">
                      Váš příspěvek: <Text variant="label">{Math.round(c.amount).toLocaleString('cs-CZ')} {item.currency}</Text>
                    </Text>
                    {item.status === 'available' ? (
                      <Pressable accessibilityRole="button" onPress={() => cancelContribution(c.id)}>
                        <RNText style={{ fontFamily: fonts.semi, fontSize: 13, color: colors.danger }}>Zrušit</RNText>
                      </Pressable>
                    ) : null}
                  </View>
                ))}
              </View>
            ) : null}

            {canContribute ? (
              <View style={{ gap: 10, marginTop: 8 }}>
                <Field
                  label={mine > 0 ? 'Přispět další částkou' : 'Kolik chcete přispět?'}
                  value={amount}
                  onChangeText={setAmount}
                  keyboardType="decimal-pad"
                  placeholder="např. 500"
                  error={amountError}
                />
                <Button label="Přispět na dárek" onPress={contribute} loading={busy} disabled={!amount.trim()} />
                <Text variant="small">Majitel seznamu uvidí jen vybranou částku, ne kdo a kolik přispěl.</Text>
              </View>
            ) : null}
          </Card>
        </Pad>

        {coordination ? (
          <>
            <Pad style={{ marginTop: 14 }}>
              <Banner icon="shopping-bag">
                <Text variant="title" style={{ fontSize: 14 }}>
                  Navrhovaný kupující: {coordination.suggested_buyer?.is_me ? 'Vy' : coordination.suggested_buyer?.name ?? '—'}
                </Text>
                <Text variant="small">
                  {coordination.suggested_buyer?.is_me
                    ? 'Přispěli jste jako první. Domluvte se s ostatními, kdo dárek koupí.'
                    : 'Přispěl jako první. Domluvte se v diskuzi, kdo dárek koupí a jak se vyrovnají peníze.'}
                </Text>
              </Banner>
            </Pad>

            <Pad style={{ marginTop: 22, gap: 4 }}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                <Feather name="lock" size={14} color={colors.burgundy} />
                <SectionLabel>Soukromá diskuze přispěvatelů</SectionLabel>
              </View>
              <Text variant="small">Majitel seznamu ji nevidí.</Text>
            </Pad>

            <Pad style={{ marginTop: 12, gap: 12 }}>
              {coordination.comments.length === 0 ? (
                <Text variant="small" style={{ textAlign: 'center' }}>Zatím žádné zprávy. Napište první!</Text>
              ) : (
                coordination.comments.map((c) => (
                  <View key={c.id} style={{ alignSelf: c.is_mine ? 'flex-end' : 'flex-start', maxWidth: '82%' }}>
                    <Text variant="caption" style={{ marginBottom: 3, textAlign: c.is_mine ? 'right' : 'left' }}>
                      {c.is_mine ? 'Vy' : c.author_name} · {time(c.created_at)}
                    </Text>
                    <View
                      style={{
                        paddingHorizontal: 14,
                        paddingVertical: 10,
                        borderRadius: 18,
                        borderBottomRightRadius: c.is_mine ? 6 : 18,
                        borderBottomLeftRadius: c.is_mine ? 18 : 6,
                        backgroundColor: c.is_mine ? colors.burgundy : colors.white,
                        borderWidth: c.is_mine ? 0 : 1,
                        borderColor: colors.border,
                      }}
                    >
                      <RNText style={{ fontFamily: fonts.regular, fontSize: 14.5, lineHeight: 20, color: c.is_mine ? colors.white : colors.text }}>
                        {c.body}
                      </RNText>
                    </View>
                  </View>
                ))
              )}
            </Pad>
          </>
        ) : null}
      </Screen>

      {coordination ? (
        <View
          style={{
            flexDirection: 'row',
            gap: 10,
            alignItems: 'center',
            paddingHorizontal: 20,
            paddingTop: 10,
            paddingBottom: insets.bottom + 10,
            backgroundColor: colors.white,
            borderTopWidth: 1,
            borderTopColor: colors.border,
          }}
        >
          <TextInput
            value={message}
            onChangeText={setMessage}
            placeholder="Napsat zprávu…"
            placeholderTextColor="#9A9194"
            accessibilityLabel="Napsat zprávu"
            onSubmitEditing={send}
            returnKeyType="send"
            style={{
              flex: 1,
              height: 44,
              paddingHorizontal: 16,
              borderRadius: 22,
              borderWidth: 1,
              borderColor: colors.inputBorder,
              backgroundColor: colors.cream,
              fontFamily: fonts.regular,
              fontSize: 15,
              color: colors.text,
            }}
          />
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Odeslat"
            onPress={send}
            disabled={sending || !message.trim()}
            style={{
              width: 44,
              height: 44,
              borderRadius: 22,
              backgroundColor: colors.burgundy,
              alignItems: 'center',
              justifyContent: 'center',
              opacity: sending || !message.trim() ? 0.5 : 1,
            }}
          >
            <Feather name="send" size={19} color={colors.white} />
          </Pressable>
        </View>
      ) : null}
    </KeyboardAvoidingView>
  );
}
