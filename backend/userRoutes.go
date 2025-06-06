// Semuanya masih dalam package main
package main

import (
	//"context"
	"database/sql"
	// "errors"
	"fmt"
	// "log"

	//"mime/multipart"
	"net/http"
	"strconv"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
	//"github.com/imagekit-developer/imagekit-go"
)

// import "github.com/imagekit-developer/imagekit-go/uploader"

// =======================
// 🧩 Helper Functions
// =======================
// GetIDParam is a helper function to get the ID parameter from the URL and convert it to an integer.
func GetIDParam(c *gin.Context) (int, string, bool) {
	idStr := c.Param("id")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ ID harus berupa angka"})
		return 0, "", false
	}
	return id, idStr, true
}

// ValidateRecordExistence is a helper function to check if a record exists in the database.
func ValidateRecordExistence(c *gin.Context, db *sql.DB, table string, id int) bool {
	valid, err := IsValidID(db, table, id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": fmt.Sprintf("❌ Gagal memeriksa ID di tabel %s", table)})
		return false
	}
	if !valid {
		c.JSON(http.StatusNotFound, gin.H{"error": fmt.Sprintf("❌ Data %s tidak ditemukan", table)})
		return false
	}
	return true
}

// IsValidID is a helper function to check if a given ID exists in the specified table.
func IsValidID(db *sql.DB, tableName string, id int) (bool, error) {
	// List of allowed table names to prevent SQL injection
	allowedTables := map[string]bool{
		"categories":       true,
		"products":         true,
		"product_variants": true,
		"product_images":   true,
		"restock_requests": true,
		"users":            true,
		"employees":        true,
		"carts":            true,
		"notifications":    true,
		// Add more allowed tables here
	}

	// Check if the table name is allowed
	if !allowedTables[tableName] {
		return false, fmt.Errorf("invalid table name: %s", tableName)
	}

	// Build the query string safely using fmt.Sprintf after validation
	query := fmt.Sprintf("SELECT EXISTS(SELECT 1 FROM %s WHERE id = ?)", tableName)

	var exists bool
	err := db.QueryRow(query, id).Scan(&exists)
	if err != nil {
		return false, err
	}

	return exists, nil
}

// CheckIfVarians is a helper function to check if the product is a variant or not.
func CheckIfVarians(db *sql.DB, productID int) (bool, error) {
	var isVarians bool
	query := "SELECT is_varians FROM products WHERE id = ?"
	err := db.QueryRow(query, productID).Scan(&isVarians)
	if err != nil {
		return false, fmt.Errorf("❌ Gagal memeriksa varian produk: %v", err)
	}
	return isVarians, nil
}

// SetNullableFieldsForVariant is a helper function to set nullable fields to NULL if the product is a variant.
// SetNullableFieldsForVariant mengosongkan field-field jika is_varians = true
func SetNullableFieldsForVariant(isVarians bool, product *ProductsModel) {
	if isVarians {
		product.IsDiscounted = false
		product.DiscountPrice = nil
		product.Price = nil
		product.Stock = nil
	}
}

// SetRequiredFieldsForNonVariant memastikan field wajib jika is_varians = false
func SetRequiredFieldsForNonVariant(product *ProductsModel) error {
	if product.Price == nil || *product.Price < 0 {
		return fmt.Errorf("❌ Harga wajib diisi dan tidak boleh negatif")
	}
	if product.Stock == nil || *product.Stock < 0 {
		return fmt.Errorf("❌ Stok wajib diisi dan tidak boleh negatif")
	}
	if product.IsDiscounted == true {
		if product.DiscountPrice == nil {
			return fmt.Errorf("❌ Harga diskon wajib diisi jika produk sedang diskon")
		}
		if *product.DiscountPrice >= *product.Price {
			return fmt.Errorf("❌ Harga diskon harus lebih kecil dari harga normal")
		}
	}
	return nil
}

// helper function untuk products
func ValidateProductInput(product *ProductsModel, c *gin.Context, db *sql.DB) error {
	if strings.TrimSpace(product.Name) == "" {
		return fmt.Errorf("❌ Nama produk tidak boleh kosong")
	}
	if strings.TrimSpace(product.Description) == "" {
		return fmt.Errorf("❌ Deskripsi produk tidak boleh kosong")
	}
	// check if categiory_id is valid
	if !ValidateRecordExistence(c, db, "categories", product.CategoryID) {
		return fmt.Errorf("❌ Kategori tidak ditemukan")
	}
	if product.CategoryID == 0 {
		return fmt.Errorf("❌ ID kategori tidak boleh 0")
	}

	if product.IsVarians {
		SetNullableFieldsForVariant(true, product)
	} else {
		if err := SetRequiredFieldsForNonVariant(product); err != nil {
			return err
		}
	}
	return nil
}

// =========================
// 🛠️ Cart TotalPrice Helpers
// =========================

// Tambahkan nilai ke total_price cart
func AddToCartTotalPrice(db *sql.DB, cartID int, amount int) error {
	_, err := db.Exec(`
		UPDATE carts
		SET total_price = total_price + ?, updated_at = NOW()
		WHERE id = ?
	`, amount, cartID)
	return err
}

// Kurangi nilai dari total_price cart
func SubtractFromCartTotalPrice(db *sql.DB, cartID int, amount int) error {
	_, err := db.Exec(`
		UPDATE carts
		SET total_price = GREATEST(0, total_price - ?), updated_at = NOW()
		WHERE id = ?
	`, amount, cartID)
	return err
}

// =========================
//    🌐 Routes Helpers
// =========================

func addRoute(
	group *gin.RouterGroup,
	method string,
	path string,
	roles []string,
	handler func(*gin.Context, *sql.DB),
	db *sql.DB,
) {
	if handler == nil {
		return
	}

	wrappedHandler := func(c *gin.Context) {
		handler(c, db)
	}

	handlers := []gin.HandlerFunc{}
	if len(roles) > 0 {
		handlers = append(handlers, AuthMiddleware(), RoleMiddleware(roles...), wrappedHandler)
	} else {
		handlers = append(handlers, wrappedHandler)
	}

	switch method {
	case "GET":
		group.GET(path, handlers...)
	case "POST":
		group.POST(path, handlers...)
	case "PATCH":
		group.PATCH(path, handlers...)
	case "DELETE":
		group.DELETE(path, handlers...)
	case "PUT":
		group.PUT(path, handlers...)
	}
}

// =========================
// 🧩 Helper Functions END
// =========================

// =========================
// 🗂️ Category Management
// =========================
func CategoryRoutes(r *gin.Engine, db *sql.DB) {
	api := r.Group("/api/v1/categories")

	// Public GET
	addRoute(api, "GET", "", []string{}, GetAllCategories, db)

	// Admin only: POST, PATCH, DELETE
	addRoute(api, "POST", "", []string{"admin"}, CreateCategory, db)
	addRoute(api, "PATCH", "/:id", []string{"admin"}, UpdateCategory, db)
	addRoute(api, "DELETE", "/:id", []string{"admin"}, DeleteCategory, db)
}

// ++++++++++++++++++++++++
//
//	Categories READ
//
// +++++++++++++++++++++++++
func GetAllCategories(c *gin.Context, db *sql.DB) {
	rows, err := db.Query(`
		SELECT id, name, description FROM categories
	`)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data kategori"})
		return
	}
	defer rows.Close()

	var categories []CategoryModel
	for rows.Next() {
		var cat CategoryModel
		err := rows.Scan(&cat.ID, &cat.Name, &cat.Description)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal membaca data kategori"})
			return
		}
		categories = append(categories, cat)
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Semua kategori berhasil diambil",
		"data":    categories,
	})
}

// ++++++++++++++++++++++++
//
//	Categories CREATE
//
// +++++++++++++++++++++++++
func CreateCategory(c *gin.Context, db *sql.DB) {
	var input CategoryModel

	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format JSON tidak valid"})
		return
	}

	if input.Name == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Nama kategori wajib diisi"})
		return
	}

	if input.Description == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Deskripsi kategori wajib diisi"})
		return
	}

	result, err := db.Exec(`INSERT INTO categories (name, description) VALUES (?, ?)`, input.Name, input.Description)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menyimpan kategori"})
		return
	}

	id, _ := result.LastInsertId()

	c.JSON(http.StatusCreated, gin.H{
		"message": "✅ Kategori berhasil ditambahkan",
		"id":      id,
	})
}

// ++++++++++++++++++++++++
//  Categories UPDATE
// +++++++++++++++++++++++++

func UpdateCategory(c *gin.Context, db *sql.DB) {
	// Validasi dan ambil ID dari parameter
	idInt, _, ok := GetIDParam(c)
	if !ok {
		return
	}

	// Cek apakah kategori ada di database
	if !ValidateRecordExistence(c, db, "categories", idInt) {
		return
	}

	var input CategoryModel

	// Bind JSON input
	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format data tidak valid"})
		return
	}

	// Validasi minimal satu field yang diupdate
	if input.Name == "" && input.Description == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Minimal satu field (nama atau deskripsi) harus diisi"})
		return
	}

	// Bangun query dinamis berdasarkan field yang diisi
	query := "UPDATE categories SET "
	var args []interface{}
	updates := []string{}

	if input.Name != "" {
		updates = append(updates, "name = ?")
		args = append(args, input.Name)
	}

	if input.Description != "" {
		updates = append(updates, "description = ?")
		args = append(args, input.Description)
	}

	query += strings.Join(updates, ", ") + " WHERE id = ?"
	args = append(args, idInt)

	// Eksekusi query
	result, err := db.Exec(query, args...)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"error":  "❌ Gagal mengupdate kategori",
			"detail": err.Error(),
		})
		return
	}

	// Cek apakah ada baris yang terupdate
	rowsAffected, err := result.RowsAffected()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"error":  "❌ Gagal memverifikasi update",
			"detail": err.Error(),
		})
		return
	}

	if rowsAffected == 0 {
		c.JSON(http.StatusNotFound, gin.H{"message": "⚠️ Tidak ada perubahan data"})
		return
	}

	// Response sukses
	response := gin.H{
		"message": "✅ Kategori berhasil diupdate",
		"id":      idInt,
	}

	if input.Name != "" {
		response["name"] = input.Name
	}
	if input.Description != "" {
		response["description"] = input.Description
	}

	c.JSON(http.StatusOK, response)
}

// ++++++++++++++++++++++++
//
//	Categories DELETE
//
// +++++++++++++++++++++++++
func DeleteCategory(c *gin.Context, db *sql.DB) {
	idInt, id, ok := GetIDParam(c)
	if !ok {
		return
	}

	// //cek apakah id valid
	if !ValidateRecordExistence(c, db, "categories", idInt) {
		return
	}

	_, err := db.Exec(`DELETE FROM categories WHERE id = ?`, id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menghapus kategori"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Kategori berhasil dihapus",
	})
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

// =========================
// 📦 Product Management
// =========================

// ProductRoutes mengatur semua rute terkait produk
func ProductRoutes(r *gin.Engine, db *sql.DB) {
	api := r.Group("/api/v1/products")

	// 🟢 Public: GET semua produk atau produk berdasarkan kategori
	addRoute(api, "GET", "", []string{}, GetAllProducts, db)
	addRoute(api, "GET", ":category_id", []string{}, GetAllProducts, db)
	addRoute(api, "GET", "id/:id_product", []string{}, GetAllProducts, db)
}

