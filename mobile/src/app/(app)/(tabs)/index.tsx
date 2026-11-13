import { Feather } from '@expo/vector-icons';
import { router } from 'expo-router';
import React from 'react';
import { View } from 'react-native';
import {
  Avatar,
  Banner,
  Card,
  Chip,
  CircleButton,
  Empty,
  ErrorNote,
  Loading,
  PageTitle,
  Pad,
  ProgressBar,
  Screen,
  SectionLabel,
  Text,
  Button,
} from '../../../components/ui';
import { useAuth } from '../../../lib/auth';
import { api } from '../../../lib/api';
import { useLoad } from '../../../lib/hooks';
import { colors } from '../../../lib/theme';
import type { Dashboard, Wishlist } from '../../../lib/types';

function formatDate(d: string | null): string {
  if (!d) return '';
  const [y, m, day] = d.split('-');
  return `${Number(day)}. ${Number(m)}. ${y}`;
}

function OccasionBadge({ w }: { w: Wishlist }) {
  return (
    <View
      style={{
        width: 52,
        height: 52,
        borderRadius: 14,
        backgroundColor: colors.goldSoft,
        alignItems: 'center',
        justifyContent: 'center',
      }}
    >
      <Feather name={w.occasion === 'christmas' ? 'star' : w.occasion === 'wedding' ? 'heart' : 'gift'} size={24} color={colors.burgundy} />
    </View>
  );
}

function OwnCard({ w }: { w: Wishlist }) {
  return (
    <Card onPress={() => router.push({ pathname: '/wishlist/[id]', params: { id: String(w.id) } })} style={{ padding: 16 }}>
      <View style={{ flexDirection: 'row', gap: 14, alignItems: 'center' }}>
        <OccasionBadge w={w} />
        <View style={{ flex: 1 }}>
          <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 }}>
            <Text variant="title" style={{ flex: 1 }} numberOfLines={1}>{w.title}</Text>
          </View>
          <Text variant="small" style={{ marginTop: 2, marginBottom: 8 }}>
            {w.occasion_label}
            {w.event_date ? ` · ${formatDate(w.event_date)}` : ''}
          </Text>
          <ProgressBar percent={w.progress_percentage} />
          <Text variant="caption" style={{ marginTop: 6 }}>
            {w.reserved_count} z {w.items_count} dárků zabráno
          </Text>
        </View>
      </View>
    </Card>
  );
}

function SharedCard({ w, showGroups }: { w: Wishlist; showGroups?: boolean }) {
  return (
    <Card
      onPress={() => router.push({ pathname: '/shared/[code]', params: { code: w.share_code } })}
      style={{ padding: 16 }}
    >
      <View style={{ flexDirection: 'row', gap: 14, alignItems: 'center' }}>
        <Avatar name={w.author?.name ?? '?'} size={52} />
        <View style={{ flex: 1 }}>
          <Text variant="title" numberOfLines={1}>{w.title}</Text>
          <Text variant="small" style={{ marginTop: 2, marginBottom: 8 }} numberOfLines={1}>
            {w.author?.name}
            {showGroups && w.groups.length ? ` · ${w.groups.map((g) => g.name).join(', ')}` : ''}
          </Text>
          <ProgressBar percent={w.progress_percentage} />
        </View>
        <Feather name="chevron-right" size={20} color={colors.muted} />
      </View>
    </Card>
  );
}

export default function WishlistsScreen() {
  const { user } = useAuth();
  const { data, loading, error, refreshing, refresh, reload } = useLoad(() => api.get<Dashboard>('/dashboard'));

  return (
    <Screen tabBar onRefresh={refresh} refreshing={refreshing}>
      <PageTitle
        eyebrow={`Dobrý den, ${user?.name.split(' ')[0] ?? ''}`}
        title="Moje seznamy"
        right={<CircleButton icon="plus" label="Vytvořit nový seznam" filled size={44} onPress={() => router.push('/wishlist/new')} />}
      />

      {loading && !data ? <Loading /> : null}
      {error && !data ? <ErrorNote message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          {data.pending_invitations_count > 0 ? (
            <Pad style={{ marginTop: 18 }}>
              <Banner icon="users" onPress={() => router.push('/groups')}>
                <Text variant="title" style={{ fontSize: 14 }}>
                  {data.pending_invitations_count === 1 ? 'Máte pozvánku do skupiny' : `Máte ${data.pending_invitations_count} pozvánky do skupin`}
                </Text>
                <Text variant="small">Klepnutím ji vyřídíte</Text>
              </Banner>
            </Pad>
          ) : null}

          <Pad style={{ marginTop: 24, gap: 10 }}>
            <SectionLabel>Moje seznamy · {data.my_wishlists.length}</SectionLabel>
            {data.my_wishlists.length === 0 ? (
              <Empty
                icon="gift"
                title="Zatím nemáte žádný seznam"
                text="Vytvořte první seznam přání a sdílejte ho s rodinou a přáteli."
                action={<Button label="Vytvořit první seznam" onPress={() => router.push('/wishlist/new')} style={{ marginTop: 8 }} />}
              />
            ) : (
              data.my_wishlists.map((w) => <OwnCard key={w.id} w={w} />)
            )}
          </Pad>

          {data.group_wishlists.length > 0 ? (
            <Pad style={{ marginTop: 26, gap: 10 }}>
              <SectionLabel>Seznamy ve skupinách</SectionLabel>
              {data.group_wishlists.map((w) => (
                <SharedCard key={w.id} w={w} showGroups />
              ))}
            </Pad>
          ) : null}
        </>
      ) : null}
    </Screen>
  );
}
