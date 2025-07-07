// Semuanya masih dalam package main
package main

import (
	"database/sql"
	"fmt"
	"log"
	"net/http"
	"regexp"
	"strconv"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
)

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
	if product.IsDiscounted {
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

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// =========================
// 🗂️ Position Management
// =========================
func PositionRoutes(r *gin.Engine, db *sql.DB) {
	api := r.Group("/api/v1/position")

	// Admin only: GET, POST, PATCH, DELETE
	addRoute(api, "GET", "", []string{"admin"}, GetAllPosition, db)
	addRoute(api, "POST", "", []string{"admin"}, CreatePosition, db)
	addRoute(api, "PATCH", "", []string{"admin"}, UpdatePosition, db)
	addRoute(api, "DELETE", "", []string{"admin"}, DeletePosition, db)
}

// ++++++++++++++++++++++++
//
//	Categories READ
//
// +++++++++++++++++++++++++
func GetAllPosition(c *gin.Context, db *sql.DB) {
	rows, err := db.Query(`
		SELECT position_name, description FROM position
	`)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data position employee"})
		return
	}
	defer rows.Close()

	var position []PositionModel
	for rows.Next() {
		var pos PositionModel
		err := rows.Scan(&pos.Name, &pos.Description)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal membaca data kategori"})
			return
		}
		position = append(position, pos)
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Semua kategori berhasil diambil",
		"data":    position,
	})
}

// ++++++++++++++++++++++++
//
//	Positions CREATE
//
// +++++++++++++++++++++++++

func CreatePosition(c *gin.Context, db *sql.DB) {
	var input PositionModel

	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format JSON tidak valid"})
		return
	}

	if !validatePositionName(input.Name) {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Nama posisi tidak valid. Hanya 1 kata huruf tanpa angka/spasi."})
		return
	}

	if input.Description == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Deskripsi posisi wajib diisi"})
		return
	}

	_, err := db.Exec(`INSERT INTO position (position_name, description) VALUES (?, ?)`, input.Name, input.Description)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menyimpan posisi"})
		return
	}

	c.JSON(http.StatusCreated, gin.H{
		"message": "✅ Posisi berhasil ditambahkan",
		"name":    input.Name,
	})
}

func validatePositionName(name string) bool {
	// Validasi: hanya huruf a-z/A-Z, tanpa spasi, tanpa angka, tanpa simbol, minimal 1 karakter
	matched, _ := regexp.MatchString(`^[A-Za-z]+$`, name)
	return matched
}

// ++++++++++++++++++++++++
//  Position UPDATE
// +++++++++++++++++++++++++

func UpdatePosition(c *gin.Context, db *sql.DB) {
	var input PositionModel

	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format JSON tidak valid"})
		return
	}

	if input.Name == "" || input.Description == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ position_name dan description wajib diisi"})
		return
	}

	input.Name = strings.TrimSpace(strings.ToLower(input.Name))
	input.Description = strings.TrimSpace(input.Description)

	if !regexp.MustCompile(`^[a-zA-Z]+$`).MatchString(input.Name) {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Nama posisi hanya boleh 1 kata huruf tanpa angka/spasi"})
		return
	}

	// Cek apakah posisi dengan nama itu ada
	var existingDesc string
	err := db.QueryRow(`SELECT description FROM position WHERE LOWER(TRIM(position_name)) = ?`, input.Name).Scan(&existingDesc)
	if err == sql.ErrNoRows {
		c.JSON(http.StatusNotFound, gin.H{"error": "⚠️ Posisi tidak ditemukan"})
		return
	} else if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mencari posisi"})
		return
	}

	// Lakukan update
	result, err := db.Exec(`UPDATE position SET description = ? WHERE LOWER(TRIM(position_name)) = ?`, input.Description, input.Name)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengupdate posisi"})
		return
	}

	rowsAffected, _ := result.RowsAffected()
	if rowsAffected == 0 {
		c.JSON(http.StatusOK, gin.H{"message": "⚠️ Tidak ada perubahan data"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message":     "✅ Posisi berhasil diperbarui",
		"position":    input.Name,
		"description": input.Description,
	})
}

// ++++++++++++++++++++++++
//
//	Position DELETE
//
// +++++++++++++++++++++++++
func DeletePosition(c *gin.Context, db *sql.DB) {
	var input struct {
		Name string `json:"position_name"`
	}

	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format JSON tidak valid"})
		return
	}

	input.Name = strings.TrimSpace(strings.ToLower(input.Name))
	if input.Name == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ position_name wajib diisi"})
		return
	}

	if !regexp.MustCompile(`^[a-zA-Z]+$`).MatchString(input.Name) {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Nama posisi hanya boleh 1 kata huruf tanpa angka/spasi"})
		return
	}

	// Cek apakah posisi ada
	var exists string
	err := db.QueryRow(`SELECT position_name FROM position WHERE LOWER(TRIM(position_name)) = ?`, input.Name).Scan(&exists)
	if err == sql.ErrNoRows {
		c.JSON(http.StatusNotFound, gin.H{"error": "⚠️ Posisi tidak ditemukan"})
		return
	} else if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mencari posisi"})
		return
	}

	// Hapus data
	_, err = db.Exec(`DELETE FROM position WHERE LOWER(TRIM(position_name)) = ?`, input.Name)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menghapus posisi"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Posisi berhasil dihapus",
		"name":    input.Name,
	})
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
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
	addRoute(api, "GET", "search", []string{}, SearchProducts, db)
	addRoute(api, "POST", "", []string{"admin"}, CreateProductWithImages, db)
}