func GetAllProducts(c *gin.Context, db *sql.DB) {
	// Cek apakah request berdasarkan ID produk
	productID := c.Param("id_product")
	categoryID := c.Param("category_id")

	var products []ProductsBasicModel
	var err error

	if productID != "" {
		// Validasi productID adalah angka
		id, err := strconv.Atoi(productID)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": "ID produk harus berupa angka"})
			return
		}

		// Ambil produk berdasarkan ID
		products, err = getProductByID(db, id)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data produk"})
			return
		}

		if len(products) == 0 {
			c.JSON(http.StatusNotFound, gin.H{"error": "Produk tidak ditemukan"})
			return
		}
	} else {
		// Ambil produk berdasarkan kategori atau semua produk
		products, err = getBasicProducts(db, categoryID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data produk"})
			return
		}
	}

	// 2. Ambil gambar & varian untuk setiap produk
	for i := range products {
		// Ambil gambar
		images, thumbnails, err := getProductImages(db, products[i].ID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil gambar produk"})
			return
		}
		products[i].Images = images
		products[i].Thumbnails = thumbnails

		// Ambil varian (jika produk punya varian)
		if products[i].IsVarians {
			variants, err := getProductVariants(db, products[i].ID)
			if err != nil {
				c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil varian produk"})
				return
			}
			products[i].Variants = variants
			products[i].Price = nil // Harga utama di-nullkan jika ada varian
			products[i].DiscountPrice = nil
			products[i].Stock = nil
		}
	}

	// Buat response message berdasarkan filter
	message := "✅ Semua produk berhasil diambil"
	if categoryID != "" {
		message = fmt.Sprintf("✅ Produk dengan kategori ID %s berhasil diambil", categoryID)
	} else if productID != "" {
		message = "✅ Produk berhasil diambil"
	}

	// Jika mengambil berdasarkan ID, kembalikan objek produk tunggal
	if productID != "" {
		c.JSON(http.StatusOK, gin.H{
			"message": message,
			"data":    products[0],
		})
	} else {
		c.JSON(http.StatusOK, gin.H{
			"message": message,
			"data":    products,
		})
	}
}

// Fungsi baru untuk mengambil produk berdasarkan ID
func getProductByID(db *sql.DB, productID int) ([]ProductsBasicModel, error) {
	query := `
        SELECT
            id, category_id, name, description,
            is_varians, is_discounted, discount_price,
            price, stock
        FROM products
        WHERE id = ?`

	rows, err := db.Query(query, productID)
	if err != nil {
		return nil, fmt.Errorf("gagal query produk: %w", err)
	}
	defer rows.Close()

	var products []ProductsBasicModel
	for rows.Next() {
		var p ProductsBasicModel
		err := rows.Scan(
			&p.ID,
			&p.CategoryID,
			&p.Name,
			&p.Description,
			&p.IsVarians,
			&p.IsDiscounted,
			&p.DiscountPrice,
			&p.Price,
			&p.Stock,
		)
		if err != nil {
			return nil, fmt.Errorf("gagal scan produk: %w", err)
		}
		products = append(products, p)
	}
	return products, nil
}

// Fungsi getBasicProducts tetap sama seperti sebelumnya

// Fungsi pembantu untuk mengambil data dasar produk
func getBasicProducts(db *sql.DB, categoryID string) ([]ProductsBasicModel, error) {
	var query string
	var args []interface{}

	// Bangun query berdasarkan ada/tidaknya categoryID
	query = `
        SELECT
            id, category_id, name, description,
            is_varians, is_discounted, discount_price,
            price, stock
        FROM products`

	if categoryID != "" {
		// Validasi categoryID adalah angka
		if _, err := strconv.Atoi(categoryID); err != nil {
			return nil, fmt.Errorf("category_id harus berupa angka")
		}
		query += " WHERE category_id = ?"
		args = append(args, categoryID)
	}

	rows, err := db.Query(query, args...)
	if err != nil {
		return nil, fmt.Errorf("gagal query produk: %w", err)
	}
	defer rows.Close()

	var products []ProductsBasicModel
	for rows.Next() {
		var p ProductsBasicModel
		err := rows.Scan(
			&p.ID,
			&p.CategoryID,
			&p.Name,
			&p.Description,
			&p.IsVarians,
			&p.IsDiscounted,
			&p.DiscountPrice,
			&p.Price,
			&p.Stock,
		)
		if err != nil {
			return nil, fmt.Errorf("gagal scan produk: %w", err)
		}
		products = append(products, p)
	}
	return products, nil
}

// Fungsi pembantu untuk mengambil gambar produk
func getProductImages(db *sql.DB, productID int) ([]string, []string, error) {
	rows, err := db.Query("SELECT image_url, thumbnail_url FROM product_images WHERE product_id = ?", productID)
	if err != nil {
		return nil, nil, err
	}
	defer rows.Close()

	var images, thumbnails []string
	for rows.Next() {
		var imgURL, thumbURL string
		err := rows.Scan(&imgURL, &thumbURL)
		if err != nil {
			return nil, nil, err
		}
		images = append(images, imgURL)
		thumbnails = append(thumbnails, thumbURL)
	}
	return images, thumbnails, nil
}

