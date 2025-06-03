package main

import (
	//"fmt"
	"crypto/rand"
	"log"
	"math/big"
	"net/http"
	"os"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
	"github.com/joho/godotenv"
)

// Inisialisasi secret key dari .env
var jwtSecret []byte

func init() {
	err := godotenv.Load()
	if err != nil {
		log.Println("⚠️ File .env tidak ditemukan, lanjut pakai environment bawaan")
	}
	secret := os.Getenv("JWT_SECRET")
	if secret == "" {
		log.Fatal("❌ JWT_SECRET tidak ditemukan di environment")
	}
	jwtSecret = []byte(secret)
}

// Claims sesuai payload token
type Claims struct {
	UserID int    `json:"user_id"`
	Username  string `json:"username"`
	Role   string `json:"role"` // user, employee, admin
	jwt.RegisteredClaims
}

// Fungsi untuk generate random ID (untuk admin)
func generateRandomID() int {
	n, err := rand.Int(rand.Reader, big.NewInt(999999))
	if err != nil {
		// Fallback ke timestamp jika random gagal
		return int(time.Now().UnixNano())
	}
	return int(n.Int64())
}

// Fungsi untuk generate JWT token yang dimodifikasi
func GenerateToken(userID int, username string, role string) (string, error) {
	// Jika role admin dan userID 0, generate random ID
	finalUserID := userID
	if role == "admin" && userID == 0 {
		finalUserID = generateRandomID()
	}

	claims := Claims{
		UserID:   finalUserID,
		Username: username,
		Role:     role,
		RegisteredClaims: jwt.RegisteredClaims{
			ExpiresAt: jwt.NewNumericDate(time.Now().Add(24 * time.Hour)),
			IssuedAt:  jwt.NewNumericDate(time.Now()),
		},
	}

	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	return token.SignedString(jwtSecret)
}

// Middleware untuk validasi token dan set data user ke context
func AuthMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		authHeader := c.GetHeader("Authorization")
		if authHeader == "" || !strings.HasPrefix(authHeader, "Bearer ") {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "❌ Token tidak ditemukan"})
			c.Abort()
			return
		}

		tokenStr := strings.TrimPrefix(authHeader, "Bearer ")
		token, err := jwt.ParseWithClaims(tokenStr, &Claims{}, func(token *jwt.Token) (interface{}, error) {
			return jwtSecret, nil
		})

		if err != nil || !token.Valid {
			log.Printf("Token error: %v\n", err)
			c.JSON(http.StatusUnauthorized, gin.H{"error": "❌ Token tidak valid atau expired"})
			c.Abort()
			return
		}

		claims, ok := token.Claims.(*Claims)
		if !ok {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "❌ Gagal parsing token"})
			c.Abort()
			return
		}

		// Tambahan validasi khusus untuk admin
        if claims.Role == "admin" && claims.UserID == 0 {
            c.JSON(http.StatusUnauthorized, gin.H{"error": "❌ Invalid admin ID"})
            c.Abort()
            return
        }

		// Simpan ke context
		c.Set("user_id", claims.UserID)
		c.Set("username", claims.Username)
		c.Set("role", claims.Role)

		c.Next()
	}
}

// Middleware untuk cek role (admin, employee, user)
func RoleMiddleware(allowedRoles ...string) gin.HandlerFunc {
	return func(c *gin.Context) {
		role, exists := c.Get("role")
		if !exists {
			c.JSON(http.StatusForbidden, gin.H{"error": "❌ Role tidak ditemukan"})
			c.Abort()
			return
		}

		for _, allowed := range allowedRoles {
			if role == allowed {
				c.Next()
				return
			}
		}

		c.JSON(http.StatusForbidden, gin.H{"error": "❌ Akses ditolak (role tidak sesuai)"})
		c.Abort()
	}
}

// Helper untuk mengambil data dari context
func GetUserID(c *gin.Context) int {
	return c.GetInt("user_id")
}

func GetUsername(c *gin.Context) string {
	return c.GetString("username")
}

func GetRole(c *gin.Context) string {
	return c.GetString("role")
}
