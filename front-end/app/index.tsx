// app/index.tsx
import { FlatList, View, ActivityIndicator, RefreshControl } from 'react-native';
import { useBreakpointValue } from '@/components/ui/utils/use-break-point-value';
import type { ProductListItemData } from '@/types/product';
import { Text } from '../components/ui/text';
import { Button, ButtonText } from '../components/ui/button';
import { useGetProductsQuery } from '@/src/store/api/productsApi';
import type { Product } from '@/types/product';
import { useCallback } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import ProductListItem from '@/components/ProductListItem';


// Pastikan ini sesuai dengan actual response API
type ApiProduct = Product;

function getWidthClass(numColumns: number): string {
  const widthMap = {
    1: 'w-full',
    2: 'w-1/2',
    3: 'w-1/3', 
    4: 'w-1/4',
    5: 'w-1/5',
    6: 'w-1/6'
  } as const;
  
  return widthMap[numColumns as keyof typeof widthMap] || 'w-full';
}

export default function HomeScreen() {
  const numColumns = useBreakpointValue({ default: 2, sm: 3, md: 4 }) as number;
  const widthClass = getWidthClass(numColumns);
  
  const { 
    data: products, 
    error, 
    isLoading, 
    isFetching, 
    refetch 
  } = useGetProductsQuery();

   // Debug: Log setiap perubahan state query
  console.log('HomeScreen state:', {
    isLoading,
    isFetching,
    error: error ? JSON.stringify(error) : null,
    products: products ? `Received ${products.length} products` : null
  });

  useFocusEffect(
    useCallback(() => {
      console.log('HomeScreen focused, refetching data...');
      refetch();
    }, [refetch])
  );

  const flattenProduct = (product: ApiProduct): ProductListItemData => {
    const isVariant = product.is_varians && product.variants?.length;
    const firstVariant = isVariant ? product.variants![0] : null;

    const thumbnail = product.thumbnails?.[0]
      || product.images?.[0]
      || 'https://via.placeholder.com/150';

    return {
      id: product.id,
      name: product.name,
      description: product.description,
      images: product.thumbnails?.length ? product.thumbnails : product.images,
      thumbnail,
      price: isVariant
        ? firstVariant?.price ?? 0
        : product.price ?? 0,
      isDiscounted: isVariant
        ? firstVariant?.is_discounted ?? false
        : product.is_discounted,
      discountPrice: isVariant
        ? firstVariant?.discount_price ?? null
        : product.discount_price ?? null
    };
};


  if (isLoading) {
    return <LoadingView />;
  }

  if (error) {
    return <ErrorView onRetry={refetch} />;
  }

  if (!products?.length) {
    return <EmptyView onRefresh={refetch} />;
  }

  return (
    <FlatList 
      data={products}
      key={`product-list-${numColumns}`}
      keyExtractor={(item) => item.id.toString()}
      numColumns={numColumns}
      contentContainerClassName="gap-2 max-w-[960px] mx-auto w-full p-2"
      columnWrapperClassName="gap-2"
      refreshControl={
        <RefreshControl
          refreshing={isFetching}
          onRefresh={refetch}
        />
      }
      renderItem={({ item }) => (
        <View className={`${widthClass} p-1`}>
          <ProductListItem product={flattenProduct(item)} />
        </View>
      )}
      ListFooterComponent={<View className="h-20" />}
    />
  );
}

// Komponen pemisah untuk state views
function LoadingView() {
  return (
    <View className="flex-1 items-center justify-center">
      <ActivityIndicator size="large" />
      <Text className="mt-4">Memuat produk...</Text>
    </View>
  );
}

function ErrorView({ onRetry }: { onRetry: () => void }) {

  return (
    <View className="flex-1 items-center justify-center">
      <Text className="text-red-500 mb-4">Gagal memuat data produk</Text>
      <Button onPress={onRetry}>
        <ButtonText>Coba Lagi</ButtonText>
      </Button>
    </View>
  );
}

function EmptyView({ onRefresh }: { onRefresh: () => void }) {
  return (
    <View className="flex-1 items-center justify-center">
      <Text className="mb-4">Tidak ada produk tersedia</Text>
      <Button onPress={onRefresh}>
        <ButtonText>Refresh</ButtonText>
      </Button>
    </View>
  );
}