// Fungsi pembantu untuk mengambil varian produk
func getProductVariants(db *sql.DB, productID int) ([]Variant, error) {
	rows, err := db.Query(`
        SELECT id, name, price, is_discounted, discount_price, stock
        FROM product_variants
        WHERE product_id = ?`, productID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var variants []Variant
	for rows.Next() {
		var v Variant
		var isDiscounted bool
		err := rows.Scan(
			&v.ID,
			&v.Name,
			&v.Price,
			&isDiscounted,
			&v.DiscountPrice,
			&v.Stock,
		)
		if err != nil {
			return nil, err
		}
		if !isDiscounted {
			v.DiscountPrice = nil // Pastikan discount_price null jika tidak diskon
		}
		variants = append(variants, v)
	}
	return variants, nil
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

// =========================
// 📦 Product Variant Management
// =========================

// ProductVariantRoutes mengatur semua rute terkait varian produk
// func ProductVariantRoutes(r *gin.Engine, db *sql.DB) {
// 	api := r.Group("/api/v1/productvariants")

// 	// 🟢 Public: GET semua varian produk
// 	addRoute(api, "GET", "", []string{}, GetAllProductVariants, db)

// 	// 🔐 Admin only: POST, PATCH, DELETE
// 	addRoute(api, "POST", "", []string{"admin"}, CreateProductVariant, db)
// 	addRoute(api, "PATCH", "/:id", []string{"admin"}, UpdateProductVariant, db)
// 	addRoute(api, "DELETE", "/:id", []string{"admin"}, DeleteProductVariant, db)
// }

// // ++++++++++++++++++++++++
// // Product Variant READ
// // +++++++++++++++++++++++++
// func GetAllProductVariants(c *gin.Context, db *sql.DB) {
// 	rows, err := db.Query(`
// 		SELECT
// 			id, product_id, name, price, is_discounted, discount_price, stock
// 		FROM product_variants
// 	`)
// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data varian produk"})
// 		return
// 	}
// 	defer rows.Close()

// 	var productVariants []ProductVariantModel

// 	for rows.Next() {
// 		var pv ProductVariantModel
// 		err := rows.Scan(
// 			&pv.ID,
// 			&pv.ProductID,
// 			&pv.Name,
// 			&pv.Price,
// 			&pv.IsDiscounted,
// 			&pv.DiscountPrice,
// 			&pv.Stock,
// 		)

// 		if err != nil {
// 			c.JSON(http.StatusInternalServerError, gin.H{"error": fmt.Sprintf("❌ Scan error: %v", err)})
// 			return
// 		}

// 		productVariants = append(productVariants, pv)
// 	}

// 	c.JSON(http.StatusOK, gin.H{
// 		"message": "✅ Semua varian produk berhasil diambil",
// 		"data":    productVariants,
// 	})
// }

// // ++++++++++++++++++++++++
// //
// //	Product Variant CREATE
// //
// // ++++++++++++++++++++++++

// // ++++++++++++++++++++++++
// //
// //	Product Variant CREATE Helper
// //
// // ValidateProductVariantInput adalah fungsi untuk memvalidasi input varian produk.
// func ValidateProductVariantInput(productVariant *ProductVariantModel) error {
// 	// Validasi nama varian produk
// 	if strings.TrimSpace(productVariant.Name) == "" {
// 		return fmt.Errorf("❌ Nama varian produk tidak boleh kosong")
// 	}

// 	// Validasi ID produk, ID produk harus ada dan lebih dari 0
// 	if productVariant.ProductID == 0 {
// 		return fmt.Errorf("❌ ID produk tidak boleh kosong")
// 	}

// 	// Validasi harga varian produk, harga tidak boleh negatif
// 	if productVariant.Price < 0 {
// 		return fmt.Errorf("❌ Harga varian produk tidak boleh negatif")
// 	}

// 	// Validasi stok varian produk, stok tidak boleh negatif
// 	if productVariant.Stock < 1 {
// 		return fmt.Errorf("❌ Stok varian produk harus diisi dan tidak boleh negatif")
// 	}

// 	// Validasi diskon jika ada, jika varian produk diskon, pastikan harga diskon lebih kecil dari harga normal
// 	if productVariant.IsDiscounted && (productVariant.DiscountPrice == nil || *productVariant.DiscountPrice >= productVariant.Price) {
// 		return fmt.Errorf("❌ Harga diskon harus lebih kecil dari harga normal")
// 	}

// 	// Validasi warna jika ada (nullable), jika ada, pastikan tidak kosong hanya jika diberikan
// 	if productVariant.Color != nil && strings.TrimSpace(*productVariant.Color) == "" {
// 		return fmt.Errorf("❌ Warna varian produk tidak boleh kosong jika diberikan")
// 	}

// 	// Validasi search_vector (nullable), bisa kosong atau diisi string
// 	if productVariant.SearchVector != nil && strings.TrimSpace(*productVariant.SearchVector) == "" {
// 		return fmt.Errorf("❌ Search vector tidak boleh kosong jika diberikan")
// 	}

// 	return nil
// }

// // ++++++++++++++++++++++++
// func CreateProductVariant(c *gin.Context, db *sql.DB) {
// 	var productVariant ProductVariantModel

// 	// Bind input JSON ke struct
// 	if err := c.ShouldBindJSON(&productVariant); err != nil {
// 		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Data varian produk tidak valid, pastikan is_service diisi "})
// 		return
// 	}

// 	// Set is_discounted berdasarkan keberadaan discount_price
// 	if productVariant.DiscountPrice != nil {
// 		productVariant.IsDiscounted = true
// 	} else {
// 		productVariant.IsDiscounted = false
// 	}

// 	// Validasi input
// 	if err := ValidateProductVariantInput(&productVariant); err != nil {
// 		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
// 		return
// 	}
// 	// Validasi apakah produk dengan ID tersebut punya is_varians = true
// 	isVarians, err := CheckIfVarians(db, int(productVariant.ProductID))
// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memeriksa varian produk"})
// 		return
// 	}
// 	if !isVarians {
// 		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Produk tidak memiliki varian"})
// 		return
// 	}

// 	// Default IsService jika tidak dikirim (Go default bool = false, jadi aman)

// 	// Set waktu
// 	productVariant.CreatedAt = time.Now()
// 	productVariant.UpdatedAt = time.Now()

// 	// SQL untuk insert
// 	query := `
// 		INSERT INTO product_variants
// 		(product_id, name, color, price, is_discounted, discount_price, stock, is_service, search_vector, created_at, updated_at)
// 		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`

// 	res, err := db.Exec(
// 		query,
// 		productVariant.ProductID,
// 		productVariant.Name,
// 		productVariant.Color,
// 		productVariant.Price,
// 		productVariant.IsDiscounted,
// 		productVariant.DiscountPrice,
// 		productVariant.Stock,
// 		productVariant.IsService,
// 		productVariant.SearchVector,
// 		productVariant.CreatedAt,
// 		productVariant.UpdatedAt,
// 	)

// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menyimpan varian produk ke database"})
// 		return
// 	}

// 	lastID, _ := res.LastInsertId()
// 	productVariant.ID = int(lastID)

// 	c.JSON(http.StatusCreated, gin.H{
// 		"message": "✅ Varian produk berhasil dibuat",
// 		"data": gin.H{
// 			"id":             productVariant.ID,
// 			"product_id":     productVariant.ProductID,
// 			"name":           productVariant.Name,
// 			"color":          productVariant.Color,
// 			"price":          productVariant.Price,
// 			"is_discounted":  productVariant.IsDiscounted,
// 			"discount_price": productVariant.DiscountPrice,
// 			"stock":          productVariant.Stock,
// 			"is_service":     productVariant.IsService,
// 			"search_vector":  productVariant.SearchVector,
// 			"created_at":     productVariant.CreatedAt,
// 			"updated_at":     productVariant.UpdatedAt,
// 		},
// 	})
// }

// // ++++++++++++++++++++++++
// // Product Variant UPDATE
// // +++++++++++++++++++++++++
// func UpdateProductVariant(c *gin.Context, db *sql.DB) {
// 	// Ambil ID dari parameter
// 	idInt, idStr, ok := GetIDParam(c)
// 	if !ok {
// 		return
// 	}

// 	// Validasi apakah varian produk dengan ID ada
// 	if !ValidateRecordExistence(c, db, "product_variants", idInt) {
// 		return
// 	}

// 	// Bind JSON ke map
// 	var input map[string]interface{}
// 	if err := c.ShouldBindJSON(&input); err != nil {
// 		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Data tidak valid"})
// 		return
// 	}

// 	if len(input) == 0 {
// 		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Tidak ada data untuk diupdate"})
// 		return
// 	}

// 	// Handle logic diskon
// 	if dpRaw, exists := input["discount_price"]; exists {
// 		if dpRaw == nil || dpRaw == "" {
// 			input["discount_price"] = nil
// 			input["is_discounted"] = false
// 		} else {
// 			discountPrice, ok := dpRaw.(float64)
// 			if !ok {
// 				c.JSON(http.StatusBadRequest, gin.H{"error": "❌ discount_price harus berupa angka"})
// 				return
// 			}

// 			var currentPrice float64
// 			if priceRaw, ok := input["price"]; ok {
// 				price, ok := priceRaw.(float64)
// 				if !ok {
// 					c.JSON(http.StatusBadRequest, gin.H{"error": "❌ price harus berupa angka"})
// 					return
// 				}
// 				currentPrice = price
// 			} else {
// 				err := db.QueryRow("SELECT price FROM product_variants WHERE id = ?", idStr).Scan(&currentPrice)
// 				if err != nil {
// 					c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil harga varian"})
// 					return
// 				}
// 			}

// 			if discountPrice >= currentPrice {
// 				c.JSON(http.StatusBadRequest, gin.H{
// 					"error": fmt.Sprintf("⚠️ discount_price harus lebih murah dari price: %.2f >= %.2f", discountPrice, currentPrice),
// 				})
// 				return
// 			}

// 			input["is_discounted"] = true
// 		}
// 	}

// 	// Validasi jika ingin ubah product_id
// 	if pidRaw, exists := input["product_id"]; exists {
// 		pidFloat, ok := pidRaw.(float64)
// 		if !ok {
// 			c.JSON(http.StatusBadRequest, gin.H{"error": "❌ product_id harus berupa angka"})
// 			return
// 		}
// 		if !ValidateRecordExistence(c, db, "products", int(pidFloat)) {
// 			return
// 		}
// 		// Validasi apakah produk dengan ID tersebut punya is_varians = true
// 		isVarians, err := CheckIfVarians(db, int(input["product_id"].(float64)))
// 		if err != nil {
// 			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memeriksa varian produk"})
// 			return
// 		}
// 		if !isVarians {
// 			c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Produk tidak memiliki varian"})
// 			return
// 		}
// 	}

// 	// Update waktu
// 	input["updated_at"] = time.Now()

// 	// Siapkan query dinamis
// 	var fields []string
// 	var values []interface{}

// 	for key, value := range input {
// 		fields = append(fields, fmt.Sprintf("%s = ?", key))
// 		values = append(values, value)
// 	}

// 	values = append(values, idStr)

// 	query := fmt.Sprintf("UPDATE product_variants SET %s WHERE id = ?", strings.Join(fields, ", "))

// 	result, err := db.Exec(query, values...)
// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengupdate varian produk"})
// 		return
// 	}

// 	rowsAffected, _ := result.RowsAffected()
// 	if rowsAffected == 0 {
// 		c.JSON(http.StatusNotFound, gin.H{"message": "⚠️ Varian produk tidak ditemukan"})
// 		return
// 	}

// 	c.JSON(http.StatusOK, gin.H{
// 		"message": "✅ Varian produk berhasil diupdate",
// 		"updated": input,
// 	})
// }

// // ++++++++++++++++++++++++
// // Product Variant DELETE
// // +++++++++++++++++++++++++

// func DeleteProductVariant(c *gin.Context, db *sql.DB) {
// 	// Ambil ID dari parameter
// 	idInt, _, ok := GetIDParam(c)
// 	if !ok {
// 		return
// 	}

// 	// Validasi apakah varian produk dengan ID tersebut ada
// 	if !ValidateRecordExistence(c, db, "product_variants", idInt) {
// 		return
// 	}

// 	// Eksekusi DELETE
// 	_, err := db.Exec("DELETE FROM product_variants WHERE id = ?", idInt)
// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menghapus varian produk"})
// 		return
// 	}

// 	c.JSON(http.StatusOK, gin.H{"message": "✅ Varian produk berhasil dihapus"})
// }

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

// ===========================
// 🖼️ Product Image Management
// ===========================
// func ProductImageRoutes(r *gin.Engine, db *sql.DB) {
// 	api := r.Group("/api/v1/product-images")

// 	// 🟢 Public: GET semua gambar produk
// 	addRoute(api, "GET", "", []string{}, GetAllProductImages, db)

// 	// 🔐 Admin only: POST, PATCH, DELETE
// 	addRoute(api, "POST", "", []string{"admin"}, CreateProductImage, db)
// 	addRoute(api, "PATCH", "/:id", []string{"admin"}, UpdateProductImage, db)
// 	addRoute(api, "DELETE", "/:id", []string{"admin"}, DeleteProductImage, db)
// }

// // ++++++++++++++++++++++++
// //
// //	Images READ
// //
// // ++++++++++++++++++++++++
// func GetAllProductImages(c *gin.Context, db *sql.DB) {
// 	productID := c.Query("product_id") // bisa kosong (ambil semua)

// 	var rows *sql.Rows
// 	var err error

// 	if productID != "" {
// 		rows, err = db.Query(`SELECT id, product_id, image_url FROM product_images WHERE product_id = ?`, productID)
// 	} else {
// 		rows, err = db.Query(`SELECT id, product_id, image_url FROM product_images`)
// 	}

// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data gambar produk"})
// 		return
// 	}
// 	defer rows.Close()

// 	var images []ProductImageModel
// 	for rows.Next() {
// 		var img ProductImageModel
// 		if err := rows.Scan(&img.ID, &img.ProductID, &img.ImageURL); err != nil {
// 			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal membaca data gambar"})
// 			return
// 		}
// 		images = append(images, img)
// 	}

// 	c.JSON(http.StatusOK, gin.H{
// 		"message": "✅ Semua gambar berhasil diambil",
// 		"data":    images,
// 	})
// }

// // ++++++++++++++++++++++++
// //  Images CREATE
// // ++++++++++++++++++++++++

// // func CreateProductImage(c *gin.Context, db *sql.DB) {
// // 	productIDStr := c.PostForm("product_id")
// // 	productID, err := strconv.Atoi(productIDStr)
// // 	if err != nil || productID == 0 {
// // 		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ 'product_id' tidak valid atau kosong"})
// // 		return
// // 	}

// // 	// Validasi keberadaan produk
// // 	if !ValidateRecordExistence(c, db, "products", productID) {
// // 		return
// // 	}

// // 	// Ambil file dari form-data
// // 	file, _, err := c.Request.FormFile("image")
// // 	if err != nil {
// // 		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Gagal membaca file gambar"})
// // 		return
// // 	}
// // 	defer file.Close()

// // 	// Buffer file
// // 	buf := new(bytes.Buffer)
// // 	_, err = io.Copy(buf, file)
// // 	if err != nil {
// // 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal buffer file"})
// // 		return
// // 	}

// // 	// Inisialisasi ImageKit
// // 	ik := imagekit.NewFromParams(imagekit.NewParams{
// // 		PrivateKey:  os.Getenv("IMAGEKIT_PRIVATE_KEY"),
// // 		PublicKey:   os.Getenv("IMAGEKIT_PUBLIC_KEY"),
// // 		UrlEndpoint: os.Getenv("IMAGEKIT_ENDPOINT_URL"),
// // 	})

// // 	if os.Getenv("IMAGEKIT_PRIVATE_KEY") == "" {
// // 	log.Fatal("❌ IMAGEKIT_PRIVATE_KEY is not set")
// // 	}

// // 	// Upload ke ImageKit
// // 	uploadRes, err := ik.Media.Upload(imagekit.UploadParam{
// // 		File:     buf.Bytes(),
// // 		FileName: fmt.Sprintf("product_%d_%d.jpg", productID, time.Now().Unix()),
// // 	})
// // 	if err != nil {
// // 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal upload gambar ke ImageKit"})
// // 		return
// // 	}

// // 	// Simpan ke database
// // 	result, err := db.Exec(`INSERT INTO product_images (product_id, image_url) VALUES (?, ?)`, productID, uploadRes.URL)
// // 	if err != nil {
// // 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal simpan gambar ke database"})
// // 		return
// // 	}
// // 	resultID, _ := result.LastInsertId()

// // 	c.JSON(http.StatusCreated, gin.H{
// // 		"message":   "✅ Gambar produk berhasil diunggah",
// // 		"id":        resultID,
// // 		"image_url": uploadRes.URL,
// // 	})
// // }

// func CreateProductImage(c *gin.Context, db *sql.DB) {
// 	var input ProductImageModel

// 	if err := c.ShouldBindJSON(&input); err != nil {
// 		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format JSON tidak valid"})
// 		return
// 	}

// 	if input.ProductID == 0 || input.ImageURL == "" {
// 		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ 'product_id' dan 'image_url' wajib diisi"})
// 		return
// 	}

// 	// Cek apakah image_url valid
// 	if !strings.HasPrefix(input.ImageURL, "http://") && !strings.HasPrefix(input.ImageURL, "https://") {
// 		c.JSON(http.StatusBadRequest, gin.H{"error": "⚠️ URL gambar tidak valid"})
// 		return
// 	}

// 	// Cek apakah product_id valid
// 	if !ValidateRecordExistence(c, db, "products", int(input.ProductID)) {
// 		return
// 	}

// 	result, err := db.Exec(`INSERT INTO product_images (product_id, image_url) VALUES (?, ?)`, input.ProductID, input.ImageURL)
// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menambahkan gambar produk"})
// 		return
// 	}
// 	resultID, _ := result.LastInsertId()

// 	c.JSON(http.StatusCreated, gin.H{
// 		"message": "✅ Gambar produk berhasil ditambahkan",
// 		"id":      resultID,
// 	})
// }

// // ++++++++++++++++++++++++
// //
// //	Images UPDATE
// //
// // ++++++++++++++++++++++++
// func UpdateProductImage(c *gin.Context, db *sql.DB) {
// 	//idStr := c.Param("id")
// 	//id string to int
// 	idInt, id, ok := GetIDParam(c)
// 	if !ok {
// 		return
// 	}
// 	//Cek apakah id valid
// 	if !ValidateRecordExistence(c, db, "product_images", idInt) {
// 		return
// 	}

// 	var input ProductImageModel
// 	if err := c.ShouldBindJSON(&input); err != nil {
// 		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format data tidak valid"})
// 		return
// 	}

// 	if input.ImageURL == "" {
// 		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ 'image_url' wajib diisi"})
// 		return
// 	}
// 	// Cek apakah image_url valid
// 	if !strings.HasPrefix(input.ImageURL, "http://") && !strings.HasPrefix(input.ImageURL, "https://") {
// 		c.JSON(http.StatusBadRequest, gin.H{"error": "⚠️ URL gambar tidak valid"})
// 		return
// 	}

// 	// Siapkan query dan args
// 	var query string
// 	var args []interface{}

// 	if input.ProductID != 0 {
// 		// Jika ProductID baru diinput, validasi dulu
// 		if !ValidateRecordExistence(c, db, "products", int(input.ProductID)) {
// 			return
// 		}

// 		query = `UPDATE product_images SET product_id = ?, image_url = ? WHERE id = ?`
// 		args = []interface{}{input.ProductID, input.ImageURL, id}
// 	} else {
// 		query = `UPDATE product_images SET image_url = ? WHERE id = ?`
// 		args = []interface{}{input.ImageURL, id}
// 	}

// 	// Eksekusi update
// 	result, err := db.Exec(query, args...)
// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengupdate gambar produk"})
// 		return
// 	}

// 	rowsAffected, _ := result.RowsAffected()
// 	if rowsAffected == 0 {
// 		c.JSON(http.StatusNotFound, gin.H{"message": "⚠️ Gambar tidak ditemukan"})
// 		return
// 	}

// 	response := gin.H{
// 		"message":   "✅ Gambar produk berhasil diupdate",
// 		"id":        id,
// 		"image_url": input.ImageURL,
// 	}
// 	if input.ProductID != 0 {
// 		response["product_id"] = input.ProductID
// 	}

// 	c.JSON(http.StatusOK, response)
// }

// // ++++++++++++++++++++++++
// //
// //	Images DELETE
// //
// // ++++++++++++++++++++++++
// func DeleteProductImage(c *gin.Context, db *sql.DB) {
// 	//id string to int
// 	idInt, id, ok := GetIDParam(c)
// 	if !ok {
// 		return
// 	}
// 	//Cek apakah id valid
// 	if !ValidateRecordExistence(c, db, "product_images", idInt) {
// 		return
// 	}

// 	res, err := db.Exec(`DELETE FROM product_images WHERE id = ?`, id)
// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menghapus gambar produk"})
// 		return
// 	}
// 	rows, _ := res.RowsAffected()
// 	if rows == 0 {
// 		c.JSON(http.StatusNotFound, gin.H{"error": "⚠️ Gambar tidak ditemukan"})
// 		return
// 	}

// 	c.JSON(http.StatusOK, gin.H{
// 		"message": "✅ Gambar produk berhasil dihapus",
// 	})
// }

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

// // =========================
// // 🛒 Cart Management
// // =========================
// func CartRoutes(r *gin.Engine, db *sql.DB) {
// 	cartGroup := r.Group("/api/v1/carts")

// 	// 🔐 Khusus customer
// 	addRoute(cartGroup, "POST", "", []string{"user"}, CreateCart, db)
// 	addRoute(cartGroup, "GET", "/:id", []string{"user"}, GetCartByID, db)

// 	// // 🔧 Optional (belum diaktifkan)
// 	// addRoute(cartGroup, "PUT", "/addprice/:id", []string{"user"}, AddCartTotalPrice, db)
// 	// addRoute(cartGroup, "PUT", "/substractprice/:id", []string{"user"}, SubtractCartTotalPrice, db)

// 	// 🔐 Khusus admin untuk hapus cart
// 	addRoute(cartGroup, "DELETE", "/:id", []string{"admin"}, DeleteCart, db)
// }

// // ++++++++++++++++++++++++
// //
// //	Cart READ
// //
// // ++++++++++++++++++++++++
// func GetCartByID(c *gin.Context, db *sql.DB) {
// 	id, _, ok := GetIDParam(c)
// 	if !ok {
// 		return
// 	}

// 	if !ValidateRecordExistence(c, db, "carts", id) {
// 		return
// 	}

// 	var cart CartModel
// 	err := db.QueryRow("SELECT id, total_price, created_at, updated_at FROM carts WHERE id = ?", id).
// 		Scan(&cart.ID, &cart.TotalPrice, &cart.CreatedAt, &cart.UpdatedAt)

// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil cart"})
// 		return
// 	}

// 	c.JSON(http.StatusOK, gin.H{"data": cart})
// }

// // ++++++++++++++++++++++++
// //
// //	Cart CREATE
// //
// // ++++++++++++++++++++++++
// func CreateCart(c *gin.Context, db *sql.DB) {
// 	var cart CartModel

// 	if err := c.ShouldBindJSON(&cart); err != nil {
// 		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Data tidak valid, masukkan id user"})
// 		return
// 	}

// 	// Cek apakah user valid
// 	if !ValidateRecordExistence(c, db, "users", cart.ID) {
// 		return
// 	}

// 	cart.TotalPrice = 0 // Set total_price ke 0 saat membuat cart baru

// 	query := "INSERT INTO carts (id, total_price, created_at, updated_at) VALUES (?, ?, NOW(), NOW())"
// 	_, err := db.Exec(query, cart.ID, cart.TotalPrice)
// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal membuat cart"})
// 		return
// 	}

// 	c.JSON(http.StatusCreated, gin.H{"message": "✅ Cart berhasil dibuat", "data": cart})
// }

// // ++++++++++++++++++++++++
// //
// //	Cart UPDATE
// //
// // ++++++++++++++++++++++++
// // func EditCart(c *gin.Context) {
// // 	c.JSON(http.StatusCreated, gin.H{"message": "✅ Produk ditambahkan ke keranjang (dummy)"})
// // }

// // ++++++++++++++++++++++++
// //
// //	Cart DELETE
// //
// // ++++++++++++++++++++++++
// func DeleteCart(c *gin.Context, db *sql.DB) {
// 	id, _, ok := GetIDParam(c)
// 	if !ok {
// 		return
// 	}

// 	if !ValidateRecordExistence(c, db, "carts", id) {
// 		return
// 	}

// 	_, err := db.Exec("DELETE FROM carts WHERE id = ?", id)
// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menghapus cart"})
// 		return
// 	}

// 	c.JSON(http.StatusOK, gin.H{"message": "✅ Cart berhasil dihapus"})
// }

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// =========================
// 🛒 Cart Item Management
// =========================
func CartItemRoutes(r *gin.Engine, db *sql.DB) {
	// 🔐 Khusus customer (user)
	customerCartItem := r.Group("/api/v1/cart-items")
	customerCartItem.Use(AuthMiddleware(), RoleMiddleware("user"))
	{
		// Create cart item
		customerCartItem.POST("", func(c *gin.Context) {
			CreateCartItem(c, db)
		})

		// Get cart item by my ID
		customerCartItem.GET("/my", func(c *gin.Context) {
			MyCartItems(c, db)
		})

		// Update quantity (jika dibutuhkan nanti)
		customerCartItem.PATCH("/:id", func(c *gin.Context) {
			UpdateCartItemQuantity(c, db)
		})

		// Delete cart item
		customerCartItem.DELETE("/:id", func(c *gin.Context) {
			DeleteCartItem(c, db)
		})
	}
}

// func CartItemRoutes(r *gin.Engine, db *sql.DB) {
// 	api := r.Group("/api/v1/cart-items")

// 	// 🔐 Customer only: Semua route hanya untuk user role
// 	addRoute(api, "POST", "", []string{"user"}, CreateCartItem, db)             // Create cart item
// 	addRoute(api, "GET", "/my", []string{"user"}, MyCartItems, db)             // Get my cart items
// 	addRoute(api, "PATCH", "/:id", []string{"user"}, UpdateCartItemQuantity, db) // Update quantity
// 	addRoute(api, "DELETE", "/:id", []string{"user"}, DeleteCartItem, db)        // Delete cart item
// }

// +++++++++++++++++++++++++++++++++
// Cart Item CREATE MY CART
// +++++++++++++++++++++++++++++++++
func CreateCartItem(c *gin.Context, db *sql.DB) {
	userID := GetUserID(c) // cart_id juga

	var input struct {
		ProductID        int  `json:"product_id"`
		ProductVariantID *int `json:"product_variant_id"` // opsional
		Quantity         int  `json:"quantity"`
	}

	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Input tidak valid"})
		return
	}
	// Cek apakah input ada product_id dan quantity
	if input.ProductID == 0 || input.Quantity == 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ product_id dan quantity harus diisi"})
		return
	}
	// Cek apakah product_id valid
	if !ValidateRecordExistence(c, db, "products", input.ProductID) {
		return
	}

	// Cek apakah cart ada
	if !ValidateRecordExistence(c, db, "carts", userID) {
		return
	}

	// Ambil data product: is_varians, price, stock
	var isVarians bool
	var is_discounted *bool
	var productPrice, productStock, discount_price *int
	err := db.QueryRow(`
		SELECT is_varians, price, stock, is_discounted, discount_price FROM products WHERE id = ?
	`, input.ProductID).Scan(&isVarians, &productPrice, &productStock, &is_discounted, &discount_price)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Produk tidak ditemukan"})
		return
	}

	var stockAvailable int
	var pricePerItem int

	// Kalau pakai variant
	if isVarians {
		if input.ProductVariantID == nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Product variant harus diisi karena produk punya variasi"})
			return
		}
		var isDiscount bool
		var productVarPrice int
		var productVarDisprice *int
		// Ambil stok dari variant
		err := db.QueryRow(`
			SELECT stock, price, is_discounted, discount_price FROM product_variants WHERE id = ? AND product_id = ?
		`, int(*input.ProductVariantID), input.ProductID).Scan(&stockAvailable, &productVarPrice, &isDiscount, &productVarDisprice)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Variant tidak ditemukan untuk produk ini"})
			return
		}
		// Cek apakah variant ada diskon
		if isDiscount {
			pricePerItem = *productVarDisprice
		} else {
			pricePerItem = productVarPrice
		}
	} else {
		if input.ProductVariantID != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Produk ini tidak memiliki variasi, hapus product_variant_id"})
			return
		}
		stockAvailable = *productStock
		if *is_discounted {
			pricePerItem = *discount_price
		} else {
			pricePerItem = *productPrice
		}
	}

	if input.Quantity <= 0 || input.Quantity > stockAvailable {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Quantity melebihi stok atau tidak valid"})
		return
	}

	totalPrice := input.Quantity * pricePerItem

	// Tambahkan ke total cart dulu
	if err := AddToCartTotalPrice(db, userID, totalPrice); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update total harga cart"})
		return
	}

	// Insert cart item
	_, err = db.Exec(`
		INSERT INTO cart_items
		(cart_id, product_id, product_variant_id, quantity, price_per_item, total_price, created_at, updated_at)
		VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
	`, userID, input.ProductID, input.ProductVariantID, input.Quantity, pricePerItem, totalPrice)
	if err != nil {
		// Rollback kalau gagal insert
		_ = SubtractFromCartTotalPrice(db, userID, totalPrice)

		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menambahkan item ke cart"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "✅ Item berhasil ditambahkan ke cart"})
}