func GetAllProducts(c *gin.Context, db *sql.DB) {
	productID := c.Param("id_product")
	categoryID := c.Param("category_id")

	var err error

	if productID != "" {
		id, err := strconv.Atoi(productID)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": "ID produk harus berupa angka"})
			return
		}

		product, err := getSingleProductWithVariantsAndImages(db, id)
		if err != nil {
			if err == sql.ErrNoRows {
				c.JSON(http.StatusNotFound, gin.H{"error": "Produk tidak ditemukan"})
			} else {
				c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data produk"})
			}
			return
		}

		// ➕ Hitung nilai tambahan
		// enrichProductWithComputedFields(&product)

		c.JSON(http.StatusOK, gin.H{
			"message": "✅ Produk berhasil diambil",
			"data":    product,
		})
		return
	}

	basicProducts, err := getBasicProducts(db, categoryID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data produk"})
		return
	}

	var fullProducts []ProductsBasicModel
	for _, p := range basicProducts {
		detail, err := getSingleProductWithVariantsAndImages(db, p.ID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": fmt.Sprintf("❌ Gagal mengambil detail produk ID %d", p.ID)})
			return
		}

		// enrichProductWithComputedFields(&detail)

		fullProducts = append(fullProducts, detail)
	}

	message := "✅ Semua produk berhasil diambil"
	if categoryID != "" {
		message = fmt.Sprintf("✅ Produk dengan kategori ID %s berhasil diambil", categoryID)
	}

	c.JSON(http.StatusOK, gin.H{
		"message": message,
		"data":    fullProducts,
	})
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
        SELECT id, name, price, discount_price, stock
        FROM product_variants
        WHERE product_id = ?`, productID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var variants []Variant
	for rows.Next() {
		var v Variant
		err := rows.Scan(&v.ID, &v.Name, &v.Price, &v.DiscountPrice, &v.Stock)
		if err != nil {
			return nil, err
		}

		v.IsDiscounted = v.DiscountPrice != nil && *v.DiscountPrice < v.Price
		variants = append(variants, v)
	}
	return variants, nil
}


func SearchProducts(c *gin.Context, db *sql.DB) {
	queryStr := c.Query("q")
	if queryStr == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Parameter 'q' (query) harus diisi"})
		return
	}

	likeQuery := "%" + strings.ToLower(queryStr) + "%"

	rows, err := db.Query(`
		SELECT id, category_id, name, description,
		       is_varians, is_discounted, discount_price,
		       price, stock
		FROM products
		WHERE LOWER(name) LIKE ?
	`, likeQuery)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal melakukan pencarian produk"})
		return
	}
	defer rows.Close()

	var products []ProductsBasicModel
	for rows.Next() {
		var p ProductsBasicModel
		err := rows.Scan(
			&p.ID, &p.CategoryID, &p.Name, &p.Description,
			&p.IsVarians, &p.IsDiscounted, &p.DiscountPrice,
			&p.Price, &p.Stock,
		)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal membaca hasil pencarian"})
			return
		}
		products = append(products, p)
	}

	// Lengkapi gambar & varian
	for i := range products {
		images, thumbnails, err := getProductImages(db, products[i].ID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil gambar produk"})
			return
		}
		products[i].Images = images
		products[i].Thumbnails = thumbnails

		if products[i].IsVarians {
			variants, err := getProductVariants(db, products[i].ID)
			if err != nil {
				c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil varian produk"})
				return
			}
			products[i].Variants = variants
			products[i].Price = nil
			products[i].DiscountPrice = nil
			products[i].Stock = nil
		}
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Hasil pencarian produk",
		"data":    products,
	})
}


func CreateProductWithImages(c *gin.Context, db *sql.DB) {
	// 1. Ambil data produk dari body request
	var input struct {
		Name          string   `json:"name"`
		Description   string   `json:"description"`
		CategoryID    int      `json:"category_id"`
		Price         *int     `json:"price"`
		DiscountPrice *int     `json:"discount_price"`
		IsDiscounted  bool     `json:"is_discounted"`
		IsVarians     bool     `json:"is_varians"`
		Stock         *int     `json:"stock"`
		SearchVector  *string  `json:"search_vector"` // Optional search vector
		Images        []struct {
			ImageURL     string  `json:"image_url"`
			ThumbnailURL *string `json:"thumbnail_url"` // Optional thumbnail URL
		} `json:"images"` // Array of image objects
		Variants []struct {
			Name          string `json:"name"`
			Price         int    `json:"price"`
			DiscountPrice *int   `json:"discount_price"`
			Stock         int    `json:"stock"`
		} `json:"variants"`
	}

	// Bind JSON ke struct
	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format data tidak valid"})
		return
	}

	// Validasi data
	if input.Name == "" || input.Description == "" || input.CategoryID == 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Nama, deskripsi, dan kategori wajib diisi"})
		return
	}

	// 2. Mulai transaksi
	tx, err := db.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal memulai transaksi"})
		return
	}

	// 3. Simpan produk ke database
	res, err := tx.Exec(`
		INSERT INTO products 
		(name, description, category_id, price, discount_price, is_discounted, 
		 is_varians, stock, search_vector, created_at, updated_at)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())`,
		input.Name, input.Description, input.CategoryID,
		input.Price, input.DiscountPrice, input.IsDiscounted,
		input.IsVarians, input.Stock, input.SearchVector)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal simpan produk"})
		return
	}
	productID, _ := res.LastInsertId()

	// 4. Simpan URL gambar yang diterima
	for _, img := range input.Images {
		// Cek apakah URL valid
		if !strings.HasPrefix(img.ImageURL, "http") {
			tx.Rollback()
			c.JSON(http.StatusBadRequest, gin.H{"error": "❌ URL gambar tidak valid"})
			return
		}
		// Simpan gambar ke database
		_, err := tx.Exec(`
			INSERT INTO product_images 
			(product_id, image_url, thumbnail_url) 
			VALUES (?, ?, ?)`, 
			productID, img.ImageURL, img.ThumbnailURL)
		if err != nil {
			tx.Rollback()
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal simpan URL gambar"})
			return
		}
	}

	// 5. Simpan varian jika ada
	if input.IsVarians {
		for _, v := range input.Variants {
			isDisc := v.DiscountPrice != nil
			if isDisc && *v.DiscountPrice >= v.Price {
				tx.Rollback()
				c.JSON(http.StatusBadRequest, gin.H{"error": fmt.Sprintf("❌ Diskon varian %s harus lebih kecil dari harga", v.Name)})
				return
			}
			_, err := tx.Exec(`
				INSERT INTO product_variants
				(product_id, name, price, discount_price, is_discounted, stock, created_at, updated_at)
				VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())`,
				productID, v.Name, v.Price, v.DiscountPrice, isDisc, v.Stock)
			if err != nil {
				tx.Rollback()
				c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menyimpan varian"})
				return
			}
		}
	}

	// 6. Commit transaksi
	tx.Commit()

	c.JSON(http.StatusCreated, gin.H{"message": "✅ Produk berhasil ditambahkan", "product_id": productID})
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

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

		//Update cart item product variant (jika dibutuhkan nanti)
		customerCartItem.PATCH("/:id/variant", func(c *gin.Context) {
			UpdateVariantCartItem(c, db)
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
	userID := GetUserID(c)

	var input struct {
		ProductID        int  `json:"product_id"`
		ProductVariantID *int `json:"product_variant_id"`
		Quantity         int  `json:"quantity"`
	}

	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Input tidak valid"})
		return
	}
	if input.ProductID == 0 || input.Quantity <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ product_id dan quantity harus diisi dengan benar"})
		return
	}

	tx, err := db.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memulai transaksi"})
		return
	}
	defer tx.Rollback()

	// Validasi record
	if !ValidateRecordExistence(c, db, "products", input.ProductID) || !ValidateRecordExistence(c, db, "carts", userID) {
		return
	}

	var isVarians bool
	var isDiscounted *bool
	var price, stock, discountPrice *int

	err = tx.QueryRow(`
		SELECT is_varians, price, stock, is_discounted, discount_price
		FROM products WHERE id = ?
	`, input.ProductID).Scan(&isVarians, &price, &stock, &isDiscounted, &discountPrice)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Produk tidak ditemukan"})
		return
	}

	var pricePerItem int
	var stockAvailable int

	if isVarians {
		if input.ProductVariantID == nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Produk ini memiliki variasi. Harap isi product_variant_id"})
			return
		}
		var isVarDisc bool
		var varPrice int
		var varDiscPrice *int
		err := tx.QueryRow(`
			SELECT stock, price, is_discounted, discount_price
			FROM product_variants
			WHERE id = ? AND product_id = ?
		`, *input.ProductVariantID, input.ProductID).Scan(&stockAvailable, &varPrice, &isVarDisc, &varDiscPrice)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Variant tidak ditemukan untuk produk ini"})
			return
		}
		pricePerItem = varPrice
		if isVarDisc && varDiscPrice != nil {
			pricePerItem = *varDiscPrice
		}
	} else {
		if input.ProductVariantID != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Produk ini tidak memiliki variasi. Hapus product_variant_id"})
			return
		}
		stockAvailable = *stock
		pricePerItem = *price
		if *isDiscounted && discountPrice != nil {
			pricePerItem = *discountPrice
		}
	}

	if input.Quantity > stockAvailable {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Quantity melebihi stok tersedia"})
		return
	}

	totalPrice := input.Quantity * pricePerItem

	// Cek duplikasi
	var existingID, existingQty, existingTotal int
	var row *sql.Row
	if input.ProductVariantID == nil {
		row = tx.QueryRow(`
			SELECT id, quantity, total_price
			FROM cart_items
			WHERE cart_id = ? AND product_id = ? AND product_variant_id IS NULL
			FOR UPDATE
		`, userID, input.ProductID)
	} else {
		row = tx.QueryRow(`
			SELECT id, quantity, total_price
			FROM cart_items
			WHERE cart_id = ? AND product_id = ? AND product_variant_id = ?
			FOR UPDATE
		`, userID, input.ProductID, input.ProductVariantID)
	}

	err = row.Scan(&existingID, &existingQty, &existingTotal)
	if err == nil {
		// Duplikat ditemukan → update
		newQty := existingQty + input.Quantity
		if newQty > stockAvailable {
			c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Quantity total melebihi stok tersedia"})
			return
		}
		newTotal := newQty * pricePerItem
		diff := newTotal - existingTotal

		_, err = tx.Exec(`
			UPDATE cart_items
			SET quantity = ?, total_price = ?, updated_at = NOW()
			WHERE id = ?
		`, newQty, newTotal, existingID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update item cart"})
			return
		}

		_, err = tx.Exec(`
			UPDATE carts SET total_price = total_price + ?, updated_at = NOW()
			WHERE id = ?
		`, diff, userID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update total harga cart"})
			return
		}

		if err := tx.Commit(); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menyimpan transaksi"})
			return
		}

		c.JSON(http.StatusOK, gin.H{"message": "✅ Quantity item diperbarui di cart"})
		return
	}

	// Tidak ditemukan → insert baru
	_, err = tx.Exec(`
		INSERT INTO cart_items
		(cart_id, product_id, product_variant_id, quantity, price_per_item, total_price, created_at, updated_at)
		VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
	`, userID, input.ProductID, input.ProductVariantID, input.Quantity, pricePerItem, totalPrice)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menambahkan item ke cart"})
		return
	}

	_, err = tx.Exec(`
		UPDATE carts SET total_price = total_price + ?, updated_at = NOW()
		WHERE id = ?
	`, totalPrice, userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update total harga cart"})
		return
	}

	if err := tx.Commit(); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menyimpan transaksi"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "✅ Item berhasil ditambahkan ke cart"})
}

// +++++++++++++++++++++++++++++++++
// Cart Item READ MY CART
// +++++++++++++++++++++++++++++++++
//---------------------------------------------------------------------------------------------------------------

func MyCartItems(c *gin.Context, db *sql.DB) {
	userID := GetUserID(c)

	if !ValidateRecordExistence(c, db, "users", userID) {
		c.JSON(http.StatusNotFound, gin.H{"error": "❌ User tidak ditemukan"})
		return
	}

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

	var (
		updatedItems   []CartsBasicModel
		totalCartPrice int
		needsUpdate    bool
	)

	for _, item := range cartItems {
		product, err := getSingleProductWithVariantsAndImages(db, item.ProductID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memuat produk"})
			return
		}

		var selectedVariant *Variant
		if item.ProductVariantID != nil {
			for _, v := range product.Variants {
				if v.ID == *item.ProductVariantID {
					selectedVariant = &v
					break
				}
			}
			if selectedVariant == nil {
				c.JSON(http.StatusNotFound, gin.H{"error": "❌ Varian tidak ditemukan"})
				return
			}
		}

		currentPrice := getCurrentPrice(product, selectedVariant)
		currentStock := getCurrentStock(product, selectedVariant)

		if item.PricePerItem != currentPrice {
			oldTotal := item.Quantity * item.PricePerItem
			newTotal := item.Quantity * currentPrice
			diff := newTotal - oldTotal

			err = updateCartItem(db, item.ID, currentPrice, newTotal)
			if err != nil {
				c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update item cart"})
				return
			}

			err = updateCartTotal(db, userID, diff)
			if err != nil {
				c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update total cart"})
				return
			}

			needsUpdate = true
			item.PricePerItem = currentPrice
			item.TotalPrice = newTotal
		}

		// ✅ Konversi ke list ringan VariantBasic
		var basicVariants []VariantBasic
		for _, v := range product.Variants {
			basicVariants = append(basicVariants, VariantBasic{
				ID:   v.ID,
				Name: v.Name,
			})
		}

		// ✅ Bangun response cart item
		responseItem := CartsBasicModel{
			ID:               item.ID,
			CartID:           item.CartID,
			ProductID:        item.ProductID,
			ProductVariantID: item.ProductVariantID,
			Name:             product.Name,
			Stock:            &currentStock,
			Thumbnails:       product.Thumbnails,
			Variants:         basicVariants,
			Quantity:         item.Quantity,
			Price:            getBasePrice(product, selectedVariant),
			PricePerItem:     item.PricePerItem,
			TotalPrice:       item.TotalPrice,
		}

		updatedItems = append(updatedItems, responseItem)
		totalCartPrice += item.TotalPrice
	}

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

func getSingleProductWithVariantsAndImages(db *sql.DB, productID int) (ProductsBasicModel, error) {
	var product ProductsBasicModel
	err := db.QueryRow(`
        SELECT id, category_id, name, search_vector,
               is_varians, is_discounted, discount_price, price, stock
        FROM products WHERE id = ?`, productID).
		Scan(&product.ID, &product.CategoryID, &product.Name, &product.Description,
			&product.IsVarians, &product.IsDiscounted, &product.DiscountPrice,
			&product.Price, &product.Stock)
	if err != nil {
		return product, err
	}

	// Variants
	product.Variants, _ = getProductVariants(db, productID)

	// Images
	product.Images, product.Thumbnails, _ = getProductImages(db, productID)

	return product, nil
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
	if diff != 0 {
		sign := "+"
		if diff < 0 {
			sign = "-"
			diff = -diff
		}
		query := fmt.Sprintf("UPDATE carts SET total_price = total_price %s ?, updated_at = NOW() WHERE id = ?", sign)
		_, err := db.Exec(query, diff, cartID)
		return err
	}
	return nil
}

func getCartTotal(db *sql.DB, cartID int) (int, error) {
	var total int
	err := db.QueryRow("SELECT total_price FROM carts WHERE id = ?", cartID).Scan(&total)
	return total, err
}

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

	tx, err := db.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memulai transaksi"})
		return
	}
	defer tx.Rollback()

	// 1. Ambil cart item saat ini
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
		WHERE id = ? AND cart_id = ?
	`, cartItemID, userID).Scan(
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

	// 2. Verifikasi variant baru milik produk yang sama
	var variantProductID int
	err = tx.QueryRow(`SELECT product_id FROM product_variants WHERE id = ?`, input.VariantID).Scan(&variantProductID)
	if err != nil || variantProductID != currentCartItem.ProductID {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Variant tidak valid untuk produk ini"})
		return
	}

	// 3. Ambil detail variant baru
	var newVariant struct {
		Price         int
		DiscountPrice *int
		Stock         int
		Name          string
	}
	err = tx.QueryRow(`
		SELECT price, discount_price, stock, name
		FROM product_variants
		WHERE id = ?
	`, input.VariantID).Scan(
		&newVariant.Price,
		&newVariant.DiscountPrice,
		&newVariant.Stock,
		&newVariant.Name,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data variant"})
		return
	}

	// 4. Cek stok cukup
	if currentCartItem.Quantity > newVariant.Stock {
		c.JSON(http.StatusBadRequest, gin.H{
			"error":           "❌ Stok tidak mencukupi",
			"stock_available": newVariant.Stock,
		})
		return
	}

	// 5. Hitung harga baru
	newPrice := newVariant.Price
	if newVariant.DiscountPrice != nil {
		newPrice = *newVariant.DiscountPrice
	}
	newTotal := currentCartItem.Quantity * newPrice

	// 6. Cek apakah item dengan variant baru sudah ada di cart user
	var existingItem struct {
		ID        int
		Quantity  int
		Total     int
	}
	err = tx.QueryRow(`
		SELECT id, quantity, total_price
		FROM cart_items
		WHERE cart_id = ? AND product_id = ? AND product_variant_id = ? AND id != ?
	`, userID, currentCartItem.ProductID, input.VariantID, cartItemID).Scan(&existingItem.ID, &existingItem.Quantity, &existingItem.Total)

	if err == nil {
		// 🧠 Jika item dengan variant baru sudah ada → gabungkan
		newQty := existingItem.Quantity + currentCartItem.Quantity
		if newQty > newVariant.Stock {
			c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Total quantity melebihi stok"})
			return
		}
		newCombinedTotal := newQty * newPrice
		priceDiff := newCombinedTotal - (currentCartItem.OldTotalPrice + existingItem.Total)

		// Update item lama (yg sudah ada) → tambah quantity
		_, err = tx.Exec(`
			UPDATE cart_items
			SET quantity = ?, price_per_item = ?, total_price = ?, updated_at = NOW()
			WHERE id = ?
		`, newQty, newPrice, newCombinedTotal, existingItem.ID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update item yang digabung"})
			return
		}

		// Hapus item yang sekarang (yang sedang di-update)
		_, err = tx.Exec(`DELETE FROM cart_items WHERE id = ?`, cartItemID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menghapus item awal"})
			return
		}

		// Update total cart
		_, err = tx.Exec(`
			UPDATE carts SET total_price = total_price + ?, updated_at = NOW()
			WHERE id = ?
		`, priceDiff, userID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update total cart"})
			return
		}

		if err := tx.Commit(); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menyimpan perubahan"})
			return
		}

		c.JSON(http.StatusOK, gin.H{
			"message": "✅ Variant digabung ke item yang sudah ada",
			"merged_to_item_id": existingItem.ID,
			"quantity_now":      newQty,
		})
		return
	}

	// 🧠 Jika belum ada item dengan variant baru → update item saat ini
	priceDiff := newTotal - currentCartItem.OldTotalPrice

	_, err = tx.Exec(`
		UPDATE cart_items
		SET product_variant_id = ?, price_per_item = ?, total_price = ?, updated_at = NOW()
		WHERE id = ?
	`, input.VariantID, newPrice, newTotal, cartItemID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update variant item"})
		return
	}

	_, err = tx.Exec(`
		UPDATE carts
		SET total_price = total_price + ?, updated_at = NOW()
		WHERE id = ?
	`, priceDiff, currentCartItem.CartID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal update total cart"})
		return
	}

	if err := tx.Commit(); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal menyimpan perubahan"})
		return
	}

	// ✅ Respon sukses
	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Variant berhasil diupdate",
		"data": gin.H{
			"id":                cartItemID,
			"cart_id":           currentCartItem.CartID,
			"product_id":        currentCartItem.ProductID,
			"product_variant_id": input.VariantID,
			"variant_name":      newVariant.Name,
			"quantity":          currentCartItem.Quantity,
			"price":             newVariant.Price,
			"discount_price":    newVariant.DiscountPrice,
			"price_per_item":    newPrice,
			"total_price":       newTotal,
			"stock":             newVariant.Stock,
		},
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

	addRoute(orderGroup, "POST", "", []string{"user"}, CreateOrder, db)                               // Buat order dari cart
	addRoute(orderGroup, "GET", "/my", []string{"user"}, GetMyOrders, db)                             // Lihat semua order milik user saat ini
	addRoute(orderGroup, "GET", "/all", []string{"employee", "admin"}, GetAllOrders, db)              // Lihat semua order
	addRoute(orderGroup, "GET", "/all/:status", []string{"employee", "admin"}, GetOrdersByStatus, db) // Lihat order by status
	addRoute(orderGroup, "GET", "/:id", []string{"user", "employee", "admin"}, GetOrderByID, db)      // Lihat order by ID
	addRoute(orderGroup, "PUT", "/:id/cancel", []string{"user"}, CancelOrder, db)                     // Cancel order milik sendiri
	addRoute(orderGroup, "PUT", "/:id/finish", []string{"employee", "admin"}, FinishOrder, db)        // Selesaikan order (untuk employee dan admin)

}

