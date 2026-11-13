import { Feather, MaterialCommunityIcons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React from 'react';
import {
  ActivityIndicator,
  Image,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleProp,
  StyleSheet,
  Text as RNText,
  TextInput,
  TextInputProps,
  TextStyle,
  View,
  ViewStyle,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors, fonts, radius } from '../lib/theme';
import type { Item } from '../lib/types';

/* ---------- Text ---------- */

type Variant = 'body' | 'small' | 'caption' | 'label' | 'title' | 'h1' | 'h2' | 'serif';

const textStyles: Record<Variant, TextStyle> = {
  body: { fontFamily: fonts.regular, fontSize: 15, lineHeight: 21, color: colors.text },
  small: { fontFamily: fonts.regular, fontSize: 13, lineHeight: 18, color: colors.muted },
  caption: { fontFamily: fonts.semi, fontSize: 12, lineHeight: 16, color: colors.muted },
  label: { fontFamily: fonts.semi, fontSize: 13, color: colors.text },
  title: { fontFamily: fonts.semi, fontSize: 16, lineHeight: 21, color: colors.text },
  h1: { fontFamily: fonts.serif, fontSize: 32, lineHeight: 36, color: colors.burgundy },
  h2: { fontFamily: fonts.serif, fontSize: 24, lineHeight: 29, color: colors.burgundy },
  serif: { fontFamily: fonts.serif, fontSize: 18, lineHeight: 23, color: colors.text },
};

export function Text({
  variant = 'body',
  style,
  ...props
}: React.ComponentProps<typeof RNText> & { variant?: Variant }) {
  return <RNText {...props} style={[textStyles[variant], style]} />;
}

export function SectionLabel({ children, style }: { children: React.ReactNode; style?: StyleProp<TextStyle> }) {
  return (
    <RNText
      style={[
        { fontFamily: fonts.bold, fontSize: 12, letterSpacing: 1, textTransform: 'uppercase', color: colors.muted },
        style,
      ]}
    >
      {children}
    </RNText>
  );
}

/* ---------- Layout ---------- */

export function Screen({
  children,
  onRefresh,
  refreshing,
  tabBar,
  contentStyle,
  flush,
}: {
  children: React.ReactNode;
  onRefresh?: () => void;
  refreshing?: boolean;
  tabBar?: boolean;
  contentStyle?: StyleProp<ViewStyle>;
  flush?: boolean;
}) {
  const insets = useSafeAreaInsets();
  const padTop = flush ? 0 : insets.top + 12;
  return (
    <ScrollView
      style={{ flex: 1, backgroundColor: colors.cream }}
      contentContainerStyle={[{ paddingTop: padTop, paddingBottom: tabBar ? 24 : insets.bottom + 24 }, contentStyle]}
      keyboardShouldPersistTaps="handled"
      refreshControl={
        onRefresh ? <RefreshControl refreshing={!!refreshing} onRefresh={onRefresh} tintColor={colors.burgundy} /> : undefined
      }
    >
      {children}
    </ScrollView>
  );
}

export function Pad({ children, style }: { children: React.ReactNode; style?: StyleProp<ViewStyle> }) {
  return <View style={[{ paddingHorizontal: 20 }, style]}>{children}</View>;
}

export function BackBar({ right }: { right?: React.ReactNode }) {
  return (
    <View style={{ paddingHorizontal: 20, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
      <CircleButton icon="chevron-left" label="Zpět" onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))} />
      {right}
    </View>
  );
}

export function CircleButton({
  icon,
  label,
  onPress,
  filled,
  light,
  size = 40,
}: {
  icon: React.ComponentProps<typeof Feather>['name'];
  label: string;
  onPress: () => void;
  filled?: boolean;
  light?: boolean;
  size?: number;
}) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      onPress={onPress}
      style={({ pressed }) => ({
        width: size,
        height: size,
        borderRadius: size / 2,
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: filled ? colors.burgundy : light ? 'rgba(255,255,255,0.16)' : colors.white,
        borderWidth: filled || light ? 0 : 1,
        borderColor: colors.border,
        opacity: pressed ? 0.7 : 1,
      })}
    >
      <Feather name={icon} size={size * 0.5} color={filled ? colors.white : light ? colors.cream : colors.burgundy} />
    </Pressable>
  );
}