// +++++++++++++++++++++++++++++++++
// Cart Item READ MY CART
// +++++++++++++++++++++++++++++++++
func MyCartItems(c *gin.Context, db *sql.DB) {
	userID := GetUserID(c)

	// 1. Verify user exists
	if !ValidateRecordExistence(c, db, "users", userID) {
		c.JSON(http.StatusNotFound, gin.H{"error": "❌ User tidak ditemukan"})
		return
	}

	// 2. Get cart items
	cartItems, err := getCartItems(db, userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data cart"})
		return
	}

	if len(cartItems) == 0 {
		c.JSON(http.StatusOK, gin.H{
			"message": "⚠️ Keranjang kosong",
			"data":    []CartsBasicModel{},
		})
		return
	}

	// 3. Process each item
	var (
		updatedItems   []CartsBasicModel
		totalCartPrice int
		needsUpdate    bool
	)

	for _, item := range cartItems {
		// 3a. Get current product data
		product, variant, err := getProductDetails(db, item.ProductID, item.ProductVariantID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memeriksa produk"})
			return
		}

		// 3b. Determine current price
		currentPrice := getCurrentPrice(product, variant)
		currentStock := getCurrentStock(product, variant)

		// 3c. Check if update needed
		if item.PricePerItem != currentPrice {
			oldTotal := item.Quantity * item.PricePerItem
			newTotal := item.Quantity * currentPrice
			diff := newTotal - oldTotal

			// Update cart item
			err = updateCartItem(db, item.ID, currentPrice, newTotal)
			if err != nil {
				c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memperbarui item cart"})
				return
			}

			// Update cart total
			err = updateCartTotal(db, userID, diff)
			if err != nil {
				c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memperbarui total cart"})
				return
			}

			needsUpdate = true
			item.PricePerItem = currentPrice
			item.TotalPrice = newTotal
		}

		// 3d. Get product images
		_, thumbnails, err := getProductImages(db, item.ProductID)
		if err != nil || len(thumbnails) == 0 {
			thumbnails = []string{"https://via.placeholder.com/150"}
		}

		// 3e. Build response
		responseItem := CartsBasicModel{
			ID:               item.ID,
			CartID:           item.CartID,
			ProductID:        item.ProductID,
			ProductVariantID: item.ProductVariantID,
			Name:             product.Name,
			Stock:            &currentStock,
			Thumbnails:       []string{thumbnails[0]}, // Only first thumbnail
			Quantity:         item.Quantity,
			Price:            getBasePrice(product, variant),
			PricePerItem:     item.PricePerItem,
			TotalPrice:       item.TotalPrice,
		}

		if variant != nil {
			responseItem.Variants = []Variant{*variant}
		}

		updatedItems = append(updatedItems, responseItem)
		totalCartPrice += item.TotalPrice
	}

	// 4. If prices were updated, get fresh cart total
	if needsUpdate {
		totalCartPrice, err = getCartTotal(db, userID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memverifikasi total cart"})
			return
		}
	}

	c.JSON(http.StatusOK, gin.H{
		"message":          "✅ Berhasil mengambil item cart",
		"data":             updatedItems,
		"total_cart_price": totalCartPrice,
	})
}

