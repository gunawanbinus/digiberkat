import { Stack } from 'expo-router';
import "@/global.css";
import { GluestackUIProvider } from "@/components/ui/gluestack-ui-provider";
import { Provider } from 'react-redux';
import { store } from '@/src/store/store';

export default function RootLayout() {
  return (
    <Provider store={store}>
      <GluestackUIProvider>
        <Stack>
          <Stack.Screen 
            name="index" 
            options={{ 
              title: 'Shop',
              headerShown: false // Jika ingin menyembunyikan header
            }} 
          />
          <Stack.Screen 
            name="cart" 
            options={{ 
              title: 'Cart',
              presentation: 'modal' // Untuk tampilan modal (opsional)
            }} 
          />
          <Stack.Screen 
            name="product/[id]" 
            options={{ 
              title: 'Product Details',
              headerBackTitle: 'Back' // Teks tombol back
            }} 
          />
        </Stack>
      </GluestackUIProvider>
    </Provider>
  );
}