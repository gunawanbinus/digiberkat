
import React, { useState, useEffect, useCallback } from 'react';
import { ActivityIndicator, ScrollView, RefreshControl } from 'react-native';
import { Box } from '@/components/ui/box';
import { VStack } from '@/components/ui/vstack';
import { HStack } from '@/components/ui/hstack';
import { Text } from '@/components/ui/text';
import { Button, ButtonText } from '@/components/ui/button';
import { Image } from '@/components/ui/image';
import { Pressable } from "@/components/ui/pressable";
import { useToast, Toast, ToastTitle, ToastDescription } from "@/components/ui/toast";
import { Icon, CloseIcon, HelpCircleIcon, CheckCircleIcon } from "@/components/ui/icon";
import { Minus, Plus, Trash2 } from 'lucide-react-native';

import {
  Select, SelectTrigger, SelectInput, SelectIcon, SelectPortal,
  SelectBackdrop, SelectContent, SelectDragIndicator, SelectDragIndicatorWrapper, SelectItem
} from "@/components/ui/select";
import { ChevronDownIcon } from "@/components/ui/icon";

import {
  useGetCartItemsQuery,
  useRemoveCartItemMutation,
  useUpdateCartItemQuantityMutation,
  useUpdateCartItemVariantMutation,
} from '@/src/store/api/cartApi';
import { useCreateOrderMutation } from '@/src/store/api/orderApi';
import { router } from 'expo-router';

