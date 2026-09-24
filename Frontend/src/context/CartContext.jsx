/* eslint-disable react-refresh/only-export-components, react-hooks/set-state-in-effect */
import { createContext, useState, useContext, useEffect } from "react";
import { useLocation } from "react-router-dom";

const CartContext = createContext();

export const CartProvider = ({ children }) => {
  const location = useLocation();

  const [cartItems, setCartItems] = useState(() => {
    try {
      const user = localStorage.getItem("user");
      if (user) {
        const parsedUser = JSON.parse(user);
        const email = parsedUser.email || parsedUser.Email;
        if (email) {
          const savedCart = localStorage.getItem(`cart_${email}`);
          return savedCart ? JSON.parse(savedCart) : [];
        }
      }
    } catch {
      // fallback
    }
    return [];
  });

  const [isCartOpen, setIsCartOpen] = useState(false);

  useEffect(() => {
    try {
      const user = localStorage.getItem("user");
      if (user) {
        const parsedUser = JSON.parse(user);
        const email = parsedUser.email || parsedUser.Email;
        if (email) {
          const savedCart = localStorage.getItem(`cart_${email}`);
          setCartItems(savedCart ? JSON.parse(savedCart) : []);
          return;
        }
      }
      setCartItems([]);
    } catch {
      setCartItems([]);
    }
  }, [location.pathname]);

  useEffect(() => {
    try {
      const user = localStorage.getItem("user");
      if (user) {
        const parsedUser = JSON.parse(user);
        const email = parsedUser.email || parsedUser.Email;
        if (email) {
          localStorage.setItem(`cart_${email}`, JSON.stringify(cartItems));
        }
      }
    } catch {
      // error handling
    }
  }, [cartItems]);

  const addToCart = (product, quantity = 1, size = "Standard", price = 0) => {
    const rawImg = product.image || product.imageUrl || product.ImageUrl || "";
    let formattedImage =
      "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
    if (rawImg.startsWith("http")) {
      formattedImage = rawImg;
    } else if (rawImg !== "") {
      const cleanPath = rawImg.replace(/^\/+/, "");
      formattedImage = `http://localhost/Gateway-Linen/GatewayLinenAdmin-main/${cleanPath}`;
    }

    const productId = product.id || product.productId || product.ProductId;

    setCartItems((prev) => {
      const selectedSize = size || "Standard";
      const existingItem = prev.find(
        (item) =>
          (item.id === productId || item.productId === productId) &&
          (item.selectedSize === selectedSize || item.size === selectedSize),
      );
      if (existingItem) {
        return prev.map((item) =>
          (item.id === productId || item.productId === productId) &&
          (item.selectedSize === selectedSize || item.size === selectedSize)
            ? { ...item, quantity: item.quantity + quantity }
            : item,
        );
      }
      return [
        ...prev,
        {
          ...product,
          id: productId,
          image: formattedImage,
          quantity,
          selectedSize,
          size: selectedSize,
          price,
        },
      ];
    });
    setIsCartOpen(true);
  };

  const removeFromCart = (productId, size = "Standard") => {
    setCartItems((prev) =>
      prev.filter(
        (item) =>
          !(
            (item.id === productId || item.productId === productId) &&
            (item.selectedSize === size || item.size === size)
          ),
      ),
    );
  };

  const updateQuantity = (productId, size = "Standard", newQuantity) => {
    if (newQuantity < 1) return;
    setCartItems((prev) =>
      prev.map((item) =>
        (item.id === productId || item.productId === productId) &&
        (item.selectedSize === size || item.size === size)
          ? { ...item, quantity: newQuantity }
          : item,
      ),
    );
  };

  const clearCart = () => {
    setCartItems([]);
  };

  const getCartTotal = () => {
    return cartItems.reduce(
      (total, item) => total + item.price * item.quantity,
      0,
    );
  };

  const toggleCart = () => setIsCartOpen((prev) => !prev);
  const toggleCartDrawer = () => setIsCartOpen((prev) => !prev);

  const cartCount = cartItems.reduce((total, item) => total + item.quantity, 0);

  return (
    <CartContext.Provider
      value={{
        cartItems,
        addToCart,
        removeFromCart,
        updateQuantity,
        clearCart,
        getCartTotal,
        isCartOpen,
        toggleCart,
        toggleCartDrawer,
        cartCount,
        setIsCartOpen,
      }}
    >
      {children}
    </CartContext.Provider>
  );
};

export const useCart = () => useContext(CartContext);
