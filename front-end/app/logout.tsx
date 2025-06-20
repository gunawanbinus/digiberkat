import { Avatar, AvatarFallbackText, AvatarImage } from "@/components/ui/avatar";
import { Box } from "@/components/ui/box";
import { Button, ButtonText } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Heading } from "@/components/ui/heading";
import { Text } from "@/components/ui/text";
import { VStack } from "@/components/ui/vstack";
import { useDispatch, useSelector } from 'react-redux';
import { selectCurrentUser, selectCurrentToken, logout } from '../src/store/api/authSlice';

function AboutMePage() {
  const dispatch = useDispatch();
  const currentUser = useSelector(selectCurrentUser);
  const token = useSelector(selectCurrentToken);

  const handleLogout = () => {
    dispatch(logout());
    // You might want to redirect the user to the login page after logout
    // For example, if you use react-router-dom: navigate('/login');
  };

  const getExpirationDate = (token: string | null) => {
    if (!token) return "N/A";
    try {
      const decodedToken = JSON.parse(atob(token.split('.')[1]));
      if (decodedToken && decodedToken.exp) {
        const date = new Date(decodedToken.exp * 1000);
        return date.toLocaleString();
      }
    } catch (e) {
      console.error("Failed to decode token", e);
    }
    return "N/A";
  };

  return (
    <Box className="flex-1 justify-center items-center p-4">
      <Card className="p-6 rounded-lg max-w-[360px] w-full">
        <Box className="flex-row items-center mb-4">
          <Avatar className="mr-4">
            <AvatarFallbackText>{currentUser.username ? currentUser.username.charAt(0).toUpperCase() : '?'}</AvatarFallbackText>
            <AvatarImage
              source={{
                uri: 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png',
              }}
            />
          </Avatar>
          <VStack>
            <Heading size="md" className="mb-1">
              {currentUser.username || 'Guest User'}
            </Heading>
            <Text size="sm">
              Role: {currentUser.role || 'N/A'}
            </Text>
          </VStack>
        </Box>
        <VStack className="mb-6">
          <Text size="sm" className="mb-1">
            Token Expired At: {getExpirationDate(token)}
          </Text>
        </VStack>
        <Button className="py-2 px-4" onPress={handleLogout}>
          <ButtonText size="sm">Log Out</ButtonText>
        </Button>
      </Card>
    </Box>
  );
}

export default AboutMePage;