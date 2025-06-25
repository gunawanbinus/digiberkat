// types/recommendation.ts

import { Product, Variant } from '@/types/product'; // Sesuaikan path jika perlu

// Definisi ulang RecommendedProduct yang mencakup properti 'Product'
// dan menambahkan 'similarity_score'.
// Jika Product bisa memiliki properti opsional seperti min_variant_price,
// maka RecommendedProduct juga harus mencerminkannya jika API AI mengembalikannya.
export interface RecommendedProduct extends Product {
  similarity_score: number;
}

// Catatan: Jika API AI Anda mengembalikan struktur Product yang sedikit berbeda
// (misalnya, selalu menyertakan `variants` meskipun is_varians false,
// atau tidak menyertakan `created_at`/`updated_at`), Anda mungkin perlu
// menyesuaikan interface Product di types/product.ts atau membuat
// interface RecommendedProduct yang lebih spesifik di sini.
// Namun, untuk saat ini, `extends Product` seharusnya cukup jika API AI
// mengembalikan semua properti Product + similarity_score.