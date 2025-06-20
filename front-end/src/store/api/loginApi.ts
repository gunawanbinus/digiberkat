// @/src/store/api/loginApi.ts
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import { setCredentials } from './authSlice';

const decodeJWT = (token: string) => {
  try {
    const base64Url = token.split('.')[1];
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split('')
        .map((c) => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
        .join('')
    );
    return JSON.parse(jsonPayload);
  } catch (e) {
    return null;
  }
};

type LoginRequest = {
  username: string;
  password: string;
  role: string;
};

export const loginApi = createApi({
  reducerPath: 'loginApi',
  baseQuery: fetchBaseQuery({ 
    baseUrl: 'http://localhost:8001/api/v1/',
    prepareHeaders: (headers) => {
      headers.set('Content-Type', 'application/json');
      return headers;
    },
  }),
  endpoints: (builder) => ({
    loginUser: builder.mutation({
      query: (credentials: LoginRequest) => ({
        url: 'login',
        method: 'POST',
        body: credentials,
      }),
      transformResponse: (response: { 
        message: string;
        role: string;
        token: string;
        user: { id: number; username: string };
      }) => {
        const decodedToken = decodeJWT(response.token);
        return {
          token: response.token,
          role: response.role,
          username: response.user.username,
          userId: response.user.id,
          expiresAt: decodedToken?.exp ? decodedToken.exp * 1000 : null
        };
      },
      transformErrorResponse: (response: any) => {
        if (response.data && typeof response.data === 'object') {
          return { 
            status: response.status,
            data: response.data 
          };
        }
        return { 
          status: response.status,
          data: { error: 'Terjadi kesalahan pada server' } 
        };
      },
      async onQueryStarted(args, { dispatch, queryFulfilled }) {
        try {
          const { data } = await queryFulfilled;
          dispatch(setCredentials(data));
        } catch (error) {
          // Error handling sudah di transformErrorResponse
        }
      },
    }),
  }),
});

export const { useLoginUserMutation } = loginApi;