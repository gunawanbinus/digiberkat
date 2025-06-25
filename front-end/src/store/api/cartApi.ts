// @/src/store/api/cartApi.ts
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

interface CartItemVariant {
  id: number;
  name: string;
}

interface CartItem {
  id: number;
  cart_id: number;
  product_id: number;
  product_variant_id: number | null;
  name: string;
  stock: number;
  thumbnails: string[];
  variants: CartItemVariant[];
  quantity: number;
  price: number;
  price_per_item: number;
  total_price: number;
}

interface CartResponse {
  data: CartItem[];
  message: string;
  total_cart_price: number;
}

interface AddToCartPayload {
  product_id: number;
  quantity: number;
  product_variant_id?: number;
}

// ✅ Interface untuk payload update variant
interface UpdateCartItemVariantPayload {
  id: number; // ID cart item yang akan diupdate
  variant_id: number; // ID variant baru
}

// ✅ Interface untuk respons sukses update variant
interface UpdateCartItemVariantSuccessResponse {
  merged_to_item_id?: number; // Opsional, jika item digabungkan
  message: string; // Pesan dari backend (kita bisa abaikan ini di UI jika mau)
  quantity_now?: number; // Opsional, jumlah quantity setelah digabung/diupdate
}

export const cartApi = createApi({
  reducerPath: 'cartApi',
  baseQuery: fetchBaseQuery({
    baseUrl: 'http://localhost:8001/api/v1/',
    prepareHeaders: (headers, { getState }) => {
      const token = (getState() as any).auth.token;
      if (token) {
        headers.set('Authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['Cart'],
  endpoints: (builder) => ({
    getCartItems: builder.query<CartResponse, void>({
      query: () => 'cart-items/my',
      providesTags: ['Cart'],
      transformErrorResponse: (response: { status: number; data?: { message?: string } }) => {
        return {
          status: response.status,
          message: response.data?.message || 'Gagal mengambil data keranjang',
        };
      },
    }),

    addToCart: builder.mutation<{ message: string }, AddToCartPayload>({
      query: (body) => ({
        url: 'cart-items',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['Cart'],
      transformErrorResponse: (response: { status: number; data?: { message?: string } }) => {
        return {
          status: response.status,
          message: response.data?.message || 'Gagal menambahkan item ke keranjang',
        };
      },
    }),

    updateCartItemQuantity: builder.mutation<{ message: string }, { id: number; quantity: number }>({
      query: ({ id, ...body }) => ({
        url: `cart-items/${id}`,
        method: 'PATCH',
        body,
      }),
      invalidatesTags: ['Cart'],
      transformErrorResponse: (response: { status: number; data?: { message?: string } }) => {
        return {
          status: response.status,
          message: response.data?.message || 'Gagal mengupdate quantity',
        };
      },
    }),

    // ✅ Endpoint baru untuk update variant
    updateCartItemVariant: builder.mutation<UpdateCartItemVariantSuccessResponse, UpdateCartItemVariantPayload>({
      query: ({ id, variant_id }) => ({
        url: `cart-items/${id}/variant`,
        method: 'PATCH',
        body: { variant_id }, // Payload harus { variant_id: number }
      }),
      invalidatesTags: ['Cart'], // Invalidate tag Cart agar data keranjang di-refetch
      transformErrorResponse: (response: { status: number; data?: { message?: string } }) => {
        // Meskipun Anda ingin pesan suksesnya generik, pesan error tetap perlu jelas
        return {
          status: response.status,
          message: response.data?.message || 'Gagal mengganti varian item keranjang',
        };
      },
    }),

    removeCartItem: builder.mutation<{ message: string }, number>({
      query: (id) => ({
        url: `cart-items/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['Cart'],
      transformErrorResponse: (response: { status: number; data?: { message?: string } }) => {
        return {
          status: response.status,
          message: response.data?.message || 'Gagal menghapus item dari keranjang',
        };
      },
    }),
  }),
});

export const {
  useGetCartItemsQuery,
  useAddToCartMutation,
  useUpdateCartItemQuantityMutation,
  useRemoveCartItemMutation,
  useUpdateCartItemVariantMutation, // ✅ Export hook baru
} = cartApi;