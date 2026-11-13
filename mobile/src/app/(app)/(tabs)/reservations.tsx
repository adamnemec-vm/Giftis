import { router } from 'expo-router';
import React, { useState } from 'react';
import { View } from 'react-native';
import {
  Button,
  Card,
  Chip,
  Empty,
  ErrorNote,
  ItemThumb,
  Loading,
  PageTitle,
  Pad,
  Screen,
  SectionLabel,
  Segmented,
  Text,
} from '../../../components/ui';
import { api, ApiError } from '../../../lib/api';
import { confirmAction, notify } from '../../../lib/dialog';
import { useLoad } from '../../../lib/hooks';
import { colors } from '../../../lib/theme';
import type { Item } from '../../../lib/types';

type WishlistRef = { id: number; title: string; share_code: string; author_name: string };
type Payload = {
  reservations: { item: Item; wishlist: WishlistRef }[];
  contributions: { id: number; amount: number; item: Item; wishlist: WishlistRef }[];
};

export default function ReservationsScreen() {
  const { data, loading, error, refreshing, refresh, reload } = useLoad(() => api.get<Payload>('/reservations'));
  const [filter, setFilter] = useState<'all' | 'reservations' | 'contributions'>('all');

  async function cancelReservation(r: Payload['reservations'][number]) {
    if (!(await confirmAction('Zrušit rezervaci?', `Dárek „${r.item.title}" bude opět k dispozici.`, 'Zrušit rezervaci', true))) return;
    try {
      await api.post(`/shared/${r.wishlist.share_code}/items/${r.item.id}/unreserve`);
      await reload();
    } catch (e) {
      notify('Nepovedlo se', e instanceof ApiError ? e.message : undefined);
    }
  }

  const reservations = filter !== 'contributions' ? data?.reservations ?? [] : [];
  const contributions = filter !== 'reservations' ? data?.contributions ?? [] : [];
  const nothing = data && reservations.length === 0 && contributions.length === 0;

  return (
    <Screen tabBar onRefresh={refresh} refreshing={refreshing}>
      <PageTitle eyebrow="Co už máte vybráno" title="Moje rezervace" />

      <Pad style={{ marginTop: 18 }}>
        <Segmented
          value={filter}
          onChange={setFilter}
          options={[
            { value: 'all', label: 'Vše' },
            { value: 'reservations', label: 'Rezervace' },
            { value: 'contributions', label: 'Příspěvky' },
          ]}
        />
      </Pad>

      {loading && !data ? <Loading /> : null}
      {error && !data ? <ErrorNote message={error} onRetry={reload} /> : null}

      {nothing ? (
        <Pad style={{ marginTop: 24 }}>
          <Empty icon="shopping-bag" title="Zatím nic" text="Až si na sdíleném seznamu zarezervujete dárek nebo na něj přispějete, uvidíte to tady." />
        </Pad>
      ) : null}

      {reservations.length > 0 ? (
        <Pad style={{ marginTop: 24, gap: 10 }}>
          <SectionLabel>Rezervace · {reservations.length}</SectionLabel>
          {reservations.map((r) => (
            <Card key={r.item.id} style={{ gap: 10 }}>
              <View style={{ flexDirection: 'row', gap: 12, alignItems: 'center' }}>
                <ItemThumb item={r.item} size={52} />
                <View style={{ flex: 1 }}>
                  <Text variant="title" numberOfLines={1}>{r.item.title}</Text>
                  <Text variant="small" numberOfLines={1}>{r.wishlist.title} · {r.wishlist.author_name}</Text>
                  {r.item.formatted_price ? (
                    <Text variant="label" style={{ color: colors.burgundy, marginTop: 2 }}>{r.item.formatted_price}</Text>
                  ) : null}
                </View>
                <Chip label="Rezervováno" tone="green" />
              </View>
              <Button label="Zrušit rezervaci" kind="danger" small onPress={() => cancelReservation(r)} />
            </Card>
          ))}
        </Pad>
      ) : null}

      {contributions.length > 0 ? (
        <Pad style={{ marginTop: 24, gap: 10 }}>
          <SectionLabel>Příspěvky na skupinové dárky · {contributions.length}</SectionLabel>
          {contributions.map((c) => (
            <Card
              key={c.id}
              onPress={() =>
                router.push({ pathname: '/shared/[code]/[itemId]', params: { code: c.wishlist.share_code, itemId: String(c.item.id) } })
              }
              style={{ gap: 10 }}
            >
              <View style={{ flexDirection: 'row', gap: 12, alignItems: 'center' }}>
                <ItemThumb item={c.item} size={52} />
                <View style={{ flex: 1 }}>
                  <Text variant="title" numberOfLines={1}>{c.item.title}</Text>
                  <Text variant="small" numberOfLines={1}>{c.wishlist.title} · {c.wishlist.author_name}</Text>
                  <Text variant="small" style={{ marginTop: 2 }}>
                    Přispěli jste <Text variant="label">{Math.round(c.amount).toLocaleString('cs-CZ')} {c.item.currency}</Text>
                  </Text>
                </View>
                <Chip label="Skupinový" tone="blue" />
              </View>
              {c.item.coordination?.suggested_buyer?.is_me ? <Chip label="Navrhovaný kupující: Vy" tone="note" /> : null}
            </Card>
          ))}
        </Pad>
      ) : null}
    </Screen>
  );
}
