// --- main.go ---
package main

import (
	"log"

	"github.com/gin-gonic/gin"
)

func CORSMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		c.Writer.Header().Set("Access-Control-Allow-Origin", "*")
		c.Writer.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS")
		c.Writer.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")

		if c.Request.Method == "OPTIONS" {
			c.AbortWithStatus(204)
			return
		}

		c.Next()
	}
}

func main() {
	// Koneksi ke database

	db, err := InitDB()
	if err != nil {
		log.Fatalf("❌ Gagal terhubung ke database: %v", err)
		return
	}

	r := gin.Default()
	r.Use(CORSMiddleware())
	// // // Setup Routes langsung dari fungsi yang sudah dibuat
	AuthRoutes(r, db)
	ProductRoutes(r, db)
	// CategoryRoutes(r, db)
	// ProductImageRoutes(r, db)
	RestockRequestRoutes(r, db)
	// NotificationRoutes(r, db)
	// ProductVariantRoutes(r, db)
	// CartRoutes(r, db)
	// StockReservationRoutes(r, db)
	CartItemRoutes(r, db)
	OrderRoutes(r, db)
	PositionRoutes(r, db)
	StatRoutes(r, db)

	// // Menjalankan server
	if err := r.Run(":8001"); err != nil {
		log.Fatalf("❌ Gagal menjalankan server: %v", err)
	}
	log.Println("✅ Server running at http://localhost:8001")
}
