import { Stack } from 'expo-router';
import React from 'react';
import { colors } from '../../lib/theme';

export default function AppLayout() {
  return (
    <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: colors.cream } }}>
      <Stack.Screen name="(tabs)" />
      <Stack.Screen name="wishlist/new" options={{ presentation: 'modal' }} />
      <Stack.Screen name="item/edit" options={{ presentation: 'modal' }} />
      <Stack.Screen name="group/new" options={{ presentation: 'modal' }} />
    </Stack>
  );
}
