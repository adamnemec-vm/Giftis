import { Feather } from '@expo/vector-icons';
import { Tabs } from 'expo-router';
import React from 'react';
import type { ColorValue } from 'react-native';
import { colors, fonts } from '../../../lib/theme';

type IconName = React.ComponentProps<typeof Feather>['name'];

function tab(title: string, icon: IconName) {
  return {
    title,
    tabBarIcon: ({ color, size }: { color: ColorValue; size: number }) => <Feather name={icon} size={size} color={color as string} />,
  };
}

export default function TabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.burgundy,
        tabBarInactiveTintColor: colors.muted,
        tabBarLabelStyle: { fontFamily: fonts.semi, fontSize: 11 },
        tabBarStyle: { backgroundColor: colors.white, borderTopColor: colors.border },
        sceneStyle: { backgroundColor: colors.cream },
      }}
    >
      <Tabs.Screen name="index" options={tab('Seznamy', 'gift')} />
      <Tabs.Screen name="groups" options={tab('Skupiny', 'users')} />
      <Tabs.Screen name="reservations" options={tab('Rezervace', 'shopping-bag')} />
      <Tabs.Screen name="profile" options={tab('Profil', 'user')} />
    </Tabs>
  );
}
