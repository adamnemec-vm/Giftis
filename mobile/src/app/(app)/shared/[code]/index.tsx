import { Feather } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import React, { useState } from 'react';
import { Text as RNText, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  BackBar,
  Banner,
  Button,
  Card,
  Chip,
  CircleButton,
  Empty,
  ErrorNote,
  ItemThumb,
  Loading,
  Pad,
  ProgressBar,
  Screen,
  Text,
} from '../../../../components/ui';
import { api, ApiError } from '../../../../lib/api';
import { confirmAction, notify } from '../../../../lib/dialog';
import { useLoad } from '../../../../lib/hooks';
import { colors, fonts } from '../../../../lib/theme';
import type { Item, Wishlist } from '../../../../lib/types';

type Payload = { wishlist: Wishlist; items: Item[]; is_owner: boolean; can_reserve: boolean };

export default function SharedWishlistScreen() {
  const { code } = useLocalSearchParams<{ code: string }>();
  const insets = useSafeAreaInsets();
  const { data, loading, error, reload, refresh, refreshing } = useLoad(() => api.get<Payload>(`/shared/${code}`));
  const [busyId, setBusyId] = useState<number | null>(null);

  if (loading && !data) return <Screen><BackBar /><Loading /></Screen>;
  if (!data) return <Screen><BackBar /><ErrorNote message={error ?? 'Seznam nenalezen.'} onRetry={reload} /></Screen>;

  const { wishlist, items, is_owner, can_reserve } = data;

  async function act(item: Item, action: 'reserve' | 'unreserve') {
    if (action === 'unreserve' && !(await confirmAction('Zrušit rezervaci?', `Dárek „${item.title}" bude opět k dispozici.`, 'Zrušit rezervaci', true))) return;
    setBusyId(item.id);
    try {
      await api.post(`/shared/${code}/items/${item.id}/${action}`);
      await reload();
    } catch (e) {
      notify('Nepovedlo se', e instanceof ApiError ? e.message : undefined);
      await reload();
    } finally {
      setBusyId(null);
    }
  }

  const openItem = (item: Item) =>
    router.push({ pathname: '/shared/[code]/[itemId]', params: { code: String(code), itemId: String(item.id) } });

  const infoText = is_owner
    ? 'Takto seznam vidí vaši přátelé. Nevidíte, kdo si co vybral.'
    : `Rezervace jsou tajné. ${wishlist.author?.name.split(' ')[0]} neuvidí vaše jméno.`;

  return (
    <Screen onRefresh={refresh} refreshing={refreshing} flush>
      <View
        style={{
          backgroundColor: colors.burgundy,
          paddingTop: insets.top + 12,
          paddingHorizontal: 20,
          paddingBottom: 22,
          borderBottomLeftRadius: 28,
          borderBottomRightRadius: 28,
          gap: 4,
        }}
      >
        <View style={{ flexDirection: 'row' }}>
          <CircleButton icon="chevron-left" label="Zpět" light onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))} />
        </View>
        <RNText style={{ fontFamily: fonts.semi, fontSize: 12.5, color: '#E9D18A', marginTop: 12 }}>
          {wishlist.occasion_label} · {wishlist.author?.name}
        </RNText>
        <RNText style={{ fontFamily: fonts.serif, fontSize: 29, lineHeight: 34, color: colors.cream }}>{wishlist.title}</RNText>
        <View
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            gap: 8,
            marginTop: 10,
            padding: 10,
            borderRadius: 12,
            backgroundColor: 'rgba(255,255,255,0.12)',
          }}
        >
          <Feather name="lock" size={16} color="#E9D18A" />
          <RNText style={{ flex: 1, fontFamily: fonts.regular, fontSize: 12.5, lineHeight: 17, color: colors.cream }}>{infoText}</RNText>
        </View>
      </View>

      <Pad style={{ marginTop: 16, gap: 12 }}>
        {items.length === 0 ? <Empty icon="package" title="Seznam je zatím prázdný" text="Autor zatím nepřidal žádné dárky." /> : null}

        {items.map((item) => (
          <Card key={item.id} style={{ gap: 12 }}>
            <View style={{ flexDirection: 'row', gap: 14 }}>
              <ItemThumb item={item} size={76} />
              <View style={{ flex: 1, gap: 3 }}>
                <Text variant="title">{item.title}</Text>
                {item.formatted_price ? (
                  <Text variant="label" style={{ color: colors.burgundy, fontSize: 15 }}>{item.formatted_price}</Text>
                ) : null}
                <View style={{ flexDirection: 'row', gap: 6, flexWrap: 'wrap', marginTop: 2 }}>
                  {item.priority === 'high' ? <Chip label="Velké přání" tone="danger" /> : null}
                  {item.is_group_gift ? <Chip label="Skupinový dárek" tone="blue" /> : null}
                </View>
                {item.description ? <Text variant="small" numberOfLines={2}>{item.description}</Text> : null}
              </View>
            </View>

            {item.is_group_gift ? (
              <View style={{ gap: 6 }}>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                  <Text variant="caption">Vybráno {Math.round(item.contributed_total).toLocaleString('cs-CZ')} {item.currency}</Text>
                  <Text variant="caption">{item.contribution_percentage} %</Text>
                </View>
                <ProgressBar percent={item.contribution_percentage} />
              </View>
            ) : null}

            {can_reserve ? (
              item.is_group_gift ? (
                <Button
                  label={(item.my_contribution_total ?? 0) > 0 || item.status !== 'available' ? 'Příspěvky a diskuze' : 'Přispět na dárek'}
                  kind={(item.my_contribution_total ?? 0) > 0 ? 'secondary' : 'primary'}
                  icon="chevron-right"
                  onPress={() => openItem(item)}
                />
              ) : item.status === 'available' ? (
                <Button label="Chci koupit / Rezervovat" loading={busyId === item.id} onPress={() => act(item, 'reserve')} />
              ) : item.is_my_reservation ? (
                <Button label="Zrušit moji rezervaci" kind="soft" loading={busyId === item.id} onPress={() => act(item, 'unreserve')} />
              ) : (
                <View style={{ height: 44, borderRadius: 12, backgroundColor: '#ECE7E1', flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8 }}>
                  <Feather name="lock" size={15} color={colors.textSoft} />
                  <RNText style={{ fontFamily: fonts.semi, fontSize: 14, color: colors.textSoft }}>Už rezervováno někým jiným</RNText>
                </View>
              )
            ) : null}
          </Card>
        ))}

        {is_owner ? (
          <Banner tone="sky" icon="settings" onPress={() => router.replace({ pathname: '/wishlist/[id]', params: { id: String(wishlist.id) } })}>
            Otevřít správu seznamu
          </Banner>
        ) : null}
      </Pad>
    </Screen>
  );
}
