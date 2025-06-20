// app/_layout.tsx
import { Stack, router } from 'expo-router';
import "@/global.css";
import { GluestackUIProvider } from "@/components/ui/gluestack-ui-provider";
import { Provider } from 'react-redux';
import { store } from '@/src/store/store';
import { View, TouchableOpacity } from 'react-native';
import { ShoppingCart, ShoppingBasket } from 'lucide-react-native'; // Pastikan Anda mengimpor ikon yang benar
import { HStack } from '@/components/ui/hstack';

export default function RootLayout() {
  return (
    <Provider store={store}>
      <GluestackUIProvider>
        <View style={{ flex: 1 }}>
          <Stack>
            <Stack.Screen
              name="index"
              options={{
                title: 'Product Digiberkat', // Judul header
                headerShown: true, // Pastikan header ditampilkan
                // Tambahkan ikon di sisi kanan header
                headerRight: () => (
                  <HStack space="md" style={{ marginRight: 15 }}> {/* Atur jarak di sini */}
                    <TouchableOpacity
                      onPress={() => router.push('/cart')}
                      className="relative p-2"
                    >
                      <ShoppingCart size={24} color="#4B5563" />
                      {/* Badge untuk jumlah item keranjang bisa ditambahkan di sini */}
                    </TouchableOpacity>
                    <TouchableOpacity
                      onPress={() => router.push('/order')}
                      className="relative p-2"
                    >
                      <ShoppingBasket size={24} color="#4B5563" />
                    </TouchableOpacity>
                  </HStack>
                ),
              }}
            />
            {/* Screens lainnya tetap sama */}
            <Stack.Screen
              name="login"
              options={{
                title: 'Login',
                headerShown: false
              }}
            />
            <Stack.Screen
              name="register"
              options={{
                title: 'Daftar',
                headerShown: false
              }}
            />
            <Stack.Screen
              name="product/[id]"
              options={{
                headerBackTitle: 'Back'
              }}
            />
            <Stack.Screen
              name="cart"
              options={{
                title: 'Keranjang Saya',
                headerRight: () => (
                  <TouchableOpacity
                    onPress={() => router.push('/order')}
                    style={{ marginRight: 15 }}
                  >
                    <ShoppingBasket size={24} color="#000" />
                  </TouchableOpacity>
                ),
              }}
            />
            <Stack.Screen
              name="order"
              options={{ title: 'Riwayat Pesanan' }}
            />
          </Stack>
        </View>
      </GluestackUIProvider>
    </Provider>
  );
}