// ++++++++++++++++++++++++
//
//	Order READ
//
// ++++++++++++++++++++++++

// Helper untuk func GetMyOrders, GetAllOrders, dan GetOrdersByStatus
func getOneOrderItem(db *sql.DB, orderID int) (*OrderItemModel, error) {
	row := db.QueryRow(`
        SELECT id, order_id, product_id, product_variant_id,
               quantity, price_at_purchase, total_price
        FROM order_items
        WHERE order_id = ?
        LIMIT 1`, orderID)

	var item OrderItemModel
	err := row.Scan(
		&item.ID,
		&item.OrderID,
		&item.ProductID,
		&item.ProductVariantID,
		&item.Quantity,
		&item.PriceAtPurchase,
		&item.TotalPrice,
	)

	if err == sql.ErrNoRows {
		return nil, nil
	} else if err != nil {
		return nil, err
	}

	return &item, nil
}

func GetMyOrders(c *gin.Context, db *sql.DB) {
	userID := c.GetInt("user_id")

	rows, err := db.Query(`
		SELECT id, user_id, status, total_price, created_at, updated_at
		FROM orders
		WHERE user_id = ?
		ORDER BY created_at DESC
	`, userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil daftar order"})
		return
	}
	defer rows.Close()

	var results []gin.H

	for rows.Next() {
		var order OrderModel
		if err := rows.Scan(
			&order.ID, &order.UserID, &order.Status,
			&order.TotalPrice, &order.CreatedAt, &order.UpdatedAt,
		); err != nil {
			continue
		}

		item, err := getOneOrderItem(db, order.ID)
		if err != nil || item == nil {
			continue
		}

		product, err := getSingleProductWithVariantsAndImages(db, item.ProductID)
		if err != nil {
			continue
		}

		// Temukan varian yang sesuai jika ada
		var selectedVariant *Variant
		if item.ProductVariantID != nil {
			for _, v := range product.Variants {
				if v.ID == *item.ProductVariantID {
					selectedVariant = &v
					break
				}
			}
		}

		thumbnail := "https://via.placeholder.com/150"
		if len(product.Thumbnails) > 0 {
			thumbnail = product.Thumbnails[0]
		}

		result := gin.H{
			"order": gin.H{
				"id":          order.ID,
				"user_id":     order.UserID,
				"status":      order.Status,
				"total_price": order.TotalPrice,
				"created_at":  order.CreatedAt,
				"updated_at":  order.UpdatedAt,
			},
			"sample_item": gin.H{
				"order_item_id":     item.ID,
				"product_id":        item.ProductID,
				"product_name":      product.Name,
				"variant":           selectedVariant,
				"quantity":          item.Quantity,
				"price_at_purchase": item.PriceAtPurchase,
				"thumbnail":         thumbnail,
			},
		}
		results = append(results, result)
	}

	if len(results) == 0 {
		c.JSON(http.StatusOK, gin.H{
			"message": "⚠️ Order kosong",
			"data":    []OrderModel{},
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Berhasil mengambil semua order dengan detail",
		"data":    results,
	})
}

func GetAllOrders(c *gin.Context, db *sql.DB) {
	rows, err := db.Query(`
		SELECT id, user_id, status, total_price, created_at, updated_at
		FROM orders
		ORDER BY created_at DESC
	`)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil daftar order"})
		return
	}
	defer rows.Close()

	var results []gin.H

	for rows.Next() {
		var order OrderModel
		if err := rows.Scan(
			&order.ID,
			&order.UserID,
			&order.Status,
			&order.TotalPrice,
			&order.CreatedAt,
			&order.UpdatedAt,
		); err != nil {
			continue
		}

		item, err := getOneOrderItem(db, order.ID)
		if err != nil || item == nil {
			continue
		}

		product, err := getSingleProductWithVariantsAndImages(db, item.ProductID)
		if err != nil {
			continue
		}

		// Cari variant (jika ada)
		var selectedVariant *Variant
		if item.ProductVariantID != nil {
			for _, v := range product.Variants {
				if v.ID == *item.ProductVariantID {
					selectedVariant = &v
					break
				}
			}
		}

		thumbnail := "https://via.placeholder.com/150"
		if len(product.Thumbnails) > 0 {
			thumbnail = product.Thumbnails[0]
		}

		result := gin.H{
			"order": gin.H{
				"id":          order.ID,
				"user_id":     order.UserID,
				"status":      order.Status,
				"total_price": order.TotalPrice,
				"created_at":  order.CreatedAt,
				"updated_at":  order.UpdatedAt,
			},
			"sample_item": gin.H{
				"order_item_id":     item.ID,
				"product_id":        item.ProductID,
				"product_name":      product.Name,
				"variant":           selectedVariant,
				"quantity":          item.Quantity,
				"price_at_purchase": item.PriceAtPurchase,
				"thumbnail":         thumbnail,
			},
		}

		results = append(results, result)
	}

	if len(results) == 0 {
		c.JSON(http.StatusOK, gin.H{
			"message": "⚠️ Order kosong",
			"data":    []OrderModel{},
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Berhasil mengambil semua order dengan detail",
		"data":    results,
	})
}

func GetOrderByID(c *gin.Context, db *sql.DB) {
	orderIDStr := c.Param("id")
	orderID, err := strconv.Atoi(orderIDStr)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "ID tidak valid"})
		return
	}

	// Ambil status dan created_at dari tabel orders
	var (
		status     string
		createdAt  time.Time
	)
	err = db.QueryRow("SELECT status, created_at FROM orders WHERE id = ?", orderID).Scan(&status, &createdAt)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "❌ Pesanan tidak ditemukan"})
		return
	}

	orderItems, err := getOrderItems(db, orderID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil data order"})
		return
	}

	if len(orderItems) == 0 {
		c.JSON(http.StatusOK, gin.H{
			"message":     "⚠️ Tidak ada item dalam pesanan",
			"data":        []OrderBasicModel{},
			"status":      status,
			"created_at":  createdAt.Format(time.RFC3339),
		})
		return
	}

	var (
		responseItems   []OrderBasicModel
		totalOrderPrice int
	)

	for _, item := range orderItems {
		product, err := getSingleProductWithVariantsAndImages(db, item.ProductID)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memuat detail produk"})
			return
		}

		var selectedVariant *Variant
		if item.ProductVariantID != nil {
			for _, v := range product.Variants {
				if v.ID == *item.ProductVariantID {
					selectedVariant = &v
					break
				}
			}
		}

		thumbnail := "https://via.placeholder.com/150"
		if len(product.Thumbnails) > 0 {
			thumbnail = product.Thumbnails[0]
		}

		responseItem := OrderBasicModel{
			ID:               item.ID,
			OrderID:          item.OrderID,
			ProductID:        item.ProductID,
			ProductVariantID: item.ProductVariantID,
			Name:             product.Name,
			Thumbnails:       []string{thumbnail},
			Quantity:         item.Quantity,
			Price:            getBasePrice(product, selectedVariant),
			PriceAtPurchase:  item.PriceAtPurchase,
			TotalPrice:       item.TotalPrice,
		}

		if selectedVariant != nil {
			responseItem.Variants = []Variant{*selectedVariant}
		}

		responseItems = append(responseItems, responseItem)
		totalOrderPrice += item.TotalPrice
	}

	c.JSON(http.StatusOK, gin.H{
		"message":           "✅ Berhasil mengambil item order",
		"data":              responseItems,
		"total_order_price": totalOrderPrice,
		"status":            status,
		"created_at":        createdAt.Format(time.RFC3339),
	})
}

