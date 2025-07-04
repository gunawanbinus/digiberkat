package main

import (
	"bytes"
	"encoding/json"
	"net/http"
	"os"
	"sort"

	"github.com/gin-gonic/gin"
)

// ==============================
// 📦 STRUCT PRODUCT + VARIANT
// ==============================

type AIProductVariant struct {
	ID            int      `json:"id"`
	ProductID     int      `json:"product_id"`
	Name          string   `json:"name"`
	Price         *float64 `json:"price"`
	IsDiscounted  bool     `json:"is_discounted"`
	DiscountPrice *float64 `json:"discount_price"`
	Stock         *int     `json:"stock"`
	Image         string   `json:"image,omitempty"`
	CreatedAt     string   `json:"created_at,omitempty"`
	UpdatedAt     string   `json:"updated_at,omitempty"`
}

type Product struct {
	ID              int                `json:"id"`
	CategoryID      int                `json:"category_id"`
	Name            string             `json:"name"`
	Description     string             `json:"description,omitempty"`
	SearchVector    string             `json:"search_vector,omitempty"`
	IsVarians       bool               `json:"is_varians"`
	IsDiscounted    bool               `json:"is_discounted"`
	DiscountPrice   *float64           `json:"discount_price"`
	Price           *float64           `json:"price"`
	Stock           *int               `json:"stock"`
	Images          []string           `json:"images"`
	Thumbnails      []string           `json:"thumbnails"`
	Variants        []AIProductVariant `json:"variants,omitempty"`
	MinVariantPrice *float64           `json:"min_variant_price,omitempty"`
	IsAvailable     *bool              `json:"is_available,omitempty"`
	CreatedAt       string             `json:"created_at,omitempty"`
	UpdatedAt       string             `json:"updated_at,omitempty"`
	SimilarityScore float64            `json:"similarity_score,omitempty"`
}

type RecommendRequest struct {
	UserQuery string    `json:"userQuery"`
	Products  []Product `json:"products"`
}

// ==============================
// 🔗 ROUTES
// ==============================

func AiRoutes(r *gin.Engine) {
	api := r.Group("/api/v1/recommend")
	api.POST("", HandleRecommendation)
}

// ==============================
// 🔍 HANDLER
// ==============================

func HandleRecommendation(c *gin.Context) {
	var req RecommendRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "❌ Format JSON tidak valid"})
		return
	}

	// Ambil kalimat kandidat dari produk
	var sentences []string
	for _, p := range req.Products {
		if p.SearchVector != "" {
			sentences = append(sentences, p.SearchVector)
		} else if p.Description != "" {
			sentences = append(sentences, p.Description)
		} else {
			sentences = append(sentences, p.Name)
		}
	}

	// Hitung similarity dengan model HuggingFace
	scores, err := getSimilarityFromHF(req.UserQuery, sentences)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "❌ Gagal mengambil skor dari model HuggingFace"})
		return
	}

	// Gabungkan skor ke produk
	for i := range req.Products {
		if i < len(scores) {
			req.Products[i].SimilarityScore = scores[i]
		}
	}

	// Urutkan produk dari yang paling relevan
	sort.Slice(req.Products, func(i, j int) bool {
		return req.Products[i].SimilarityScore > req.Products[j].SimilarityScore
	})

	// Ambil top 3
	top := 3
	if len(req.Products) < 3 {
		top = len(req.Products)
	}

	c.JSON(http.StatusOK, gin.H{
		"data":    req.Products[:top],
		"message": "✅ Rekomendasi berhasil dihasilkan",
	})
}

// ==============================
// 📡 HUGGINGFACE REQUEST
// ==============================

func getSimilarityFromHF(query string, sentences []string) ([]float64, error) {
	url := "https://api-inference.huggingface.co/models/LazarusNLP/all-indo-e5-small-v4"
	token := os.Getenv("HF_TOKEN")

	payload := map[string]interface{}{
		"inputs": map[string]interface{}{
			"source_sentence": query,
			"sentences":       sentences,
		},
	}
	jsonData, _ := json.Marshal(payload)

	req, _ := http.NewRequest("POST", url, bytes.NewBuffer(jsonData))
	req.Header.Set("Authorization", "Bearer "+token)
	req.Header.Set("Content-Type", "application/json")

	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	var result []float64
	err = json.NewDecoder(resp.Body).Decode(&result)
	return result, err
}
