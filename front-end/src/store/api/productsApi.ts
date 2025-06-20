// src/store/api/productsApi.ts
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { Product } from '@/types/product';

// Define a type for the data returned by the AI recommendation API
export interface RecommendedProduct extends Product {
  similarity_score: number;
}

export const productsApi = createApi({
  reducerPath: 'productsApi',
  baseQuery: fetchBaseQuery({
    baseUrl: 'http://localhost:8001/api/v1/', // <-- Ini adalah base URL untuk API backend utama Anda
    prepareHeaders: (headers) => {
      // Contoh jika menggunakan auth token:
      // const token = localStorage.getItem('token');
      // if (token) headers.set('Authorization', `Bearer ${token}`);
      return headers;
    },
  }),
  tagTypes: ['Product'], // Untuk caching dan invalidation
  endpoints: (builder) => ({
    getProducts: builder.query<Product[], void>({
      query: () => 'products',
      transformResponse: (response: { data: Product[] }) => response.data,
      providesTags: ['Product'], // Tag untuk caching
    }),
    getProductById: builder.query<Product, number>({
      query: (id) => `products/id/${id}`,
      transformResponse: (response: { data: Product }) => response.data,
      providesTags: (result, error, id) => [{ type: 'Product', id }],
    }),

    // --- NEW: AI Recommendation Endpoint ---
    getRecommendedProducts: builder.mutation<
      { recommendations: RecommendedProduct[] }, // Expected response type
      { userQuery: string; products: Product[] } // Payload type
    >({
      query: ({ userQuery, products }) => ({
        // !!! GANTI URL DI BAWAH INI DENGAN PUBLIC URL NGROK ANDA !!!
        // Saat ini: https://b905-34-169-85-212.ngrok-free.app
        url: 'https://0913-34-125-81-73.ngrok-free.app/recommend', // <--- PASTE URL NGROK DI SINI
        method: 'POST',
        body: { userQuery, products },
      }),
      // Tidak perlu invalidatesTags karena ini hanya rekomendasi, bukan mengubah data produk
      // transformErrorResponse: (response: any) => {
      //   return {
      //     status: response.status,
      //     message: response.data?.error || 'Gagal mendapatkan rekomendasi AI',
      //   };
      // },
    }),
  }),
});

export const {
  useGetProductsQuery,
  useGetProductByIdQuery,
  useLazyGetProductsQuery,
  useGetRecommendedProductsMutation, // Export the new mutation hook
} = productsApi;