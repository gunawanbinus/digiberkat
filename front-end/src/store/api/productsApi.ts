//src/store/api/productsApi.ts
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { Product, Variant } from '@/types/product';

export const productsApi = createApi({
  reducerPath: 'productsApi',
  baseQuery: fetchBaseQuery({ 
    baseUrl: 'http://localhost:8001/api/v1/',
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

  }),
});

export const { 
  useGetProductsQuery, 
  useGetProductByIdQuery,
  useLazyGetProductsQuery // Untuk fetch manual
} = productsApi;