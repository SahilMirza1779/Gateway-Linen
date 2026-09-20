/* eslint-disable react-refresh/only-export-components, react-hooks/set-state-in-effect, react-hooks/exhaustive-deps */
import { createContext, useState, useContext, useEffect } from "react";
import { useLocation } from "react-router-dom";

const CartContext = createContext();

export const CartProvider = ({ children }) => {
  const [cartItems, setCartItems] = useState([]);
  const [isCartOpen, setIsCartOpen] = useState(false);
  const location = useLocation();

  // 1. Har baar page badalne par (Login/Logout) current user ka cart load karna
  useEffect(() => {
    const user = localStorage.getItem("user");
    if (user) {
      const parsedUser = JSON.parse(user);
      // User ki specific email se cart dhundho
      const savedCart = localStorage.getItem(`cart_${parsedUser.email}`);
      if (savedCart) {
        setCartItems(JSON.parse(savedCart));
      } else {
        setCartItems([]); // Naye user ka empty cart
      }
    } else {
      setCartItems([]); // Bina login wale ka empty cart
    }
  }, [location.pathname]);

  // 2. Jaise hi cart update ho, use current user ke email ke sath save karna
  useEffect(() => {
    const user = localStorage.getItem("user");
    if (user) {
      const parsedUser = JSON.parse(user);
      localStorage.setItem(
        `cart_${parsedUser.email}`,
        JSON.stringify(cartItems),
      );
    }
  }, [cartItems]);

  const addToCart = (product, quantity, size, price) => {
    setCartItems((prev) => {
      const existingItem = prev.find(
        (item) => item.id === product.id && item.size === size,
      );
      if (existingItem) {
        return prev.map((item) =>
          item.id === product.id && item.size === size
            ? { ...item, quantity: item.quantity + quantity }
            : item,
        );
      }
      return [...prev, { ...product, quantity, size, price }];
    });
    setIsCartOpen(true);
  };

  const removeFromCart = (productId, size) => {
    setCartItems((prev) =>
      prev.filter((item) => !(item.id === productId && item.size === size)),
    );
  };

  const updateQuantity = (productId, size, newQuantity) => {
    if (newQuantity < 1) return;
    setCartItems((prev) =>
      prev.map((item) =>
        item.id === productId && item.size === size
          ? { ...item, quantity: newQuantity }
          : item,
      ),
    );
  };

  const toggleCart = () => setIsCartOpen(!isCartOpen);

  const cartCount = cartItems.reduce((total, item) => total + item.quantity, 0);

  return (
    <CartContext.Provider
      value={{
        cartItems,
        addToCart,
        removeFromCart,
        updateQuantity,
        isCartOpen,
        toggleCart,
        cartCount,
        setIsCartOpen,
      }}
    >
      {children}
    </CartContext.Provider>
  );
};

export const useCart = () => useContext(CartContext);
