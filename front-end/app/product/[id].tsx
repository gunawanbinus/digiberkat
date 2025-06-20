import { Stack, useLocalSearchParams } from 'expo-router';
import { useState, useEffect } from 'react';
import { View, ScrollView, TouchableOpacity, ActivityIndicator } from 'react-native';
import { useGetProductByIdQuery } from '@/src/store/api/productsApi';
import { useAddToCartMutation } from '@/src/store/api/cartApi'; // Import the new mutation hook

import { Card } from '@/components/ui/card';
import { Button, ButtonText } from '@/components/ui/button';
import { Image } from '@/components/ui/image';
import { Text } from '@/components/ui/text';
import { VStack } from '@/components/ui/vstack';
import { Box } from '@/components/ui/box';
import { Heading } from '@/components/ui/heading';
import { useToast, Toast, ToastTitle } from '@/components/ui/toast'; // Import Toast components
import { Icon } from '@/components/ui/icon';
import { CheckCircle } from 'lucide-react-native'; // Using CheckCircle for success icon

interface AddToCartPayload {
  product_id: number;
  quantity: number;
  product_variant_id?: number;
}

export default function ProductDetailsScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data: product, isLoading, isError } = useGetProductByIdQuery(Number(id));
  const [addToCart, { isLoading: isAddingToCart }] = useAddToCartMutation(); // Initialize the mutation hook
  const toast = useToast(); // Initialize toast hook

  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [selectedVariant, setSelectedVariant] = useState(
    product?.is_varians && product.variants && product.variants.length > 0
      ? product.variants[0]
      : null
  );
  const [quantity, setQuantity] = useState(1);
  const [maxStock, setMaxStock] = useState(product?.stock || 1);

  useEffect(() => {
    if (product) {
      // Update max stock based on selected variant or product stock
      const stock = selectedVariant?.stock ?? product.stock ?? 1;
      setMaxStock(stock);

      // Adjust quantity if it exceeds the new max stock
      if (quantity > stock) {
        setQuantity(stock > 0 ? stock : 1);
      }
    }
  }, [product, selectedVariant, quantity]); // Added quantity to dependency array

  const handleIncrement = () => {
    if (quantity < maxStock) {
      setQuantity(quantity + 1);
    }
  };

  const handleDecrement = () => {
    if (quantity > 1) {
      setQuantity(quantity - 1);
    }
  };

  const handleAddToCart = async () => {
    if (!product) return;

    const payload: AddToCartPayload = {
      product_id: product.id,
      quantity: quantity,
    };

    if (selectedVariant) {
      payload.product_variant_id = selectedVariant.id;
    }

    try {
      await addToCart(payload).unwrap(); // Call the mutation and unwrap to handle success/error
      toast.show({
        placement: 'top',
        render: ({ id: toastId }) => (
          <Toast
            nativeID={`toast-${toastId}`}
            className="px-5 py-3 gap-4 shadow-soft-1 items-center flex-row bg-green-500" // Green background for success
          >
            <Icon as={CheckCircle} size="xl" className="fill-white stroke-none" />
            <ToastTitle size="sm" className="text-white">
              Produk berhasil ditambahkan ke keranjang!
            </ToastTitle>
          </Toast>
        ),
      });
      // Optionally reset quantity or navigate
      // setQuantity(1);
    } catch (error) {
      console.error('Failed to add to cart:', error);
      toast.show({
        placement: 'top',
        render: ({ id: toastId }) => (
          <Toast
            nativeID={`toast-${toastId}`}
            className="px-5 py-3 gap-4 shadow-soft-1 items-center flex-row bg-red-500" // Red background for error
          >
            <Icon as={CheckCircle} size="xl" className="fill-white stroke-none" /> {/* You might want a different icon for error */}
            <ToastTitle size="sm" className="text-white">
              Gagal menambahkan produk ke keranjang.
            </ToastTitle>
          </Toast>
        ),
      });
    }
  };

  if (isLoading) {
    return (
      <View className="flex-1 justify-center items-center">
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (isError || !product) {
    return (
      <View className="flex-1 justify-center items-center">
        <Text>Produk tidak ditemukan</Text>
      </View>
    );
  }

  const displayPrice = selectedVariant?.price ?? product.price;
  const displayDiscountPrice = selectedVariant?.is_discounted
    ? selectedVariant.discount_price
    : product.is_discounted
    ? product.discount_price
    : null;

  const hasDiscount =
    displayDiscountPrice !== null &&
    displayPrice !== null &&
    displayDiscountPrice < displayPrice;

  const discountPercent = hasDiscount
    ? Math.round(((displayPrice - displayDiscountPrice) / displayPrice) * 100)
    : 0;

  const imagesArray = Array.isArray(product.images)
    ? product.images
    : [product.images || 'https://via.placeholder.com/150'];
  const currentImage = imagesArray[currentImageIndex];

  const nextImage = () => {
    if (currentImageIndex < imagesArray.length - 1) setCurrentImageIndex(currentImageIndex + 1);
  };

  const prevImage = () => {
    if (currentImageIndex > 0) setCurrentImageIndex(currentImageIndex - 1);
  };

  return (
    <ScrollView className="flex-1">
      <Box className="flex-1 items-center p-4">
        <Stack.Screen options={{ title: product.name }} />
        <Card className="p-5 rounded-lg max-w-[960px] w-full">
          <View className="relative mb-6">
            <Image
              source={{ uri: currentImage }}
              className="h-[240px] w-full rounded-md aspect-[4/3]"
              alt={`${product.name} image`}
              resizeMode="contain"
            />
            {imagesArray.length > 1 && (
              <>
                <TouchableOpacity
                  onPress={prevImage}
                  disabled={currentImageIndex === 0}
                  className="absolute left-2 top-1/2 bg-black/50 p-2 rounded-full"
                >
                  <Text className="text-white text-lg">{'<'}</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  onPress={nextImage}
                  disabled={currentImageIndex === imagesArray.length - 1}
                  className="absolute right-2 top-1/2 bg-black/50 p-2 rounded-full"
                >
                  <Text className="text-white text-lg">{'>'}</Text>
                </TouchableOpacity>
              </>
            )}
            {hasDiscount && (
              <View className="absolute top-2 left-2 bg-red-600 px-2 py-1 rounded-md">
                <Text className="text-white text-xs font-bold">{discountPercent}% OFF</Text>
              </View>
            )}
          </View>

          {imagesArray.length > 1 && (
            <View className="flex-row justify-center mb-4">
              {imagesArray.map((_, index) => (
                <View
                  key={index}
                  className={`w-2 h-2 rounded-full mx-1 ${
                    index === currentImageIndex ? 'bg-primary-500' : 'bg-gray-300'
                  }`}
                />
              ))}
            </View>
          )}

          <Text className="text-lg font-bold mb-2 text-typography-800">{product.name}</Text>

          {product.is_varians && product.variants && (
            <View className="mb-4">
              <Text className="text-sm font-medium mb-2">Pilihan Varian:</Text>
              <View className="flex-row flex-wrap">
                {product.variants.map((variant) => (
                  <TouchableOpacity
                    key={variant.id}
                    onPress={() => setSelectedVariant(variant)}
                    className={`px-3 py-2 mr-2 mb-2 rounded-md border ${
                      selectedVariant?.id === variant.id
                        ? 'border-primary-500 bg-primary-100'
                        : 'border-gray-300'
                    }`}
                  >
                    <Text className="text-sm">{variant.name}</Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>
          )}

          <View className="mb-4">
            {hasDiscount ? (
              <>
                <Text className="text-base text-gray-500 line-through">
                  Rp {displayPrice?.toLocaleString('id-ID')}
                </Text>
                <Heading size="lg" className="text-red-600">
                  Rp {displayDiscountPrice?.toLocaleString('id-ID')}
                </Heading>
              </>
            ) : (
              <Heading size="lg">
                Rp {displayPrice ? displayPrice.toLocaleString('id-ID') : 'Harga bervariasi'}
              </Heading>
            )}
          </View>

          <Text className="text-sm text-gray-700 mb-6">{product.description}</Text>

          <Box className="flex-col sm:flex-row items-center mb-4">
            <View className="flex-row items-center border border-gray-300 rounded-md mb-3 sm:mb-0 sm:mr-3">
              <TouchableOpacity
                onPress={handleDecrement}
                disabled={quantity <= 1}
                className="px-3 py-2 bg-gray-100"
              >
                <Text className="text-lg">-</Text>
              </TouchableOpacity>
              <Text className="px-4 py-2 text-center min-w-[40px]">{quantity}</Text>
              <TouchableOpacity
                onPress={handleIncrement}
                disabled={quantity >= maxStock}
                className="px-3 py-2 bg-gray-100"
              >
                <Text className="text-lg">+</Text>
              </TouchableOpacity>
            </View>
            <Text className="text-sm text-gray-600 mb-3 sm:mb-0">
              Stok tersedia: {maxStock}
            </Text>
          </Box>

          <Box className="flex-col sm:flex-row">
            <Button
              className="px-4 py-2 mr-0 mb-3 sm:mr-3 sm:mb-0 sm:flex-1"
              onPress={handleAddToCart}
              disabled={maxStock <= 0 || isAddingToCart} // Disable button while adding to cart
            >
              <ButtonText size="sm">
                {isAddingToCart ? (
                  <ActivityIndicator color="#fff" />
                ) : maxStock <= 0 ? (
                  'Stok Habis'
                ) : (
                  `Add to cart (${quantity})`
                )}
              </ButtonText>
            </Button>
            <Button variant="outline" className="px-4 py-2 border-outline-300 sm:flex-1">
              <ButtonText size="sm" className="text-typography-600">
                Wishlist
              </ButtonText>
            </Button>
          </Box>
        </Card>
      </Box>
    </ScrollView>
  );
}