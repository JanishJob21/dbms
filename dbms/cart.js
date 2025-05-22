// Ensure cart is loaded from localStorage
let cart = [];

function loadCartFromLocalStorage() {
    const storedCart = localStorage.getItem('cart');
    if (storedCart) {
        cart = JSON.parse(storedCart);
    }
}

function saveCartToLocalStorage() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

// Load cart data when the page loads
loadCartFromLocalStorage();
renderCart();

function renderCart() {
    const cartTable = document.getElementById('cartTable');
    cartTable.innerHTML = '';

    cart.forEach((item, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.name}</td>
            <td>${item.quantity}</td>
            <td>${item.price}</td>
            <td>${(item.quantity * item.price).toFixed(2)}</td>
            <td>
                <button class="btn btn-warning btn-sm" onclick="editCartItem(${index})">Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteCartItem(${index})">Delete</button>
            </td>
        `;
        cartTable.appendChild(row);
    });
}

function addItem(name, quantity, price) {
    cart.push({ name, quantity, price });
    saveCartToLocalStorage();
    renderCart();
}

function editCartItem(index) {
    const item = cart[index];
    const newQuantity = prompt('Enter new quantity:', item.quantity);

    if (newQuantity && !isNaN(newQuantity)) {
        cart[index].quantity = parseInt(newQuantity);
        saveCartToLocalStorage();
        renderCart();
    }
}

function deleteCartItem(index) {
    cart.splice(index, 1);
    saveCartToLocalStorage();
    renderCart();
}

// Example usage
addItem('Apple', 2, 1.5);
addItem('Banana', 3, 0.75);