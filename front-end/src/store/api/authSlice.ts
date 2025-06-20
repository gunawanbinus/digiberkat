// @/src/store/api/authSlice.ts
import { createSlice, PayloadAction } from '@reduxjs/toolkit';

interface AuthState {
  token: string | null;
  role: string | null;
  username: string | null;
  userId: number | null;
  expiresAt: number | null;
}

const initialState: AuthState = {
  token: null,
  role: null,
  username: null,
  userId: null,
  expiresAt: null,
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    setCredentials: (state, action: PayloadAction<{
      token: string;
      role: string;
      username: string;
      userId: number;
      expiresAt?: number | null;
    }>) => {
      state.token = action.payload.token;
      state.role = action.payload.role;
      state.username = action.payload.username;
      state.userId = action.payload.userId;
      state.expiresAt = action.payload.expiresAt || null;
    },
    logout: (state) => {
      state.token = null;
      state.role = null;
      state.username = null;
      state.userId = null;
      state.expiresAt = null;
    },
  },
});

export const { setCredentials, logout } = authSlice.actions;

// Selectors
export const selectCurrentToken = (state: { auth: AuthState }) => state.auth.token;
export const selectCurrentUser = (state: { auth: AuthState }) => ({
  username: state.auth.username,
  role: state.auth.role,
  userId: state.auth.userId
});

export const selectIsAuthenticated = (state: { auth: AuthState }) => {
  const { token, expiresAt } = state.auth;
  return token !== null && (expiresAt === null || Date.now() < expiresAt);
};

export default authSlice.reducer;