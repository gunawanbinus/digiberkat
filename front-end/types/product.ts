// types/product.ts

export interface Product {
  id: number;
  category_id: number;
  name: string;
  description: string;
  is_varians: boolean;
  is_discounted: boolean;
  discount_price: number | null;
  price: number | null; // Sudah benar: bisa number atau null
  stock: number | null; // Sudah benar: bisa number atau null
  images: string[];
  thumbnails: string[];
  variants?: Variant[] | null; // Bisa array of Variant atau null jika is_varians false
  created_at?: string;
  updated_at?: string;
  // Jika API Anda kadang mengembalikan ini, bisa ditambahkan sebagai opsional
  min_variant_price?: number | null;
  is_available?: boolean;
}

export interface Variant {
  id: number;
  product_id: number;
  name: string;
  price: number | null; // **PERBAIKAN UTAMA DI SINI**: price juga bisa null
  is_discounted: boolean;
  discount_price: number | null;
  stock: number | null; // **PERBAIKAN KECIL**: stock varian juga bisa null jika tidak ada stok
  image?: string; // URL gambar spesifik varian, opsional
  created_at?: string;
  updated_at?: string;
}

// Tambahkan definisi untuk tipe RecommendedProduct yang sesuai dengan respons AI Anda
// Ini penting agar TypeScript memahami struktur data yang diterima dari AI.
export interface RecommendedProduct {
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
  variants?: Variant[] | null; // API AI mungkin mengembalikan varian
  similarity_score: number; // Ini adalah properti tambahan dari API AI
  // Jika API AI mengembalikan min_variant_price atau is_available, tambahkan di sini
  min_variant_price?: number | null;
  is_available?: boolean;
}


// ✅ Tipe aman untuk ditampilkan di list UI
// Pastikan properti ini mencerminkan bagaimana Anda menampilkan data di UI
export type ProductListItemData = Pick<Product,
  'id' | 'name' | 'description' | 'images' // 'images' bisa jadi array, tapi thumbnail biasanya cuma satu
> & {
  // Pastikan properti harga ini selalu ada dan bukan null saat ditampilkan
  // Logika untuk menentukan harga mana yang diambil (price atau discount_price atau dari varian)
  // harus dilakukan di komponen yang memetakan Product ke ProductListItemData.
  price: number; // Ini harga yang sudah final (bisa harga asli atau diskon) untuk ditampilkan
  isDiscounted: boolean; // Menunjukkan apakah harga yang ditampilkan adalah harga diskon
  discountPrice: number | null; // Harga diskon asli, mungkin untuk ditampilkan detail
  thumbnail: string; // Ambil salah satu dari 'thumbnails' jika 'images' adalah array
};