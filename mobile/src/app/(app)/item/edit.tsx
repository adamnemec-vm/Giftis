import { Feather } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import { router, useLocalSearchParams } from 'expo-router';
import React, { useEffect, useState } from 'react';
import { Image, KeyboardAvoidingView, Platform, Pressable, ScrollView, Switch, Text as RNText, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Banner, Button, Field, ItemIcon, Segmented, Text } from '../../../components/ui';
import { api, ApiError } from '../../../lib/api';
import { confirmAction, notify } from '../../../lib/dialog';
import { colors, fonts } from '../../../lib/theme';
import { ICONS, type Item } from '../../../lib/types';

type Priority = Item['priority'];
type Photo = { uri: string; name: string; type: string };

async function buildForm(fields: Record<string, string | null>, photo: Photo, method?: 'PUT'): Promise<FormData> {
  const form = new FormData();
  if (method) form.append('_method', method);
  for (const [key, value] of Object.entries(fields)) {
    if (value !== null && value !== '') form.append(key, value);
  }
  if (Platform.OS === 'web') {
    const blob = await (await fetch(photo.uri)).blob();
    form.append('image', blob, photo.name);
  } else {
    form.append('image', { uri: photo.uri, name: photo.name, type: photo.type } as unknown as Blob);
  }
  return form;
}

