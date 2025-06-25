// // app/_layout.tsx
// import { Stack, router } from 'expo-router';
// import "@/global.css";
// import { GluestackUIProvider } from "@/components/ui/gluestack-ui-provider";
// import { Provider } from 'react-redux';
// import { store, persistor } from '@/src/store/store';
// import { PersistGate } from 'redux-persist/integration/react';
// import React, { useState } from 'react';
// import { View, TouchableOpacity } from 'react-native';
// import { ShoppingCart, ShoppingBasket, LogIn, LogOut, Menu, Home } from 'lucide-react-native'; // Import Home ikon
// import { HStack } from '@/components/ui/hstack';

// // Import Actionsheet components
// import {
//   Actionsheet,
//   ActionsheetContent,
//   ActionsheetItem,
//   ActionsheetItemText,
//   ActionsheetDragIndicator,
//   ActionsheetDragIndicatorWrapper,
//   ActionsheetBackdrop,
//   ActionsheetIcon
// } from "@/components/ui/actionsheet";
// import { Icon } from "@/components/ui/icon";

// export default function RootLayout() {
//   const [showActionsheet, setShowActionsheet] = useState(false);
//   const handleClose = () => setShowActionsheet(false);

//   const [isLoggedIn, setIsLoggedIn] = useState(false); // Placeholder untuk status login

//   const handleLogin = () => {
//     router.push('/login');
//     setIsLoggedIn(true);
//     handleClose();
//   };

//   const handleLogout = () => {
//     console.log("User logged out");
//     setIsLoggedIn(false);
//     router.push('/logout');
//     handleClose();
//   };

//   return (
//     <Provider store={store}>
//       <PersistGate loading={null} persistor={persistor}>
//         <GluestackUIProvider>
//           <Stack>
//             <Stack.Screen
//               name="index"
//               options={{
//                 title: 'Product Digiberkat',
//                 headerShown: true,
//                 headerRight: () => (
//                   <HStack space="md" style={{ marginRight: 15 }}>
//                     <TouchableOpacity
//                       onPress={() => setShowActionsheet(true)}
//                       className="relative p-2"
//                     >
//                       <Menu size={24} color="#4B5563" />
//                     </TouchableOpacity>
//                   </HStack>
//                 ),
//               }}
//             />

//             {/* Screens lainnya tetap sama */}
//             <Stack.Screen
//               name="login"
//               options={{
//                 title: 'Login',
//                 headerShown: false
//               }}
//             />
//             <Stack.Screen
//               name="register"
//               options={{
//                 title: 'Daftar',
//                 headerShown: false
//               }}
//             />
//             <Stack.Screen
//               name="product/[id]"
//               options={{
//                 headerBackTitle: 'Back'
//               }}
//             />
//             <Stack.Screen
//               name="cart"
//               options={{
//                 title: 'Keranjang Saya',
//                 headerRight: () => (
//                   <HStack space="md" style={{ marginRight: 15 }}>
//                     <TouchableOpacity
//                       onPress={() => setShowActionsheet(true)}
//                       className="relative p-2"
//                     >
//                       <Menu size={24} color="#4B5563" />
//                     </TouchableOpacity>
//                   </HStack>
//                 ),
//               }}
//             />
//             <Stack.Screen
//               name="order"
//               options={{
//                 title: 'Riwayat Pesanan',
//                 headerRight: () => ( // ✅ Tambahkan headerRight di halaman order
//                   <HStack space="md" style={{ marginRight: 15 }}>
//                     <TouchableOpacity
//                       onPress={() => setShowActionsheet(true)}
//                       className="relative p-2"
//                     >
//                       <Menu size={24} color="#4B5563" />
//                     </TouchableOpacity>
//                   </HStack>
//                 ),
//               }}
//             />
//           </Stack>

//           {/* Actionsheet utama */}
//           <Actionsheet isOpen={showActionsheet} onClose={handleClose}>
//             <ActionsheetBackdrop />
//             <ActionsheetContent>
//               <ActionsheetDragIndicatorWrapper>
//                 <ActionsheetDragIndicator />
//               </ActionsheetDragIndicatorWrapper>

//               {/* ✅ Tombol Homepage */}
//               <ActionsheetItem onPress={() => { router.push('/'); handleClose(); }}>
//                 <ActionsheetIcon className="stroke-background-700" as={Home} />
//                 <ActionsheetItemText>Homepage</ActionsheetItemText>
//               </ActionsheetItem>

