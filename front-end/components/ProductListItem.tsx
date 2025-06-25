
// import { Card } from '@/components/ui/card';
// import { Image } from '@/components/ui/image';
// import { Text } from '@/components/ui/text';
// import { Heading } from '@/components/ui/heading';
// import { Link } from 'expo-router';
// import { Pressable, View } from 'react-native';
// import type { ProductListItemData } from '@/types/product';

// export default function ProductListItem({ product }: { product: ProductListItemData }) {
//   const hasDiscount = product.isDiscounted && product.discountPrice !== null;

//   const discountPercent =
//     hasDiscount && product.price && product.discountPrice
//       ? Math.round(((product.price - product.discountPrice) / product.price) * 100)
//       : 0;

//   const productImage =
//     product.thumbnail || product.images?.[0] || 'https://via.placeholder.com/150';

//   return (
//     <Link href={{ pathname: "/product/[id]", params: { id: product.id.toString() } }} asChild>
//       <Pressable className="flex-1">
//         <Card className="p-5 rounded-lg flex-1">
//           <Image
//             source={{ uri: productImage }}
//             className="mb-6 h-[240px] w-full rounded-md aspect-[4/3]"
//             alt={`${product.name} image`}
//             resizeMode="contain"
//           />

//           {hasDiscount && (
//             <View className="absolute top-2 left-2 bg-red-600 px-2 py-1 rounded-md">
//               <Text className="text-white text-xs font-bold">{discountPercent}% OFF</Text>
//             </View>
//           )}

//           <Text className="text-sm font-normal mb-2 text-typography-700">
//             {product.name}
//           </Text>

//           <View className="mb-4 flex-row items-center space-x-2">
//             {hasDiscount ? (
//               <>
//                 <Text className="text-sm text-gray-500 line-through">
//                   Rp {product.price?.toLocaleString('id-ID')}
//                 </Text>
//                 <Heading size="md" className="text-red-600">
//                   Rp {product.discountPrice?.toLocaleString('id-ID')}
//                 </Heading>
//                 <Text className="text-xs font-semibold text-red-600 bg-red-100 px-2 py-1 rounded">
//                   -{discountPercent}%
//                 </Text>
//               </>
//             ) : (
//               <Heading size="md" className="text-typography-800">
//                 Rp {product.price.toLocaleString('id-ID')}
//               </Heading>
//             )}
//           </View>
//         </Card>
//       </Pressable>
//     </Link>
//   );
// }
// @/components/ProductListItem.tsx
import React from 'react';
import { Pressable, View } from 'react-native';
import { Card } from '@/components/ui/card';
import { Image } from '@/components/ui/image';
import { Text } from '@/components/ui/text';
import { Heading } from '@/components/ui/heading';
import { Link } from 'expo-router';
import type { ProductListItemData } from '@/types/product';

export default function ProductListItem({ product }: { product: ProductListItemData }) {
  const hasDiscount = product.isDiscounted && product.discountPrice !== null;

  const discountPercent =
    hasDiscount && product.price && product.discountPrice
      ? Math.round(((product.price - product.discountPrice) / product.price) * 100)
      : 0;

  const productImage = product.thumbnail || product.images?.[0] || 'https://via.placeholder.com/150';

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

          <View className="mb-2">
            {hasDiscount ? (
              <>
                <Text className="text-sm text-gray-400 line-through">
                  Rp {product.price?.toLocaleString('id-ID')}
                </Text>

                <View className="flex-row items-center gap-2">
                  <Heading size="lg" className="text-red-600 font-bold">
                    {product.discountPrice?.toLocaleString('id-ID')}
                  </Heading>
                  <View className="bg-red-100 rounded px-2 py-0.5">
                    <Text className="text-xs font-semibold text-red-600">-{discountPercent}%</Text>
                  </View>
                </View>
              </>
            ) : (
              <Heading size="lg" className="text-typography-900 font-bold">
                Rp {product.price.toLocaleString('id-ID')}
              </Heading>
            )}
          </View>
        </Card>
      </Pressable>
    </Link>
  );
}
