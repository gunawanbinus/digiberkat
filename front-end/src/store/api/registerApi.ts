// @/src/store/api/registerApi.ts
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const registerApi = createApi({
  reducerPath: 'registerApi',
  baseQuery: fetchBaseQuery({ 
    baseUrl: 'http://localhost:8001/api/v1/',
    prepareHeaders: (headers) => {
      headers.set('Content-Type', 'application/json');
      return headers;
    },
  }),
  endpoints: (builder) => ({
    registerUser: builder.mutation({
      query: (credentials: { 
        username: string; 
        email: string; 
        password: string 
      }) => ({
        url: 'register/user',
        method: 'POST',
        body: credentials,
      }),
      transformResponse: () => {
        return { success: true };
      },
      transformErrorResponse: (response: any) => {
        if (response.data && typeof response.data === 'object') {
          if ('error' in response.data) {
            return { error: response.data.error };
          }
          if ('message' in response.data) {
            return { error: response.data.message };
          }
        }
        return { error: 'Terjadi kesalahan saat registrasi' };
      },
    }),
  }),
});

export const { useRegisterUserMutation } = registerApi;