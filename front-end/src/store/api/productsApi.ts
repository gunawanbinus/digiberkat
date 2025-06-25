// src/store/api/productsApi.ts
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { Product } from '@/types/product';
import type { RecommendedProduct } from '@/types/recommendation';

// Definisi tipe untuk struktur respons mentah dari API AI Anda
interface RawAIResponse {
  data: RecommendedProduct[];
  message: string;
}

export const productsApi = createApi({
  reducerPath: 'productsApi',
  baseQuery: fetchBaseQuery({
    baseUrl: 'http://localhost:8001/api/v1/', // <-- Base URL untuk API backend utama Anda
    prepareHeaders: (headers) => {
      // Contoh jika menggunakan auth token:
      // const token = localStorage.getItem('token');
      // if (token) headers.set('Authorization', `Bearer ${token}`);
      return headers;
    },
  }),
  tagTypes: ['Product', 'Recommendation'],
  endpoints: (builder) => ({
    getProducts: builder.query<Product[], void>({
      query: () => 'products',
      transformResponse: (response: { data: Product[] }) => response.data,
      providesTags: ['Product'],
    }),
    getProductById: builder.query<Product, number>({
      query: (id) => `products/id/${id}`,
      transformResponse: (response: { data: Product }) => response.data,
      providesTags: (result, error, id) => [{ type: 'Product', id }],
    }),

    // NEW: AI Recommendation Endpoint
    getRecommendedProducts: builder.mutation<
      RecommendedProduct[], // ✅ Tipe ini adalah output respons SETELAH transformResponse
      { userQuery: string; products: Product[] } // Payload yang dikirim ke API
    >({
      query: ({ userQuery, products }) => ({
        url: 'https://f9d1-35-243-134-151.ngrok-free.app/recommend', // <--- PASTE URL NGROK ANDA DI SINI
        method: 'POST',
        body: { userQuery, products },
      }),
      // ✅ Gunakan transformResponse untuk mengekstrak array 'data'
      // Ini akan memastikan bahwa 'action.payload' di extraReducers adalah RecommendedProduct[]
      transformResponse: (response: RawAIResponse) => response.data,
      // Karena data akan disimpan ke slice terpisah, kita tidak perlu providesTags/invalidatesTags di sini.
      // Caching mutasi bawaan RTK Query hanya men-cache hasil terakhir.
    }),
  }),
});

export const {
  useGetProductsQuery,
  useGetProductByIdQuery,
  useLazyGetProductsQuery,
  useGetRecommendedProductsMutation, // Export the new mutation hook
} = productsApi;