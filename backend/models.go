package main

import (
	"time"
)

type CategoryModel struct {
	ID          int    `json:"id"`
	Name        string `json:"name"`
	Description string `json:"description"`
}

type PositionModel struct {
	Name        string `json:"position_name"`
	Description string `json:"description"`
}

type ProductsModel struct {
	ID            int       `json:"id"`
	CategoryID    int       `json:"category_id"`
	Name          string    `json:"name"`
	Description   string    `json:"description"`
	IsVarians     bool      `json:"is_varians"`
	IsDiscounted  bool      `json:"is_discounted"`
	DiscountPrice *int      `json:"discount_price"`
	Price         *int      `json:"price"`
	Stock         *int      `json:"stock"`
	SearchVector  *string   `json:"search_vector"`
	CreatedAt     time.Time `json:"created_at"`
	UpdatedAt     time.Time `json:"updated_at"`
}

type ProductsBasicModel struct {
	ID            int       `json:"id"`
	CategoryID    int       `json:"category_id"`
	Name          string    `json:"name"`
	Description   string    `json:"description"`
	IsVarians     bool      `json:"is_varians"`
	IsDiscounted  bool      `json:"is_discounted"`
	DiscountPrice *int      `json:"discount_price"`
	Price         *int      `json:"price"`
	Stock         *int      `json:"stock"`
	Images        []string  `json:"images"`     // Array URL gambar
	Thumbnails    []string  `json:"thumbnails"` // Array thumbnail
	Variants      []Variant `json:"variants,omitempty"`
}

type Variant struct {
	ID            int    `json:"id"`
	Name          string `json:"name"`
	Price         int    `json:"price"`
	IsDiscounted  bool   `json:"is_discounted"`
	DiscountPrice *int   `json:"discount_price"` // NULLable
	Stock         int    `json:"stock"`
}

type ProductVariantModel struct { //color, is_services
	ID            int       `json:"id"`
	ProductID     int       `json:"product_id"`
	Name          string    `json:"name"`
	Price         int       `json:"price"`
	IsDiscounted  bool      `json:"is_discounted"`
	DiscountPrice *int      `json:"discount_price"` // NULLable
	Stock         int       `json:"stock"`
	SearchVector  *string   `json:"search_vector"` // NULLable
	CreatedAt     time.Time `json:"created_at"`
	UpdatedAt     time.Time `json:"updated_at"`
}

type ProductImageModel struct {
	ID           int    `json:"id"`
	ProductID    int    `json:"product_id"`
	ImageURL     string `json:"image_url"`
	ThumbnailURL string `json:"thumbnail_url"`
}

type CartsBasicModel struct {
	ID               int       `json:"id"` // id cart item
	CartID           int       `json:"cart_id"`
	ProductID        int       `json:"product_id"`
	ProductVariantID *int      `json:"product_variant_id"`
	Name             string    `json:"name"`
	Stock            *int      `json:"stock"`
	Thumbnails       []string  `json:"thumbnails"` // Array thumbnail
	Variants         []Variant `json:"variants,omitempty"`
	Quantity         int       `json:"quantity"`
	Price            int       `json:"price"`
	PricePerItem     int       `json:"price_per_item"`
	TotalPrice       int       `json:"total_price"`
}

type CartModel struct {
	ID         int       `json:"id"`          // Sama dengan user_id
	TotalPrice int       `json:"total_price"` // total harga semua item di cart
	CreatedAt  time.Time `json:"created_at"`
	UpdatedAt  time.Time `json:"updated_at"`
}
type CartItemModel struct {
	ID               int       `json:"id"`
	CartID           int       `json:"cart_id"`
	ProductID        int       `json:"product_id"`
	ProductVariantID *int      `json:"product_variant_id"` // bisa NULL, jadi pakai pointer
	Quantity         int       `json:"quantity"`
	PricePerItem     int       `json:"price_per_item"` // harga per item
	TotalPrice       int       `json:"total_price"`    // total harga (quantity * price_per_item)
	CreatedAt        time.Time `json:"created_at"`
	UpdatedAt        time.Time `json:"updated_at"`
}

type OrderBasicModel struct {
	ID               int       `json:"id"` // id cart item
	OrderID           int       `json:"order_id"`
	ProductID        int       `json:"product_id"`
	ProductVariantID *int      `json:"product_variant_id"`
	Name             string    `json:"name"`
	Thumbnails       []string  `json:"thumbnails"` // Array thumbnail
	Variants         []Variant `json:"variants,omitempty"`
	Quantity         int       `json:"quantity"`
	Price            int       `json:"price"`
	PriceAtPurchase     int       `json:"price_at_purchase"`
	TotalPrice       int       `json:"total_price"`
}

type OrderModel struct {
	ID         int       `json:"id"`
	UserID     int       `json:"user_id"`
	CartUserID *int      `json:"cart_user_id"` // bisa NULL, pakai pointer
	Status     string    `json:"status"`
	TotalPrice int       `json:"total_price"`
	ExpiredAt  time.Time `json:"expired_at"`
	CreatedAt  time.Time `json:"created_at"`
	UpdatedAt  time.Time `json:"updated_at"`
}

type OrderItemModel struct {
	ID               int  `json:"id"`
	OrderID          int  `json:"order_id"`
	ProductID        int  `json:"product_id"`
	ProductVariantID *int `json:"product_variant_id"` // bisa NULL, pakai pointer
	Quantity         int  `json:"quantity"`
	PriceAtPurchase  int  `json:"price_at_purchase"`
	TotalPrice       int  `json:"total_price"`
}

type RestockRequestModel struct {
	ID               int       `json:"id"`
	UserID           int       `json:"user_id"`            // Tak Bisa NULL
	ProductID        int       `json:"product_id"`         // Tak Bisa NULL
	ProductVariantID *int      `json:"product_variant_id"` // Bisa NULL
	Message          string    `json:"message"`            //Tak Bisa NULL
	Status           string    `json:"status"`             // ENUM: "unread", "read", "done"
	CreatedAt        time.Time `json:"created_at"`
}

type NotificationModel struct {
	ID        int       `json:"id"`
	UserID    int       `json:"user_id"`
	Message   string    `json:"message"`
	IsRead    bool      `json:"is_read"`
	CreatedAt time.Time `json:"created_at"`
}