// Reused helper functions
func getCartItems(db *sql.DB, cartID int) ([]CartItemModel, error) {
	rows, err := db.Query(`
        SELECT id, cart_id, product_id, product_variant_id,
               quantity, price_per_item, total_price
        FROM cart_items
        WHERE cart_id = ?`, cartID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var items []CartItemModel
	for rows.Next() {
		var item CartItemModel
		err := rows.Scan(
			&item.ID,
			&item.CartID,
			&item.ProductID,
			&item.ProductVariantID,
			&item.Quantity,
			&item.PricePerItem,
			&item.TotalPrice,
		)
		if err != nil {
			return nil, err
		}
		items = append(items, item)
	}
	return items, nil
}

func getProductDetails(db *sql.DB, productID int, variantID *int) (ProductsBasicModel, *Variant, error) {
	// Get basic product info
	products, err := getBasicProducts(db, "")
	if err != nil {
		return ProductsBasicModel{}, nil, err
	}

	var product ProductsBasicModel
	for _, p := range products {
		if p.ID == productID {
			product = p
			break
		}
	}

	// Get variant if exists
	var variant *Variant
	if variantID != nil {
		variants, err := getProductVariants(db, productID)
		if err != nil {
			return ProductsBasicModel{}, nil, err
		}

		for _, v := range variants {
			if v.ID == *variantID {
				variant = &v
				break
			}
		}
	}

	return product, variant, nil
}

func getCurrentPrice(product ProductsBasicModel, variant *Variant) int {
	if variant != nil {
		if variant.DiscountPrice != nil {
			return *variant.DiscountPrice
		}
		return variant.Price
	}

	if product.DiscountPrice != nil {
		return *product.DiscountPrice
	}
	return *product.Price
}

func getBasePrice(product ProductsBasicModel, variant *Variant) int {
	if variant != nil {
		return variant.Price
	}
	return *product.Price
}

func getCurrentStock(product ProductsBasicModel, variant *Variant) int {
	if variant != nil {
		return variant.Stock
	}
	return *product.Stock
}

func updateCartItem(db *sql.DB, itemID int, newPrice int, newTotal int) error {
	_, err := db.Exec(`
        UPDATE cart_items
        SET price_per_item = ?, total_price = ?, updated_at = NOW()
        WHERE id = ?`,
		newPrice, newTotal, itemID)
	return err
}

func updateCartTotal(db *sql.DB, cartID int, diff int) error {
	if diff > 0 {
		_, err := db.Exec(`
            UPDATE carts
            SET total_price = total_price + ?, updated_at = NOW()
            WHERE id = ?`,
			diff, cartID)
		return err
	} else if diff < 0 {
		_, err := db.Exec(`
            UPDATE carts
            SET total_price = total_price - ?, updated_at = NOW()
            WHERE id = ?`,
			-diff, cartID)
		return err
	}
	return nil
}

func getCartTotal(db *sql.DB, cartID int) (int, error) {
	var total int
	err := db.QueryRow("SELECT total_price FROM carts WHERE id = ?", cartID).Scan(&total)
	return total, err
}

// func MyCartItems(c *gin.Context, db *sql.DB) {
// 	userID := GetUserID(c) // Ini cart_id juga

// 	// Cek apakah cart ada
// 	if !ValidateRecordExistence(c, db, "carts", userID) {
// 		return
// 	}

// 	rows, err := db.Query(`
// 		SELECT id, cart_id, product_id, product_variant_id, quantity, price_per_item, total_price
// 		FROM cart_items
// 		WHERE cart_id = ?
// 	`, userID)
// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data cart item"})
// 		return
// 	}
// 	defer rows.Close()

// 	var items []CartsBasicModel
// 	for rows.Next() {
// 		var item CartsBasicModel
// 		if err := rows.Scan(
// 			&item.ID,
// 			&item.CartID,
// 			&item.ProductID,
// 			&item.ProductVariantID,
// 			&item.Quantity,
// 			&item.PricePerItem,
// 			&item.TotalPrice,
// 		); err != nil {
// 			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memproses data cart item"})
// 			return
// 		}
// 		items = append(items, item)
// 	}

// 	if err = rows.Err(); err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Kesalahan saat membaca data cart item"})
// 		return
// 	}
// 	 // 1. Ambil data dasar produk (dengan filter kategori jika ada)
//     products, err := getBasicProducts(db, "")
//     if err != nil {
//         c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data produk"})
//         return
//     }

//     // 2. Ambil gambar & varian untuk setiap produk
//     for i := range products {
//         // Ambil gambar
//         _, thumbnails, err := getProductImages(db, products[i].ID)
//         if err != nil {
//             c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil gambar produk"})
//             return
//         }
//         products[i].Thumbnails = thumbnails

//         // Ambil varian (jika produk punya varian)
//         if products[i].IsVarians {
//             variants, err := getProductVariants(db, products[i].ID)
//             if err != nil {
//                 c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil varian produk"})
//                 return
//             }
//             products[i].Variants = variants
//             products[i].Price = nil // Harga utama di-nullkan jika ada varian
//             products[i].DiscountPrice = nil
//             products[i].Stock = nil
//         }
//     }

// 	if len(items) == 0 {
// 		c.JSON(http.StatusOK, gin.H{
// 			"message": "⚠️ Keranjang kosong",
// 			"data":    []CartItemModel{},
// 		})
// 		return
// 	}

// 	c.JSON(http.StatusOK, gin.H{
// 		"message": "✅ Berhasil mengambil item dalam cart kamu",
// 		"data":    items,
// 	})
// }

// +++++++++++++++++++++++++++++++++
// Cart Item UPDATE MY CART
// +++++++++++++++++++++++++++++++++
func UpdateCartItemQuantity(c *gin.Context, db *sql.DB) {
	userID := GetUserID(c)
	itemID := c.Param("id")

	var input struct {
		Quantity int `json:"quantity"`
	}
	if err := c.ShouldBindJSON(&input); err != nil || input.Quantity <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Quantity tidak valid atau tidak diisi"})
		return
	}

	// Ambil data cart item dulu (harus milik user yang login)
	var cartID, productID, oldQuantity, pricePerItem int
	var productVariantID *int

	err := db.QueryRow(`
		SELECT cart_id, product_id, product_variant_id, quantity, price_per_item
		FROM cart_items WHERE id = ? AND cart_id = ?
	`, itemID, userID).Scan(&cartID, &productID, &productVariantID, &oldQuantity, &pricePerItem)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "❌ Item tidak ditemukan atau bukan milik kamu"})
		return
	}

	// Cek apakah produk menggunakan variant
	var isVarians bool
	if productVariantID == nil {
		isVarians = false
	} else {
		isVarians = true
	}

	var stockAvailable int
	if isVarians {
		if productVariantID == nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Variant wajib karena produk punya variasi"})
			return
		}
		err = db.QueryRow(`SELECT stock FROM product_variants WHERE id = ?`, *productVariantID).Scan(&stockAvailable)
	} else {
		err = db.QueryRow(`SELECT stock FROM products WHERE id = ?`, productID).Scan(&stockAvailable)
	}
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Gagal ambil data stok"})
		return
	}

	if input.Quantity > stockAvailable {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Quantity melebihi stok"})
		return
	}

	oldTotal := oldQuantity * pricePerItem
	newTotal := input.Quantity * pricePerItem
	diff := newTotal - oldTotal

	// Update total harga di cart
	if diff > 0 {
		if err := AddToCartTotalPrice(db, userID, diff); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update total harga cart"})
			return
		}
	} else if diff < 0 {
		if err := SubtractFromCartTotalPrice(db, userID, -diff); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update total harga cart"})
			return
		}
	}

	// Update cart item
	_, err = db.Exec(`
		UPDATE cart_items
		SET quantity = ?, total_price = ?, updated_at = NOW()
		WHERE id = ?
	`, input.Quantity, newTotal, itemID)

	if err != nil {
		// Balikin total price cart kalau gagal update item
		if diff > 0 {
			_ = SubtractFromCartTotalPrice(db, userID, diff)
		} else if diff < 0 {
			_ = AddToCartTotalPrice(db, userID, -diff)
		}

		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update item"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "✅ Quantity berhasil diupdate"})
}

