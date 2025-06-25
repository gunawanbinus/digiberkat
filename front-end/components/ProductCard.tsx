// @/components/ProductCard.tsx
import React from 'react';
import { Pressable, View } from 'react-native';
import { Card } from '@/components/ui/card';
import { Image } from '@/components/ui/image';
import { Text } from '@/components/ui/text';
import { Heading } from '@/components/ui/heading';
import { Link } from 'expo-router';
// --- PERUBAHAN DI SINI ---
// Import RecommendedProduct dari tempat Anda menyimpannya (misal: types/recommendation.ts)
import type { RecommendedProduct } from '@/types/recommendation'; 

// ProductCard akan menerima RecommendedProduct
export default function ProductCard({ product }: { product: RecommendedProduct }) {
  // Logika untuk menentukan apakah ada diskon, sama seperti di ProductListItem
  const hasDiscount = product.is_discounted && product.discount_price !== null;

  const discountPercent =
    hasDiscount && product.price && product.discount_price
      ? Math.round(((product.price - product.discount_price) / product.price) * 100)
      : 0;

  // Logika untuk mendapatkan gambar produk, sama seperti di ProductListItem
  // RecommendedProduct sudah punya properti images dan thumbnails
  const productImage = product.thumbnails?.[0] || product.images?.[0] || 'https://via.placeholder.com/150/0000FF/FFFFFF?text=No+Image';

  // Logika untuk harga, jika ada varian, ambil harga varian pertama
  const displayPrice = product.is_varians && product.variants?.length
    ? (product.variants[0].is_discounted && product.variants[0].discount_price !== null 
        ? product.variants[0].discount_price 
        : product.variants[0].price) 
    : (product.is_discounted && product.discount_price !== null 
        ? product.discount_price 
        : product.price);

  const originalPrice = product.is_varians && product.variants?.length
    ? product.variants[0].price
    : product.price;

  return (
    <Link href={{ pathname: '/product/[id]', params: { id: product.id.toString() } }} asChild>
      <Pressable className="flex-1">
        <Card
          className="
            p-5 
            rounded-lg 
            flex-1 
            border border-gray-300 
            bg-white 
            shadow-md 
            relative
          "
          style={{
            shadowColor: '#000',
            shadowOffset: { width: 0, height: 2 },
            shadowOpacity: 0.1,
            shadowRadius: 6,
            elevation: 3,
          }}
        >
          <Image
            source={{ uri: productImage }}
            className="mb-4 h-[240px] w-full rounded-md aspect-[4/3]"
            alt={`${product.name} image`}
            resizeMode="contain"
          />

          {hasDiscount && (
            <View className="absolute top-3 left-3 bg-red-600 px-2 py-1 rounded-md z-10 shadow-lg">
              <Text className="text-white text-xs font-bold">{discountPercent}% OFF</Text>
            </View>
          )}

          <Text
            className="text-base font-semibold mb-3 text-typography-900"
            numberOfLines={2}
            ellipsizeMode="tail"
          >
            {product.name}
          </Text>

          {/* Opsional: Tampilkan similarity_score jika diinginkan */}
          {/* <Text className="text-xs text-gray-500 mb-1">Skor Relevansi: {product.similarity_score.toFixed(3)}</Text> */}

          <View className="mb-2">
            {hasDiscount ? (
              <>
                <Text className="text-sm text-gray-400 line-through">
                  Rp {originalPrice?.toLocaleString('id-ID')}
                </Text>

                <View className="flex-row items-center gap-2">
                  <Heading size="lg" className="text-red-600 font-bold">
                    {displayPrice?.toLocaleString('id-ID')}
                  </Heading>
                  <View className="bg-red-100 rounded px-2 py-0.5">
                    <Text className="text-xs font-semibold text-red-600">-{discountPercent}%</Text>
                  </View>
                </View>
              </>
            ) : (
              <Heading size="lg" className="text-typography-900 font-bold">
                Rp {displayPrice?.toLocaleString('id-ID')}
              </Heading>
            )}
          </View>
        </Card>
      </Pressable>
    </Link>
  );
}