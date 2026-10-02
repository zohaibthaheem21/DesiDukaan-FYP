#!/usr/bin/env python3
"""
DesiDukaan Data Science - Dataset Exporter for EDA & ML Modeling
------------------------------------------------------------------
Exports e-commerce database records into CSV files for Data Analytics,
Jupyter Notebooks, and machine learning model training.
"""

import os
import csv
import json
from datetime import datetime

def generate_mock_datasets():
    """Generates clean CSV datasets for data science analysis"""
    export_dir = os.path.join(os.path.dirname(__file__), 'datasets')
    os.makedirs(export_dir, exist_ok=True)
    
    # 1. Products Dataset
    products_csv = os.path.join(export_dir, 'products_dataset.csv')
    products_data = [
        ['product_id', 'title', 'shop_id', 'category', 'price', 'quantity', 'created_at'],
        [1, 'Handmade Sindhi Ajrak Shawl', 10, 'Apparel', 3500.00, 15, '2025-01-10'],
        [2, 'Traditional Multani Pottery Vase', 10, 'Home Decor', 2200.00, 8, '2025-01-12'],
        [3, 'Embroidered Khussa Footwear', 12, 'Footwear', 2800.00, 20, '2025-01-15'],
        [4, 'Handcrafted Wooden Mirror Frame', 12, 'Home Decor', 4500.00, 5, '2025-01-20'],
        [5, 'Traditional Balochi Mirrorwork Bag', 14, 'Accessories', 1950.00, 12, '2025-01-22'],
    ]
    with open(products_csv, 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerows(products_data)
        
    # 2. Reviews & Sentiment Dataset
    reviews_csv = os.path.join(export_dir, 'reviews_sentiment_dataset.csv')
    reviews_data = [
        ['review_id', 'product_id', 'customer_id', 'rating', 'review_text', 'sentiment_label', 'sentiment_score'],
        [101, 1, 501, 5, 'Absolutely love this handicraft! The quality and fabric are outstanding.', 'positive', 0.92],
        [102, 1, 502, 5, 'Beautiful product with rich traditional colors.', 'positive', 0.88],
        [103, 2, 503, 3, 'Decent quality product. Delivery took a bit long.', 'neutral', 0.15],
        [104, 3, 504, 2, 'Fabric quality was lower than expected. Color faded.', 'negative', -0.55],
        [105, 4, 505, 5, 'Exquisite artisan craftsmanship! Super fast shipping.', 'positive', 0.95],
    ]
    with open(reviews_csv, 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerows(reviews_data)

    # 3. RFM Customer Segments Dataset
    rfm_csv = os.path.join(export_dir, 'customer_rfm_segments.csv')
    rfm_data = [
        ['customer_id', 'recency_days', 'frequency_orders', 'monetary_total_pkr', 'rfm_segment'],
        [501, 3, 7, 18500.00, 'VIP'],
        [502, 8, 4, 11200.00, 'Loyal Customer'],
        [503, 14, 2, 4500.00, 'Potential Loyalist'],
        [504, 35, 1, 2800.00, 'At Risk'],
        [505, 2, 6, 16400.00, 'VIP'],
    ]
    with open(rfm_csv, 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerows(rfm_data)

    print("=" * 60)
    print(" CSV Datasets Exported Successfully!")
    print(f" Directory: {export_dir}")
    print(f"  • {os.path.basename(products_csv)}")
    print(f"  • {os.path.basename(reviews_csv)}")
    print(f"  • {os.path.basename(rfm_csv)}")
    print("=" * 60)

if __name__ == '__main__':
    generate_mock_datasets()