func UpdateVariantCartItem(c *gin.Context, db *sql.DB) {
	userID := GetUserID(c)
	cartItemID, _, ok := GetIDParam(c)
	if !ok {
		return
	}
	var input struct {
		VariantID int `json:"variant_id"`
	}
	if err := c.ShouldBindJSON(&input); err != nil || input.VariantID <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Variant ID tidak valid atau tidak diisi"})
		return
	}

	// Mulai transaksi
	tx, err := db.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memulai transaksi"})
		return
	}
	defer tx.Rollback()

	// 1. Ambil data cart item saat ini
	var currentCartItem struct {
		CartID        int
		ProductID     int
		OldVariantID  *int
		Quantity      int
		OldPrice      int
		OldTotalPrice int
	}

	err = tx.QueryRow(`
        SELECT cart_id, product_id, product_variant_id, quantity, price_per_item, total_price
        FROM cart_items
        WHERE id = ? AND cart_id = ?`, cartItemID, userID).Scan(
		&currentCartItem.CartID,
		&currentCartItem.ProductID,
		&currentCartItem.OldVariantID,
		&currentCartItem.Quantity,
		&currentCartItem.OldPrice,
		&currentCartItem.OldTotalPrice,
	)

	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "❌ Item cart tidak ditemukan atau bukan milik kamu"})
		return
	}

	// 2. Verifikasi variant baru termasuk dalam product yang sama
	var variantProductID int
	err = tx.QueryRow(`
        SELECT product_id FROM product_variants
        WHERE id = ?`, input.VariantID).Scan(&variantProductID)

	if err != nil || variantProductID != currentCartItem.ProductID {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Variant tidak valid untuk produk ini"})
		return
	}

	// 3. Ambil data variant baru
	var newVariant struct {
		Price         int
		DiscountPrice *int
		Stock         int
		Name          string
	}

	err = tx.QueryRow(`
        SELECT price, discount_price, stock, name
        FROM product_variants
        WHERE id = ?`, input.VariantID).Scan(
		&newVariant.Price,
		&newVariant.DiscountPrice,
		&newVariant.Stock,
		&newVariant.Name,
	)

	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data variant"})
		return
	}

	// 4. Cek stok
	if currentCartItem.Quantity > newVariant.Stock {
		c.JSON(http.StatusBadRequest, gin.H{
			"error":           "❌ Stok tidak mencukupi",
			"stock_available": newVariant.Stock,
		})
		return
	}

	// 5. Tentukan harga yang akan digunakan (diskon atau normal)
	newPrice := newVariant.Price
	if newVariant.DiscountPrice != nil {
		newPrice = *newVariant.DiscountPrice
	}

	// 6. Hitung perbedaan harga
	newTotalPrice := currentCartItem.Quantity * newPrice
	priceDiff := newTotalPrice - currentCartItem.OldTotalPrice

	// 7. Update cart item
	_, err = tx.Exec(`
        UPDATE cart_items
        SET product_variant_id = ?,
            price_per_item = ?,
            total_price = ?,
            updated_at = NOW()
        WHERE id = ?`,
		input.VariantID,
		newPrice,
		newTotalPrice,
		cartItemID,
	)

	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update variant item"})
		return
	}

	// 8. Update total cart
	_, err = tx.Exec(`
        UPDATE carts
        SET total_price = total_price + ?,
            updated_at = NOW()
        WHERE id = ?`,
		priceDiff,
		currentCartItem.CartID,
	)

	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update total cart"})
		return
	}

	// Commit transaksi jika semua berhasil
	if err := tx.Commit(); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menyimpan perubahan"})
		return
	}

	// 9. Response dengan data terupdate
	updatedItem := struct {
		ID               int    `json:"id"`
		CartID           int    `json:"cart_id"`
		ProductID        int    `json:"product_id"`
		ProductVariantID int    `json:"product_variant_id"`
		VariantName      string `json:"variant_name"`
		Quantity         int    `json:"quantity"`
		Price            int    `json:"price"`
		DiscountPrice    *int   `json:"discount_price,omitempty"`
		PricePerItem     int    `json:"price_per_item"`
		TotalPrice       int    `json:"total_price"`
		Stock            int    `json:"stock"`
	}{
		ID:               cartItemID,
		CartID:           currentCartItem.CartID,
		ProductID:        currentCartItem.ProductID,
		ProductVariantID: input.VariantID,
		VariantName:      newVariant.Name,
		Quantity:         currentCartItem.Quantity,
		Price:            newVariant.Price,
		DiscountPrice:    newVariant.DiscountPrice,
		PricePerItem:     newPrice,
		TotalPrice:       newTotalPrice,
		Stock:            newVariant.Stock,
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Variant berhasil diupdate",
		"data":    updatedItem,
	})
}

// +++++++++++++++++++++++++++++++++
// Cart Item DELETE MY CART
// +++++++++++++++++++++++++++++++++
func DeleteCartItem(c *gin.Context, db *sql.DB) {
	userID := GetUserID(c)
	itemID, _, ok := GetIDParam(c)
	if !ok {
		return
	}
	// Ambil total_price dari cart item & pastikan item milik user
	var cartID, itemTotal int
	err := db.QueryRow(`
		SELECT cart_id, total_price
		FROM cart_items
		WHERE id = ? AND cart_id = ?
	`, itemID, userID).Scan(&cartID, &itemTotal)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "❌ Item tidak ditemukan atau bukan milik kamu"})
		return
	}

	// Kurangi total harga di cart
	if err := SubtractFromCartTotalPrice(db, cartID, itemTotal); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update total harga cart"})
		return
	}

	// Hapus item
	_, err = db.Exec(`DELETE FROM cart_items WHERE id = ?`, itemID)
	if err != nil {
		// Balikin harga kalau gagal hapus item
		_ = AddToCartTotalPrice(db, cartID, itemTotal)
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menghapus item"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "🗑️ Item berhasil dihapus dari cart"})
}

// Untuk rollback stok ke inventory
func ReturnStockToInventory(db *sql.DB, items []OrderItemModel) error {
	tx, err := db.Begin()
	if err != nil {
		return err
	}
	defer func() {
		if err != nil {
			tx.Rollback()
		} else {
			tx.Commit()
		}
	}()

	for _, item := range items {
		if item.ProductVariantID != nil {
			_, err = tx.Exec("UPDATE product_variants SET stock = stock + ? WHERE id = ?", item.Quantity, *item.ProductVariantID)
		} else {
			_, err = tx.Exec("UPDATE products SET stock = stock + ? WHERE id = ?", item.Quantity, item.ProductID)
		}
		if err != nil {
			return err
		}
	}
	return nil
}

// =========================
// 📦 Order Management
// =========================
func OrderRoutes(r *gin.Engine, db *sql.DB) {
	orderGroup := r.Group("/api/v1/orders")

	// 👤 USER: Customer routes (create order, lihat order sendiri, cancel order)
	addRoute(orderGroup, "POST", "", []string{"user"}, CreateOrder, db)           // Buat order dari cart
	addRoute(orderGroup, "GET", "/my", []string{"user"}, GetMyOrders, db)         // Lihat semua order milik user saat ini
	addRoute(orderGroup, "PUT", "/:id/cancel", []string{"user"}, CancelOrder, db) // Cancel order milik sendiri
	addRoute(orderGroup, "PUT", "/:id/finish", []string{"employee","admin"}, FinishOrder, db) // Selesaikan order (untuk employee dan admin)

}

// ++++++++++++++++++++++++
//
//	Order READ
//
// ++++++++++++++++++++++++
func GetMyOrders(c *gin.Context, db *sql.DB) {
	userID := c.GetInt("user_id")

	// Ambil semua order user
	orderRows, err := db.Query(`
		SELECT id, user_id, cart_user_id, status, total_price, expired_at, created_at, updated_at
		FROM orders
		WHERE user_id = ?
		ORDER BY created_at DESC
	`, userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal mengambil data order"})
		return
	}
	defer orderRows.Close()

	// Struct gabungan order dan items
	type OrderWithItems struct {
		Order OrderModel       `json:"order"`
		Items []OrderItemModel `json:"items"`
	}

	var allOrders []OrderWithItems

	for orderRows.Next() {
		var order OrderModel
		err := orderRows.Scan(
			&order.ID, &order.UserID, &order.CartUserID, &order.Status,
			&order.TotalPrice, &order.ExpiredAt, &order.CreatedAt, &order.UpdatedAt,
		)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal membaca data order"})
			return
		}

		// Ambil order items
		itemRows, err := db.Query(`
			SELECT id, order_id, product_id, product_variant_id, quantity, price_at_purchase, total_price
			FROM order_items
			WHERE order_id = ?
		`, order.ID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal mengambil item order"})
			return
		}

		var items []OrderItemModel
		for itemRows.Next() {
			var item OrderItemModel
			err := itemRows.Scan(
				&item.ID, &item.OrderID, &item.ProductID, &item.ProductVariantID,
				&item.Quantity, &item.PriceAtPurchase, &item.TotalPrice,
			)
			if err != nil {
				c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal membaca item order"})
				itemRows.Close()
				return
			}
			items = append(items, item)
		}
		itemRows.Close()

		allOrders = append(allOrders, OrderWithItems{
			Order: order,
			Items: items,
		})
	}

	if len(allOrders) == 0 {
		c.JSON(http.StatusOK, gin.H{"message": "Belum ada order"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"orders": allOrders,
	})
}

// ++++++++++++++++++++++++
//
//	Order CREATE
//
// ++++++++++++++++++++++++
// func CreateStockReservationTx(tx *sql.Tx, orderID, userID int, items []OrderItemModel) error {
// 	now := time.Now()
//
// 	// 1. Insert ke temp_stock_reservations
// 	res, err := tx.Exec(`
// 		INSERT INTO temp_stock_reservations (user_id, order_id, reserved_at, expired_at, created_at, updated_at)
// 		VALUES (?, ?, ?, ?, ?, ?)`,
// 		userID, orderID, now, now, now, now)
// 	if err != nil {
// 		return fmt.Errorf("gagal insert stock reservation: %v", err)
// 	}
// 	tempResID, _ := res.LastInsertId()
//
// 	// 2. Untuk setiap item, simpan detail dan kurangi stok
// 	for _, item := range items {
// 		// Simpan detail
// 		_, err := tx.Exec(`
// 			INSERT INTO temp_stock_details (temp_reservation_id, product_id, product_variant_id, quantity, created_at, updated_at)
// 			VALUES (?, ?, ?, ?, ?, ?)`,
// 			tempResID, item.ProductID, item.ProductVariantID, item.Quantity, now, now)
// 		if err != nil {
// 			return fmt.Errorf("gagal insert detail stock: %v", err)
// 		}
//
// 		// Kurangi stok
// 		if item.ProductVariantID != nil {
// 			_, err = tx.Exec(`
// 				UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?`,
// 				item.Quantity, *item.ProductVariantID, item.Quantity)
// 		} else {
// 			_, err = tx.Exec(`
// 				UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?`,
// 				item.Quantity, item.ProductID, item.Quantity)
// 		}
// 		if err != nil {
// 			return fmt.Errorf("gagal mengurangi stok: %v", err)
// 		}
// 	}
//
// 	return nil
// }

func CreateOrder(c *gin.Context, db *sql.DB) {
	userID := GetUserID(c)

	// Cek cart & item-nya
	var cart CartModel
	err := db.QueryRow(`SELECT id, total_price FROM carts WHERE id = ?`, userID).
		Scan(&cart.ID, &cart.TotalPrice)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Cart tidak ditemukan"})
		return
	}

	rows, err := db.Query(`
		SELECT id, product_id, product_variant_id, quantity, price_per_item, total_price
		FROM cart_items WHERE cart_id = ?
	`, userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil cart items"})
		return
	}
	defer rows.Close()

	var items []OrderItemModel
	for rows.Next() {
		var item OrderItemModel
		var variantID sql.NullInt64
		if err := rows.Scan(&item.ID, &item.ProductID, &variantID, &item.Quantity, &item.PriceAtPurchase, &item.TotalPrice); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal membaca cart item"})
			return
		}
		if variantID.Valid {
			id := int(variantID.Int64)
			item.ProductVariantID = &id
		}
		items = append(items, item)
	}

	if len(items) == 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "⚠️ Cart kosong, tidak bisa membuat order"})
		return
	}

	now := time.Now()
	expiration := now.Add(36000)

	// Transaksi pembuatan order
	tx, err := db.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memulai transaksi"})
		return
	}

	// Insert ke orders
	res, err := tx.Exec(`
		INSERT INTO orders (user_id, cart_user_id, status, total_price, expired_at, created_at, updated_at)
		VALUES (?, ?, ?, ?, ?, ?, ?)`,
		userID, cart.ID, "pending", cart.TotalPrice, expiration, now, now)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal membuat order"})
		return
	}
	orderID, _ := res.LastInsertId()

	// Simpan order_items
	for _, item := range items {
		_, err := tx.Exec(`
			INSERT INTO order_items (order_id, product_id, product_variant_id, quantity, price_at_purchase, total_price)
			VALUES (?, ?, ?, ?, ?, ?)`,
			orderID, item.ProductID, item.ProductVariantID, item.Quantity, item.PriceAtPurchase, item.TotalPrice)
		if err != nil {
			tx.Rollback()
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menyimpan order item"})
			return
		}
	}

	// Kurangi stok di products
	for _, item := range items {
		// Kurangi stok
		if item.ProductVariantID != nil {
			_, err = tx.Exec(`
				UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?`,
				item.Quantity, *item.ProductVariantID, item.Quantity)
		} else {
			_, err = tx.Exec(`
				UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?`,
				item.Quantity, item.ProductID, item.Quantity)
		}
		if err != nil {
			tx.Rollback()
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengurangi stok"})
			return
		}
	}

	// Hapus cart items dan reset total cart
	_, _ = tx.Exec(`DELETE FROM cart_items WHERE cart_id = ?`, userID)
	_, _ = tx.Exec(`UPDATE carts SET total_price = 0 WHERE id = ?`, userID)

	if err := tx.Commit(); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menyelesaikan order"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message":    "✅ Order berhasil dibuat",
		"order_id":   orderID,
		"expired_at": expiration,
	})
}

