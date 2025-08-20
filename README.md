# intentory-php

Auth

POST /auth/register {name,email,password} → cria user (trigger cria o cart)

POST /auth/login {email,password} → {token}

Suppliers

GET /suppliers?active=1

POST /suppliers {name, ...}

GET /suppliers/{id}

PUT /suppliers/{id}

DELETE /suppliers/{id} (se não houver vínculos críticos)

Products

GET /products?q=nome&active=1

POST /products {sku,name,unit_price,stock,...}

GET /products/{id}

PUT /products/{id}

DELETE /products/{id}

Product–Suppliers (N:N)

GET /products/{id}/suppliers

POST /products/{id}/suppliers {supplier_id,cost_price,lead_time_days,supply_sku}

DELETE /products/{id}/suppliers/{supplier_id}

Cart (1:1 por user)

GET /me/cart → retorna carrinho do usuário autenticado

Itens do carrinho

POST /me/cart/items {product_id, quantity} → grava unit_price do produto atual

PUT /me/cart/items/{product_id} {quantity}

DELETE /me/cart/items/{product_id}

POST /me/cart/checkout → muda status para CHECKED_OUT (e aqui você poderia, no futuro, gerar um pedido, abater estoque etc.)




# Estrutura do banco de dados:

-- Recomendo estes defaults no banco:
-- CREATE DATABASE inventory_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE inventory_dev;

-- 1) USERS
CREATE TABLE users (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2) SUPPLIERS (fornecedores)
CREATE TABLE suppliers (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(160) NOT NULL,
  cnpj VARCHAR(32) NULL,                  -- opcional (ou outro identificador)
  email VARCHAR(160) NULL,
  phone VARCHAR(40) NULL,
  address TEXT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_suppliers_name (name)
) ENGINE=InnoDB;

-- 3) PRODUCTS
CREATE TABLE products (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  sku VARCHAR(64) NOT NULL,               -- identificador interno
  name VARCHAR(160) NOT NULL,
  description TEXT NULL,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  stock INT NOT NULL DEFAULT 0,           -- estoque disponível
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_products_sku (sku),
  INDEX idx_products_name (name),
  CHECK (unit_price >= 0),
  CHECK (stock >= 0)
) ENGINE=InnoDB;

-- 4) PRODUCT_SUPPLIERS (N:N)
CREATE TABLE product_suppliers (
  product_id BIGINT NOT NULL,
  supplier_id BIGINT NOT NULL,
  supply_sku VARCHAR(64) NULL,            -- SKU do fornecedor (se houver)
  cost_price DECIMAL(12,2) NULL,          -- custo praticado por este fornecedor
  lead_time_days INT NULL,                -- prazo de entrega estimado
  PRIMARY KEY (product_id, supplier_id),
  CONSTRAINT fk_ps_product FOREIGN KEY (product_id) REFERENCES products(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ps_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CHECK (cost_price IS NULL OR cost_price >= 0),
  CHECK (lead_time_days IS NULL OR lead_time_days >= 0)
) ENGINE=InnoDB;

-- 5) CARTS (1:1 com users)
-- Regra: cada usuário tem exatamente UM carrinho.
-- Vamos criar o carrinho automaticamente via trigger após inserir um usuário.
CREATE TABLE carts (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL UNIQUE,         -- UNIQUE garante no máx. 1 por usuário
  status ENUM('ACTIVE','CHECKED_OUT','ABANDONED') NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6) CART_ITEMS (produtos dentro do carrinho)
CREATE TABLE cart_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  cart_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,      -- “congela” o preço do momento do add
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cart_product (cart_id, product_id), -- um produto por carrinho
  INDEX idx_cart_items_cart (cart_id),
  INDEX idx_cart_items_product (product_id),
  CONSTRAINT fk_ci_cart FOREIGN KEY (cart_id) REFERENCES carts(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ci_product FOREIGN KEY (product_id) REFERENCES products(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CHECK (quantity > 0),
  CHECK (unit_price >= 0)
) ENGINE=InnoDB;
