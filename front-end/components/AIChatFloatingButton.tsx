// @/components/AIChatFloatingButton.tsx
import React, { useState, useCallback } from 'react';
import { StyleSheet, ActivityIndicator, TextInput, Platform } from 'react-native';
import {
  Button,
  ButtonIcon,
  ButtonText,
} from '@/components/ui/button';
import {
  Toast,
  useToast,
  ToastTitle,
  ToastDescription,
} from '@/components/ui/toast';
import {
  Icon,
  MessageCircleIcon,
  CloseIcon,
  CheckCircleIcon,
  AlertCircleIcon,
} from '@/components/ui/icon';
import { VStack } from '@/components/ui/vstack';
import { HStack } from '@/components/ui/hstack';
import { Pressable } from '@/components/ui/pressable';
import { Box } from '@/components/ui/box';
import {
  FormControl,
  FormControlLabel,
  FormControlLabelText,
  FormControlError,
  FormControlErrorIcon,
  FormControlErrorText,
} from '@/components/ui/form-control';

// --- Import Modal components from Gluestack UI ---
import {
  Modal,
  ModalBackdrop,
  ModalContent,
  ModalHeader,
  ModalCloseButton,
  ModalBody,
  ModalFooter,
} from '@/components/ui/modal';
import { Heading } from '@/components/ui/heading'; // Assuming you have Heading component

import { useGetProductsQuery, useGetRecommendedProductsMutation } from '@/src/store/api/productsApi';
import type { Product } from '@/types/product';

interface AIChatFloatingButtonProps {
  // Anda bisa menambahkan props jika diperlukan, misalnya untuk styling
}

