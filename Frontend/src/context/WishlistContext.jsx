/* eslint-disable react-refresh/only-export-components, react-hooks/set-state-in-effect, react-hooks/exhaustive-deps */
import { createContext, useState, useContext, useEffect } from "react";
import { useLocation } from "react-router-dom";

const WishlistContext = createContext();

export const WishlistProvider = ({ children }) => {
  const [wishlistItems, setWishlistItems] = useState([]);
  const [isWishlistOpen, setIsWishlistOpen] = useState(false);
  const location = useLocation();

  // 1. Har baar page badalne par current user ki wishlist load karna
  useEffect(() => {
    const user = localStorage.getItem("user");
    if (user) {
      try {
        const parsedUser = JSON.parse(user);
        if (parsedUser.email) {
          const savedWishlist = localStorage.getItem(
            `wishlist_${parsedUser.email}`,
          );
          if (savedWishlist) {
            setWishlistItems(JSON.parse(savedWishlist));
          } else {
            setWishlistItems([]);
          }
        }
      } catch {
        setWishlistItems([]);
      }
    } else {
      setWishlistItems([]);
    }
  }, [location.pathname]);

  // 2. Jaise hi wishlist update ho, current user ke email ke sath save karna
  useEffect(() => {
    const user = localStorage.getItem("user");
    if (user) {
      try {
        const parsedUser = JSON.parse(user);
        if (parsedUser.email) {
          localStorage.setItem(
            `wishlist_${parsedUser.email}`,
            JSON.stringify(wishlistItems),
          );
        }
      } catch {
        // error handling
      }
    }
  }, [wishlistItems]);

  const toggleWishlistItem = (product) => {
    setWishlistItems((prev) => {
      const exists = prev.find((item) => item.id === product.id);
      if (exists) {
        return prev.filter((item) => item.id !== product.id);
      }
      return [...prev, product];
    });
  };

  const removeFromWishlist = (productId) => {
    setWishlistItems((prev) => prev.filter((item) => item.id !== productId));
  };

  // Dono functions provide kar diye hain taaki kahin confusion na ho
  const toggleWishlist = () => setIsWishlistOpen((prev) => !prev);
  const toggleWishlistDrawer = () => setIsWishlistOpen((prev) => !prev);

  const isInWishlist = (productId) => {
    return wishlistItems.some((item) => item.id === productId);
  };

  return (
    <WishlistContext.Provider
      value={{
        wishlistItems,
        toggleWishlistItem,
        removeFromWishlist,
        isWishlistOpen,
        toggleWishlist,
        toggleWishlistDrawer, // Navbar ke sath fully synchronized
        setIsWishlistOpen,
        isInWishlist,
      }}
    >
      {children}
    </WishlistContext.Provider>
  );
};

export const useWishlist = () => useContext(WishlistContext);
