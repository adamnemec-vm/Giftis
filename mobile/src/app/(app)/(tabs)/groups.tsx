import { router } from 'expo-router';
import React, { useState } from 'react';
import { View } from 'react-native';
import {
  Avatar,
  Button,
  Card,
  Chip,
  CircleButton,
  Empty,
  ErrorNote,
  Loading,
  PageTitle,
  Pad,
  Screen,
  SectionLabel,
  Text,
} from '../../../components/ui';
import { api, ApiError } from '../../../lib/api';
import { notify } from '../../../lib/dialog';
import { useLoad } from '../../../lib/hooks';
import type { Group } from '../../../lib/types';

type Payload = { groups: Group[]; invitations: Group[] };

export default function GroupsScreen() {
  const { data, loading, error, refreshing, refresh, reload } = useLoad(() => api.get<Payload>('/groups'));
  const [busyId, setBusyId] = useState<number | null>(null);

  async function respond(group: Group, action: 'accept' | 'decline') {
    setBusyId(group.id);
    try {
      await api.post(`/groups/${group.id}/${action}`);
      await reload();
      if (action === 'accept') router.push({ pathname: '/group/[id]', params: { id: String(group.id) } });
    } catch (e) {
      notify('Nepovedlo se', e instanceof ApiError ? e.message : undefined);
    } finally {
      setBusyId(null);
    }
  }

  return (
    <Screen tabBar onRefresh={refresh} refreshing={refreshing}>
      <PageTitle
        eyebrow="Sdílejte seznamy s blízkými"
        title="Skupiny"
        right={<CircleButton icon="plus" label="Vytvořit skupinu" filled size={44} onPress={() => router.push('/group/new')} />}
      />

      {loading && !data ? <Loading /> : null}
      {error && !data ? <ErrorNote message={error} onRetry={reload} /> : null}

      {data ? (
        <>
          {data.invitations.length > 0 ? (
            <Pad style={{ marginTop: 24, gap: 10 }}>
              <SectionLabel>Čekající pozvánky · {data.invitations.length}</SectionLabel>
              {data.invitations.map((g) => (
                <View
                  key={g.id}
                  style={{ padding: 16, borderRadius: 20, backgroundColor: '#FFF6DC', borderWidth: 1, borderColor: '#E9D18A', gap: 4 }}
                >
                  <Text variant="title" style={{ fontSize: 17 }}>{g.name}</Text>
                  <Text variant="small">Zve vás: {g.owner_name}</Text>
                  <View style={{ flexDirection: 'row', gap: 10, marginTop: 10 }}>
                    <Button label="Přijmout" small style={{ flex: 1 }} loading={busyId === g.id} onPress={() => respond(g, 'accept')} />
                    <Button label="Odmítnout" kind="secondary" small style={{ flex: 1 }} disabled={busyId === g.id} onPress={() => respond(g, 'decline')} />
                  </View>
                </View>
              ))}
            </Pad>
          ) : null}

          <Pad style={{ marginTop: 24, gap: 10 }}>
            <SectionLabel>Moje skupiny · {data.groups.length}</SectionLabel>
            {data.groups.length === 0 ? (
              <Empty
                icon="users"
                title="Zatím nejste v žádné skupině"
                text="Založte skupinu pro rodinu nebo přátele a domluvte se, kdo uvidí čí seznamy."
                action={<Button label="Vytvořit skupinu" onPress={() => router.push('/group/new')} style={{ marginTop: 8 }} />}
              />
            ) : (
              data.groups.map((g) => (
                <Card key={g.id} onPress={() => router.push({ pathname: '/group/[id]', params: { id: String(g.id) } })} style={{ padding: 16 }}>
                  <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
                    <Text variant="serif">{g.name}</Text>
                    {g.is_owner ? <Chip label="Zakladatel" tone="gold" /> : null}
                  </View>
                  <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 12 }}>
                    <View style={{ flexDirection: 'row' }}>
                      {(g.members_preview ?? []).map((n, i) => (
                        <View key={i} style={{ marginLeft: i === 0 ? 0 : -10 }}>
                          <Avatar name={n} tone={i} />
                        </View>
                      ))}
                    </View>
                    <Text variant="small">
                      {g.members_count} {g.members_count === 1 ? 'člen' : g.members_count && g.members_count < 5 ? 'členové' : 'členů'}
                    </Text>
                  </View>
                </Card>
              ))
            )}
          </Pad>
        </>
      ) : null}
    </Screen>
  );
}
