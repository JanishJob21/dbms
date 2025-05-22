// Add event listener for addProductForm if it exists
const addProductForm = document.getElementById('addProductForm');
if (addProductForm) {
    addProductForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const name = document.getElementById('productName').value;
        const category = document.getElementById('productCategory').value;
        const price = document.getElementById('productPrice').value;
        const stock = document.getElementById('productStock').value;

        // Simulate adding product to backend
        const productId = Date.now(); // Temporary ID for demonstration
        addProductToTable({ productId, name, category, price, stock });

        // Clear the form
        document.getElementById('addProductForm').reset();
    });
}

function addProductToTable(product) {
    const table = document.getElementById('productTable');
    const row = table.insertRow();
    row.setAttribute('data-id', product.productId);
    row.innerHTML = `
        <td>${product.productId}</td>
        <td>${product.name}</td>
        <td>${product.category}</td>
        <td>${product.price}</td>
        <td>${product.stock}</td>
        <td>
            <button class="btn btn-warning btn-sm" onclick="editProduct(${product.productId})">Edit</button>
            <button class="btn btn-danger btn-sm" onclick="deleteProduct(${product.productId})">Delete</button>
            <button class="btn btn-success btn-sm" onclick="addToCart('${product.name}', ${product.price})">Add to Cart</button>
        </td>
    `;
}

function editProduct(productId) {
    const row = document.querySelector(`[data-id='${productId}']`);
    const cells = row.children;

    document.getElementById('updateProductId').value = productId;
    document.getElementById('updateProductName').value = cells[1].textContent;
    document.getElementById('updateProductCategory').value = cells[2].textContent;
    document.getElementById('updateProductPrice').value = cells[3].textContent;
    document.getElementById('updateProductStock').value = cells[4].textContent;

    const updateModal = new bootstrap.Modal(document.getElementById('updateProductModal'));
    updateModal.show();
}

// Add event listener for updateProductForm if it exists
const updateProductForm = document.getElementById('updateProductForm');
if (updateProductForm) {
    updateProductForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const productId = document.getElementById('updateProductId').value;
        const name = document.getElementById('updateProductName').value;
        const category = document.getElementById('updateProductCategory').value;
        const price = document.getElementById('updateProductPrice').value;
        const stock = document.getElementById('updateProductStock').value;

        const row = document.querySelector(`[data-id='${productId}']`);
        row.children[1].textContent = name;
        row.children[2].textContent = category;
        row.children[3].textContent = price;
        row.children[4].textContent = stock;

        const updateModal = bootstrap.Modal.getInstance(document.getElementById('updateProductModal'));
        updateModal.hide();
    });
}

function deleteProduct(productId) {
    const row = document.querySelector(`[data-id='${productId}']`);
    row.remove();
}

// Cart functionality
let cart = JSON.parse(localStorage.getItem('cart')) || [];

function addToCart(productName, price, image) {
    const product = {
        name: productName,
        price: price,
        image: image,
        quantity: 1
    };

    // Check if product already exists in cart
    const existingProduct = cart.find(item => item.name === productName);
    if (existingProduct) {
        existingProduct.quantity += 1;
    } else {
        cart.push(product);
    }

    // Save to localStorage
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Show notification
    showNotification(`${productName} added to cart!`);
}

function showNotification(message, type = 'success') {
    console.log('Notification:', message); // Debug log
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.fontSize = '1.3rem';
    notification.style.border = '3px solid #155724';
    notification.style.fontWeight = 'bold';
    notification.style.zIndex = '9999';
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 5000); // Show for 5 seconds
}

// Search functionality for home page
function searchProducts() {
    const searchInput = document.querySelector('.search-input');
    if (!searchInput) return; // Exit if search input doesn't exist (cart page)

    const searchTerm = searchInput.value.toLowerCase();
    const productCards = document.querySelectorAll('.feature-card');

    productCards.forEach(card => {
        const productName = card.querySelector('h5').textContent.toLowerCase();
        const productDescription = card.querySelector('p').textContent.toLowerCase();
        
        if (productName.includes(searchTerm) || productDescription.includes(searchTerm)) {
            card.closest('.col-md-3').style.display = 'block';
        } else {
            card.closest('.col-md-3').style.display = 'none';
        }
    });
}

