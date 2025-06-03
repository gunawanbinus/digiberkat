package main

import (
	"database/sql"
	"os"
	//"fmt"
	"net/http"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
	"golang.org/x/crypto/bcrypt"
)

// Route setup
func AuthRoutes(r *gin.Engine, db *sql.DB) {
	r.POST("/api/v1/login", func(c *gin.Context) {
		handleLoginWithRole(c, db)
	})
	r.POST("/api/v1/register/user", func(c *gin.Context) {
		handleUserRegister(c, db)
	})
	r.POST("/api/v1/register/employee", func(c *gin.Context) {
		handleEmployeeRegister(c, db)
	})
	r.POST("/api/v1/register/admin", func(c *gin.Context) {
        handleAdminRegister(c, db)
    })
}

// =================== LOGIN ===================

type RoleLoginInput struct {
	Username    string `json:"username"` //usename user,employee and admin
	Password string `json:"password"`
	Role     string `json:"role"` // "user", "admin", or "employee"
}

func handleLoginWithRole(c *gin.Context, db *sql.DB) {
	var input RoleLoginInput
	if err := c.ShouldBindJSON(&input); err != nil || input.Username == "" || input.Password == "" || input.Role == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Username, password, dan role wajib diisi"})
		return
	}
	username := strings.ToLower(strings.TrimSpace(input.Username))
    password := strings.TrimSpace(input.Password)
    role := strings.ToLower(strings.TrimSpace(input.Role))

	 // Daftar role yang valid
    validRoles := map[string]bool{
        "user":    true,
        "admin":   true, 
        "employee": true,
    }
    
    if !validRoles[role] {
        c.JSON(http.StatusBadRequest, gin.H{
            "error": "Role tidak valid",
            "allowed_roles": []string{"user", "admin", "employee"},
        })
        return
    }

	switch role {
	case "user":
		if user, found := findUserByUsername(db, username); found {
			if checkPassword(c, password, user.Password) {
				respondWithToken(c, user.ID, user.Username, "user")
				return
			}
			return
		} else {
			//c.JSON(http.StatusUnauthorized, gin.H{"error": "❌ Email tidak ditemukan sebagai user"})
			c.JSON(http.StatusUnauthorized, gin.H{"error": "❌ Username tidak ditemukan"})
		}
	case "admin":
		if admin, found := findAdminByUsername(db, username); found {
			if checkPassword(c, password, admin.Password) {
				respondWithToken(c, 0, admin.Username, "admin")
				return
			}
			return
		} else {
			//c.JSON(http.StatusUnauthorized, gin.H{"error": "❌ Email tidak ditemukan sebagai admin"})
			c.JSON(http.StatusUnauthorized, gin.H{"error": "❌ Email tidak ditemukan"})
		}
	case "employee":
		if emp, found := findEmployeeByUsername(db, username); found {
			if checkPassword(c, password, emp.Password) {
				respondWithToken(c, emp.ID, emp.Username, "employee")
				return
			}
			return
		} else {
			//c.JSON(http.StatusUnauthorized, gin.H{"error": "❌ Email tidak ditemukan sebagai employee"})
			c.JSON(http.StatusUnauthorized, gin.H{"error": "❌ Email tidak ditemukan"})
		}
	}
}
// Helper functions


// =================== REGISTER USER ===================

type UserRegisterInput struct {
	Username string `json:"username"`
	Password string `json:"password"`
}