//               {isLoggedIn ? (
//                 <>
//                   <ActionsheetItem onPress={() => { router.push('/cart'); handleClose(); }}>
//                     <ActionsheetIcon className="stroke-background-700" as={ShoppingCart} />
//                     <ActionsheetItemText>Keranjang Saya</ActionsheetItemText>
//                   </ActionsheetItem>
//                   <ActionsheetItem onPress={() => { router.push('/order'); handleClose(); }}>
//                     <ActionsheetIcon className="stroke-background-700" as={ShoppingBasket} />
//                     <ActionsheetItemText>Riwayat Pesanan</ActionsheetItemText>
//                   </ActionsheetItem>
//                   <ActionsheetItem onPress={handleLogout}>
//                     <ActionsheetIcon className="stroke-background-700" as={LogOut} />
//                     <ActionsheetItemText>Logout</ActionsheetItemText>
//                   </ActionsheetItem>
//                 </>
//               ) : (
//                 <ActionsheetItem onPress={handleLogin}>
//                   <ActionsheetIcon className="stroke-background-700" as={LogIn} />
//                   <ActionsheetItemText>Login</ActionsheetItemText>
//                 </ActionsheetItem>
//               )}
//             </ActionsheetContent>
//           </Actionsheet>

//         </GluestackUIProvider>
//       </PersistGate>
//     </Provider>
//   );
// }

// app/_layout.tsx
import { Stack, router } from 'expo-router';
import "@/global.css";
import { GluestackUIProvider } from "@/components/ui/gluestack-ui-provider";
import { Provider } from 'react-redux'; // ✅ Hapus useSelector dan useDispatch dari sini
import { store, persistor } from '@/src/store/store';
import { PersistGate } from 'redux-persist/integration/react';
import React from 'react'; // ✅ Hapus useState dari sini, tidak perlu di RootLayout

// ✅ Import komponen HeaderActions
import { HeaderActions } from '../components/HeaderActions';

// Hapus semua import icon dan component Gluestack UI yang terkait dengan header/actionsheet/alertdialog
// karena sudah dipindahkan ke HeaderActions.tsx
// import { View, TouchableOpacity } from 'react-native';
// import { ShoppingCart, ShoppingBasket, LogIn, LogOut, Menu, Home } from 'lucide-react-native';
// import { HStack } from '@/components/ui/hstack';
// import {
//   Actionsheet, ActionsheetContent, ActionsheetItem, ActionsheetItemText,
//   ActionsheetDragIndicator, ActionsheetDragIndicatorWrapper, ActionsheetBackdrop, ActionsheetIcon
// } from "@/components/ui/actionsheet";
// import { Icon } from "@/components/ui/icon";
// import {
//   AlertDialog, AlertDialogBackdrop, AlertDialogContent, AlertDialogHeader,
//   AlertDialogCloseButton, AlertDialogFooter, AlertDialogBody,
// } from "@/components/ui/alert-dialog";
// import { Button, ButtonText } from "@/components/ui/button";
// import { Heading } from "@/components/ui/heading";
// import { Text } from "@/components/ui/text";
// import { logout, selectIsAuthenticated } from '@/src/store/api/authSlice';


export default function RootLayout() {
  // ✅ Hapus semua state dan fungsi yang terkait dengan Actionsheet/AlertDialog dari sini
  // const [showActionsheet, setShowActionsheet] = useState(false);
  // const handleCloseActionsheet = () => setShowActionsheet(false);
  // const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);
  // const handleCloseLogoutConfirm = () => setShowLogoutConfirm(false);
  // const isAuthenticated = useSelector(selectIsAuthenticated);
  // const dispatch = useDispatch();
  // const handleLogin = () => { router.push('/login'); handleCloseActionsheet(); };
  // const handleLogoutPress = () => { setShowLogoutConfirm(true); handleCloseActionsheet(); };
  // const confirmLogout = () => { dispatch(logout()); console.log("User logged out"); router.push('/'); handleCloseLogoutConfirm(); };


  return (
    <Provider store={store}>
      <PersistGate loading={null} persistor={persistor}>
        <GluestackUIProvider>
          <Stack>
            <Stack.Screen
              name="index"
              options={{
                title: 'Product Digiberkat',
                headerShown: true,
                headerRight: () => <HeaderActions />
              }}
            />

            {/* Screens lainnya tetap sama */}
            <Stack.Screen
              name="login"
              options={{
                title: 'Login',
                headerShown: false
              }}
            />
            <Stack.Screen
              name="register"
              options={{
                title: 'Daftar',
                headerShown: false
              }}
            />
            <Stack.Screen
              name="product/[id]"
              options={{
                headerBackTitle: 'Back'
              }}
            />
            <Stack.Screen
              name="cart"
              options={{
                title: 'Keranjang Saya',
                headerRight: () => <HeaderActions />
              }}
            />
            <Stack.Screen
              name="order"
              options={{
                title: 'Riwayat Pesanan',
                headerRight: () => <HeaderActions />
              }}
            />
          </Stack>

          {/* ✅ Hapus Actionsheet dan AlertDialog dari sini, sudah di HeaderActions */}

        </GluestackUIProvider>
      </PersistGate>
    </Provider>
  );
}