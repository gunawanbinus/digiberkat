import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

interface CartItem {
  id: number;
  cart_id: number;
  product_id: number;
  product_variant_id: number | null;
  name: string;
  stock: number;
  thumbnails: string[];
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
} = cartApi;