func handleUserRegister(c *gin.Context, db *sql.DB) {
    // 1. Validasi Input
    var input UserRegisterInput
    if err := c.ShouldBindJSON(&input); err != nil || input.Username == "" || input.Password == "" {
        c.JSON(http.StatusBadRequest, gin.H{"error": "Username dan password wajib diisi"})
        return
    }

    // 2. Normalisasi Input
    username := strings.ToLower(strings.TrimSpace(input.Username))
    password := strings.TrimSpace(input.Password)

    // 3. Validasi Username/Email
    if !strings.Contains(username, "@") {
        c.JSON(http.StatusBadRequest, gin.H{"error": "Format username harus berupa email yang valid"})
        return
    }
    if len(username) < 3 {
        c.JSON(http.StatusBadRequest, gin.H{"error": "Username minimal 3 karakter"})
        return
    }

    // 4. Validasi Password
    if len(password) < 6 {
        c.JSON(http.StatusBadRequest, gin.H{"error": "Password minimal 6 karakter"})
        return
    }

    // 5. Cek Ketersediaan Username
    if _, found := findUserByUsername(db, username); found {
        c.JSON(http.StatusConflict, gin.H{"error": "Username sudah terdaftar"})
        return
    }

    // 6. Enkripsi Password
    hashedPwd, err := bcrypt.GenerateFromPassword([]byte(password), bcrypt.DefaultCost)
    if err != nil {
        c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal mengenkripsi password"})
        return
    }

    // 7. Default Thumbnail
    thumbnailURL := "https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png"

    // 8. Mulai Transaksi Database
    tx, err := db.Begin()
    if err != nil {
        c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal memulai transaksi database"})
        return
    }

    // 9. Insert User
    res, err := tx.Exec(
        "INSERT INTO users (username, thumbnail_url, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
        username, thumbnailURL, string(hashedPwd),
    )
    if err != nil {
        tx.Rollback()
        c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal mendaftarkan user"})
        return
    }

    // 10. Dapatkan User ID
    userID, err := res.LastInsertId()
    if err != nil {
        tx.Rollback()
        c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal mendapatkan ID user"})
        return
    }

    // 11. Buat Cart untuk User
    _, err = tx.Exec(
        "INSERT INTO carts (id, user_id, total_price, created_at, updated_at) VALUES (?, ?, 0, NOW(), NOW())",
        userID, userID, // ID cart sama dengan user_id
    )
    if err != nil {
        tx.Rollback()
        c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal membuat cart untuk user"})
        return
    }

    // 12. Commit Transaksi
    if err := tx.Commit(); err != nil {
        c.JSON(http.StatusInternalServerError, gin.H{"error": "Gagal menyelesaikan pendaftaran"})
        return
    }

    // 13. Berikan Response
    respondWithToken(c, int(userID), username, "user")
}

// =================== REGISTER EMPLOYEE ===================

type EmployeeRegisterInput struct {
	Username    string `json:"username"`
	Password string `json:"password"`
	PositionName     string `json:"position_name"` 
}

func handleEmployeeRegister(c *gin.Context, db *sql.DB) {
	var input EmployeeRegisterInput
	if err := c.ShouldBindJSON(&input); err != nil || input.Username == "" || input.Password == "" || input.PositionName == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Username, password, dan position_name wajib diisi"})
		return
	}

	if !validPosition(db, strings.ToLower(input.PositionName)) {
	c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Role tidak valid. Pastikan sesuai dengan data di tabel position."})
	return
}


	// Untuk employee
	// periksa apakah email sudah terdaftar
	// jika sudah terdaftar, kembalikan status 409 Conflict
	if _, found := findEmployeeByUsername(db, strings.ToLower(input.Username)); found {
		c.JSON(http.StatusConflict, gin.H{"error": "❌ Username sudah terdaftar"})
		return
	}
	//periksa format email
	// jika tidak valid, kembalikan status 400 Bad Request
	if !strings.Contains(input.Username, "@") {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format Username harus berupa email yang valid"})
		return
	}
	// periksa panjang password
	// jika kurang dari 6 karakter, kembalikan status 400 Bad Request
	if len(input.Password) < 6 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Password minimal 6 karakter"})
		return
	}

	//hashedPwd, _ := bcrypt.GenerateFromPassword([]byte(input.Password), bcrypt.DefaultCost)
	hashedPwd, err := bcrypt.GenerateFromPassword([]byte(input.Password), bcrypt.DefaultCost)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengenkripsi password"})
		return
	}

	thumbnail_url := "https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png"

	res, err := db.Exec("INSERT INTO employees (username, thumbnail_url, position_name, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)",
		strings.ToLower(input.Username), thumbnail_url, input.PositionName, string(hashedPwd), time.Now(), time.Now())
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mendaftarkan employee"})
		return
	}
	id, _ := res.LastInsertId()
	// Langsung login (generate token)
	respondWithToken(c, int(id), strings.ToLower(input.Username), "employee")
	//c.JSON(http.StatusCreated, gin.H{"message": "✅ Registrasi employee berhasil"})
}