// ++++++++++++++++++++++++
//
//	Order UPDATE
//
// ++++++++++++++++++++++++
// // start helper
// func DeleteStockReservationTx(tx *sql.Tx, orderID int) error {
// 	// Hapus detail reservasi stok
// 	_, err := tx.Exec(`
// 		DELETE FROM temp_stock_details
// 		WHERE temp_reservation_id IN (SELECT id FROM temp_stock_reservations WHERE order_id = ?)
// 	`, orderID)
// 	if err != nil {
// 		return fmt.Errorf("gagal hapus temp_stock_details: %v", err)
// 	}
//
// 	// Hapus reservasi stok
// 	_, err = tx.Exec(`
// 		DELETE FROM temp_stock_reservations
// 		WHERE order_id = ?
// 	`, orderID)
// 	if err != nil {
// 		return fmt.Errorf("gagal hapus temp_stock_reservations: %v", err)
// 	}
//
// 	return nil
// }

func ReturnStockToInventoryTx(tx *sql.Tx, items []OrderItemModel) error {
	for _, item := range items {
		// Update stok produk atau produk varian
		if item.ProductVariantID != nil {
			// Update stok untuk produk varian
			_, err := tx.Exec(`
				UPDATE product_variants
				SET stock = stock + ?
				WHERE id = ?
			`, item.Quantity, *item.ProductVariantID)
			if err != nil {
				return fmt.Errorf("gagal mengembalikan stok ke produk varian: %v", err)
			}
		} else {
			// Update stok untuk produk
			_, err := tx.Exec(`
				UPDATE products
				SET stock = stock + ?
				WHERE id = ?
			`, item.Quantity, item.ProductID)
			if err != nil {
				return fmt.Errorf("gagal mengembalikan stok ke produk: %v", err)
			}
		}
	}

	return nil
}

func GetOrderItems(db *sql.DB, orderID int) ([]OrderItemModel, error) {
	// Query untuk mengambil item berdasarkan order_id
	rows, err := db.Query(`
		SELECT oi.id, oi.order_id, oi.product_id, oi.product_variant_id, oi.quantity, oi.price_at_purchase, oi.total_price
		FROM order_items oi
		WHERE oi.order_id = ?
	`, orderID)
	if err != nil {
		return nil, fmt.Errorf("gagal mengambil data order items: %v", err)
	}
	defer rows.Close()

	var orderItems []OrderItemModel
	for rows.Next() {
		var item OrderItemModel
		// Scan hasil query ke dalam struct OrderItemModel
		if err := rows.Scan(&item.ID, &item.OrderID, &item.ProductID, &item.ProductVariantID, &item.Quantity, &item.PriceAtPurchase, &item.TotalPrice); err != nil {
			return nil, fmt.Errorf("gagal memindahkan data ke struct: %v", err)
		}
		// Tambahkan item ke dalam slice orderItems
		orderItems = append(orderItems, item)
	}

	// Pastikan tidak ada error setelah iterasi
	if err := rows.Err(); err != nil {
		return nil, fmt.Errorf("error dalam iterasi rows: %v", err)
	}

	return orderItems, nil
}

//end helper

func CancelOrder(c *gin.Context, db *sql.DB) {
	userID := c.GetInt("user_id")
	orderIDStr := c.Param("id")
	orderID, err := strconv.Atoi(orderIDStr)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "ID tidak valid"})
		return
	}

	// Cek order-nya
	var order OrderModel
	err = db.QueryRow(`
		SELECT id, user_id, status FROM orders
		WHERE id = ?
	`, orderID).Scan(&order.ID, &order.UserID, &order.Status)
	if err == sql.ErrNoRows {
		c.JSON(http.StatusNotFound, gin.H{"error": "Order tidak ditemukan"})
		return
	} else if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal cek order"})
		return
	}

	if order.UserID != userID {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "Tidak bisa membatalkan order orang lain"})
		return
	}

	if order.Status != "pending" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Order tidak bisa dibatalkan"})
		return
	}

	// Ambil semua itemnya buat kembalikan stok
	items, err := GetOrderItems(db, orderID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal ambil item order"})
		return
	}

	tx, err := db.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal mulai transaksi"})
		return
	}

	// Ubah status jadi cancelled
	_, err = tx.Exec(`UPDATE orders SET status = ?, updated_at = ? WHERE id = ?`, "cancelled", time.Now(), orderID)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal mengubah status order"})
		return
	}

	// Kembalikan stok products
	err = ReturnStockToInventoryTx(tx, items)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal mengembalikan stok"})
		return
	}

	err = tx.Commit()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal menyimpan pembatalan"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "Order berhasil dibatalkan"})
}

func FinishOrder(c *gin.Context, db *sql.DB) {
	orderIDStr := c.Param("id")
	orderID, err := strconv.Atoi(orderIDStr)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "ID tidak valid"})
		return
	}

	// Cek order-nya
	var order OrderModel
	err = db.QueryRow(`
		SELECT id, user_id, status FROM orders
		WHERE id = ?
	`, orderID).Scan(&order.ID, &order.UserID, &order.Status)
	if err == sql.ErrNoRows {
		c.JSON(http.StatusNotFound, gin.H{"error": "Order tidak ditemukan"})
		return
	} else if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal cek order"})
		return
	}

	if order.Status != "pending" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Order sudah expired, batal, atau selesai"})
		return
	}

	tx, err := db.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal mulai transaksi"})
		return
	}

	// Ubah status jadi done
	_, err = tx.Exec(`UPDATE orders SET status = ?, updated_at = ? WHERE id = ?`, "done", time.Now(), orderID)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal mengubah status order"})
		return
	}

	err = tx.Commit()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal menyimpan pembatalan"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "Order berhasil diselesaikan"})
}

// ++++++++++++++++++++++++
//
//	Order DELETE
//
// ++++++++++++++++++++++++
// func DeleteStockReservationAndReturn(orderID int, db *sql.DB) {
// 	// Memulai transaksi
// 	tx, err := db.Begin()
// 	if err != nil {
// 		log.Printf("Failed to begin transaction: %v", err)
// 		return
// 	}
//
// 	// Pastikan kita melakukan rollback jika terjadi error
// 	defer tx.Rollback()
//
// 	// Ambil semua item yang terkait dengan order dari temp_stock_details
// 	rows, err := tx.Query(`
// 		SELECT tsd.product_id, tsd.product_variant_id, tsd.quantity
// 		FROM temp_stock_details tsd
// 		LEFT JOIN temp_stock_reservations tsr ON tsr.id = tsd.temp_reservation_id
// 		WHERE tsr.order_id = ?
// 	`, orderID)
// 	if err != nil {
// 		log.Printf("Failed to get stock details for order %d: %v", orderID, err)
// 		return
// 	}
// 	defer rows.Close()
//
// 	var stockDetails []struct {
// 		ProductID        int
// 		ProductVariantID *int
// 		Quantity         int
// 	}
//
// 	// Ambil detail produk dan varian untuk dikembalikan ke inventory
// 	for rows.Next() {
// 		var detail struct {
// 			ProductID        int
// 			ProductVariantID *int
// 			Quantity         int
// 		}
// 		if err := rows.Scan(&detail.ProductID, &detail.ProductVariantID, &detail.Quantity); err != nil {
// 			log.Printf("Failed to scan stock details for order %d: %v", orderID, err)
// 			return
// 		}
// 		stockDetails = append(stockDetails, detail)
// 	}
//
// 	// Hapus temp_stock_reservations dan temp_stock_details
// 	_, err = tx.Exec(`
// 		DELETE tsd FROM temp_stock_details tsd
// 		JOIN temp_stock_reservations tsr ON tsr.id = tsd.temp_reservation_id
// 		WHERE tsr.order_id = ?
// 	`, orderID)
// 	if err != nil {
// 		log.Printf("Failed to delete temp stock details for order %d: %v", orderID, err)
// 		return
// 	}
//
// 	_, err = tx.Exec(`
// 		DELETE FROM temp_stock_reservations WHERE order_id = ?
// 	`, orderID)
// 	if err != nil {
// 		log.Printf("Failed to delete temp stock reservations for order %d: %v", orderID, err)
// 		return
// 	}
//
// 	// Kembalikan stok ke inventory
// 	for _, detail := range stockDetails {
// 		var execErr error
// 		if detail.ProductVariantID != nil {
// 			// Update stok untuk product variant
// 			_, execErr = tx.Exec(`
// 				UPDATE product_variants
// 				SET stock = stock + ?
// 				WHERE id = ?
// 			`, detail.Quantity, *detail.ProductVariantID)
// 		} else {
// 			// Update stok untuk product utama
// 			_, execErr = tx.Exec(`
// 				UPDATE products
// 				SET stock = stock + ?
// 				WHERE id = ?
// 			`, detail.Quantity, detail.ProductID)
// 		}
// 		if execErr != nil {
// 			log.Printf("Failed to update stock for product %d or variant %v: %v", detail.ProductID, detail.ProductVariantID, execErr)
// 			return
// 		}
// 	}
//
// 	// Commit transaksi jika semuanya berhasil
// 	if err := tx.Commit(); err != nil {
// 		log.Printf("Failed to commit transaction: %v", err)
// 	}
// }

// func CheckAndExpireOrders(c *gin.Context, db *sql.DB) {
// 	// Query untuk mengambil semua order dengan status 'waitToBuy' dan timer_expiration yang lewat
// 	rows, err := db.Query(`
// 		SELECT o.id, o.user_id, o.timer_expiration
// 		FROM orders o
// 		WHERE o.status = 'waitToBuy' AND o.timer_expiration < NOW()
// 	`)
// 	if err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to check expired orders"})
// 		return
// 	}
// 	defer rows.Close()
//
// 	var expiredOrders []struct {
// 		OrderID int `json:"order_id"`
// 		UserID  int `json:"user_id"`
// 	}
//
// 	// Ambil semua order yang sudah kadaluarsa
// 	for rows.Next() {
// 		var order struct {
// 			OrderID int `json:"order_id"`
// 			UserID  int `json:"user_id"`
// 		}
// 		if err := rows.Scan(&order.OrderID, &order.UserID); err != nil {
// 			c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to scan order data"})
// 			return
// 		}
// 		expiredOrders = append(expiredOrders, order)
// 	}
//
// 	// Pastikan tidak ada error setelah iterasi rows
// 	if err := rows.Err(); err != nil {
// 		c.JSON(http.StatusInternalServerError, gin.H{"error": "Error iterating rows"})
// 		return
// 	}
//
// 	// Proses setiap order yang kadaluarsa
// 	for _, order := range expiredOrders {
// 		// Update status order menjadi 'expired'
// 		_, err := db.Exec(`
// 			UPDATE orders
// 			SET status = 'expired'
// 			WHERE id = ?
// 		`, order.OrderID)
// 		if err != nil {
// 			c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to update order status"})
// 			return
// 		}
//
// 		// Hapus temp_stock_reservations terkait order ini dan kembalikan stok ke inventory
// 		DeleteStockReservationAndReturn(order.OrderID, db)
// 	}
//
// 	// Response sukses
// 	c.JSON(http.StatusOK, gin.H{"message": "Expired orders have been processed"})
// }
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~


