import React from 'react';
import { View } from 'react-native';
import { Avatar, Button, Card, PageTitle, Pad, Screen, Text } from '../../../components/ui';
import { API_URL } from '../../../lib/api';
import { useAuth } from '../../../lib/auth';
import { confirmAction } from '../../../lib/dialog';

export default function ProfileScreen() {
  const { user, logout } = useAuth();

  return (
    <Screen tabBar>
      <PageTitle title="Profil" />
      <Pad style={{ marginTop: 20, gap: 14 }}>
        <Card style={{ flexDirection: 'row', alignItems: 'center', gap: 14, padding: 16 }}>
          <Avatar name={user?.name ?? '?'} size={56} />
          <View style={{ flex: 1 }}>
            <Text variant="title">{user?.name}</Text>
            <Text variant="small">{user?.email}</Text>
          </View>
        </Card>

        <Card style={{ gap: 4 }}>
          <Text variant="label">Server</Text>
          <Text variant="small" selectable>{API_URL}</Text>
        </Card>

        <Button
          label="Odhlásit se"
          kind="secondary"
          icon="log-out"
          onPress={async () => {
            if (await confirmAction('Odhlásit se?', 'Na tomto zařízení budete odhlášeni.', 'Odhlásit')) await logout();
          }}
        />
      </Pad>
    </Screen>
  );
}