type AdminRegisterInput struct {
    Username string `json:"username"` // Email untuk admin
    Password string `json:"password"`
    SecretCode string `json:"secret_code"` // Kode rahasia untuk verifikasi admin
}

func handleAdminRegister(c *gin.Context, db *sql.DB) {
    var input AdminRegisterInput
    if err := c.ShouldBindJSON(&input); err != nil || input.Username == "" || input.Password == "" || input.SecretCode == "" {
        c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Username, password, dan secret code wajib diisi"})
        return
    }

    // Verifikasi secret code (simpan di environment variable)
    if input.SecretCode != os.Getenv("ADMIN_SECRET_CODE") {
        c.JSON(http.StatusUnauthorized, gin.H{"error": "❌ Kode registrasi admin tidak valid"})
        return
    }

    // Validasi format email
    if !strings.Contains(input.Username, "@") {
        c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format username harus berupa email yang valid"})
        return
    }

    // Validasi panjang password
    if len(input.Password) < 8 {
        c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Password minimal 8 karakter"})
        return
    }

    // Cek apakah username sudah terdaftar
    if _, found := findAdminByUsername(db, strings.ToLower(input.Username)); found {
        c.JSON(http.StatusConflict, gin.H{"error": "❌ Username sudah terdaftar"})
        return
    }

    // Enkripsi password
    hashedPwd, err := bcrypt.GenerateFromPassword([]byte(input.Password), bcrypt.DefaultCost)
    if err != nil {
        c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengenkripsi password"})
        return
    }

    // Default thumbnail
    thumbnailURL := "https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png"

    // Insert ke database
    res, err := db.Exec("INSERT INTO admins (username, thumbnail_url, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?)",
        strings.ToLower(input.Username), thumbnailURL, string(hashedPwd), time.Now(), time.Now())
    
    if err != nil {
        c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mendaftarkan admin"})
        return
    }

    id, _ := res.LastInsertId()
    respondWithToken(c, int(id), strings.ToLower(input.Username), "admin")
}


// =================== DATABASE HELPER ===================

func findUserByUsername(db *sql.DB, username string) (User, bool) {
	var u User
	err := db.QueryRow("SELECT id, username, password  FROM users WHERE username = ?", username).Scan(&u.ID,  &u.Username, &u.Password)
	return u, err == nil
}

func findAdminByUsername(db *sql.DB, username string) (Admin, bool) {
	var a Admin
	err := db.QueryRow("SELECT id, username, password FROM admins WHERE username = ?", username).
		Scan( &a.Username, &a.Password)
	return a, err == nil
}

func findEmployeeByUsername(db *sql.DB, username string) (Employee, bool) {
	var e Employee
	err := db.QueryRow("SELECT id, username, password FROM employees WHERE username = ?", username).
		Scan(&e.ID, &e.Username, &e.Password)
	return e, err == nil
}

// =================== UTILITY ===================
func validPosition(db *sql.DB, positionName string) bool {
	query := "SELECT 1 FROM `position` WHERE position_name = ? LIMIT 1"
	rows, err := db.Query(query, positionName)
	if err != nil {
		return false
	}
	defer rows.Close()

	return rows.Next()
}


func checkPassword(c *gin.Context, plainPwd, hashedPwd string) bool {
	err := bcrypt.CompareHashAndPassword([]byte(hashedPwd), []byte(plainPwd))
	if err != nil {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "❌ Password salah"})
		return false
	}
	return true
}

func respondWithToken(c *gin.Context, id int, username, role string) {
	token, err := GenerateToken(id, username, role)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal membuat token"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "✅ Registrasi atau Login berhasil",
		"token":   token,
		"role":    role,
		"user": gin.H{
			"id":    id,
			"username":  username,
		},
	})
}
