// @/src/store/store.ts
import { configureStore } from '@reduxjs/toolkit';
import { setupListeners } from '@reduxjs/toolkit/query';
import {
  persistReducer,
  persistStore,
  FLUSH,
  REHYDRATE,
  PAUSE,
  PERSIST,
  PURGE,
  REGISTER,
} from 'redux-persist';
// ✅ Import AsyncStorage untuk React Native
import AsyncStorage from '@react-native-async-storage/async-storage'; // Pastikan Anda sudah menginstal ini!

import { registerApi } from '@/src/store/api/registerApi';
import { loginApi } from '@/src/store/api/loginApi';
import authReducer from '@/src/store/api/authSlice';
import recommendationReducer from '@/src/store/api/recommendationSlice'; // ✅ Path yang benar ke recommendationSlice
import { productsApi } from '@/src/store/api/productsApi';
import { cartApi } from '@/src/store/api/cartApi';
import { orderApi } from '@/src/store/api/orderApi';

// Konfigurasi persist untuk slice 'auth'
const authPersistConfig = {
  key: 'auth',
  storage: AsyncStorage, // ✅ Gunakan AsyncStorage
  whitelist: ['token', 'role', 'username', 'userId', 'expiresAt'],
};

// Konfigurasi persist untuk slice 'recommendation'
const recommendationPersistConfig = {
  key: 'recommendation',
  storage: AsyncStorage, // ✅ Gunakan AsyncStorage
  whitelist: ['recommendedProducts', 'lastUpdated', 'hasRecommendations'], // Properti yang ingin Anda persist
};

const persistedAuthReducer = persistReducer(authPersistConfig, authReducer);
const persistedRecommendationReducer = persistReducer(recommendationPersistConfig, recommendationReducer); // ✅ Buat reducer rekomendasi yang di-persist

export const store = configureStore({
  reducer: {
    [loginApi.reducerPath]: loginApi.reducer,
    [registerApi.reducerPath]: registerApi.reducer,
    [productsApi.reducerPath]: productsApi.reducer,
    [cartApi.reducerPath]: cartApi.reducer,
    [orderApi.reducerPath]: orderApi.reducer,
    auth: persistedAuthReducer, // Gunakan reducer auth yang sudah di-persist
    recommendation: persistedRecommendationReducer, // ✅ Gunakan reducer rekomendasi yang sudah di-persist
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      // ✅ Abaikan semua aksi Redux-Persist untuk menghindari masalah serializableCheck
      serializableCheck: {
        ignoredActions: [FLUSH, REHYDRATE, PAUSE, PERSIST, PURGE, REGISTER],
      },
    })
      .concat(loginApi.middleware)
      .concat(registerApi.middleware)
      .concat(productsApi.middleware)
      .concat(cartApi.middleware)
      .concat(orderApi.middleware),
});

setupListeners(store.dispatch);

export const persistor = persistStore(store);

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;