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
    setCartItems((prev) => {
      const selectedSize = size || "Standard";
      const existingItem = prev.find(
        (item) =>
          item.id === product.id &&
          (item.selectedSize === selectedSize || item.size === selectedSize),
      );
      if (existingItem) {
        return prev.map((item) =>
          item.id === product.id &&
          (item.selectedSize === selectedSize || item.size === selectedSize)
            ? { ...item, quantity: item.quantity + quantity }
            : item,
        );
      }
      return [
        ...prev,
        { ...product, quantity, selectedSize, size: selectedSize, price },
      ];
    });
    setIsCartOpen(true);
  };

  const removeFromCart = (productId, size = "Standard") => {
    setCartItems((prev) =>
      prev.filter(
        (item) =>
          !(
            item.id === productId &&
            (item.selectedSize === size || item.size === size)
          ),
      ),
    );
  };

  const updateQuantity = (productId, size = "Standard", newQuantity) => {
    if (newQuantity < 1) return;
    setCartItems((prev) =>
      prev.map((item) =>
        item.id === productId &&
        (item.selectedSize === size || item.size === size)
          ? { ...item, quantity: newQuantity }
          : item,
      ),
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
