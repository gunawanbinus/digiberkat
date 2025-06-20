// @/src/store/store.ts
// @/src/store/store.ts
import { configureStore } from '@reduxjs/toolkit';
import { setupListeners } from '@reduxjs/toolkit/query';
import { persistReducer, persistStore } from 'redux-persist';
import storage from 'redux-persist/lib/storage';

import { registerApi } from '@/src/store/api/registerApi';
import { loginApi } from '@/src/store/api/loginApi';
import authReducer from '@/src/store/api/authSlice';
import { productsApi } from '@/src/store/api/productsApi';
import { cartApi } from '@/src/store/api/cartApi';
import { orderApi } from '@/src/store/api/orderApi';

const authPersistConfig = {
  key: 'auth',
  storage,
  whitelist: ['token', 'role', 'username', 'userId', 'expiresAt']
};

const persistedAuthReducer = persistReducer(authPersistConfig, authReducer);

export const store = configureStore({
  reducer: {
    [loginApi.reducerPath]: loginApi.reducer,
    [registerApi.reducerPath]: registerApi.reducer,
    [productsApi.reducerPath]: productsApi.reducer,
    [cartApi.reducerPath]: cartApi.reducer,
    [orderApi.reducerPath]: orderApi.reducer,
    auth: persistedAuthReducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: {
        ignoredActions: ['persist/PERSIST'],
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