-- DesiDukaan Data Science & Analytics Extension Schema
-- Adds tables for Product Reviews, Sentiment Analysis, User Interactions, Recommendations, and RFM Segmentation

USE desidukaan;

-- 1. Product Reviews & Sentiment Analysis Table
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT NOT NULL,
    sentiment_label ENUM('positive', 'neutral', 'negative') DEFAULT 'neutral',
    sentiment_score DECIMAL(5,4) DEFAULT 0.0000, -- Score between -1.0000 and +1.0000
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. User Interactions & Behavioral Log Table (for Recommendation Engine & Funnel Analysis)
CREATE TABLE IF NOT EXISTS user_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    interaction_type ENUM('view', 'wishlist', 'cart', 'purchase') NOT NULL,
    weight DECIMAL(3,2) DEFAULT 1.00, -- View=1.0, Wishlist=2.0, Cart=3.0, Purchase=5.0
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 3. Product Recommendation Engine Output Table
CREATE TABLE IF NOT EXISTS product_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    score DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
    algorithm VARCHAR(50) DEFAULT 'collaborative_filtering',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product_rec (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 4. Customer RFM Segmentation Table
CREATE TABLE IF NOT EXISTS customer_rfm_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNIQUE NOT NULL,
    recency_days INT NOT NULL DEFAULT 0,
    frequency_orders INT NOT NULL DEFAULT 0,
    monetary_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    rfm_score VARCHAR(10) DEFAULT '111',
    rfm_segment ENUM('VIP', 'Loyal Customer', 'Potential Loyalist', 'At Risk', 'Lost') DEFAULT 'Potential Loyalist',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index optimization for Data Analytics queries
CREATE INDEX idx_reviews_product ON product_reviews(product_id);
CREATE INDEX idx_reviews_sentiment ON product_reviews(sentiment_label);
CREATE INDEX idx_interactions_user ON user_interactions(user_id);
CREATE INDEX idx_interactions_product ON user_interactions(product_id);
CREATE INDEX idx_interactions_type ON user_interactions(interaction_type);
CREATE INDEX idx_recommendations_user ON product_recommendations(user_id, score DESC);