// Product data
const productData = {
    "Fruits": ["Apples", "Bananas", "Oranges", "Grapes", "Mangoes"],
    "Vegetables": ["Potatoes", "Tomatoes", "Carrots", "Spinach", "Cabbage"],
    "Dairy": ["Milk", "Cheese", "Yogurt", "Butter", "Eggs"],
    "Bakery": ["Bread", "Buns", "Cakes", "Cookies", "Croissants"],
    "Beverages": ["Coffee", "Tea", "Juice", "Soda", "Water"]
};

// Add event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listeners to all "Add to Cart" buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const card = this.closest('.feature-card');
            const productName = card.querySelector('h5').textContent;
            const priceText = card.querySelector('.price').textContent;
            const price = parseFloat(priceText.replace(/[^0-9.-]+/g, ''));
            const productId = this.getAttribute('data-product-id');
            
            // Get user data from localStorage
            const userData = JSON.parse(localStorage.getItem('userData'));
            if (!userData || !userData.id) {
                window.location.href = 'login.html';
                return;
            }

            // Add to cart using the API
            fetch('/api/cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userData.id,
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    showNotification(`${productName} added to cart!`);
                } else {
                    alert('Failed to add to cart: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add to cart');
            });
        });
    });

    // Add event listener to search input
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', searchProducts);
    }

    // Initialize category select
    const categorySelect = document.getElementById('categorySelect');
    if (categorySelect) {
        Object.keys(productData).forEach(category => {
            categorySelect.add(new Option(category, category));
        });

        // Add category change event listener
        categorySelect.addEventListener('change', function() {
            const productSelect = document.getElementById('productSelect');
            productSelect.innerHTML = '<option value="">Select a product</option>';
            
            if (this.value) {
                const products = productData[this.value];
                products.forEach(product => {
                    productSelect.add(new Option(product, product));
                });
            }
        });
    }

    // Load products from backend
    fetchProducts();
});

// Function to fetch products from backend
async function fetchProducts() {
    try {
        const response = await fetch('/api/products');
        const products = await response.json();
        displayProducts(products);
    } catch (error) {
        console.error('Error fetching products:', error);
        showNotification('Error loading products', 'error');
    }
}

// Function to handle adding product to cart
async function addProductToCart(productId, productName) {
    try {
        // Get user data from localStorage
        const userData = JSON.parse(localStorage.getItem('userData'));
        if (!userData || !userData.id) {
            window.location.href = 'login.html';
            return;
        }

        // Add to cart using the API
        const response = await fetch('/api/cart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userData.id,
                product_id: productId,
                quantity: 1
            })
        });

        const data = await response.json();
        if (data.message) {
            showNotification(`${productName} added to cart!`);
        } else {
            throw new Error(data.error || 'Failed to add to cart');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message || 'Failed to add to cart', 'error');
    }
}

// Function to display products in the UI
function displayProducts(products) {
    const productsContainer = document.querySelector('.features-section .row');
    if (!productsContainer) return;

    productsContainer.innerHTML = products.map(product => `
        <div class="col-md-3">
            <div class="feature-card">
                <img src="${product.image_url}" alt="${product.name}">
                <div class="p-3">
                    <h5>${product.name}</h5>
                    <p>${product.description}</p>
                    <p class="price">Price: $${product.price.toFixed(2)}</p>
                    <button class="btn btn-primary add-to-cart-btn" data-product-id="${product.id}" data-product-name="${product.name}">Add to Cart</button>
                </div>
            </div>
        </div>
    `).join('');

    // Add event listeners to the new buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            addProductToCart(productId, productName);
        });
    });
}