// components/HeaderActions.tsx
import React, { useState } from 'react';
import { TouchableOpacity } from 'react-native';
import { useSelector, useDispatch } from 'react-redux'; // Import hooks Redux
import { router } from 'expo-router';

// Lucide Icons
import { ShoppingCart, ShoppingBasket, LogIn, LogOut, Menu, Home } from 'lucide-react-native';

// Gluestack UI Components
import { HStack } from '@/components/ui/hstack';
import { Icon } from "@/components/ui/icon";
import { Button, ButtonText } from "@/components/ui/button"; // Untuk AlertDialog Buttons
import { Heading } from "@/components/ui/heading";
import { Text } from "@/components/ui/text";

// Actionsheet Components
import {
  Actionsheet,
  ActionsheetContent,
  ActionsheetItem,
  ActionsheetItemText,
  ActionsheetDragIndicator,
  ActionsheetDragIndicatorWrapper,
  ActionsheetBackdrop,
  ActionsheetIcon
} from "@/components/ui/actionsheet";

// AlertDialog Components
import {
  AlertDialog,
  AlertDialogBackdrop,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogCloseButton,
  AlertDialogFooter,
  AlertDialogBody,
} from "@/components/ui/alert-dialog";

// Redux Auth Slice
import { logout, selectIsAuthenticated } from '@/src/store/api/authSlice';

export function HeaderActions() {
  const [showActionsheet, setShowActionsheet] = useState(false);
  const handleCloseActionsheet = () => setShowActionsheet(false);

  const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);
  const handleCloseLogoutConfirm = () => setShowLogoutConfirm(false);

  // Menggunakan useSelector dan useDispatch di dalam komponen ini
  const isAuthenticated = useSelector(selectIsAuthenticated);
  const dispatch = useDispatch();

  const handleLogin = () => {
    router.push('/login');
    handleCloseActionsheet();
  };

  const handleLogoutPress = () => {
    setShowLogoutConfirm(true); // Tampilkan modal konfirmasi
    handleCloseActionsheet(); // Tutup actionsheet setelah menekan logout
  };

  const confirmLogout = () => {
    dispatch(logout());
    console.log("User logged out");
    router.push('/');
    handleCloseLogoutConfirm(); // Tutup modal setelah logout
  };

  return (
    <HStack space="md" style={{ marginRight: 15 }}>
      <TouchableOpacity
        onPress={() => setShowActionsheet(true)}
        className="relative p-2"
      >
        <Menu size={24} color="#4B5563" />
      </TouchableOpacity>

      {/* Actionsheet utama */}
      <Actionsheet isOpen={showActionsheet} onClose={handleCloseActionsheet}>
        <ActionsheetBackdrop />
        <ActionsheetContent>
          <ActionsheetDragIndicatorWrapper>
            <ActionsheetDragIndicator />
          </ActionsheetDragIndicatorWrapper>

          <ActionsheetItem onPress={() => { router.push('/'); handleCloseActionsheet(); }}>
            <ActionsheetIcon className="stroke-background-700" as={Home} />
            <ActionsheetItemText>Homepage</ActionsheetItemText>
          </ActionsheetItem>

          {isAuthenticated ? (
            <>
              <ActionsheetItem onPress={() => { router.push('/cart'); handleCloseActionsheet(); }}>
                <ActionsheetIcon className="stroke-background-700" as={ShoppingCart} />
                <ActionsheetItemText>Keranjang Saya</ActionsheetItemText>
              </ActionsheetItem>
              <ActionsheetItem onPress={() => { router.push('/order'); handleCloseActionsheet(); }}>
                <ActionsheetIcon className="stroke-background-700" as={ShoppingBasket} />
                <ActionsheetItemText>Riwayat Pesanan</ActionsheetItemText>
              </ActionsheetItem>
              <ActionsheetItem onPress={handleLogoutPress}>
                <ActionsheetIcon className="stroke-background-700" as={LogOut} />
                <ActionsheetItemText>Logout</ActionsheetItemText>
              </ActionsheetItem>
            </>
          ) : (
            <ActionsheetItem onPress={handleLogin}>
              <ActionsheetIcon className="stroke-background-700" as={LogIn} />
              <ActionsheetItemText>Login</ActionsheetItemText>
            </ActionsheetItem>
          )}
        </ActionsheetContent>
      </Actionsheet>

      {/* AlertDialog Konfirmasi Logout */}
      <AlertDialog isOpen={showLogoutConfirm} onClose={handleCloseLogoutConfirm}>
        <AlertDialogBackdrop />
        <AlertDialogContent>
          <AlertDialogHeader>
            <Heading className="text-typography-950 font-semibold" size="md">
              Konfirmasi Logout
            </Heading>
            <AlertDialogCloseButton onPress={handleCloseLogoutConfirm} />
          </AlertDialogHeader>
          <AlertDialogBody className="mt-3 mb-4">
            <Text size="sm">
              Apakah Anda yakin ingin logout?
            </Text>
          </AlertDialogBody>
          <AlertDialogFooter className="">
            <Button
              variant="outline"
              action="secondary"
              onPress={handleCloseLogoutConfirm}
              size="sm"
              style={{ marginRight: 12 }}
            >
              <ButtonText>Batal</ButtonText>
            </Button>
            <Button
              action="negative"
              onPress={confirmLogout}
              size="sm"
            >
              <ButtonText>Logout</ButtonText>
            </Button>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </HStack>
  );
}