export function PageTitle({ eyebrow, title, right }: { eyebrow?: string; title: string; right?: React.ReactNode }) {
  return (
    <View style={{ paddingHorizontal: 20, flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between' }}>
      <View style={{ flex: 1, paddingRight: 12 }}>
        {eyebrow ? <Text variant="small" style={{ marginBottom: 2 }}>{eyebrow}</Text> : null}
        <Text variant="h1">{title}</Text>
      </View>
      {right}
    </View>
  );
}

/* ---------- Cards & chips ---------- */

export function Card({
  children,
  style,
  onPress,
}: {
  children: React.ReactNode;
  style?: StyleProp<ViewStyle>;
  onPress?: () => void;
}) {
  const base: ViewStyle = {
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    padding: 14,
  };
  if (onPress) {
    return (
      <Pressable accessibilityRole="button" onPress={onPress} style={({ pressed }) => [base, style, pressed && { opacity: 0.85 }]}>
        {children}
      </Pressable>
    );
  }
  return <View style={[base, style]}>{children}</View>;
}

type Tone = 'neutral' | 'green' | 'amber' | 'blue' | 'sky' | 'danger' | 'gold' | 'note';
const toneColors: Record<Tone, { bg: string; fg: string }> = {
  neutral: { bg: '#F3EEE7', fg: colors.textSoft },
  green: { bg: colors.greenSoft, fg: colors.green },
  amber: { bg: colors.amberSoft, fg: colors.amber },
  blue: { bg: colors.blueSoft, fg: colors.blue },
  sky: { bg: colors.skySoft, fg: colors.sky },
  danger: { bg: colors.dangerSoft, fg: colors.danger },
  gold: { bg: colors.gold, fg: colors.text },
  note: { bg: colors.noteSoft, fg: colors.amber },
};

export function Chip({ label, tone = 'neutral', icon }: { label: string; tone?: Tone; icon?: React.ComponentProps<typeof Feather>['name'] }) {
  const t = toneColors[tone];
  return (
    <View
      style={{
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
        alignSelf: 'flex-start',
        paddingHorizontal: 9,
        paddingVertical: 3,
        borderRadius: radius.pill,
        backgroundColor: t.bg,
      }}
    >
      {icon ? <Feather name={icon} size={11} color={t.fg} /> : null}
      <RNText style={{ fontFamily: fonts.bold, fontSize: 11.5, color: t.fg }}>{label}</RNText>
    </View>
  );
}

export function ProgressBar({ percent, height = 6 }: { percent: number; height?: number }) {
  return (
    <View style={{ height, borderRadius: height / 2, backgroundColor: colors.sand, overflow: 'hidden' }}>
      <View style={{ width: `${Math.max(0, Math.min(100, percent))}%`, height: '100%', backgroundColor: colors.gold }} />
    </View>
  );
}

export function Banner({
  children,
  tone = 'note',
  icon,
  onPress,
}: {
  children: React.ReactNode;
  tone?: 'note' | 'sky' | 'danger';
  icon?: React.ComponentProps<typeof Feather>['name'];
  onPress?: () => void;
}) {
  const palette =
    tone === 'sky'
      ? { bg: colors.skySoft, border: '#BFDDF0', fg: colors.sky }
      : tone === 'danger'
        ? { bg: colors.dangerSoft, border: '#F3B8C1', fg: colors.danger }
        : { bg: colors.noteSoft, border: colors.noteBorder, fg: colors.text };
  const inner = (
    <View
      style={{
        flexDirection: 'row',
        alignItems: 'center',
        gap: 10,
        padding: 13,
        borderRadius: radius.md + 2,
        backgroundColor: palette.bg,
        borderWidth: 1,
        borderColor: palette.border,
      }}
    >
      {icon ? <Feather name={icon} size={18} color={palette.fg} /> : null}
      <View style={{ flex: 1 }}>{typeof children === 'string' ? <Text variant="small" style={{ color: palette.fg }}>{children}</Text> : children}</View>
      {onPress ? <Feather name="chevron-right" size={18} color={colors.burgundy} /> : null}
    </View>
  );
  return onPress ? (
    <Pressable accessibilityRole="button" onPress={onPress}>
      {inner}
    </Pressable>
  ) : (
    inner
  );
}

/* ---------- Buttons & fields ---------- */

type BtnKind = 'primary' | 'secondary' | 'ghost' | 'danger' | 'soft';

export function Button({
  label,
  onPress,
  kind = 'primary',
  loading,
  disabled,
  icon,
  style,
  small,
}: {
  label: string;
  onPress: () => void;
  kind?: BtnKind;
  loading?: boolean;
  disabled?: boolean;
  icon?: React.ComponentProps<typeof Feather>['name'];
  style?: StyleProp<ViewStyle>;
  small?: boolean;
}) {
  const palette: Record<BtnKind, { bg: string; fg: string; border?: string }> = {
    primary: { bg: colors.burgundy, fg: colors.white },
    secondary: { bg: colors.white, fg: colors.burgundy, border: colors.burgundy },
    ghost: { bg: 'transparent', fg: colors.burgundy },
    danger: { bg: colors.dangerSoft, fg: colors.danger },
    soft: { bg: colors.goldSoft, fg: colors.burgundy },
  };
  const p = palette[kind];
  const off = disabled || loading;
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      accessibilityState={{ disabled: !!off, busy: !!loading }}
      onPress={off ? undefined : onPress}
      style={({ pressed }) => [
        {
          height: small ? 40 : 48,
          borderRadius: radius.md,
          paddingHorizontal: 16,
          flexDirection: 'row',
          alignItems: 'center',
          justifyContent: 'center',
          gap: 8,
          backgroundColor: p.bg,
          borderWidth: p.border ? 1.5 : 0,
          borderColor: p.border,
          opacity: off ? 0.55 : pressed ? 0.8 : 1,
        },
        style,
      ]}
    >
      {loading ? (
        <ActivityIndicator color={p.fg} />
      ) : (
        <>
          {icon ? <Feather name={icon} size={17} color={p.fg} /> : null}
          <RNText style={{ fontFamily: fonts.semi, fontSize: small ? 13.5 : 15, color: p.fg }}>{label}</RNText>
        </>
      )}
    </Pressable>
  );
}

export function Field({
  label,
  error,
  hint,
  ...props
}: TextInputProps & { label: string; error?: string; hint?: string }) {
  return (
    <View style={{ gap: 6 }}>
      <RNText style={textStyles.label}>{label}</RNText>
      <TextInput
        placeholderTextColor="#9A9194"
        accessibilityLabel={label}
        {...props}
        style={[
          {
            minHeight: 48,
            paddingHorizontal: 14,
            paddingVertical: props.multiline ? 12 : 0,
            borderRadius: radius.md,
            borderWidth: 1,
            borderColor: error ? colors.danger : colors.inputBorder,
            backgroundColor: colors.cream,
            fontFamily: fonts.regular,
            fontSize: 16,
            color: colors.text,
          },
          props.multiline ? { minHeight: 84, textAlignVertical: 'top' } : null,
          props.style,
        ]}
      />
      {error ? <Text variant="small" style={{ color: colors.danger }}>{error}</Text> : hint ? <Text variant="small">{hint}</Text> : null}
    </View>
  );
}

export function Segmented<T extends string>({
  options,
  value,
  onChange,
}: {
  options: { value: T; label: string }[];
  value: T;
  onChange: (v: T) => void;
}) {
  return (
    <View style={{ flexDirection: 'row', padding: 3, borderRadius: 12, backgroundColor: '#F3EEE7' }}>
      {options.map((o) => {
        const active = o.value === value;
        return (
          <Pressable
            key={o.value}
            accessibilityRole="button"
            accessibilityState={{ selected: active }}
            onPress={() => onChange(o.value)}
            style={{
              flex: 1,
              height: 38,
              borderRadius: 10,
              alignItems: 'center',
              justifyContent: 'center',
              backgroundColor: active ? colors.white : 'transparent',
              shadowColor: '#000',
              shadowOpacity: active ? 0.1 : 0,
              shadowRadius: 3,
              shadowOffset: { width: 0, height: 1 },
            }}
          >
            <RNText style={{ fontFamily: active ? fonts.bold : fonts.semi, fontSize: 13.5, color: active ? colors.burgundy : colors.textSoft }}>
              {o.label}
            </RNText>
          </Pressable>
        );
      })}
    </View>
  );
}

/* ---------- Domain bits ---------- */

const ICON_MAP: Record<string, React.ComponentProps<typeof MaterialCommunityIcons>['name']> = {
  gift: 'gift-outline',
  book: 'book-open-page-variant-outline',
  electronics: 'headphones',
  clothing: 'tshirt-crew-outline',
  toy: 'teddy-bear',
  jewelry: 'diamond-stone',
  experience: 'ticket-confirmation-outline',
  home: 'home-outline',
  sports: 'basketball',
  beauty: 'lipstick',
};

export function ItemIcon({ icon, size = 26 }: { icon: string; size?: number }) {
  return <MaterialCommunityIcons name={ICON_MAP[icon] ?? 'gift-outline'} size={size} color={colors.burgundy} />;
}

export function ItemThumb({ item, size = 56 }: { item: Pick<Item, 'icon' | 'image_url'>; size?: number }) {
  return (
    <View
      style={{
        width: size,
        height: size,
        borderRadius: size * 0.26,
        backgroundColor: colors.sand,
        alignItems: 'center',
        justifyContent: 'center',
        overflow: 'hidden',
      }}
    >
      {item.image_url ? (
        <Image source={{ uri: item.image_url }} style={{ width: size, height: size }} resizeMode="cover" />
      ) : (
        <ItemIcon icon={item.icon ?? 'gift'} size={size * 0.5} />
      )}
    </View>
  );
}

export function Avatar({ name, tone = 0, size = 34 }: { name: string; tone?: number; size?: number }) {
  const tones = [
    { bg: colors.goldSoft, fg: colors.burgundy },
    { bg: '#D9E8F2', fg: colors.sky },
    { bg: '#E3EFE0', fg: '#245B34' },
    { bg: '#F3EEE7', fg: colors.textSoft },
  ];
  const t = tones[tone % tones.length];
  return (
    <View
      style={{
        width: size,
        height: size,
        borderRadius: size / 2,
        backgroundColor: t.bg,
        borderWidth: 2,
        borderColor: colors.white,
        alignItems: 'center',
        justifyContent: 'center',
      }}
    >
      <RNText style={{ fontFamily: fonts.bold, fontSize: size * 0.38, color: t.fg }}>{(name || '?').charAt(0).toUpperCase()}</RNText>
    </View>
  );
}

export function Loading() {
  return (
    <View style={{ padding: 40, alignItems: 'center' }}>
      <ActivityIndicator color={colors.burgundy} />
    </View>
  );
}

export function ErrorNote({ message, onRetry }: { message: string; onRetry?: () => void }) {
  return (
    <View style={{ padding: 20, gap: 12 }}>
      <Banner tone="danger" icon="alert-circle">{message}</Banner>
      {onRetry ? <Button label="Zkusit znovu" kind="secondary" onPress={onRetry} /> : null}
    </View>
  );
}

export function Empty({ icon, title, text, action }: { icon: React.ComponentProps<typeof Feather>['name']; title: string; text?: string; action?: React.ReactNode }) {
  return (
    <Card style={{ alignItems: 'center', padding: 28, gap: 8 }}>
      <Feather name={icon} size={34} color={colors.gold} />
      <Text variant="title" style={{ textAlign: 'center' }}>{title}</Text>
      {text ? <Text variant="small" style={{ textAlign: 'center' }}>{text}</Text> : null}
      {action}
    </Card>
  );
}

export const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center' },
});