export function AIChatFloatingButton(props: AIChatFloatingButtonProps) {
  const toast = useToast();
  const [userQuery, setUserQuery] = useState('');
  const [isQueryInvalid, setIsQueryInvalid] = useState(false);
  const [isChatModalVisible, setIsChatModalVisible] = useState(false); // State untuk mengontrol visibilitas modal

  // Ambil semua produk dari RTK Query
  const { data: productsData, isLoading: productsLoading } = useGetProductsQuery();
  const [
    getRecommendedProducts,
    { isLoading: isAiLoading, error: aiError }
  ] = useGetRecommendedProductsMutation();

  const showSuccessToast = useCallback((message: string) => {
    toast.show({
      placement: 'top',
      duration: 30000,
      render: ({ id }) => (
        <Toast
          action="success"
          variant="outline"
          className="p-4 gap-6 border-success-500 w-full shadow-hard-5 max-w-[443px] flex-row justify-between"
        >
          <HStack space="md">
            <Icon as={CheckCircleIcon} className="stroke-success-500 mt-0.5" />
            <VStack space="xs">
              <ToastTitle className="font-semibold text-success-500">Rekomendasi AI!</ToastTitle>
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
            <Icon as={AlertCircleIcon} className="stroke-error-500 mt-0.5" />
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

  const handleSendQuery = async () => {
    if (!userQuery.trim()) {
      setIsQueryInvalid(true);
      return;
    }
    setIsQueryInvalid(false); // Reset invalid state

    setIsChatModalVisible(false); // Tutup modal setelah mengirim

    if (!productsData || productsData.length === 0) {
      showErrorToast('Data produk tidak tersedia untuk rekomendasi.');
      return;
    }
    if (productsLoading) { // Tambahkan pengecekan ini
      showErrorToast('Produk masih dimuat, mohon tunggu sebentar.');
      return;
    }

    try {
      console.log('Sending user query:', userQuery);
      console.log('Sending products count:', productsData.length);

      const result = await getRecommendedProducts({ userQuery, products: productsData }).unwrap();
      console.log('AI Recommendations:', result.recommendations);

      if (result.recommendations && result.recommendations.length > 0) {
        let recommendationMessage = 'Berikut 3 produk terbaik untuk Anda:\n\n';
        result.recommendations.forEach((p, index) => {
          // Menggunakan optional chaining untuk harga, dan fallback jika null
          const priceDisplay = p.price !== null ? p.price?.toLocaleString('id-ID') :
                               p.variants?.[0]?.price !== null ? p.variants?.[0]?.price?.toLocaleString('id-ID') : 'N/A';

          recommendationMessage += `${index + 1}. **${p.name}**\n   Rp ${priceDisplay}\n   Skor: ${p.similarity_score.toFixed(3)}\n\n`;
        });
        showSuccessToast(recommendationMessage);
      } else {
        showErrorToast('Tidak ada rekomendasi yang ditemukan. Coba pertanyaan lain.');
      }
    } catch (err: any) {
      console.error('Failed to get AI recommendations:', err);
      const errorMessage = err?.data?.error || err?.message || 'Gagal mendapatkan rekomendasi AI. Pastikan AI server berjalan.';
      showErrorToast(errorMessage);
    } finally {
      setUserQuery(''); // Clear query after sending
    }
  };

  const handleOpenChatModal = useCallback(() => {
    setUserQuery(''); // Reset query saat membuka modal
    setIsQueryInvalid(false); // Reset invalid state
    setIsChatModalVisible(true);
  }, []);

  const handleCloseChatModal = useCallback(() => {
    setIsChatModalVisible(false);
  }, []);


  return (
    <Box style={styles.floatingButtonContainer}>
      <Button
        size="lg"
        className="rounded-full p-4 shadow-lg bg-blue-600"
        onPress={handleOpenChatModal}
      >
        <ButtonIcon as={MessageCircleIcon} size="xl" className="stroke-white" />
      </Button>

      {/* --- GLUESTACK UI MODAL UNTUK FORM CHAT AI --- */}
      <Modal
        isOpen={isChatModalVisible}
        onClose={handleCloseChatModal}
        avoidKeyboard={Platform.OS === 'ios'} // Gluestack UI's avoidKeyboard prop for iOS
      >
        <ModalBackdrop />
        <ModalContent className="bg-white rounded-lg p-6 w-full max-w-sm">
          <ModalHeader className="mb-4 flex-row justify-between items-center">
            <Heading size="lg">Asisten AI Rekomendasi</Heading>
            <ModalCloseButton onPress={handleCloseChatModal}>
              <Icon as={CloseIcon} size="md" />
            </ModalCloseButton>
          </ModalHeader>
          <ModalBody className="mb-6">
            <FormControl isInvalid={isQueryInvalid} size="md">
              <FormControlLabel>
                <FormControlLabelText className="text-gray-700 mb-2">
                  Apa yang Anda cari?
                </FormControlLabelText>
              </FormControlLabel>
              <TextInput
                className="border border-gray-300 rounded-md p-3 text-lg bg-white"
                placeholder="Contoh: Saya mencari headset gaming murah dengan kualitas suara jernih."
                value={userQuery}
                onChangeText={(text) => {
                  setUserQuery(text);
                  setIsQueryInvalid(false);
                }}
                multiline
                numberOfLines={4}
                style={styles.textInput}
                autoFocus={true} // Otomatis fokus saat modal terbuka
              />
              {isQueryInvalid && (
                <FormControlError className="mt-2">
                  <FormControlErrorIcon as={AlertCircleIcon} />
                  <FormControlErrorText>
                    Input tidak boleh kosong.
                  </FormControlErrorText>
                </FormControlError>
              )}
            </FormControl>
          </ModalBody>
          <ModalFooter className="flex-row justify-end">
            <Button
              variant="outline"
              action="secondary"
              onPress={handleCloseChatModal}
              disabled={isAiLoading}
              className="mr-2"
            >
              <ButtonText>Batal</ButtonText>
            </Button>
            <Button
              onPress={handleSendQuery}
              disabled={isAiLoading || !userQuery.trim()}
            >
              {isAiLoading ? (
                <HStack space="sm">
                  <ActivityIndicator color="#fff" />
                  <ButtonText>Mengirim...</ButtonText>
                </HStack>
              ) : (
                <ButtonText>Dapatkan Rekomendasi</ButtonText>
              )}
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </Box>
  );
}

const styles = StyleSheet.create({
  floatingButtonContainer: {
    position: 'absolute',
    bottom: 20,
    right: 20,
    zIndex: 100,
  },
  textInput: {
    minHeight: 100,
    textAlignVertical: 'top',
  },
});