import React, { useState, useEffect } from 'react';
import { Alert, AlertText, AlertIcon } from '@/components/ui/alert';
import { useRouter } from 'expo-router';
import { InfoIcon } from '@/components/ui/icon';
import { useRegisterUserMutation } from '@/src/store/api/registerApi';
import { selectIsAuthenticated } from '@/src/store/api/authSlice';
import { Card } from '@/components/ui/card';
import { Text } from '@/components/ui/text';
import { Heading } from '@/components/ui/heading';
import { Button, ButtonText } from '@/components/ui/button';
import { Box } from '@/components/ui/box';
import { VStack } from '@/components/ui/vstack';
import { Input, InputField } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { HStack } from '@/components/ui/hstack';
import { Link } from 'expo-router';
import { useSelector } from 'react-redux';

export default function RegisterScreen() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [showAlert, setShowAlert] = useState(false);
  const [alertType, setAlertType] = useState<'error' | 'success'>('error');
  const [registerUser, { isLoading }] = useRegisterUserMutation();
  const router = useRouter();
  const isAuthenticated = useSelector(selectIsAuthenticated);
  const [isReady, setIsReady] = useState(false);

  useEffect(() => {
    setIsReady(true); // Tandai bahwa komponen sudah siap
  }, []);

  useEffect(() => {
    if (isReady && isAuthenticated) {
      router.push('/');
    }
  }, [isReady, isAuthenticated]);

  const validateEmail = (email: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

  const handleRegister = async () => {
    setShowAlert(false);
    setErrorMessage('');

    if (!validateEmail(email)) {
      setAlertType('error');
      setErrorMessage('Format email tidak valid');
      setShowAlert(true);
      return;
    }

    if (password.length < 6) {
      setAlertType('error');
      setErrorMessage('Password harus minimal 6 karakter');
      setShowAlert(true);
      return;
    }

    if (password !== confirmPassword) {
      setAlertType('error');
      setErrorMessage('Password dan konfirmasi password tidak sama');
      setShowAlert(true);
      return;
    }

    try {
      const response = await registerUser({
        username: email.split('@')[0], // optional logic
        email,
        password,
      }).unwrap();

      setAlertType('success');
      setErrorMessage('Registrasi berhasil! Mengarahkan ke login...');
      setShowAlert(true);

      setTimeout(() => {
        router.push('/login');
      }, 2000);
    } catch (err: any) {
      console.log('Error detail:', err);

      setAlertType('error');
      setErrorMessage(err.error || 'Terjadi kesalahan saat registrasi');
      setShowAlert(true);
    }
  };

  return (

      <Box style={{ flex: 1, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 16, backgroundColor: '#f3f4f6' }}>
        <Card style={{ padding: 32, width: '100%', maxWidth: 420, backgroundColor: 'white', borderRadius: 16, elevation: 4 }}>
          <VStack space="lg">
            <VStack space="sm" style={{ alignItems: 'center' }}>
              <Heading size="xl" style={{ textAlign: 'center', color: '#1f2937', fontWeight: '700' }}>
                Buat Akun Baru
              </Heading>
              <Text style={{ color: '#6b7280', textAlign: 'center' }}>
                Isi form berikut untuk mendaftar
              </Text>
            </VStack>

            {showAlert && (
              <Alert action={alertType}>
                <AlertIcon as={InfoIcon} />
                <AlertText>{errorMessage}</AlertText>
              </Alert>
            )}

            <VStack space="md">
              <VStack space="xs">
                <Text style={{ fontWeight: '500', color: '#374151' }}>Email</Text>
                <Input variant="rounded" size="md" style={{ borderColor: '#e5e7eb' }}>
                  <InputField
                    value={email}
                    onChangeText={setEmail}
                    placeholder="contoh@email.com"
                    autoCapitalize="none"
                    keyboardType="email-address"
                  />
                </Input>
              </VStack>

              <VStack space="xs">
                <Text style={{ fontWeight: '500', color: '#374151' }}>Password</Text>
                <Input variant="rounded" size="md" style={{ borderColor: '#e5e7eb' }}>
                  <InputField
                    value={password}
                    onChangeText={setPassword}
                    placeholder="Minimal 6 karakter"
                    secureTextEntry
                  />
                </Input>
              </VStack>

              <VStack space="xs">
                <Text style={{ fontWeight: '500', color: '#374151' }}>Konfirmasi Password</Text>
                <Input variant="rounded" size="md" style={{ borderColor: '#e5e7eb' }}>
                  <InputField
                    value={confirmPassword}
                    onChangeText={setConfirmPassword}
                    placeholder="Ketik ulang password"
                    secureTextEntry
                  />
                </Input>
              </VStack>
            </VStack>

            <VStack space="sm">
              <Button
                onPress={handleRegister}
                isDisabled={isLoading}
                style={{
                  backgroundColor: '#3b82f6',
                  borderRadius: 12,
                  height: 48,
                }}
              >
                {isLoading ? (
                  <Spinner size="small" color="white" />
                ) : (
                  <ButtonText style={{ fontWeight: '600', color: 'white' }}>Daftar</ButtonText>
                )}
              </Button>

              <HStack style={{ justifyContent: 'center', marginTop: 16 }}>
                <Text style={{ color: '#6b7280' }}>Sudah punya akun? </Text>
                <Link href="/login" style={{ color: '#3b82f6', fontWeight: '500' }}>
                  Masuk disini
                </Link>
              </HStack>
            </VStack>
          </VStack>
        </Card>
      </Box>

  );
}
