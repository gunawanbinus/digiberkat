// // app/index.tsx
// import { FlatList, View, ActivityIndicator, RefreshControl } from 'react-native';
// import { useBreakpointValue } from '@/components/ui/utils/use-break-point-value';
// import type { ProductListItemData } from '@/types/product';
// import { Text } from '../components/ui/text';
// import { Button, ButtonText } from '../components/ui/button';
// import { useGetProductsQuery } from '@/src/store/api/productsApi';
// import type { Product } from '@/types/product';
// import { useCallback } from 'react';
// import { useFocusEffect } from '@react-navigation/native';
// import ProductListItem from '@/components/ProductListItem';


// // Pastikan ini sesuai dengan actual response API
// type ApiProduct = Product;

// function getWidthClass(numColumns: number): string {
//   const widthMap = {
//     1: 'w-full',
//     2: 'w-1/2',
//     3: 'w-1/3', 
//     4: 'w-1/4',
//     5: 'w-1/5',
//     6: 'w-1/6'
//   } as const;
  
//   return widthMap[numColumns as keyof typeof widthMap] || 'w-full';
// }

// export default function HomeScreen() {
//   const numColumns = useBreakpointValue({ default: 2, sm: 3, md: 4 }) as number;
//   const widthClass = getWidthClass(numColumns);
  
//   const { 
//     data: products, 
//     error, 
//     isLoading, 
//     isFetching, 
//     refetch 
//   } = useGetProductsQuery();

//    // Debug: Log setiap perubahan state query
//   console.log('HomeScreen state:', {
//     isLoading,
//     isFetching,
//     error: error ? JSON.stringify(error) : null,
//     products: products ? `Received ${products.length} products` : null
//   });

//   useFocusEffect(
//     useCallback(() => {
//       console.log('HomeScreen focused, refetching data...');
//       refetch();
//     }, [refetch])
//   );

//   const flattenProduct = (product: ApiProduct): ProductListItemData => {
//     const isVariant = product.is_varians && product.variants?.length;
//     const firstVariant = isVariant ? product.variants![0] : null;

//     const thumbnail = product.thumbnails?.[0]
//       || product.images?.[0]
//       || 'https://via.placeholder.com/150';

//     return {
//       id: product.id,
//       name: product.name,
//       description: product.description,
//       images: product.thumbnails?.length ? product.thumbnails : product.images,
//       thumbnail,
//       price: isVariant
//         ? firstVariant?.price ?? 0
//         : product.price ?? 0,
//       isDiscounted: isVariant
//         ? firstVariant?.is_discounted ?? false
//         : product.is_discounted,
//       discountPrice: isVariant
//         ? firstVariant?.discount_price ?? null
//         : product.discount_price ?? null
//     };
// };


//   if (isLoading) {
//     return <LoadingView />;
//   }

//   if (error) {
//     return <ErrorView onRetry={refetch} />;
//   }

//   if (!products?.length) {
//     return <EmptyView onRefresh={refetch} />;
//   }

//   return (
//     <FlatList 
//       data={products}
//       key={`product-list-${numColumns}`}
//       keyExtractor={(item) => item.id.toString()}
//       numColumns={numColumns}
//       contentContainerClassName="gap-2 max-w-[960px] mx-auto w-full p-2"
//       columnWrapperClassName="gap-2"
//       refreshControl={
//         <RefreshControl
//           refreshing={isFetching}
//           onRefresh={refetch}
//         />
//       }
//       renderItem={({ item }) => (
//         <View className={`${widthClass} p-1`}>
//           <ProductListItem product={flattenProduct(item)} />
//         </View>
//       )}
//       ListFooterComponent={<View className="h-20" />}
//     />
//   );
// }

// // Komponen pemisah untuk state views
// function LoadingView() {
//   return (
//     <View className="flex-1 items-center justify-center">
//       <ActivityIndicator size="large" />
//       <Text className="mt-4">Memuat produk...</Text>
//     </View>
//   );
// }

// function ErrorView({ onRetry }: { onRetry: () => void }) {

//   return (
//     <View className="flex-1 items-center justify-center">
//       <Text className="text-red-500 mb-4">Gagal memuat data produk</Text>
//       <Button onPress={onRetry}>
//         <ButtonText>Coba Lagi</ButtonText>
//       </Button>
//     </View>
//   );
// }

// function EmptyView({ onRefresh }: { onRefresh: () => void }) {
//   return (
//     <View className="flex-1 items-center justify-center">
//       <Text className="mb-4">Tidak ada produk tersedia</Text>
//       <Button onPress={onRefresh}>
//         <ButtonText>Refresh</ButtonText>
//       </Button>
//     </View>
//   );
// }

// app/index.tsx
// app/index.tsx
import React, { useCallback } from 'react';
import { FlatList, View, RefreshControl, TouchableOpacity } from 'react-native';
import { useBreakpointValue } from '@/components/ui/utils/use-break-point-value';
import { Text } from '@/components/ui/text';
import { Button } from '@/components/ui/button';
import { useGetProductsQuery } from '@/src/store/api/productsApi'; // Tetap butuh ini untuk `productsData` di FloatingButton
import type { Product } from '@/types/product';
import { useFocusEffect } from '@react-navigation/native';
import ProductListItem from '@/components/ProductListItem';
import { Spinner } from '@/components/ui/spinner';
import { ShoppingCart, ShoppingBasket } from 'lucide-react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router } from 'expo-router';