func getOrderItems(db *sql.DB, orderID int) ([]OrderItemModel, error) {
	rows, err := db.Query(`
        SELECT id, order_id, product_id, product_variant_id,
               quantity, price_at_purchase, total_price
        FROM order_items
        WHERE order_id = ?`, orderID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var items []OrderItemModel
	for rows.Next() {
		var item OrderItemModel
		err := rows.Scan(
			&item.ID,
			&item.OrderID,
			&item.ProductID,
			&item.ProductVariantID,
			&item.Quantity,
			&item.PriceAtPurchase,
			&item.TotalPrice,
		)
		if err != nil {
			return nil, err
		}
		items = append(items, item)
	}
	return items, nil
}

func GetOrdersByStatus(c *gin.Context, db *sql.DB) {
	orderStatus := c.Param("status")
	if orderStatus != "pending" && orderStatus != "done" && orderStatus != "cancelled" && orderStatus != "expired" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Status tidak valid"})
		return
	}

	rows, err := db.Query(`
		SELECT id, user_id, status, total_price, created_at, updated_at
		FROM orders
		WHERE status = ?
		ORDER BY created_at DESC
	`, orderStatus)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil daftar order"})
		return
	}
	defer rows.Close()

	var results []gin.H

	for rows.Next() {
		var order OrderModel
		err := rows.Scan(
			&order.ID,
			&order.UserID,
			&order.Status,
			&order.TotalPrice,
			&order.CreatedAt,
			&order.UpdatedAt,
		)
		if err != nil {
			continue
		}

		item, err := getOneOrderItem(db, order.ID)
		if err != nil || item == nil {
			continue
		}

		product, err := getSingleProductWithVariantsAndImages(db, item.ProductID)
		if err != nil {
			continue
		}

		// Cari varian jika ada
		var selectedVariant *Variant
		if item.ProductVariantID != nil {
			for _, v := range product.Variants {
				if v.ID == *item.ProductVariantID {
					selectedVariant = &v
					break
				}
			}
		}

		thumbnail := "https://via.placeholder.com/150"
		if len(product.Thumbnails) > 0 {
			thumbnail = product.Thumbnails[0]
		}

		result := gin.H{
			"order": gin.H{
				"id":          order.ID,
				"user_id":     order.UserID,
				"status":      order.Status,
				"total_price": order.TotalPrice,
				"created_at":  order.CreatedAt,
				"updated_at":  order.UpdatedAt,
			},
			"sample_item": gin.H{
				"order_item_id":     item.ID,
				"product_id":        item.ProductID,
				"product_name":      product.Name,
				"variant":           selectedVariant,
				"quantity":          item.Quantity,
				"price_at_purchase": item.PriceAtPurchase,
				"thumbnail":         thumbnail,
			},
		}

		results = append(results, result)
	}

	if len(results) == 0 {
		c.JSON(http.StatusOK, gin.H{
			"message": "⚠️ Order kosong",
			"data":    []OrderModel{},
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Berhasil mengambil semua order dengan detail",
		"data":    results,
	})
}


