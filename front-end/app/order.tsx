import React from 'react';
import { View, Text, TouchableOpacity, ScrollView, ActivityIndicator } from 'react-native';
import { Image } from '@/components/ui/image';
import { useToast, Toast, ToastTitle, ToastDescription } from "@/components/ui/toast";
import { Icon, CloseIcon, CheckCircleIcon, HelpCircleIcon } from "@/components/ui/icon";
import { HStack } from "@/components/ui/hstack";
import { VStack } from "@/components/ui/vstack";
import { Pressable } from "@/components/ui/pressable";
import { Center } from '@/components/ui/center'; // Import Center for aligning QR Code

// Import QRCode library
import QRCode from 'react-native-qrcode-svg';

import { useGetMyOrdersQuery, useCancelOrderMutation } from '@/src/store/api/orderApi';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';

interface OrderItem {
  order: {
    id: number;
    created_at: string;
    status: string;
    total_price: number;
  };
  sample_item: {
    thumbnail: string;
    product_name: string;
    quantity: number;
  };
}

const Order = () => {
  const { data: ordersData, isLoading, isError } = useGetMyOrdersQuery();
  const [cancelOrder, { isLoading: isCancelling }] = useCancelOrderMutation();
  const toast = useToast();

  const [cancellingOrderId, setCancellingOrderId] = React.useState<number | null>(null);

  // --- Toast Functions ---
  const showSuccessToast = React.useCallback((message: string) => {
    toast.show({
      placement: 'top',
      duration: 3000,
      render: ({ id }) => (
        <Toast
          action="success"
          variant="outline"
          className="p-4 gap-6 border-success-500 w-full shadow-hard-5 max-w-[443px] flex-row justify-between"
        >
          <HStack space="md">
            <Icon as={CheckCircleIcon} className="stroke-success-500 mt-0.5" />
            <VStack space="xs">
              <ToastTitle className="font-semibold text-success-500">Sukses!</ToastTitle>
              <ToastDescription size="sm">{message}</ToastDescription>
            </VStack>
          </HStack>
          <Pressable onPress={() => toast.close(id)}>
            <Icon as={CloseIcon} />
          </Pressable>
        </Toast>
      ),
    });
  }, [toast]);

  const showErrorToast = React.useCallback((message: string) => {
    toast.show({
      placement: 'top',
      duration: 3000,
      render: ({ id }) => (
        <Toast
          action="error"
          variant="outline"
          className="p-4 gap-6 border-error-500 w-full shadow-hard-5 max-w-[443px] flex-row justify-between"
        >
          <HStack space="md">
            <Icon as={HelpCircleIcon} className="stroke-error-500 mt-0.5" />
            <VStack space="xs">
              <ToastTitle className="font-semibold text-error-500">Error!</ToastTitle>
              <ToastDescription size="sm">{message}</ToastDescription>
            </VStack>
          </HStack>
          <Pressable onPress={() => toast.close(id)}>
            <Icon as={CloseIcon} />
          </Pressable>
        </Toast>
      ),
    });
  }, [toast]);

  // --- Handle Cancel Order ---
  const handleCancelOrder = React.useCallback(async (orderId: number) => {
    setCancellingOrderId(orderId);
    try {
      const result = await cancelOrder(orderId).unwrap();
      showSuccessToast(result.message || 'Pesanan berhasil dibatalkan!');
    } catch (error: any) {
      const errorMessage = error?.data?.message || error?.message || 'Gagal membatalkan pesanan. Silakan coba lagi.';
      showErrorToast(errorMessage);
    } finally {
      setCancellingOrderId(null);
    }
  }, [cancelOrder, showSuccessToast, showErrorToast]);

  // --- Handle Show QR Code Toast ---
  const showQrCodeToast = React.useCallback((orderId: number) => {
    toast.show({
      placement: 'top', // Posisikan di atas layar (gunakan 'top', 'bottom', 'top-right', atau 'bottom-right' sesuai kebutuhan)
      duration: null, // Biarkan persistent sampai ditutup manual
      render: ({ id }) => (
        <Toast
          action="info"
          variant="outline"
          className="p-6 gap-6 border-blue-500 w-full shadow-hard-5 max-w-[300px] flex-col justify-center items-center"
        >
          <ToastTitle className="font-bold text-blue-600 mb-4">QR Code Pesanan #{orderId}</ToastTitle>
          <Center style={{ marginBottom: 20 }}>
            <QRCode
              value={String(orderId)} // Data yang akan dienkode (ID pesanan)
              size={220} // Ukuran QR Code
              color="black"
              backgroundColor="white"
            />
          </Center>
          <ToastDescription size="sm" className="text-center">
            Tunjukkan QR code kepada kasir, agar kasir dapat mengetahui pesanan yang akan Anda bayar.
          </ToastDescription>
          <Pressable onPress={() => toast.close(id)} className="absolute top-4 right-4 p-2">
            <Icon as={CloseIcon} size="md" className="stroke-gray-600" />
          </Pressable>
        </Toast>
      ),
    });
  }, [toast]);


  if (isLoading) {
    return (
      <View className="flex-1 justify-center items-center p-4">
        <ActivityIndicator size="large" />
        <Text className="text-center mt-2">Memuat riwayat pesanan...</Text>
      </View>
    );
  }

  if (isError) {
    return (
      <View className="flex-1 justify-center items-center p-4">
        <Text className="text-center text-red-500">Gagal memuat riwayat pesanan</Text>
      </View>
    );
  }

  if (!ordersData || !ordersData.data || ordersData.data.length === 0) {
    return (
      <View className="flex-1 justify-center items-center p-4">
        <Text className="text-center">Belum ada riwayat pesanan</Text>
      </View>
    );
  }

  const formatDate = (dateString: string) => {
    const date = parseISO(dateString);
    return format(date, 'dd MMM - HH:mm', { locale: id }) + ' WIB';
  };

  const getStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
      case 'selesai':
        return 'bg-green-100 text-green-800';
      case 'dikirim':
        return 'bg-blue-100 text-blue-800';
      case 'pending':
        return 'bg-yellow-100 text-yellow-800';
      case 'dibatalkan':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <ScrollView className="flex-1 bg-white">
      <View className="p-4 border-b border-gray-200 sticky top-0 bg-white z-10">
        <Text className="text-xl font-bold">Riwayat Pesanan</Text>
        <View className="flex-row space-x-4 mt-2 overflow-x-auto">
          <TouchableOpacity className="px-3 py-1 bg-blue-600 rounded-full">
            <Text className="text-white text-sm whitespace-nowrap">
              Semua Status
            </Text>
          </TouchableOpacity>
          <TouchableOpacity className="px-3 py-1 border border-gray-300 rounded-full">
            <Text className="text-gray-800 text-sm whitespace-nowrap">
              Semua Jenis Transaksi
            </Text>
          </TouchableOpacity>
          <TouchableOpacity className="px-3 py-1 border border-gray-300 rounded-full">
            <Text className="text-gray-800 text-sm whitespace-nowrap">
              Semua Tahun
            </Text>
          </TouchableOpacity>
        </View>
      </View>

      <View className="divide-y divide-gray-200">
        {ordersData.data.map((orderItem: OrderItem) => {
          const isCurrentOrderCancelling = isCancelling && cancellingOrderId === orderItem.order.id;
          const isCancellable = orderItem.order.status.toLowerCase() === 'pending';
          const isQRShowable = orderItem.order.status.toLowerCase() === 'pending' || orderItem.order.status.toLowerCase() === 'selesai'; // QR bisa ditunjukkan untuk pending atau selesai

          return (
            <View key={orderItem.order.id} className="p-4">
              <Text className="text-sm text-gray-500 mb-2">
                Dibuat {formatDate(orderItem.order.created_at)}
              </Text>
              <View className="flex-row items-center text-sm text-gray-500 mb-3">
                <Text>Datang ke toko untuk membayar dan mengambil pesanan</Text>
                <Text className="mx-1">•</Text>
                <Text>Pesanan</Text>
              </View>

              <View className="border border-gray-200 rounded-lg p-3 mb-3">
                <View className="flex-row justify-between items-center mb-2">
                  <Text className={`px-2 py-1 rounded text-xs font-medium ${getStatusColor(orderItem.order.status)}`}>
                    {orderItem.order.status}
                  </Text>
                  <Text className="text-xs text-gray-500">ID: {orderItem.order.id}</Text>
                </View>

                <View className="flex-row items-center mt-2">
                  <View className="w-16 h-16 relative rounded-md overflow-hidden mr-3">
                    <Image
                      source={{ uri: orderItem.sample_item.thumbnail }}
                      alt={orderItem.sample_item.product_name}
                      className="w-full h-full object-cover"
                      resizeMode="cover"
                    />
                  </View>
                  <View className="flex-1">
                    <Text className="font-medium line-clamp-1">{orderItem.sample_item.product_name}</Text>
                    <Text className="text-sm text-gray-500">
                      {orderItem.sample_item.quantity} Produk
                    </Text>
                  </View>
                </View>

                <View className="mt-3 pt-3 border-t border-gray-200 flex-row justify-between items-center">
                  <View>
                    <Text className="text-xs text-gray-500">Total Pembelian</Text>
                    <Text className="font-bold">
                      {new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0,
                      }).format(orderItem.order.total_price)}
                    </Text>
                  </View>
                  <View className="flex-row space-x-2">
                    {/* Tombol Tunjukan QR */}
                    {isQRShowable && ( // Hanya tampilkan jika status memungkinkan
                      <TouchableOpacity
                        className="px-3 py-1 border border-gray-300 rounded-full"
                        onPress={() => showQrCodeToast(orderItem.order.id)}
                      >
                        <Text className="text-gray-800 text-sm">
                          Tunjukan QR
                        </Text>
                      </TouchableOpacity>
                    )}

                    {/* Tombol Batalkan Pesanan */}
                    {isCancellable && (
                      <TouchableOpacity
                        className={`px-3 py-1 ${isCurrentOrderCancelling ? 'bg-gray-400' : 'bg-red-600'} rounded-full`}
                        onPress={() => handleCancelOrder(orderItem.order.id)}
                        disabled={isCurrentOrderCancelling}
                      >
                        {isCurrentOrderCancelling ? (
                          <ActivityIndicator size="small" color="#fff" />
                        ) : (
                          <Text className="text-white text-sm">
                            Batalkan Pesanan
                          </Text>
                        )}
                      </TouchableOpacity>
                    )}
                  </View>
                </View>
              </View>
            </View>
          );
        })}
      </View>
    </ScrollView>
  );
};

export default Order;