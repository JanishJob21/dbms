from flask import Flask, request, jsonify, send_from_directory, render_template, session
from flask_cors import CORS
import mysql.connector
from mysql.connector import Error
import os
from dotenv import load_dotenv
from decimal import Decimal
import json
from werkzeug.security import check_password_hash, generate_password_hash

# Load environment variables
load_dotenv()

app = Flask(__name__, 
    static_url_path='',
    static_folder='.',
    template_folder='.'
)
CORS(app)
app.secret_key = os.getenv('SECRET_KEY', 'your-secret-key-here')  # Add a secret key for sessions

# Custom JSON encoder to handle Decimal
class DecimalEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, Decimal):
            return float(obj)
        return super(DecimalEncoder, self).default(obj)

app.json_encoder = DecimalEncoder

# Database configuration
db_config = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_NAME', 'grocery_store')
}

def get_db_connection():
    try:
        connection = mysql.connector.connect(**db_config)
        if connection.is_connected():
            return connection
    except Error as e:
        print(f"Error connecting to MySQL: {e}")
        return None

@app.route('/')
def serve_index():
    return send_from_directory('.', 'index.html')

@app.route('/<path:path>')
def serve_static(path):
    if os.path.exists(path):
        return send_from_directory('.', path)
    return send_from_directory('.', 'index.html')

@app.route('/api/login', methods=['POST'])
def login():
    try:
        data = request.json
        username = data.get('username')
        password = data.get('password')

        print(f"Login attempt - Username: {username}")  # Debug log

        conn = get_db_connection()
        if conn is None:
            return jsonify({'error': 'Database connection failed'}), 500

        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM users WHERE email = %s", (username,))
        user = cursor.fetchone()

        print(f"User found: {user is not None}")  # Debug log

        cursor.close()
        conn.close()

        if user and user['password'] == password:
            return jsonify({
                'message': 'Login successful',
                'user': {
                    'id': user['id'],
                    'username': user['username'],
                    'email': user['email']
                }
            })
        return jsonify({'error': 'Invalid username or password'}), 401

    except Error as e:
        print(f"Database error: {str(e)}")  # Debug log
        return jsonify({'error': str(e)}), 500
    except Exception as e:
        print(f"Unexpected error: {str(e)}")  # Debug log
        return jsonify({'error': str(e)}), 500

@app.route('/api/signup', methods=['POST'])
def signup():
    try:
        data = request.json
        username = data.get('username')
        password = data.get('password')
        email = data.get('email')

        conn = get_db_connection()
        if conn is None:
            return jsonify({'error': 'Database connection failed'}), 500

        cursor = conn.cursor()
        
        # Check if username or email already exists
        cursor.execute("SELECT * FROM users WHERE username = %s OR email = %s", (username, email))
        if cursor.fetchone():
            return jsonify({'error': 'Username or email already exists'}), 400

        # Insert new user
        cursor.execute(
            "INSERT INTO users (username, password, email) VALUES (%s, %s, %s)",
            (username, password, email)  # In production, hash the password
        )
        conn.commit()

        cursor.close()
        conn.close()

        return jsonify({'message': 'Signup successful'})

    except Error as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/products', methods=['GET'])
def get_products():
    try:
        conn = get_db_connection()
        if conn is None:
            return jsonify({'error': 'Database connection failed'}), 500
        
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, product_name as name, description, price, image_url, stock FROM products")
        products = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        return jsonify(products)
    except Error as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/products/<int:product_id>', methods=['GET'])
def get_product(product_id):
    try:
        conn = get_db_connection()
        if conn is None:
            return jsonify({'error': 'Database connection failed'}), 500
        
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, product_name as name, description, price, image_url, stock FROM products WHERE id = %s", (product_id,))
        product = cursor.fetchone()
        
        cursor.close()
        conn.close()
        
        if product:
            return jsonify(product)
        return jsonify({'error': 'Product not found'}), 404
    except Error as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/cart', methods=['GET'])
def get_cart():
    try:
        user_id = request.args.get('user_id')
        if not user_id:
            return jsonify({'error': 'User ID is required'}), 400

        conn = get_db_connection()
        if conn is None:
            return jsonify({'error': 'Database connection failed'}), 500

        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT c.id, c.quantity, p.id as product_id, p.product_name as name, 
                   p.description, p.price, p.image_url, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = %s
        """, (user_id,))
        cart_items = cursor.fetchall()

        cursor.close()
        conn.close()

        return jsonify(cart_items)
    except Error as e:
        print(f"Database error: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/api/cart', methods=['POST'])
def add_to_cart():
    try:
        data = request.json
        user_id = data.get('user_id')
        product_id = data.get('product_id')
        quantity = data.get('quantity', 1)

        if not all([user_id, product_id]):
            return jsonify({'error': 'User ID and product ID are required'}), 400

        conn = get_db_connection()
        if conn is None:
            return jsonify({'error': 'Database connection failed'}), 500

        cursor = conn.cursor()

        # Check if product already in cart
        cursor.execute("""
            SELECT id, quantity FROM cart 
            WHERE user_id = %s AND product_id = %s
        """, (user_id, product_id))
        existing_item = cursor.fetchone()

        if existing_item:
            # Update quantity if product already in cart
            cursor.execute("""
                UPDATE cart 
                SET quantity = quantity + %s 
                WHERE id = %s
            """, (quantity, existing_item[0]))
        else:
            # Add new item to cart
            cursor.execute("""
                INSERT INTO cart (user_id, product_id, quantity) 
                VALUES (%s, %s, %s)
            """, (user_id, product_id, quantity))

        conn.commit()
        cursor.close()
        conn.close()

        return jsonify({'message': 'Cart updated successfully'})
    except Error as e:
        print(f"Database error: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/api/cart/<int:cart_item_id>', methods=['DELETE'])
def remove_from_cart(cart_item_id):
    try:
        conn = get_db_connection()
        if conn is None:
            return jsonify({'error': 'Database connection failed'}), 500

        cursor = conn.cursor()
        cursor.execute("DELETE FROM cart WHERE id = %s", (cart_item_id,))
        conn.commit()

        cursor.close()
        conn.close()

        return jsonify({'message': 'Item removed from cart successfully'})
    except Error as e:
        print(f"Database error: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/api/cart/<int:cart_item_id>', methods=['PUT'])
def update_cart_item(cart_item_id):
    try:
        data = request.json
        quantity = data.get('quantity')

        if quantity is None:
            return jsonify({'error': 'Quantity is required'}), 400

        conn = get_db_connection()
        if conn is None:
            return jsonify({'error': 'Database connection failed'}), 500

        cursor = conn.cursor()
        cursor.execute("""
            UPDATE cart 
            SET quantity = %s 
            WHERE id = %s
        """, (quantity, cart_item_id))
        conn.commit()

        cursor.close()
        conn.close()

        return jsonify({'message': 'Cart item updated successfully'})
    except Error as e:
        print(f"Database error: {str(e)}")
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True) 