// ++++++++++++++++++++++++
//
//	Order CREATE
//
// ++++++++++++++++++++++++
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
		addRoute(employeeAdminRestock, "GET", "", []string{"employee", "admin"}, GetUnreadRestockRequests, db)
	}

	// 🔐 Khusus admin
	adminRestock := r.Group("/api/v1/restock-requests")
	adminRestock.Use(AuthMiddleware(), RoleMiddleware("admin"))
	{
		addRoute(adminRestock, "PUT", "/:id/read", []string{"admin"}, UpdateRestockRequestStatus, db)
		addRoute(adminRestock, "DELETE", "/:id", []string{"admin"}, DeleteRestockRequest, db)
	}
}

// ++++++++++++++++++++++++
//
//	RestockRequest READ
//
// ++++++++++++++++++++++++
func GetUnreadRestockRequests(c *gin.Context, db *sql.DB) {
	rows, err := db.Query(`
		SELECT rr.id, rr.product_id, rr.product_variant_id
		FROM restock_requests rr
		WHERE rr.status = 'unread'
		ORDER BY rr.created_at ASC
	`)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil permintaan restock"})
		return
	}
	defer rows.Close()

	type RestockDisplay struct {
		RequestID   int     `json:"id"`
		ProductID   int     `json:"product_id"`
		ProductName string  `json:"product_name"`
		Thumbnail   string  `json:"thumbnail"`
		VariantID   *int    `json:"variant_id,omitempty"`
		VariantName *string `json:"variant_name,omitempty"`
		Stock       int     `json:"stock"`
	}

	var results []RestockDisplay

	for rows.Next() {
		var (
			requestID          int
			productID          int
			productVariantID   sql.NullInt64
		)
		if err := rows.Scan(&requestID, &productID, &productVariantID); err != nil {
			continue
		}

		product, err := getSingleProductWithVariantsAndImages(db, productID)
		if err != nil {
			continue
		}

		stockValue := 0
		if product.Stock != nil {
			stockValue = *product.Stock
		}
		r := RestockDisplay{
			RequestID:   requestID,
			ProductID:   product.ID,
			ProductName: product.Name,
			Thumbnail:   "", // default
			Stock:       stockValue, // fallback if no variant
		}

		if len(product.Thumbnails) > 0 {
			r.Thumbnail = product.Thumbnails[0]
		}

		if productVariantID.Valid {
			variantID := int(productVariantID.Int64)
			r.VariantID = &variantID
			for _, v := range product.Variants {
				if v.ID == variantID {
					r.VariantName = &v.Name
					r.Stock = v.Stock
					break
				}
			}
		}

		results = append(results, r)
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Berhasil mengambil data produk",
		"data":    results,
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

	// Cek apakah id valid dalam tabel restock_requests
	if !ValidateRecordExistence(c, db, "restock_requests", idInt) {
		return
	}

	// Update status permintaan restock
	result, err := db.Exec(`UPDATE restock_requests SET status = "read" WHERE id = ?`, idInt)
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

// =========================
// Khusus untuk web admin
// =========================

func StatRoutes(r *gin.Engine, db *sql.DB) {
	statGroup := r.Group("/api/v1/stats")

	addRoute(statGroup, "GET", "/sales", []string{"admin"}, GetSalesPerMonth, db) // Lihat nominal penjualan per bulan dalam setahun terakhir
	addRoute(statGroup, "GET", "/lowstocks", []string{"admin"}, GetLowStocks, db) // Lihat produk yang hampir atau sudah habis
	addRoute(statGroup, "GET", "/employees", []string{"admin"}, GetEmployees, db) // Lihat semua akun karyawan
}

type SalesPerMonth struct {
	Month      string `json:"month"`
	TotalSales int64  `json:"total_sales"`
}

func GetSalesPerMonth(c *gin.Context, db *sql.DB) {
	rows, err := db.Query(`
		SELECT created_at, total_price
		FROM orders
		WHERE status = 'done' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
		ORDER BY created_at ASC;
	`)
	if err != nil {
		log.Printf("SQL Query Error: %v", err)
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil daftar penjualan: " + err.Error()})
		return
	}
	defer rows.Close()

	monthlySalesMap := make(map[string]int64)
	var uniqueMonths []string

	for rows.Next() {
		var createdAt time.Time
		var totalPrice int64

		if err := rows.Scan(&createdAt, &totalPrice); err != nil {
			log.Printf("Error scanning row: %v", err)
			continue
		}

		monthKey := createdAt.Format("January 2006")
		if _, exists := monthlySalesMap[monthKey]; !exists {
			uniqueMonths = append(uniqueMonths, monthKey)
		}
		monthlySalesMap[monthKey] += totalPrice
	}

	var results []SalesPerMonth
	for _, month := range uniqueMonths {
		results = append(results, SalesPerMonth{
			Month:      month,
			TotalSales: monthlySalesMap[month],
		})
	}

	if len(results) == 0 {
		c.JSON(http.StatusOK, gin.H{
			"message": "⚠️ Data penjualan kosong",
			"data":    []SalesPerMonth{},
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Berhasil mengambil data penjualan per bulan selama setahun",
		"data":    results,
	})
}
func GetLowStocks(c *gin.Context, db *sql.DB) {
    // Ambil produk yang stok <= 2 dan bukan null
    rows, err := db.Query(`
        SELECT 
            p.id, 
            p.name, 
            (SELECT thumbnail_url FROM product_images WHERE product_id = p.id LIMIT 1) as thumbnail_url,
            pv.id, 
            pv.name, 
            CASE 
                WHEN pv.stock IS NOT NULL THEN pv.stock
                ELSE p.stock
            END as stock
        FROM products p
        LEFT JOIN product_variants pv ON p.id = pv.product_id
        WHERE 
            (pv.stock IS NOT NULL AND pv.stock <= 1) OR
            (pv.stock IS NULL AND p.stock IS NOT NULL AND p.stock <= 1)
        ORDER BY p.id, pv.id;
    `)
    if err != nil {
        c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil daftar produk"})
        return
    }
    defer rows.Close()

    type LowStockProducts struct {
        ProductID   int     `json:"product_id"`
        ProductName string  `json:"product_name"`
        Thumbnail   *string `json:"thumbnail"`  // Menggunakan pointer karena bisa null
        VariantID   *int    `json:"variant_id"`
        VariantName *string `json:"variant_name"`
        Stock       int     `json:"stock"`
    }

    var products []LowStockProducts

    for rows.Next() {
        var product LowStockProducts
        err := rows.Scan(
            &product.ProductID,
            &product.ProductName,
            &product.Thumbnail,
            &product.VariantID,
            &product.VariantName,
            &product.Stock,
        )
        if err != nil {
            c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal memproses data produk"})
            return
        }

        // Hanya tambahkan jika stock <= 2 (double check)
        if product.Stock <= 2 {
            products = append(products, product)
        }
    }

    if err = rows.Err(); err != nil {
        c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Error saat membaca data produk"})
        return
    }

    if len(products) == 0 {
        c.JSON(http.StatusOK, gin.H{
            "message": "⚠️ Tidak ada produk dengan stok rendah",
            "data":    []LowStockProducts{},
        })
        return
    }

    c.JSON(http.StatusOK, gin.H{
        "message": "✅ Berhasil mengambil data produk dengan stok rendah",
        "data":    products,
    })
}

func GetEmployees(c *gin.Context, db *sql.DB) {
	rows, err := db.Query(`
		SELECT id, username, thumbnail_url
		FROM employees;
	`)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil daftar akun karyawan"})
		return
	}
	defer rows.Close()

	type EmployeeAccounts struct {
		ID            int    `json:"id"`
		Username      string `json:"username"`
		ThumbnailURL  string `json:"thumbnail_url"`
	}

	var results []EmployeeAccounts

	for rows.Next() {
		var employees EmployeeAccounts
		err := rows.Scan(
			&employees.ID,
			&employees.Username,
			&employees.ThumbnailURL,
		)
		if err != nil {
			continue
		}

		results = append(results, employees)
	}

	if len(results) == 0 {
		c.JSON(http.StatusOK, gin.H{
			"message": "⚠️ Data akun kosong",
			"data":    []EmployeeAccounts{},
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Berhasil mengambil data akun karyawan",
		"data":    results,
	})
}
