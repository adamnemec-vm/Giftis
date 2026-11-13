import { router, useLocalSearchParams } from 'expo-router';
import React, { useState } from 'react';
import { Pressable, Text as RNText, View } from 'react-native';
import {
  Avatar,
  BackBar,
  Banner,
  Button,
  Card,
  Chip,
  Empty,
  ErrorNote,
  Field,
  Loading,
  Pad,
  ProgressBar,
  Screen,
  SectionLabel,
  Text,
} from '../../../components/ui';
import { api, ApiError } from '../../../lib/api';
import { confirmAction, notify } from '../../../lib/dialog';
import { useLoad } from '../../../lib/hooks';
import { colors, fonts } from '../../../lib/theme';
import type { Group, Wishlist } from '../../../lib/types';

type Member = { id: number; name: string; email: string; is_owner?: boolean };
type Payload = {
  group: Group;
  members: Member[];
  pending_members: Member[];
  wishlists: (Wishlist & { is_mine: boolean })[];
};

export default function GroupDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data, loading, error, reload, refresh, refreshing } = useLoad(() => api.get<Payload>(`/groups/${id}`));
  const [email, setEmail] = useState('');
  const [inviteError, setInviteError] = useState<string | undefined>();
  const [inviteOk, setInviteOk] = useState<string | undefined>();
  const [busy, setBusy] = useState(false);

  if (loading && !data) return <Screen><BackBar /><Loading /></Screen>;
  if (!data) return <Screen><BackBar /><ErrorNote message={error ?? 'Skupina nenalezena.'} onRetry={reload} /></Screen>;

  const { group, members, pending_members, wishlists } = data;

  async function invite() {
    setBusy(true);
    setInviteError(undefined);
    setInviteOk(undefined);
    try {
      const res = await api.post<{ message: string }>(`/groups/${group.id}/invite`, { email: email.trim() });
      setInviteOk(res.message);
      setEmail('');
      await reload();
    } catch (e) {
      const err = e as ApiError;
      setInviteError(err.field('email') ?? err.message);
    } finally {
      setBusy(false);
    }
  }

  async function removeMember(m: Member, pending: boolean) {
    if (!pending && !(await confirmAction('Odebrat člena?', `${m.name} bude odebrán ze skupiny.`, 'Odebrat', true))) return;
    try {
      await api.delete(`/groups/${group.id}/members/${m.id}`);
      await reload();
    } catch (e) {
      notify('Nepovedlo se', e instanceof ApiError ? e.message : undefined);
    }
  }

  async function leave() {
    if (!(await confirmAction('Opustit skupinu?', 'Přestanete vidět seznamy členů této skupiny.', 'Opustit', true))) return;
    try {
      await api.post(`/groups/${group.id}/leave`);
      router.replace('/groups');
    } catch (e) {
      notify('Nepovedlo se', e instanceof ApiError ? e.message : undefined);
    }
  }

  async function destroy() {
    if (!(await confirmAction('Smazat skupinu?', 'Skupina bude trvale smazána pro všechny členy.', 'Smazat', true))) return;
    try {
      await api.delete(`/groups/${group.id}`);
      router.replace('/groups');
    } catch (e) {
      notify('Nepovedlo se', e instanceof ApiError ? e.message : undefined);
    }
  }

  return (
    <Screen onRefresh={refresh} refreshing={refreshing}>
      <BackBar />
      <Pad style={{ marginTop: 14 }}>
        <Text variant="h1">{group.name}</Text>
        <Text variant="small">Zakladatel: {group.owner_name} · {members.length} {members.length === 1 ? 'člen' : members.length < 5 ? 'členové' : 'členů'}</Text>
      </Pad>

      <Pad style={{ marginTop: 22, gap: 10 }}>
        <SectionLabel>Členové</SectionLabel>
        <Card style={{ padding: 0 }}>
          {members.map((m, i) => (
            <View
              key={m.id}
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                gap: 12,
                paddingHorizontal: 16,
                paddingVertical: 12,
                borderTopWidth: i === 0 ? 0 : 1,
                borderTopColor: colors.border,
              }}
            >
              <Avatar name={m.name} tone={i} size={38} />
              <View style={{ flex: 1 }}>
                <Text variant="title" style={{ fontSize: 15 }}>{m.name}</Text>
                <Text variant="small">{m.email}</Text>
              </View>
              {m.is_owner ? <Chip label="Zakladatel" tone="gold" /> : null}
              {group.is_owner && !m.is_owner ? (
                <Pressable accessibilityRole="button" accessibilityLabel={`Odebrat ${m.name}`} onPress={() => removeMember(m, false)}>
                  <RNText style={{ fontFamily: fonts.semi, fontSize: 13, color: colors.danger }}>Odebrat</RNText>
                </Pressable>
              ) : null}
            </View>
          ))}
          {pending_members.map((m) => (
            <View
              key={`p${m.id}`}
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                gap: 12,
                paddingHorizontal: 16,
                paddingVertical: 12,
                borderTopWidth: 1,
                borderTopColor: colors.border,
                backgroundColor: '#FFFBF0',
              }}
            >
              <Avatar name={m.name} tone={3} size={38} />
              <View style={{ flex: 1 }}>
                <Text variant="title" style={{ fontSize: 15 }}>{m.name}</Text>
                <Chip label="Čeká na přijetí" tone="amber" />
              </View>
              {group.is_owner ? (
                <Pressable accessibilityRole="button" onPress={() => removeMember(m, true)}>
                  <RNText style={{ fontFamily: fonts.semi, fontSize: 13, color: colors.danger }}>Zrušit pozvánku</RNText>
                </Pressable>
              ) : null}
            </View>
          ))}
        </Card>
      </Pad>

      {group.is_owner ? (
        <Pad style={{ marginTop: 16 }}>
          <Card style={{ gap: 10 }}>
            <Field
              label="Pozvat člena e-mailem"
              value={email}
              onChangeText={setEmail}
              autoCapitalize="none"
              keyboardType="email-address"
              placeholder="teta.alena@example.com"
              hint="Uživatel musí mít u nás už založený účet."
              error={inviteError}
            />
            {inviteOk ? <Text variant="small" style={{ color: colors.green }}>{inviteOk}</Text> : null}
            <Button label="Odeslat pozvánku" icon="send" onPress={invite} loading={busy} disabled={!email.trim()} />
          </Card>
        </Pad>
      ) : null}

      <Pad style={{ marginTop: 24, gap: 10 }}>
        <SectionLabel>Seznamy přání ve skupině</SectionLabel>
        <Text variant="small" style={{ marginTop: -4 }}>Zobrazují se jen seznamy, které majitelé zpřístupnili této skupině.</Text>
        {wishlists.length === 0 ? (
          <Empty icon="package" title="Zatím tu nejsou žádné sdílené seznamy" text="Svůj seznam zpřístupníte na jeho stránce v sekci „Sdílet se skupinami“." />
        ) : (
          wishlists.map((w) => (
            <Card
              key={w.id}
              onPress={() =>
                w.is_mine
                  ? router.push({ pathname: '/wishlist/[id]', params: { id: String(w.id) } })
                  : router.push({ pathname: '/shared/[code]', params: { code: w.share_code } })
              }
              style={{ gap: 8, borderColor: w.is_mine ? colors.gold : colors.border }}
            >
              <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                <Chip label={w.occasion_label} />
                {w.is_mine ? <Chip label="Váš seznam" tone="gold" /> : <Text variant="small">{w.author?.name}</Text>}
              </View>
              <Text variant="title">{w.title}</Text>
              <ProgressBar percent={w.progress_percentage} />
            </Card>
          ))
        )}
      </Pad>

      <Pad style={{ marginTop: 28 }}>
        {group.is_owner ? (
          <Button label="Smazat skupinu" kind="danger" icon="trash-2" onPress={destroy} />
        ) : (
          <Button label="Opustit skupinu" kind="secondary" icon="log-out" onPress={leave} />
        )}
      </Pad>
    </Screen>
  );
}
