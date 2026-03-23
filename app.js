class EShop {
    constructor() {
        this.cartCount = 0;
        this.cartItems = new Map(); 
        this.init();
    }

    async init() {
        await this.loadProducts();
        this.bindEvents();
    }

    async loadProducts() {
        const response = await fetch('api/products.php');
        const products = await response.json();
        
        await this.loadCartState();
        
        const productGrid = document.getElementById('products');
        productGrid.innerHTML = products.map(product => {
            const qty = this.cartItems.get(product.id) || 0;
            const isInCart = qty > 0;
            const outOfStock = product.stock === 0;

            return `
        <div class="product-card" data-id="${product.id}" style="position: relative;">

            ${outOfStock ? `
                <div style="
                    position: absolute; top: 12px; right: 12px;
                    background: #dc3545; color: white;
                    padding: 4px 12px; border-radius: 20px;
                    font-size: 0.75rem; font-weight: 700;
                    text-transform: uppercase; letter-spacing: 0.05em;
                    z-index: 2; box-shadow: 0 2px 8px rgba(220,53,69,0.4);
                ">Out of Stock</div>
            ` : ''}

            <img src="/ecommerce/${product.image}" alt="${product.name}"
                 style="${outOfStock ? 'opacity: 0.55; filter: grayscale(40%);' : ''}">
            <h3>${product.name}</h3>
            <p class="product-description">${product.description?.substring(0, 80) || 'No description'}...</p>
            <p class="price">$${product.price}</p>

            <div style="display: flex; gap: 0.5rem;">
                <button class="add-to-cart"
                    onclick="${outOfStock ? '' : `eshop.addToCart(${product.id})`}"
                    ${outOfStock ? 'disabled' : ''}
                    style="flex: 1.2; padding: 0.5rem 0.8rem; font-size: 0.9rem;
                           ${outOfStock ? 'background: #6c757d !important; cursor: not-allowed; opacity: 0.7;' : ''}">
                    ${outOfStock ? '🚫 Out of Stock' : 'Add to Cart'}
                </button>
                <a href="#" onclick="eshop.showProductDetailById(${product.id}); return false;"
                   style="background: #6c757d; color: white; text-decoration: none;
                          padding: 0.5rem 0.8rem; border-radius: 5px; display: inline-block;
                          text-align: center; font-size: 0.9rem; line-height: 1.2; font-weight: 500;">
                    View Product
                </a>
            </div>

            ${isInCart && !outOfStock ? `
                <div class="quantity-controls">
                    <button class="qty-btn" onclick="eshop.changeQty(${product.id}, -1)">-</button>
                    <input type="number" class="quantity-input" value="${qty}" min="0" readonly>
                    <button class="qty-btn" onclick="eshop.changeQty(${product.id}, 1)">+</button>
                </div>
            ` : ''}
        </div>
`;
        }).join('');
    }

    async showProductDetailById(productId) {
        const response = await fetch('api/products.php');
        const products = await response.json();
        const product = products.find(p => p.id == productId);
        if (product) this.showProductDetail(product);
    }

    showProductDetail(product) {
        const outOfStock = product.stock === 0;
        const lowStock   = product.stock > 0 && product.stock <= 5;

        document.getElementById('detail-image').src = `/ecommerce/${product.image}`;
        document.getElementById('detail-image').alt = product.name;
        document.getElementById('detail-image').style.filter = outOfStock ? 'grayscale(40%)' : '';
        document.getElementById('detail-title').textContent = product.name;
        document.getElementById('detail-price').textContent = `$${product.price}`;
        document.getElementById('detail-description').textContent = product.description;

        // Stock badge
        const stockEl = document.getElementById('detail-stock');
        if (outOfStock) {
            stockEl.innerHTML = `<span style="color:#dc3545;">🚫 Out of Stock</span>`;
        } else if (lowStock) {
            stockEl.innerHTML = `<span style="color:#fd7e14;">⚠️ Only ${product.stock} left in stock!</span>`;
        } else {
            stockEl.innerHTML = `<span style="color:#28a745;">✅ In Stock (${product.stock} available)</span>`;
        }

        // Add to cart button
        const addBtn = document.getElementById('detail-add-cart');
        if (outOfStock) {
            addBtn.textContent = '🚫 Out of Stock';
            addBtn.disabled = true;
            addBtn.style.background = '#6c757d';
            addBtn.style.cursor = 'not-allowed';
            addBtn.onclick = null;
        } else {
            addBtn.textContent = 'Add to Cart';
            addBtn.disabled = false;
            addBtn.style.background = '';
            addBtn.style.cursor = '';
            addBtn.onclick = () => this.addToCart(product.id);
        }

        document.getElementById('product-modal').style.display = 'block';
    }

    async loadCartState() {
        const response = await fetch('api/getcart.php');
        const cartItems = await response.json();
        this.cartItems.clear();
        cartItems.forEach(item => {
            this.cartItems.set(item.product_id, item.quantity);
        });
        this.updateCartCount();
    }

    changeQty(productId, delta) {
        const currentQty = this.cartItems.get(productId) || 0;
        const newQty = Math.max(0, currentQty + delta);
        fetch('api/updatecart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity: newQty })
        }).then(() => {
            this.cartItems.set(productId, newQty);
            this.loadProducts();  
            this.updateCartCount();
        });
    }

    async addToCart(productId) {
        try {
            const response = await fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: 1 })
            });
            if (response.ok) {
                this.changeQty(productId, 1);  
                alert('Added to cart!');
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
        }
    }

    async showCart() {
        try {
            const response = await fetch('api/getcart.php');
            const cartItems = await response.json();
            const cartItemsDiv = document.getElementById('cart-items');
            if (cartItems.length === 0) {
                cartItemsDiv.innerHTML = '<p>Your cart is empty</p>';
            } else {
                cartItemsDiv.innerHTML = cartItems.map(item => `
                    <div class="cart-item">
                        <div>
                            <strong>${item.name}</strong> 
                            <span style="color: #666;">x${item.quantity}</span>
                        </div>
                        <div>
                            <span>$${ (item.price * item.quantity).toFixed(2) }</span>
                            <button class="remove-btn" onclick="eshop.removeFromCart(${item.product_id})">Remove</button>
                        </div>
                    </div>
                `).join('');
            }
            const total = cartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            document.getElementById('total-price').textContent = total.toFixed(2);
        } catch (error) {
            console.error('Error loading cart:', error);
        }
    }

    removeFromCart(productId) {
        fetch('api/removecart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        }).then(() => {
            this.loadCartState();
            this.loadProducts();
            this.updateCartCount();
            this.showCart();  
        });
    }

    updateCartCount() {
        fetch('api/getcart.php')
            .then(res => res.json())
            .then(cartItems => {
                const totalQty = cartItems.reduce((sum, item) => sum + item.quantity, 0);
                document.getElementById('cart-count').textContent = totalQty;
            });
    }

    bindEvents() {
        document.getElementById('cart-btn').onclick = () => {
            document.getElementById('cart-modal').style.display = 'block';
            this.showCart();
        };
        document.querySelector('.close').onclick = () => {
            document.getElementById('cart-modal').style.display = 'none';
        };
        document.getElementById('product-close').onclick = () => {
            document.getElementById('product-modal').style.display = 'none';
        };
        document.getElementById('checkout').onclick = () => {
            window.location.href = 'checkout.php';
        };
        window.onclick = (event) => {
            const cartModal = document.getElementById('cart-modal');
            const productModal = document.getElementById('product-modal');
            if (event.target == cartModal) cartModal.style.display = 'none';
            if (event.target == productModal) productModal.style.display = 'none';
        };
    }
}

const eshop = new EShop();
