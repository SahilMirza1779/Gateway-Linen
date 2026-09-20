import { FiX, FiHeart, FiTrash2, FiShoppingCart } from "react-icons/fi";
import { useNavigate } from "react-router-dom";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

export default function WishlistDrawer() {
  const {
    isWishlistOpen,
    toggleWishlist,
    wishlistItems,
    removeFromWishlist,
    setIsWishlistOpen,
  } = useWishlist();
  const { addToCart } = useCart();
  const navigate = useNavigate();

  if (!isWishlistOpen) return null;

  const handleProductClick = (productId) => {
    setIsWishlistOpen(false);
    navigate(`/product/${productId}`);
  };

  const handleMoveToCart = (product) => {
    // Default size 'Standard' aur quantity '1' ke sath cart me bhejenge
    addToCart(
      { ...product },
      1,
      "Standard",
      product.basePrice || product.price,
    );
    removeFromWishlist(product.id);
    setIsWishlistOpen(false); // Drawer close karke cart open karne denge
  };

  return (
    <div className="fixed inset-0 z-50 flex justify-end">
      <div
        className="absolute inset-0 bg-black bg-opacity-50 transition-opacity"
        onClick={toggleWishlist}
      ></div>

      <div className="relative w-full max-w-md bg-white h-full shadow-2xl flex flex-col transform transition-transform duration-300">
        <div className="flex items-center justify-between p-5 border-b border-gray-100">
          <h2 className="text-xl font-serif font-bold text-[#031D44] flex items-center gap-2">
            <FiHeart className="text-red-500 fill-current" /> Your Wishlist
          </h2>
          <button
            onClick={toggleWishlist}
            className="p-2 text-gray-400 hover:text-gray-700 bg-gray-50 rounded-full"
          >
            <FiX size={20} />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto p-5">
          {wishlistItems.length === 0 ? (
            <div className="h-full flex flex-col items-center justify-center text-gray-400">
              <FiHeart size={48} className="mb-4 opacity-20" />
              <p>Your wishlist is empty.</p>
            </div>
          ) : (
            <div className="flex flex-col gap-6">
              {wishlistItems.map((item, index) => (
                <div
                  key={index}
                  className="flex gap-4 border-b border-gray-50 pb-4"
                >
                  <div
                    onClick={() => handleProductClick(item.id)}
                    className="w-24 h-24 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 cursor-pointer hover:opacity-80 transition-opacity"
                  >
                    <img
                      src={item.image || (item.gallery && item.gallery[0])}
                      alt={item.name}
                      className="w-full h-full object-cover"
                    />
                  </div>
                  <div className="flex-1 flex flex-col justify-between">
                    <div>
                      <div className="flex justify-between items-start">
                        <h3
                          onClick={() => handleProductClick(item.id)}
                          className="font-bold text-sm text-[#031D44] line-clamp-2 pr-2 cursor-pointer hover:text-[#B58E58] transition-colors"
                        >
                          {item.name}
                        </h3>
                        <button
                          onClick={() => removeFromWishlist(item.id)}
                          className="text-gray-400 hover:text-red-500 transition-colors"
                          title="Remove from wishlist"
                        >
                          <FiTrash2 size={16} />
                        </button>
                      </div>
                      <p className="text-sm font-bold text-[#B58E58] mt-2">
                        CAD ${item.basePrice || item.price || "0.00"}
                      </p>
                    </div>
                    <button
                      onClick={() => handleMoveToCart(item)}
                      className="mt-3 flex items-center justify-center gap-2 w-full py-2 border border-[#031D44] text-[#031D44] text-xs font-bold rounded-lg hover:bg-[#031D44] hover:text-white transition-all"
                    >
                      <FiShoppingCart size={14} /> Add to Cart
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
