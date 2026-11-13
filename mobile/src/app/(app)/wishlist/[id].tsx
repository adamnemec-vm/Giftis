import { Feather } from '@expo/vector-icons';
import * as Clipboard from 'expo-clipboard';
import { router, useLocalSearchParams } from 'expo-router';
import React, { useState } from 'react';
import { Pressable, Share, Switch, View } from 'react-native';
import {
  BackBar,
  Banner,
  Button,
  Card,
  Chip,
  Empty,
  ErrorNote,
  ItemThumb,
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
import { colors } from '../../../lib/theme';
import type { Item, Wishlist } from '../../../lib/types';

type Payload = {
  wishlist: Wishlist;
  items: Item[];
  groups: { id: number; name: string; shared: boolean }[];
  is_owner: boolean;
};

function StatusChip({ item }: { item: Item }) {
  if (item.is_group_gift && item.status === 'available') return <Chip label="Skupinový" tone="blue" />;
  return item.status === 'reserved' ? <Chip label="Rezervováno" tone="amber" /> : <Chip label="Volné" tone="green" />;
}

export default function WishlistDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data, loading, error, reload, setData } = useLoad(() => api.get<Payload>(`/wishlists/${id}`));
  const [copied, setCopied] = useState(false);

  if (loading && !data) return <Screen><BackBar /><Loading /></Screen>;
  if (!data) return <Screen><BackBar /><ErrorNote message={error ?? 'Seznam nenalezen.'} onRetry={reload} /></Screen>;

  const { wishlist, items, groups } = data;

  async function shareLink() {
    try {
      await Share.share({ message: `Podívej se na můj seznam přání „${wishlist.title}": ${wishlist.share_url}`, url: wishlist.share_url });
    } catch {}
  }

  async function copyLink() {
    await Clipboard.setStringAsync(wishlist.share_url);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  }

  async function removeWishlist() {
    if (!(await confirmAction('Smazat seznam?', 'Seznam i všechny jeho dárky budou trvale smazány. Tuto akci nelze vrátit zpět.', 'Smazat', true))) return;
    try {
      await api.delete(`/wishlists/${wishlist.id}`);
      router.replace('/');
    } catch (e) {
      notify('Nepovedlo se', e instanceof ApiError ? e.message : undefined);
    }
  }

  async function toggleGroup(groupId: number, shared: boolean) {
    const next = groups.map((g) => (g.id === groupId ? { ...g, shared } : g));
    setData({ ...data!, groups: next });
    try {
      await api.put(`/wishlists/${wishlist.id}/groups`, { group_ids: next.filter((g) => g.shared).map((g) => g.id) });
    } catch (e) {
      notify('Sdílení se nepovedlo uložit', e instanceof ApiError ? e.message : undefined);
      await reload();
    }
  }

  async function markItem(item: Item, action: 'mark-reserved' | 'clear-reservation') {
    try {
      await api.post(`/items/${item.id}/${action}`);
      await reload();
    } catch (e) {
      notify('Nepovedlo se', e instanceof ApiError ? e.message : undefined);
    }
  }

  return (
    <View style={{ flex: 1, backgroundColor: colors.cream }}>
      <Screen
        contentStyle={{ paddingBottom: 120 }}
      >
        <BackBar
          right={
            <View style={{ flexDirection: 'row', gap: 8 }}>
              <Button label="Upravit" kind="secondary" small icon="edit-2" onPress={() => router.push({ pathname: '/wishlist/new', params: { id: String(wishlist.id) } })} />
              <Button label="Smazat" kind="danger" small icon="trash-2" onPress={removeWishlist} />
            </View>
          }
        />

        <Pad style={{ marginTop: 14, gap: 4 }}>
          <Chip label={wishlist.occasion_label} tone="gold" />
          <Text variant="h1" style={{ marginTop: 6 }}>{wishlist.title}</Text>
          <Text variant="small">
            {wishlist.event_date ? wishlist.event_date.split('-').reverse().map(Number).join('. ') : wishlist.occasion_label}
          </Text>
          {wishlist.description ? <Text variant="body" style={{ marginTop: 6 }}>{wishlist.description}</Text> : null}
        </Pad>

        <Pad style={{ marginTop: 16, flexDirection: 'row', gap: 10 }}>
          <Button label="Sdílet odkaz" icon="share" onPress={shareLink} style={{ flex: 1 }} />
          <Button label={copied ? 'Zkopírováno' : 'Kopírovat'} icon={copied ? 'check' : 'copy'} kind="secondary" onPress={copyLink} />
        </Pad>

        <Pad style={{ marginTop: 14 }}>
          <Card style={{ gap: 8 }}>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
              <Text variant="label">Splnění seznamu</Text>
              <Text variant="label" style={{ color: colors.burgundy }}>{wishlist.progress_percentage} %</Text>
            </View>
            <ProgressBar percent={wishlist.progress_percentage} height={8} />
            <Text variant="small">Režim překvapení: vidíte jen stav, ne kdo dárek vybral.</Text>
          </Card>
        </Pad>

        {groups.length > 0 ? (
          <Pad style={{ marginTop: 14 }}>
            <Card style={{ gap: 6 }}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                <Feather name="users" size={18} color={colors.burgundy} />
                <Text variant="label">Sdílet se skupinami</Text>
              </View>
              {groups.map((g) => (
                <View key={g.id} style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', minHeight: 40 }}>
                  <Text variant="body">{g.name}</Text>
                  <Switch
                    value={g.shared}
                    onValueChange={(v) => toggleGroup(g.id, v)}
                    trackColor={{ true: colors.burgundy, false: '#D9D0C5' }}
                    accessibilityLabel={`Sdílet se skupinou ${g.name}`}
                  />
                </View>
              ))}
            </Card>
          </Pad>
        ) : null}

        <Pad style={{ marginTop: 24, gap: 10 }}>
          <SectionLabel>Dárky · {items.length}</SectionLabel>
          {items.length === 0 ? (
            <Empty icon="gift" title="Seznam je zatím prázdný" text="Přidejte první dárek, který byste chtěli dostat." />
          ) : (
            items.map((item) => (
              <Card key={item.id} style={{ gap: 10 }}>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel={`Upravit dárek ${item.title}`}
                  onPress={() => router.push({ pathname: '/item/edit', params: { wishlistId: String(wishlist.id), itemId: String(item.id) } })}
                  style={{ flexDirection: 'row', gap: 12, alignItems: 'center' }}
                >
                  <ItemThumb item={item} />
                  <View style={{ flex: 1 }}>
                    <Text variant="title" numberOfLines={2}>{item.title}</Text>
                    <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 3 }}>
                      {item.formatted_price ? <Text variant="label" style={{ color: colors.burgundy }}>{item.formatted_price}</Text> : null}
                      {item.priority === 'high' ? <Chip label="Velké přání" tone="danger" /> : null}
                    </View>
                    {item.is_group_gift ? (
                      <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 8 }}>
                        <View style={{ flex: 1 }}><ProgressBar percent={item.contribution_percentage} height={5} /></View>
                        <Text variant="caption">{item.contribution_percentage} %</Text>
                      </View>
                    ) : null}
                  </View>
                  <StatusChip item={item} />
                </Pressable>
                {!item.is_group_gift ? (
                  item.status === 'available' ? (
                    <Button label="Označit jako rezervované" kind="ghost" small onPress={() => markItem(item, 'mark-reserved')} />
                  ) : (
                    <Button label="Uvolnit rezervaci" kind="ghost" small onPress={() => markItem(item, 'clear-reservation')} />
                  )
                ) : null}
              </Card>
            ))
          )}
        </Pad>
      </Screen>

      <View style={{ position: 'absolute', left: 0, right: 0, bottom: 0, padding: 20, paddingBottom: 32, backgroundColor: 'rgba(250,247,242,0.96)' }}>
        <Button
          label="Přidat dárek"
          icon="plus"
          onPress={() => router.push({ pathname: '/item/edit', params: { wishlistId: String(wishlist.id) } })}
        />
      </View>
    </View>
  );
}
