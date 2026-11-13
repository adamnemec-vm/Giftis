import { Feather } from '@expo/vector-icons';
import * as Linking from 'expo-linking';
import { useLocalSearchParams } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import React, { useEffect, useRef, useState } from 'react';
import { KeyboardAvoidingView, Platform, Text as RNText, ScrollView, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Button, Field, Text } from '../components/ui';
import { API_URL, ApiError } from '../lib/api';
import { useAuth } from '../lib/auth';
import { colors, fonts } from '../lib/theme';

const API_ORIGIN = API_URL.replace(/\/api\/v1$/, '');

export default function LoginScreen() {
  const { login, register, loginWithGoogleCode } = useAuth();
  const insets = useSafeAreaInsets();
  const params = useLocalSearchParams<{ code?: string }>();
  const [mode, setMode] = useState<'login' | 'register'>('login');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [busy, setBusy] = useState(false);
  const [googleBusy, setGoogleBusy] = useState(false);
  const [error, setError] = useState<ApiError | Error | null>(null);
  const exchanged = useRef(false);

  const fieldError = (f: string) => (error instanceof ApiError ? error.field(f) : undefined);
  const generalError = !error
    ? null
    : error instanceof ApiError
      ? (error.field('code') ?? (Object.keys(error.errors).length ? null : error.message))
      : error.message;

  async function finishGoogle(code: string) {
    setGoogleBusy(true);
    setError(null);
    try {
      await loginWithGoogleCode(code);
    } catch (e) {
      setError(e as Error);
    } finally {
      setGoogleBusy(false);
    }
  }

  // Web: Google nás vrací zpět na /login?code=…
  useEffect(() => {
    if (params.code && !exchanged.current) {
      exchanged.current = true;
      void finishGoogle(String(params.code));
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [params.code]);

  async function googleSignIn() {
    setError(null);
    if (Platform.OS === 'web') {
      const back = `${window.location.origin}/login`;
      window.location.href = `${API_ORIGIN}/auth/google/mobile?return=${encodeURIComponent(back)}`;
      return;
    }
    const back = Linking.createURL('google');
    setGoogleBusy(true);
    try {
      const result = await WebBrowser.openAuthSessionAsync(`${API_ORIGIN}/auth/google/mobile?return=${encodeURIComponent(back)}`, back);
      if (result.type === 'success') {
        const code = Linking.parse(result.url).queryParams?.code;
        if (typeof code === 'string') await loginWithGoogleCode(code);
        else setError(new Error('Přihlášení přes Google se nepovedlo.'));
      }
    } catch (e) {
      setError(e as Error);
    } finally {
      setGoogleBusy(false);
    }
  }

  async function submit() {
    setBusy(true);
    setError(null);
    try {
      if (mode === 'login') await login(email.trim(), password);
      else await register(name.trim(), email.trim(), password, confirmation);
    } catch (e) {
      setError(e as Error);
    } finally {
      setBusy(false);
    }
  }

  return (
    <KeyboardAvoidingView style={{ flex: 1, backgroundColor: colors.burgundy }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={{ flexGrow: 1 }} keyboardShouldPersistTaps="handled" bounces={false}>
        <View style={{ paddingTop: insets.top + 36, paddingBottom: 34, alignItems: 'center', gap: 10 }}>
          <View
            style={{
              width: 104,
              height: 104,
              borderRadius: 30,
              backgroundColor: colors.cream,
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <Feather name="gift" size={54} color={colors.burgundy} />
          </View>
          <RNText style={{ fontFamily: fonts.serif, fontSize: 48, color: colors.cream, marginTop: 6 }}>
            Gift<RNText style={{ color: colors.gold }}>is</RNText>
          </RNText>
          <Text variant="body" style={{ color: '#F3E9DD', textAlign: 'center', fontSize: 16 }}>
            Radost z dárků bez duplicit.
          </Text>
        </View>

        <View
          style={{
            flex: 1,
            backgroundColor: colors.cream,
            borderTopLeftRadius: 28,
            borderTopRightRadius: 28,
            padding: 24,
            paddingBottom: insets.bottom + 24,
            gap: 14,
          }}
        >
          <Text variant="h2">{mode === 'login' ? 'Vítejte' : 'Nový účet'}</Text>
          <Text variant="small" style={{ marginTop: -8 }}>
            Vytvářejte seznamy přání, sdílejte je s blízkými a nechte si dárky rezervovat. Překvapení zůstane zachováno.
          </Text>

          <Button label="Pokračovat přes Google" kind="secondary" icon="log-in" onPress={googleSignIn} loading={googleBusy} />

          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}>
            <View style={{ flex: 1, height: 1, backgroundColor: colors.inputBorder }} />
            <Text variant="caption">nebo e-mailem</Text>
            <View style={{ flex: 1, height: 1, backgroundColor: colors.inputBorder }} />
          </View>

          {mode === 'register' ? (
            <Field label="Jméno" value={name} onChangeText={setName} autoCapitalize="words" error={fieldError('name')} />
          ) : null}
          <Field
            label="E-mail"
            value={email}
            onChangeText={setEmail}
            autoCapitalize="none"
            autoCorrect={false}
            keyboardType="email-address"
            error={fieldError('email')}
          />
          <Field label="Heslo" value={password} onChangeText={setPassword} secureTextEntry error={fieldError('password')} />
          {mode === 'register' ? (
            <Field label="Heslo znovu" value={confirmation} onChangeText={setConfirmation} secureTextEntry />
          ) : null}

          {generalError ? <Text variant="small" style={{ color: colors.danger }}>{generalError}</Text> : null}

          <Button label={mode === 'login' ? 'Přihlásit se' : 'Vytvořit účet'} onPress={submit} loading={busy} />
          <Button
            kind="ghost"
            label={mode === 'login' ? 'Nemám účet – registrovat se' : 'Už mám účet – přihlásit se'}
            onPress={() => {
              setMode(mode === 'login' ? 'register' : 'login');
              setError(null);
            }}
          />
          <Text variant="caption" style={{ textAlign: 'center', fontFamily: fonts.regular }}>
            Server: {API_URL}
          </Text>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