export default function CartScreen() {
  const { data: cartData, isLoading, isError, refetch } = useGetCartItemsQuery();
  const [updateQuantity, { isLoading: updatingQuantity }] = useUpdateCartItemQuantityMutation();
  const [removeItem, { isLoading: removingItem }] = useRemoveCartItemMutation();
  const [createOrder, { isLoading: creatingOrder }] = useCreateOrderMutation();
  const [updateVariant, { isLoading: updatingVariant }] = useUpdateCartItemVariantMutation();
  const toast = useToast();

  const [loadingItemId, setLoadingItemId] = useState<number | null>(null);
  const [localQuantities, setLocalQuantities] = useState<Record<number, number>>({});
  const [updateTimeout, setUpdateTimeout] = useState<ReturnType<typeof setTimeout> | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    if (cartData?.data) {
      const initialQuantities = cartData.data.reduce((acc, item) => {
        acc[item.id] = item.quantity;
        return acc;
      }, {} as Record<number, number>);
      setLocalQuantities(initialQuantities);
    }
  }, [cartData]);

  const cartItems = cartData?.data || [];
  const isEmpty = cartItems.length === 0;

  // --- Toast Functions ---
  const showSuccessToast = useCallback((message: string) => {
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
              <ToastTitle className="font-semibold text-success-500">Success!</ToastTitle>
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

  const showErrorToast = useCallback((message: string) => {
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

  const handleRemoveItem = useCallback(async (itemId: number) => {
    setLoadingItemId(itemId);
    try {
      await removeItem(itemId).unwrap();
      showSuccessToast('Produk berhasil dihapus dari keranjang.');
    } catch (error: any) {
      showErrorToast(error?.data?.message || 'Gagal menghapus produk.');
    } finally {
      setLoadingItemId(null);
    }
  }, [removeItem, showSuccessToast, showErrorToast]);


  const showDeleteConfirmation = useCallback((itemId: number) => {
    const toastId = Math.random().toString();

    toast.show({
      id: toastId,
      placement: 'top',
      duration: null,
      render: ({ id }) => (
        <Toast
          action="error"
          variant="outline"
          className="p-4 gap-6 border-error-500 w-full shadow-hard-5 max-w-[443px] flex-row justify-between"
        >
          <HStack space="md">
            <Icon as={HelpCircleIcon} className="stroke-error-500 mt-0.5" />
            <VStack space="xs">
              <ToastTitle className="font-semibold text-error-500">Konfirmasi</ToastTitle>
              <ToastDescription size="sm">
                Yakin ingin hapus produk dari keranjang?
              </ToastDescription>
            </VStack>
          </HStack>
          <HStack className="min-[450px]:gap-3 gap-1">
            <Button
              variant="link"
              size="sm"
              className="px-3.5 self-center"
              onPress={async () => {
                await handleRemoveItem(itemId);
                toast.close(id);
              }}
            >
              <ButtonText>Yakin</ButtonText>
            </Button>
            <Button
              variant="link"
              size="sm"
              className="px-3.5 self-center"
              onPress={() => {
                setLocalQuantities(prev => ({
                  ...prev,
                  [itemId]: prev[itemId] > 0 ? prev[itemId] : 1
                }));
                toast.close(id);
              }}
            >
              <ButtonText>Batal</ButtonText>
            </Button>
          </HStack>
        </Toast>
      ),
    });
  }, [toast, handleRemoveItem]);


  const debouncedUpdateQuantity = useCallback(
    async (itemId: number, newQuantity: number) => {
      if (updateTimeout) clearTimeout(updateTimeout);

      const timeout = setTimeout(async () => {
        const item = cartItems.find(item => item.id === itemId);
        if (!item) {
          showErrorToast('Produk tidak ditemukan di keranjang.');
          return;
        }

        if (newQuantity < 1 || newQuantity > item.stock) {
          showErrorToast(`Kuantitas tidak valid atau melebihi stok (${item.stock}).`);
          setLocalQuantities(prev => ({
            ...prev,
            [itemId]: item.quantity,
          }));
          return;
        }

        setLoadingItemId(itemId);
        try {
          await updateQuantity({ id: itemId, quantity: newQuantity }).unwrap();
          showSuccessToast('Kuantitas berhasil diupdate');
        } catch (error: any) {
          setLocalQuantities(prev => ({
            ...prev,
            [itemId]: item.quantity,
          }));
          showErrorToast(error?.data?.message || 'Gagal mengupdate kuantitas.');
        } finally {
          setLoadingItemId(null);
        }
      }, 700);

      setUpdateTimeout(timeout);
    },
    [cartItems, updateQuantity, updateTimeout, showErrorToast, showSuccessToast]
  );

  const handleQuantityChange = useCallback((itemId: number, change: number) => {
    setLocalQuantities(prev => {
      const currentQuantity = prev[itemId] || 0;
      let newQuantity = currentQuantity + change;

      if (newQuantity < 0) newQuantity = 0;

      const item = cartItems.find(i => i.id === itemId);

      if (!item) {
        showErrorToast('Produk tidak ditemukan.');
        return prev;
      }

      if (newQuantity === 0) {
        showDeleteConfirmation(itemId);
        return { ...prev, [itemId]: 0 };
      }

      if (newQuantity > item.stock) {
        showErrorToast(`Stok untuk produk ini hanya ${item.stock}.`);
        return { ...prev, [itemId]: item.stock };
      }

      const updatedQuantities = { ...prev, [itemId]: newQuantity };
      debouncedUpdateQuantity(itemId, newQuantity);
      return updatedQuantities;
    });
  }, [cartItems, debouncedUpdateQuantity, showDeleteConfirmation, showErrorToast]);

  const handleVariantChange = useCallback(async (itemId: number, newVariantId: string) => {
    const item = cartItems.find(i => i.id === itemId);
    if (!item) {
      showErrorToast('Produk tidak ditemukan di keranjang.');
      return;
    }

    if (!item.variants || item.variants.length === 0) {
        showErrorToast('Varian tidak tersedia untuk produk ini.');
        return;
    }

    const currentVariantId = item.product_variant_id?.toString();
    if (currentVariantId === newVariantId) {
      return;
    }

    setLoadingItemId(itemId);
    try {
      const response = await updateVariant({ id: itemId, variant_id: parseInt(newVariantId) }).unwrap();
      showSuccessToast(response.message || 'Varian berhasil diupdate!');
    } catch (error: any) {
      showErrorToast(error?.data?.message || 'Gagal mengganti varian.');
    } finally {
      setLoadingItemId(null);
    }
  }, [cartItems, updateVariant, showSuccessToast, showErrorToast]);

  const handleCreateOrder = useCallback(async () => {
    if (isEmpty) {
      showErrorToast('Keranjang Anda kosong. Tambahkan produk sebelum memesan.');
      return;
    }

    try {
      const response = await createOrder().unwrap();
      showSuccessToast(response.message || 'Order berhasil dibuat!');
      router.push('/order');
    } catch (error: any) {
      const errorMessage = error?.data?.message || error?.message || 'Gagal membuat order. Silakan coba lagi.';
      showErrorToast(errorMessage);
    }
  }, [createOrder, isEmpty, showErrorToast, showSuccessToast]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await refetch();
    setRefreshing(false);
  }, [refetch]);

  useEffect(() => {
    return () => {
      if (updateTimeout) clearTimeout(updateTimeout);
    };
  }, [updateTimeout]);

  if (isLoading) {
    return (
      <Box style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator size="large" color="#0000ff" />
        <Text style={{ marginTop: 8 }}>Memuat keranjang...</Text>
      </Box>
    );
  }

  if (isError) {
    return (
      <Box style={{ flex: 1, justifyContent: 'center', alignItems: 'center', padding: 16 }}>
        <Text style={{ fontSize: 16, color: '#999', textAlign: 'center', marginBottom: 16 }}>
          Gagal memuat keranjang. Silakan coba lagi.
        </Text>
        <Button onPress={onRefresh}>
          <ButtonText>Refresh</ButtonText>
        </Button>
      </Box>
    );
  }

  return (
    <Box style={{ flex: 1, backgroundColor: '#F7F7F7' }}>
      <ScrollView
        contentContainerStyle={{ flexGrow: 1, padding: 16 }}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >
        <VStack space="md" style={{ flex: 1 }}>
          {!isEmpty && (
            <HStack style={{ justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
              <Text style={{ fontSize: 18, fontWeight: 'bold' }}>Keranjang Belanja</Text>
              <Text style={{ color: '#999' }}>{cartItems.length} item</Text>
            </HStack>
          )}

          {isEmpty ? (
            <Box style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
              <Text style={{ fontSize: 16, color: '#999', marginBottom: 8 }}>Keranjang kamu masih kosong</Text>
              <Button variant="outline" onPress={() => router.push("/")}>
                <ButtonText>Belanja Sekarang</ButtonText>
              </Button>
            </Box>
          ) : (
            <>
              <VStack space="sm" style={{ flex: 1 }}>
                {cartItems.map((item) => {
                  const isCurrentLoading = loadingItemId === item.id;
                  const currentQuantity = localQuantities[item.id] ?? item.quantity;

                  // Cari nama varian yang sedang dipilih
                  const selectedVariant = item.variants?.find(
                    (v) => v.id === item.product_variant_id
                  );
                  // Gunakan nama varian jika ditemukan, jika tidak, tampilkan pesan default
                  const displayedVariantName = selectedVariant ? selectedVariant.name : 'Varian tidak dikenal';

                  return (
                    <Box
                      key={item.id}
                      style={{
                        backgroundColor: '#fff',
                        borderRadius: 12,
                        padding: 16,
                        shadowColor: '#000',
                        shadowOffset: { width: 0, height: 1 },
                        shadowOpacity: 0.1,
                        shadowRadius: 4,
                        elevation: 2,
                        marginBottom: 8,
                      }}
                    >
                      <HStack space="md" style={{ alignItems: 'center' }}>
                        {item.thumbnails?.[0] && (
                          <Image
                            source={{ uri: item.thumbnails[0] }}
                            alt={item.name}
                            style={{ width: 80, height: 80, borderRadius: 8 }}
                            resizeMode="cover"
                          />
                        )}

                        <VStack space="sm" style={{ flex: 1 }}>
                          <Text style={{ fontWeight: '600' }} numberOfLines={2}>
                            {item.name}
                          </Text>

                          {/* Render dropdown hanya jika item.variants ada dan tidak kosong */}
                          {item.variants && item.variants.length > 0 && (
                            <VStack space="xs" style={{ width: '100%', maxWidth: 200, marginTop: 4 }}>
                              {/* <Text size="sm" style={{ color: '#64748b' }}>Varian:</Text> */}
                              <Select
                                selectedValue={item.product_variant_id?.toString()}
                                onValueChange={(value) => handleVariantChange(item.id, value)}
                                isDisabled={isCurrentLoading || updatingVariant}
                              >
                                <SelectTrigger variant="outline" size="sm" style={{ opacity: (isCurrentLoading || updatingVariant) ? 0.6 : 1 }}>
                                  {/* Tampilkan nama varian yang sedang dipilih di SelectInput */}
                                  <SelectInput
                                    placeholder="Pilih Varian"
                                    value={displayedVariantName}
                                    style={{ textAlign: 'center' }} // ✅ Rata tengah di sini
                                  />
                                  {isCurrentLoading && updatingVariant ? (
                                    <ActivityIndicator size="small" color="#000" style={{ marginRight: 8 }} />
                                  ) : (
                                    <SelectIcon className="mr-3" as={ChevronDownIcon} />
                                  )}
                                </SelectTrigger>
                                <SelectPortal>
                                  <SelectBackdrop />
                                  <SelectContent
                                    style={{ paddingVertical: 8, paddingHorizontal: 4 }}
                                  >
                                    <SelectDragIndicatorWrapper>
                                      <SelectDragIndicator />
                                    </SelectDragIndicatorWrapper>
                                    {item.variants.map((variant) => (
                                      <SelectItem
                                        key={variant.id}
                                        label={variant.name}
                                        value={variant.id.toString()}
                                      />
                                    ))}
                                  </SelectContent>
                                </SelectPortal>
                              </Select>
                            </VStack>
                          )}

                          {item.price_per_item && item.price_per_item < item.price ? (
                            <HStack space="sm" style={{ alignItems: 'center', marginTop: 4 }}>
                              <Text style={{ color: '#FF6347', fontWeight: 'bold' }}>
                                Rp {item.price_per_item?.toLocaleString('id-ID')}
                              </Text>
                              <Text style={{ color: '#999', fontWeight: 'normal', textDecorationLine: 'line-through', fontSize: 13 }}>
                                Rp {item.price?.toLocaleString('id-ID')}
                              </Text>
                            </HStack>
                          ) : (
                            <Text style={{ color: '#007bff', fontWeight: 'bold', marginTop: 4 }}>
                              Rp {item.price?.toLocaleString('id-ID')}
                            </Text>
                          )}

                          <HStack style={{ justifyContent: 'space-between', alignItems: 'center', marginTop: 8 }}>
                            <HStack
                              style={{
                                alignItems: 'center',
                                borderWidth: 1,
                                borderColor: '#e2e8f0',
                                borderRadius: 8,
                                paddingHorizontal: 8,
                              }}
                            >
                              <Button
                                size="xs"
                                variant="link"
                                onPress={() => handleQuantityChange(item.id, -1)}
                                disabled={isCurrentLoading || currentQuantity <= 1 || updatingVariant}
                                style={{
                                  padding: 4,
                                  opacity: (isCurrentLoading || currentQuantity <= 1 || updatingVariant) ? 0.5 : 1,
                                }}
                              >
                                <Minus width={16} height={16} />
                              </Button>
                              <Text style={{ minWidth: 24, textAlign: 'center' }}>
                                {(isCurrentLoading && (updatingQuantity || updatingVariant)) || (isCurrentLoading && removingItem) ? (
                                  <ActivityIndicator size="small" />
                                ) : (
                                  currentQuantity
                                )}
                              </Text>
                              <Button
                                size="xs"
                                variant="link"
                                onPress={() => handleQuantityChange(item.id, 1)}
                                disabled={currentQuantity >= item.stock || isCurrentLoading || updatingVariant}
                                style={{
                                  padding: 4,
                                  opacity: (currentQuantity >= item.stock || isCurrentLoading || updatingVariant) ? 0.5 : 1,
                                }}
                              >
                                <Plus width={16} height={16} />
                              </Button>
                            </HStack>

                            {/* Tombol sampah tetap di kanan bawah */}
                            <Button
                              size="xs"
                              variant="link"
                              onPress={() => showDeleteConfirmation(item.id)}
                              style={{
                                padding: 4,
                                opacity: isCurrentLoading || updatingVariant ? 0.5 : 1,
                              }}
                              disabled={isCurrentLoading || updatingVariant}
                            >
                              <Trash2 width={20} height={20} stroke="#ef4444" />
                            </Button>

                          </HStack>
                        </VStack>
                      </HStack>
                    </Box>
                  );
                })}
              </VStack>

              <Box
                style={{
                  backgroundColor: '#fff',
                  borderRadius: 12,
                  padding: 16,
                  shadowColor: '#000',
                  shadowOffset: { width: 0, height: -1 },
                  shadowOpacity: 0.1,
                  shadowRadius: 4,
                  elevation: 2,
                  marginTop: 16,
                }}
              >
                <HStack style={{ justifyContent: 'space-between', marginBottom: 12 }}>
                  <Text style={{ fontSize: 16 }}>Total Harga:</Text>
                  <Text style={{ fontWeight: 'bold', fontSize: 16 }}>
                    Rp {(cartData?.total_cart_price ?? 0).toLocaleString('id-ID')}
                  </Text>
                </HStack>
                <Button
                  onPress={handleCreateOrder}
                  disabled={creatingOrder || isEmpty}
                  style={{
                    backgroundColor: '#007bff',
                    borderRadius: 8,
                    height: 48,
                    opacity: (creatingOrder || isEmpty) ? 0.7 : 1,
                  }}
                >
                  {creatingOrder ? (
                    <ActivityIndicator color="#fff" />
                  ) : (
                    <ButtonText style={{ color: '#fff', fontWeight: 'bold', fontSize: 16 }}>
                      Pesan Sekarang
                    </ButtonText>
                  )}
                </Button>
              </Box>
            </>
          )}
        </VStack>
      </ScrollView>
    </Box>
  );
}