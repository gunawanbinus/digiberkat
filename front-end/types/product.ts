// types/product.ts
export interface Product {
  id: number;
  category_id: number;
  name: string;
  description: string;
  is_varians: boolean;
  is_discounted: boolean;
  discount_price: number | null;
  price: number | null;
  stock: number | null;
  images: string[];
  thumbnails: string[];
  variants?: Variant[];
  created_at?: string;
  updated_at?: string;
}

export interface Variant {
  id: number;
  product_id: number;
  name: string;
  price: number;
  is_discounted: boolean;
  discount_price: number | null;
  stock: number;
  image?: string;
  created_at?: string;
  updated_at?: string;
}

// ✅ Tipe aman untuk ditampilkan di list UI
export type ProductListItemData = Pick<Product,
  'id' | 'name' | 'description' | 'images'
> & {
  price: number;
  isDiscounted: boolean;
  discountPrice: number | null;
  thumbnail: string;
};
