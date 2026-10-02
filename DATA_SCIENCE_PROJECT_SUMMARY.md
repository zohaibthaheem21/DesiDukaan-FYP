# DesiDukaan - Data-Driven E-Commerce & Analytics Platform (FYP)

## 📌 Data Science & Machine Learning Enhancements Overview

**DesiDukaan** has been upgraded from a multi-vendor marketplace into a comprehensive **Data-Driven E-Commerce & Business Intelligence Platform**, featuring full-stack data engineering, machine learning models, NLP sentiment classification, collaborative filtering recommendation systems, customer segmentation, and demand forecasting pipelines.

---

## 🛠️ Data Science Modules & Architecture

### 1. 🤖 Item-Based Collaborative Filtering Recommendation Engine
- **Algorithm**: Cosine Similarity matrix on user behavioral interactions (`view`, `wishlist`, `cart`, `purchase`).
- **Feature**: Recommends top personalized products to logged-in customers dynamically on the customer storefront and dashboard.
- **Database Tables**: `user_interactions`, `product_recommendations`.
- **Implementation**: [`analytics/analytics_engine.py`](file:///d:/Hard%20Wala%20Material/DesiDukaan/analytics/analytics_engine.py) & [`includes/analytics_engine.php`](file:///d:/Hard%20Wala%20Material/DesiDukaan/includes/analytics_engine.php).

### 2. 💬 NLP Sentiment Analysis on Customer Reviews
- **Algorithm**: Rule-based & TF-IDF term frequency sentiment classifier calculating continuous polarity scores (`-1.0` to `+1.0`) and sentiment labels (`positive`, `neutral`, `negative`).
- **Feature**: Real-time evaluation of customer product feedback and vendor rating polarity.
- **Database Table**: `product_reviews`.
- **Implementation**: [`analytics/analytics_engine.py`](file:///d:/Hard%20Wala%20Material/DesiDukaan/analytics/analytics_engine.py).

### 3. 👥 Customer RFM (Recency, Frequency, Monetary) Segmentation
- **Model**: Recency-Frequency-Monetary (RFM) clustering model classifying platform users into actionable segments: `VIP`, `Loyal Customer`, `Potential Loyalist`, and `At Risk`.
- **Feature**: Enables targeted marketing and customer retention analytics for platform administrators and shop vendors.
- **Database Table**: `customer_rfm_analytics`.

### 4. 📈 Sales Trend & Demand Forecasting
- **Algorithm**: Time-series moving average & linear trend projection model for forecasting vendor sales revenue and product demand.
- **Feature**: Displayed live on the Seller Business Intelligence Dashboard ([`seller/dashboard.php`](file:///d:/Hard%20Wala%20Material/DesiDukaan/seller/dashboard.php)).

### 5. 📊 Dataset Exporter for EDA & Machine Learning (CSV Pipelines)
- **Script**: [`analytics/export_dataset.py`](file:///d:/Hard%20Wala%20Material/DesiDukaan/analytics/export_dataset.py) exports clean tabular e-commerce datasets into CSV format (`products_dataset.csv`, `reviews_sentiment_dataset.csv`, `customer_rfm_segments.csv`) ready for Exploratory Data Analysis (EDA) in **Jupyter Notebooks**, **Google Colab**, **Pandas**, and **Scikit-learn**.

---

## 📝 How to Present DesiDukaan on Your CV for Italian Master's Applications

### **Project Title**: 
> **DesiDukaan – Data-Driven Multi-Vendor E-Commerce & Analytics Platform** *(Final Year Project)*

### **Bullet Points for CV**:
* *Designed relational database schema and scalable data engineering architecture for a localized multi-vendor e-commerce platform handling user interactions and transactions.*
* *Engineered an Item-Based Collaborative Filtering recommendation system using Cosine Similarity to deliver personalized product suggestions.*
* *Implemented an NLP Sentiment Analysis pipeline using TF-IDF and term frequency polarity scoring to evaluate customer reviews and vendor ratings.*
* *Constructed an RFM (Recency, Frequency, Monetary) customer segmentation model and time-series demand forecasting dashboard for business intelligence insights.*
* *Exported e-commerce behavioral datasets into structured CSV pipelines for Exploratory Data Analysis (EDA) in Python (Pandas/Scikit-learn).*

---

## 🚀 How to Run the Data Science Features
1. **Initialize Analytics Database & Generate Datasets**:
   Open browser at: `http://localhost/DesiDukaan/setup_data_science_features.php` or execute [`setup_data_science_features.php`](file:///d:/Hard%20Wala%20Material/DesiDukaan/setup_data_science_features.php).
2. **Execute Python ML Engine**:
   ```bash
   python analytics/analytics_engine.py
   ```
3. **Export CSV Datasets for EDA & Jupyter Notebooks**:
   ```bash
   python analytics/export_dataset.py
   ```
