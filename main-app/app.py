from flask import Flask, jsonify, render_template, request
import requests
import os
from functools import lru_cache

app = Flask(__name__)

product_service_host = "localhost" if os.getenv("HOSTNAME") is None else "product-service"
cart_service_host = "localhost" if os.getenv("HOSTNAME") is None else "cart-service"
review_service_host = "localhost" if os.getenv("HOSTNAME") is None else "review-service"


@lru_cache(maxsize=128)
def get_products(product_id):
    try:
        response = requests.get(f"http://{product_service_host}:3000/products/{product_id}")
        response.raise_for_status()
        return response.json()
    except requests.exceptions.RequestException as e:
        print(f"Error fetching product data: {e}")
        return {"error": "Failed to fetch product data"}


def get_carts(product_id):
    try:
        response = requests.get(f"http://{cart_service_host}:3002/cart/{product_id}")
        response.raise_for_status()
        data = response.json()

        print("CART RESPONSE:", data)
        return int(data)

        if 'data' in data:
            cart_item = data['data']
            if isinstance(cart_item, dict) and 'product_id' in cart_item:
                if cart_item['product_id'] == product_id:
                    return cart_item.get('quantity', 0)

        return 0

    except requests.exceptions.RequestException as e:
        print(f"Error fetching cart data: {e}")
        return 0


def get_reviews(product_id):
    try:
        response = requests.get(f"http://{review_service_host}:3003/product/{product_id}/review")
        response.raise_for_status()
        data = response.json()

        return {
            "reviews": data.get("reviews", []),
            "product": data.get("product", {})
        }

    except requests.exceptions.RequestException as e:
        print(f"Error fetching review data: {e}")
        return {"reviews": [], "product": {}}


@app.route('/product/<int:product_id>')
def get_product_info(product_id):
    product = get_products(product_id)
    cart = get_carts(product_id)
    review = get_reviews(product_id)

    combined_response = {
        "product": product if "error" not in product else None,
        "cart": cart,
        "reviews": review.get("reviews", []) if "error" not in review else []
    }

    if request.args.get('format') == 'json':
        return jsonify({
            "data": combined_response,
            "message": "Product data fetched successfully"
        })

    return render_template('product.html', **combined_response)


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=3005, debug=True)