export default function ItemFormScreen() {
  const { wishlistId, itemId } = useLocalSearchParams<{ wishlistId: string; itemId?: string }>();
  const editing = !!itemId;
  const insets = useSafeAreaInsets();

  const [title, setTitle] = useState('');
  const [price, setPrice] = useState('');
  const [currency, setCurrency] = useState('Kč');
  const [url, setUrl] = useState('');
  const [description, setDescription] = useState('');
  const [icon, setIcon] = useState<string | null>(editing ? null : 'gift');
  const [existingImage, setExistingImage] = useState<string | null>(null);
  const [photo, setPhoto] = useState<Photo | null>(null);
  const [priority, setPriority] = useState<Priority>('medium');
  const [isGroup, setIsGroup] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);

  useEffect(() => {
    if (!editing) return;
    api
      .get<{ items: Item[] }>(`/wishlists/${wishlistId}`)
      .then(({ items }) => {
        const item = items.find((i) => String(i.id) === itemId);
        if (!item) return;
        setTitle(item.title);
        setPrice(item.price !== null ? String(item.price) : '');
        setCurrency(item.currency);
        setUrl(item.url ?? '');
        setDescription(item.description ?? '');
        setExistingImage(item.image_url);
        setIcon(item.image_url ? null : item.icon ?? 'gift');
        setPriority(item.priority);
        setIsGroup(item.is_group_gift);
      })
      .catch(() => {});
  }, [editing, wishlistId, itemId]);

  async function pickPhoto(source: 'library' | 'camera') {
    try {
      const options: ImagePicker.ImagePickerOptions = {
        mediaTypes: ['images'],
        allowsEditing: true,
        aspect: [1, 1],
        quality: 0.7,
      };
      if (source === 'camera') {
        const perm = await ImagePicker.requestCameraPermissionsAsync();
        if (!perm.granted) {
          notify('Fotoaparát není povolen', 'Povolte přístup k fotoaparátu v nastavení telefonu.');
          return;
        }
      }
      const result =
        source === 'camera' ? await ImagePicker.launchCameraAsync(options) : await ImagePicker.launchImageLibraryAsync(options);
      if (result.canceled || !result.assets?.length) return;

      const asset = result.assets[0];
      const type = asset.mimeType ?? 'image/jpeg';
      const ext = type.includes('png') ? 'png' : type.includes('webp') ? 'webp' : 'jpg';
      setPhoto({ uri: asset.uri, name: asset.fileName ?? `dar.${ext}`, type });
      setIcon(null);
    } catch {
      notify('Fotku se nepodařilo načíst');
    }
  }

  function removePhoto() {
    setPhoto(null);
    setExistingImage(null);
    setIcon('gift');
  }

  async function save() {
    setBusy(true);
    setError(null);

    const priceValue = price.trim() ? String(Number(price.replace(/\s/g, '').replace(',', '.'))) : null;
    const base = {
      title: title.trim(),
      price: priceValue,
      currency: currency.trim() || 'Kč',
      url: url.trim() || null,
      description: description.trim() || null,
      priority,
    };

    try {
      if (photo) {
        const form = await buildForm({ ...base, is_group_gift: isGroup ? '1' : '0' }, photo, editing ? 'PUT' : undefined);
        if (editing) await api.post(`/items/${itemId}`, form);
        else await api.post(`/wishlists/${wishlistId}/items`, form);
      } else {
        const body = { ...base, price: priceValue === null ? null : Number(priceValue), is_group_gift: isGroup, ...(icon ? { icon } : {}) };
        if (editing) await api.put(`/items/${itemId}`, body);
        else await api.post(`/wishlists/${wishlistId}/items`, body);
      }
      router.back();
    } catch (e) {
      setError(e as ApiError);
    } finally {
      setBusy(false);
    }
  }

  async function remove() {
    if (!(await confirmAction('Smazat dárek?', 'Dárek bude trvale odstraněn ze seznamu.', 'Smazat', true))) return;
    try {
      await api.delete(`/items/${itemId}`);
      router.back();
    } catch (e) {
      notify('Nepovedlo se', e instanceof ApiError ? e.message : undefined);
    }
  }

  const previewUri = photo?.uri ?? existingImage;

  return (
    <KeyboardAvoidingView style={{ flex: 1, backgroundColor: colors.white }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={{ padding: 20, paddingBottom: insets.bottom + 24, gap: 16 }} keyboardShouldPersistTaps="handled">
        <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
          <Button label="Zrušit" kind="ghost" small onPress={() => router.back()} />
          <Text variant="title">{editing ? 'Upravit dárek' : 'Nový dárek'}</Text>
          <View style={{ width: 70 }} />
        </View>

        <Field label="Název dárku" value={title} onChangeText={setTitle} placeholder="např. Bezdrátová sluchátka" error={error?.field('title')} />

        <View style={{ flexDirection: 'row', gap: 10 }}>
          <View style={{ flex: 1 }}>
            <Field label="Cena" value={price} onChangeText={setPrice} keyboardType="decimal-pad" placeholder="4990" error={error?.field('price')} />
          </View>
          <View style={{ width: 96 }}>
            <Field label="Měna" value={currency} onChangeText={setCurrency} autoCapitalize="none" error={error?.field('currency')} />
          </View>
        </View>

        <Field
          label="Odkaz na e-shop"
          value={url}
          onChangeText={setUrl}
          autoCapitalize="none"
          keyboardType="url"
          placeholder="https://www.alza.cz/…"
          hint="Celá adresa včetně https://"
          error={error?.field('url')}
        />

        <View style={{ gap: 8 }}>
          <RNText style={{ fontFamily: fonts.semi, fontSize: 13, color: colors.text }}>Náhled dárku</RNText>

          {previewUri ? (
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 14 }}>
              <Image source={{ uri: previewUri }} accessibilityLabel="Náhled vybrané fotky" style={{ width: 96, height: 96, borderRadius: 18, backgroundColor: colors.sand }} />
              <View style={{ flex: 1, gap: 8 }}>
                <Button label="Změnit fotku" kind="secondary" small icon="image" onPress={() => pickPhoto('library')} />
                <Button label="Odebrat fotku" kind="ghost" small icon="x" onPress={removePhoto} />
              </View>
            </View>
          ) : (
            <View style={{ flexDirection: 'row', gap: 10 }}>
              <Button label="Vybrat fotku" kind="secondary" small icon="image" style={{ flex: 1 }} onPress={() => pickPhoto('library')} />
              {Platform.OS !== 'web' ? <Button label="Vyfotit" kind="secondary" small icon="camera" style={{ flex: 1 }} onPress={() => pickPhoto('camera')} /> : null}
            </View>
          )}
          {error?.field('image') ? <Text variant="small" style={{ color: colors.danger }}>{error.field('image')}</Text> : null}
          <Text variant="small">…nebo zvolte ikonu:</Text>

          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 10 }}>
            {ICONS.map((name) => {
              const active = icon === name;
              return (
                <Pressable
                  key={name}
                  accessibilityRole="button"
                  accessibilityLabel={`Ikona ${name}`}
                  accessibilityState={{ selected: active }}
                  onPress={() => {
                    setIcon(name);
                    setPhoto(null);
                    setExistingImage(null);
                  }}
                  style={{
                    width: 54,
                    height: 54,
                    borderRadius: 27,
                    alignItems: 'center',
                    justifyContent: 'center',
                    backgroundColor: active ? colors.goldSoft : colors.cream,
                    borderWidth: active ? 2 : 1,
                    borderColor: active ? colors.burgundy : colors.inputBorder,
                  }}
                >
                  <ItemIcon icon={name} size={26} />
                </Pressable>
              );
            })}
          </ScrollView>
        </View>

        <View style={{ gap: 8 }}>
          <RNText style={{ fontFamily: fonts.semi, fontSize: 13, color: colors.text }}>Priorita</RNText>
          <Segmented
            value={priority}
            onChange={setPriority}
            options={[
              { value: 'low', label: 'Nízká' },
              { value: 'medium', label: 'Střední' },
              { value: 'high', label: 'Velké přání' },
            ]}
          />
        </View>

        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 12, padding: 14, borderRadius: 14, backgroundColor: colors.blueSoft }}>
          <View style={{ flex: 1 }}>
            <Text variant="label" style={{ color: colors.blue }}>Skupinový dárek</Text>
            <Text variant="small" style={{ color: '#34415F' }}>Přátelé se mohou složit na cenu. Vyžaduje vyplněnou cenu.</Text>
          </View>
          <Switch value={isGroup} onValueChange={setIsGroup} trackColor={{ true: colors.burgundy, false: '#D9D0C5' }} accessibilityLabel="Skupinový dárek" />
        </View>

        <Field label="Doplňující informace (volitelné)" value={description} onChangeText={setDescription} multiline error={error?.field('description')} />

        {error && !Object.keys(error.errors).length ? <Banner tone="danger" icon="alert-circle">{error.message}</Banner> : null}
        {error?.field('is_group_gift') ? <Banner tone="danger" icon="alert-circle">{error.field('is_group_gift')!}</Banner> : null}

        <Button label={editing ? 'Uložit změny' : 'Přidat dárek'} onPress={save} loading={busy} />
        {editing ? <Button label="Smazat dárek" kind="danger" icon="trash-2" onPress={remove} /> : null}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
