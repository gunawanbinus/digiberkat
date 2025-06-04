// import { Stack, useLocalSearchParams } from 'expo-router';
// import { useState, useEffect } from 'react';
// import { View, ScrollView, TouchableOpacity, ActivityIndicator } from 'react-native';
// import { useGetProductByIdQuery } from '@/src/store/api/productsApi';

// import { Card } from '@/components/ui/card';
// import { Button, ButtonText } from '@/components/ui/button';
// import { Image } from '@/components/ui/image';
// import { Text } from '@/components/ui/text';
// import { VStack } from '@/components/ui/vstack';
// import { Box } from '@/components/ui/box';
// import { Heading } from '@/components/ui/heading';

// export default function ProductDetailsScreen() {
//   const { id } = useLocalSearchParams<{ id: string }>();
//   const { data: product, isLoading, isError } = useGetProductByIdQuery(Number(id));

//   const [currentImageIndex, setCurrentImageIndex] = useState(0);
//   const [selectedVariant, setSelectedVariant] = useState(
//     product?.is_varians && product.variants && product.variants.length > 0 
//       ? product.variants[0] 
//       : null
//   );
//   const [quantity, setQuantity] = useState(1);
//   const [maxStock, setMaxStock] = useState(product?.stock || 1);

//   useEffect(() => {
//     if (product) {
//       // Update max stock based on selected variant or product stock
//       const stock = selectedVariant?.stock || product.stock;
//       setMaxStock(stock);
      
//       // Adjust quantity if it exceeds the new max stock
//       if (quantity > stock) {
//         setQuantity(stock > 0 ? stock : 1);
//       }
//     }
//   }, [product, selectedVariant]);

//   const handleIncrement = () => {
//     if (quantity < maxStock) {
//       setQuantity(quantity + 1);
//     }
//   };

//   const handleDecrement = () => {
//     if (quantity > 1) {
//       setQuantity(quantity - 1);
//     }
//   };

//   const handleAddToCart = () => {
//     // Here you would typically dispatch an action to add to cart
//     console.log(`Added ${quantity} items to cart`);
//     // Reset quantity after adding to cart (optional)
//     // setQuantity(1);
//   };

//   if (isLoading) {
//     return (
//       <View className="flex-1 justify-center items-center">
//         <ActivityIndicator size="large" />
//       </View>
//     );
//   }

//   if (isError || !product) {
//     return (
//       <View className="flex-1 justify-center items-center">
//         <Text>Produk tidak ditemukan</Text>
//       </View>
//     );
//   }

//   const displayPrice = selectedVariant?.price ?? product.price;
//   const displayDiscountPrice = selectedVariant?.is_discounted
//     ? selectedVariant.discount_price
//     : product.is_discounted
//     ? product.discount_price
//     : null;

//   const hasDiscount =
//     displayDiscountPrice !== null &&
//     displayPrice !== null &&
//     displayDiscountPrice < displayPrice;

//   const discountPercent = hasDiscount
//     ? Math.round(((displayPrice - displayDiscountPrice) / displayPrice) * 100)
//     : 0;

//   const imagesArray = Array.isArray(product.images) 
//     ? product.images 
//     : [product.images || 'https://via.placeholder.com/150'];
//   const currentImage = imagesArray[currentImageIndex];

//   const nextImage = () => {
//     if (currentImageIndex < imagesArray.length - 1) setCurrentImageIndex(currentImageIndex + 1);
//   };

//   const prevImage = () => {
//     if (currentImageIndex > 0) setCurrentImageIndex(currentImageIndex - 1);
//   };

//   return (
//     <ScrollView className="flex-1">
//       <Box className="flex-1 items-center p-4">
//         <Stack.Screen options={{ title: product.name }} />
//         <Card className="p-5 rounded-lg max-w-[960px] w-full">
//           {/* ... (bagian sebelumnya tetap sama) ... */}

//           <Box className="flex-col sm:flex-row items-center mb-4">
//             <View className="flex-row items-center border border-gray-300 rounded-md mb-3 sm:mb-0 sm:mr-3">
//               <TouchableOpacity
//                 onPress={handleDecrement}
//                 disabled={quantity <= 1}
//                 className="px-3 py-2 bg-gray-100"
//               >
//                 <Text className="text-lg">-</Text>
//               </TouchableOpacity>
//               <Text className="px-4 py-2 text-center min-w-[40px]">{quantity}</Text>
//               <TouchableOpacity
//                 onPress={handleIncrement}
//                 disabled={quantity >= maxStock}
//                 className="px-3 py-2 bg-gray-100"
//               >
//                 <Text className="text-lg">+</Text>
//               </TouchableOpacity>
//             </View>
//             <Text className="text-sm text-gray-600 mb-3 sm:mb-0">
//               Stok tersedia: {maxStock}
//             </Text>
//           </Box>

//           <Box className="flex-col sm:flex-row">
//             <Button 
//               className="px-4 py-2 mr-0 mb-3 sm:mr-3 sm:mb-0 sm:flex-1"
//               onPress={handleAddToCart}
//               disabled={maxStock <= 0}
//             >
//               <ButtonText size="sm">
//                 {maxStock <= 0 ? 'Stok Habis' : `Add to cart (${quantity})`}
//               </ButtonText>
//             </Button>
//             <Button variant="outline" className="px-4 py-2 border-outline-300 sm:flex-1">
//               <ButtonText size="sm" className="text-typography-600">
//                 Wishlist
//               </ButtonText>
//             </Button>
//           </Box>
//         </Card>
//       </Box>
//     </ScrollView>
//   );
// }