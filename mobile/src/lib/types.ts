export type User = { id: number; name: string; email: string; is_admin: boolean };

export type GroupRef = { id: number; name: string };

export type Wishlist = {
  id: number;
  title: string;
  description: string | null;
  occasion: 'christmas' | 'birthday' | 'wedding' | 'anniversary' | 'other';
  occasion_label: string;
  event_date: string | null;
  share_code: string;
  share_url: string;
  items_count: number;
  reserved_count: number;
  progress_percentage: number;
  author: { id: number; name: string } | null;
  groups: GroupRef[];
  is_mine?: boolean;
};

export type Coordination = {
  suggested_buyer: { name: string; is_me: boolean } | null;
  comments: { id: number; author_name: string; body: string; created_at: string | null; is_mine: boolean }[];
};

export type Item = {
  id: number;
  wishlist_id: number;
  title: string;
  url: string | null;
  price: number | null;
  currency: string;
  formatted_price: string | null;
  description: string | null;
  priority: 'low' | 'medium' | 'high';
  status: 'available' | 'reserved';
  is_group_gift: boolean;
  icon: string | null;
  image_url: string | null;
  contributed_total: number;
  contribution_percentage: number;
  remaining_amount: number;
  // pouze na sdílených seznamech
  is_my_reservation?: boolean;
  my_contributions?: { id: number; amount: number }[];
  my_contribution_total?: number;
  coordination?: Coordination | null;
};

export type Group = {
  id: number;
  name: string;
  owner_id: number;
  owner_name: string | null;
  is_owner: boolean;
  members_count: number | null;
  members_preview?: string[];
};

export type Dashboard = {
  my_wishlists: Wishlist[];
  group_wishlists: Wishlist[];
  pending_invitations_count: number;
};

export const OCCASIONS: { value: Wishlist['occasion']; label: string }[] = [
  { value: 'christmas', label: 'Vánoce' },
  { value: 'birthday', label: 'Narozeniny' },
  { value: 'wedding', label: 'Svatba' },
  { value: 'anniversary', label: 'Výročí' },
  { value: 'other', label: 'Ostatní' },
];

export const ICONS = ['gift', 'book', 'electronics', 'clothing', 'toy', 'jewelry', 'experience', 'home', 'sports', 'beauty'] as const;
export type IconName = (typeof ICONS)[number];