// =========================
// ➕ RestockRequest Management
// =========================
func RestockRequestRoutes(r *gin.Engine, db *sql.DB) {
	// 🔐 Khusus user
	userRestock := r.Group("/api/v1/restock-requests")
	userRestock.Use(AuthMiddleware(), RoleMiddleware("user"))
	{
		addRoute(userRestock, "POST", "", []string{"user"}, CreateRestockRequest, db)
	}

	// 🔐 Khusus employee dan admin
	employeeAdminRestock := r.Group("/api/v1/restock-requests")
	employeeAdminRestock.Use(AuthMiddleware(), RoleMiddleware("employee", "admin"))
	{
		addRoute(employeeAdminRestock, "GET", "", []string{"employee", "admin"}, GetAllRestockRequests, db)
	}

	// 🔐 Khusus admin
	adminRestock := r.Group("/api/v1/restock-requests")
	adminRestock.Use(AuthMiddleware(), RoleMiddleware("admin"))
	{
		addRoute(adminRestock, "PATCH", "/:id", []string{"admin"}, UpdateRestockRequestStatus, db)
		addRoute(adminRestock, "DELETE", "/:id", []string{"admin"}, DeleteRestockRequest, db)
	}
}

// ++++++++++++++++++++++++
//
//	RestockRequest READ
//
// ++++++++++++++++++++++++
func GetAllRestockRequests(c *gin.Context, db *sql.DB) {
	status := c.Query("status")
	productID := c.Query("product_id")

	// Menyusun query dasar untuk mengambil permintaan restock
	query := `SELECT id, user_id, product_id, product_variant_id, message, status, created_at FROM restock_requests WHERE 1=1`
	args := []interface{}{}

	// Menambahkan filter jika status diberikan
	if status != "" {
		query += ` AND status = ?`
		args = append(args, status)
	}

	// Menambahkan filter jika product_id diberikan
	if productID != "" {
		query += ` AND product_id = ?`
		args = append(args, productID)
	}

	// Menjalankan query
	rows, err := db.Query(query, args...)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data permintaan restock"})
		return
	}
	defer rows.Close()

	// Mengambil data dari query
	var requests []RestockRequestModel
	for rows.Next() {
		var r RestockRequestModel
		if err := rows.Scan(&r.ID, &r.UserID, &r.ProductID, &r.ProductVariantID, &r.Message, &r.Status, &r.CreatedAt); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal membaca data"})
			return
		}
		requests = append(requests, r)
	}

	// Menyusun response
	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Semua permintaan restock berhasil diambil",
		"data":    requests,
	})
}

// ++++++++++++++++++++++++
//  RestockRequest CREATE
// ++++++++++++++++++++++++

func CreateRestockRequest(c *gin.Context, db *sql.DB) {
	var input RestockRequestModel

	// Memasukkan data dari body request ke dalam model
	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format JSON tidak valid"})
		return
	}

	// Validasi field wajib
	if input.UserID == 0 || input.ProductID == 0 || input.Message == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Semua field wajib diisi (user_id, product_id, message)"})
		return
	}

	// Cek apakah user_id valid
	if !ValidateRecordExistence(c, db, "users", int(input.UserID)) {
		return
	}

	// Cek apakah product_id valid
	if !ValidateRecordExistence(c, db, "products", int(input.ProductID)) {
		return
	}

	// Cek apakah produk adalah varian
	isVarians, err := CheckIfVarians(db, int(input.ProductID))
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	// Jika produk adalah varian, pastikan product_variant_id diisi
	if isVarians && input.ProductVariantID == nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Produk ini adalah varian, product_variant_id harus diisi"})
		return
	}

	// Insert permintaan restock ke database
	res, err := db.Exec(`INSERT INTO restock_requests (user_id, product_id, product_variant_id, message, status, created_at)
		VALUES (?, ?, ?, ?, 'unread', NOW())`,
		input.UserID, input.ProductID, input.ProductVariantID, input.Message)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengirim permintaan restock"})
		return
	}

	lastID, _ := res.LastInsertId()

	// Menyusun response sukses
	c.JSON(http.StatusCreated, gin.H{
		"message": "✅ Permintaan restock berhasil dibuat",
		"data": gin.H{
			"id":                 lastID,
			"user_id":            input.UserID,
			"product_id":         input.ProductID,
			"product_variant_id": input.ProductVariantID,
			"message":            input.Message,
			"status":             "unread",
			"created_at":         input.CreatedAt,
		},
	})
}

// ++++++++++++++++++++++++
//  RestockRequest UPDATE
// ++++++++++++++++++++++++

func UpdateRestockRequestStatus(c *gin.Context, db *sql.DB) {
	// Mengambil parameter ID dari URL
	idInt, _, ok := GetIDParam(c)
	if !ok {
		return
	}

	var input struct {
		Status string `json:"status"`
	}

	// Validasi format input JSON
	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format data tidak valid"})
		return
	}

	// Cek apakah status valid
	validStatuses := map[string]bool{"unread": true, "read": true, "done": true}
	if !validStatuses[input.Status] {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Status tidak valid permitted (unread, read, done)"})
		return
	}

	// Cek apakah id valid dalam tabel restock_requests
	if !ValidateRecordExistence(c, db, "restock_requests", idInt) {
		return
	}

	// Update status permintaan restock
	result, err := db.Exec(`UPDATE restock_requests SET status = ? WHERE id = ?`, input.Status, idInt)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengupdate status"})
		return
	}

	// Mengecek apakah ada baris yang terpengaruh (updated)
	rowsAffected, _ := result.RowsAffected()
	if rowsAffected == 0 {
		c.JSON(http.StatusNotFound, gin.H{"message": "⚠️ Permintaan tidak ditemukan"})
		return
	}

	// Response sukses
	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Status permintaan restock diperbarui",
		"data": gin.H{
			"id":     idInt,
			"status": input.Status,
		},
	})
}

// ++++++++++++++++++++++++
//  RestockRequest DELETE
// ++++++++++++++++++++++++

func DeleteRestockRequest(c *gin.Context, db *sql.DB) {
	//id string to int
	idInt, id, ok := GetIDParam(c)
	if !ok {
		return
	}
	//Cek apakah id valid
	if !ValidateRecordExistence(c, db, "restock_requests", idInt) {
		return
	}

	_, error := db.Exec(`DELETE FROM restock_requests WHERE id = ?`, idInt)
	if error != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menghapus permintaan"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Permintaan restock berhasil dihapus",
		"id":      id,
	})
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

// =========================
// 💬 Notification Management
// =========================

func NotificationRoutes(r *gin.Engine, db *sql.DB) {
	// 🔓 Public (tanpa middleware) - GET notification by ID
	publicNotif := r.Group("/api/v1/notifications")
	{
		addRoute(publicNotif, "GET", "/:id", []string{}, GetNotificationByID, db)
	}

	// 🔐 Admin only - semua route selain GET by ID
	adminNotif := r.Group("/api/v1/notifications")
	adminNotif.Use(AuthMiddleware(), RoleMiddleware("admin"))
	{
		addRoute(adminNotif, "GET", "/", []string{"admin"}, GetAllNotifications, db)
		addRoute(adminNotif, "POST", "/", []string{"admin"}, CreateNotification, db)
		addRoute(adminNotif, "PATCH", "/:id/read", []string{"admin"}, MarkNotificationRead, db)
		addRoute(adminNotif, "DELETE", "/:id", []string{"admin"}, DeleteNotification, db)
	}
}

// ++++++++++++++++++++++++
//
//	Notification READ
//
// ++++++++++++++++++++++++
// Get all notifications (optional: filter by user_id)
func GetAllNotifications(c *gin.Context, db *sql.DB) {
	userID := c.Query("user_id")

	var rows *sql.Rows
	var err error

	if userID != "" {
		rows, err = db.Query("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC", userID)
	} else {
		rows, err = db.Query("SELECT * FROM notifications ORDER BY created_at DESC")
	}
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil notifikasi"})
		return
	}
	defer rows.Close()

	var notifications []NotificationModel
	for rows.Next() {
		var n NotificationModel
		if err := rows.Scan(&n.ID, &n.UserID, &n.Message, &n.IsRead, &n.CreatedAt); err == nil {
			notifications = append(notifications, n)
		}
	}
	c.JSON(http.StatusOK, gin.H{
		"message": "✅ notifikasi berhasil diambil",
		"data":    notifications,
	})

}

// ++++++++++++++++++++++++
//
//	Notification CREATE
//
// ++++++++++++++++++++++++
// Create notification
func CreateNotification(c *gin.Context, db *sql.DB) {
	var input NotificationModel
	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format data tidak valid"})
		return
	}
	if input.UserID == 0 || input.Message == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ user_id dan message wajib diisi"})
		return
	}

	// Cek apakah user_id valid
	if !ValidateRecordExistence(c, db, "users", int(input.UserID)) {
		return
	}

	res, err := db.Exec(`INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, false, NOW())`, input.UserID, input.Message)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menyimpan notifikasi"})
		return
	}
	lastID, _ := res.LastInsertId()
	c.JSON(http.StatusCreated, gin.H{
		"message": "✅ Notifikasi berhasil dibuat",
		"data": gin.H{
			"id":      lastID,
			"user_id": input.UserID,
			"message": input.Message,
			"is_read": false,
		},
	})
}

// ++++++++++++++++++++++++
//
//	Notification UPDATE
//
// ++++++++++++++++++++++++
// Mark notification as read
func MarkNotificationRead(c *gin.Context, db *sql.DB) {
	// id string to int
	idInt, id, ok := GetIDParam(c)
	if !ok {
		return
	}
	// Cek apakah id valid
	if !ValidateRecordExistence(c, db, "notifications", idInt) {
		return
	}

	var isRead bool
	error := db.QueryRow("SELECT is_read FROM notifications WHERE id = ?", id).Scan(&isRead)
	if error != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memeriksa status notifikasi"})
		return
	}
	if isRead {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Notifikasi sudah dibaca sebelumnya"})
		return
	}

	_, err := db.Exec("UPDATE notifications SET is_read = true WHERE id = ?", id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengupdate status notifikasi"})
		return
	}
	c.JSON(http.StatusOK, gin.H{
		"message": fmt.Sprintf("✅ Notifikasi dengan id %s ditandai sebagai sudah dibaca", id),
		"id":      id,
		"is_read": true,
	})
}

// ++++++++++++++++++++++++
//
//	Notification DELETE
//
// ++++++++++++++++++++++++
// Delete notification
func DeleteNotification(c *gin.Context, db *sql.DB) {
	// id string to int
	idInt, id, ok := GetIDParam(c)
	if !ok {
		return
	}
	// Cek apakah id valid
	if !ValidateRecordExistence(c, db, "notifications", idInt) {
		return
	}

	// Hapus notifikasi dari database
	_, err := db.Exec("DELETE FROM notifications WHERE id = ?", id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menghapus notifikasi"})
		return
	}
	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Notifikasi berhasil dihapus",
		"id":      id,
		"status":  "deleted",
	})
}

// ++++++++++++++++++++++++
//
//	Notification FIND
//
// ++++++++++++++++++++++++
// Get notification by ID
func GetNotificationByID(c *gin.Context, db *sql.DB) {
	// id string to int
	idInt, id, ok := GetIDParam(c)
	if !ok {
		return
	}
	// Cek apakah id valid
	if !ValidateRecordExistence(c, db, "notifications", idInt) {
		return
	}
	//id := c.Param("id")
	var n NotificationModel
	err := db.QueryRow("SELECT * FROM notifications WHERE id = ?", id).
		Scan(&n.ID, &n.UserID, &n.Message, &n.IsRead, &n.CreatedAt)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "❌ Notifikasi tidak ditemukan"})
		return
	}
	c.JSON(http.StatusOK, n)
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