// --- Import komponen baru untuk AI Chat ---
import { AIChatFloatingButton } from '@/components/AIChatFloatingButton';

// Pastikan ini sesuai dengan actual response API
type ApiProduct = Product;

function getWidthClass(numColumns: number): string {
  const widthMap = {
    1: 'w-full',
    2: 'w-1/2',
    3: 'w-1/3',
    4: 'w-1/4',
    5: 'w-1/5',
    6: 'w-1/6',
  } as const;

  return widthMap[numColumns as keyof typeof widthMap] || 'w-full';
}

export default function HomeScreen() {
  const numColumns = useBreakpointValue({ default: 2, sm: 3, md: 4 }) as number;
  const widthClass = getWidthClass(numColumns);

  const { data: products, error, isLoading, isFetching, refetch } = useGetProductsQuery();

  // Debug: Log setiap perubahan state query
  console.log('HomeScreen state:', {
    isLoading,
    isFetching,
    error: error ? JSON.stringify(error) : null,
    products: products ? `Received ${products.length} products` : null,
  });

  useFocusEffect(
    useCallback(() => {
      console.log('HomeScreen focused, refetching data...');
      refetch();
    }, [refetch])
  );

  const flattenProduct = (product: ApiProduct) => {
    const isVariant = product.is_varians && product.variants?.length;
    const firstVariant = isVariant ? product.variants![0] : null;

    const thumbnail =
      product.thumbnails?.[0] || product.images?.[0] || 'https://via.placeholder.com/150';

    return {
      id: product.id,
      name: product.name,
      description: product.description,
      images: product.thumbnails?.length ? product.thumbnails : product.images,
      thumbnail,
      price: isVariant ? firstVariant?.price ?? 0 : product.price ?? 0,
      isDiscounted: isVariant ? firstVariant?.is_discounted ?? false : product.is_discounted,
      discountPrice: isVariant
        ? firstVariant?.discount_price ?? null
        : product.discount_price ?? null,
    };
  };

  // const renderHeader = () => (
  //   <SafeAreaView edges={['top']} className="bg-white">
  //     <View className="flex-row items-center justify-between px-4 py-3 border-b border-gray-200">
  //       <Text className="text-xl font-bold text-gray-900">Our Products</Text>
  //       <TouchableOpacity
  //         onPress={() => router.push('/cart')}
  //         className="relative p-2"
  //       >
  //         <ShoppingCart size={24} color="#4B5563" />
  //         {/* You can add a badge here if you have cart items count */}
  //         {/* <View className="absolute -top-1 -right-1 bg-red-500 rounded-full w-5 h-5 items-center justify-center">
  //           <Text className="text-white text-xs">3</Text>
  //         </View> */}
  //       </TouchableOpacity>
  //       <TouchableOpacity
  //         onPress={() => router.push('/order')}
  //         className="relative p-2"
  //       >
  //         <ShoppingBasket size={24} color="#4B5563" />
  //       </TouchableOpacity>
  //     </View>
  //   </SafeAreaView>
  // );

  if (isLoading) {
    return (
      <View className="flex-1">
        {/* {renderHeader()} */}
        <LoadingView />
        <AIChatFloatingButton /> {/* Tetap tampilkan tombol AI bahkan saat loading */}
      </View>
    );
  }

  if (error) {
    return (
      <View className="flex-1">
        {/* {renderHeader()} */}
        <ErrorView onRetry={refetch} />
        <AIChatFloatingButton /> {/* Tetap tampilkan tombol AI */}
      </View>
    );
  }

  if (!products?.length) {
    return (
      <View className="flex-1">
        {/* {renderHeader()} */}
        <EmptyView onRefresh={refetch} />
        <AIChatFloatingButton /> {/* Tetap tampilkan tombol AI */}
      </View>
    );
  }

  return (
    <View className="flex-1">
      {/* {renderHeader()} */}
      <FlatList
        data={products}
        key={`product-list-${numColumns}`}
        keyExtractor={(item) => item.id.toString()}
        numColumns={numColumns}
        contentContainerClassName="gap-2 max-w-[960px] mx-auto w-full p-2"
        columnWrapperClassName="gap-2"
        refreshControl={<RefreshControl refreshing={isFetching} onRefresh={refetch} />}
        renderItem={({ item }) => (
          <View className={`${widthClass} p-1`}>
            <ProductListItem product={flattenProduct(item)} />
          </View>
        )}
        ListFooterComponent={<View className="h-20" />}
      />
      {/* --- Tambahkan tombol AI di sini --- */}
      <AIChatFloatingButton />
    </View>
  );
}

// Komponen pemisah untuk state views

function LoadingView() {
  return (
    <View className="flex-1 items-center justify-center">
      <Spinner size="large" />
      <Text className="mt-4">Memuat produk...</Text>
    </View>
  );
}

function ErrorView({ onRetry }: { onRetry: () => void }) {
  return (
    <View className="flex-1 items-center justify-center">
      <Text className="text-red-500 mb-4">Gagal memuat data produk</Text>
      <Button onPress={onRetry}>Coba Lagi</Button>
    </View>
  );
}

function EmptyView({ onRefresh }: { onRefresh: () => void }) {
  return (
    <View className="flex-1 items-center justify-center">
      <Text className="mb-4">Tidak ada produk tersedia</Text>
      <Button onPress={onRefresh}>Refresh</Button>
    </View>
  );
}