// src/store/api/recommendationSlice.ts
import { createSlice, PayloadAction } from '@reduxjs/toolkit';
import { RecommendedProduct } from '@/types/recommendation';
import { productsApi } from '@/src/store/api/productsApi'; // Import productsApi untuk integrasi

interface RecommendationState {
  recommendedProducts: RecommendedProduct[];
  lastUpdated: number | null;
  hasRecommendations: boolean; // Menandakan apakah ada rekomendasi yang berhasil diambil
}

const initialState: RecommendationState = {
  recommendedProducts: [],
  lastUpdated: null,
  hasRecommendations: false,
};

const recommendationSlice = createSlice({
  name: 'recommendation',
  initialState,
  reducers: {
    setRecommendations: (state, action: PayloadAction<RecommendedProduct[]>) => {
      state.recommendedProducts = action.payload;
      state.lastUpdated = Date.now();
      state.hasRecommendations = action.payload.length > 0;
    },
    clearRecommendations: (state) => {
      state.recommendedProducts = [];
      state.lastUpdated = null;
      state.hasRecommendations = false;
    },
  },
  extraReducers: (builder) => {
    // Tangani hasil sukses dari mutasi getRecommendedProducts
    builder.addMatcher(
      productsApi.endpoints.getRecommendedProducts.matchFulfilled,
      // ✅ Dengan transformResponse di productsApi.ts, action.payload sekarang adalah RecommendedProduct[]
      (state, action: PayloadAction<RecommendedProduct[]>) => {
        state.recommendedProducts = action.payload; // Gunakan action.payload langsung
        state.lastUpdated = Date.now();
        state.hasRecommendations = action.payload.length > 0;
      }
    );
  },
});

export const { setRecommendations, clearRecommendations } = recommendationSlice.actions;

export default recommendationSlice.reducer;