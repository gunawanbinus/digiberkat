package main

import "time"

type User struct {
	ID        int       `json:"id"`
	Username  string    `json:"username"`
	Password  string    `json:"password"` // hashed password
	Phone     string       `json:"phone"`
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}

type Admin struct {
	Username     string    `json:"username"`
	Password  string    `json:"password"` // hashed password
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}

type Employee struct {
	ID        int       `json:"id"`
	Username  string    `json:"username"`
	PositionName  string    `json:"position_name"`  
	Password  string    `json:"password"` // hashed password
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}
