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
    addToCart(
      { ...product },
      1,
      "Standard",
      product.basePrice || product.price,
    );
    removeFromWishlist(product.id);
    setIsWishlistOpen(false);
  };

  return (
    <div className="fixed inset-0 z-50 flex justify-end font-sans">
      {/* Backdrop - Normal darkness with smooth blur */}
      <div
        className="absolute inset-0 bg-black/30 backdrop-blur-md transition-opacity"
        onClick={toggleWishlist}
      ></div>

      {/* Drawer Container */}
      <div className="relative w-full max-w-md bg-white h-full shadow-2xl flex flex-col transform transition-transform duration-300">
        {/* Header */}
        <div className="flex items-center justify-between p-5 bg-[#031D44] text-white border-b border-[#031D44]">
          <h2 className="text-lg font-serif font-bold flex items-center gap-2">
            <FiHeart className="text-[#B58E58] fill-current" /> Your Wishlist
          </h2>
          <button
            onClick={toggleWishlist}
            className="p-2 text-white/80 hover:text-white bg-white/10 rounded-full transition-colors cursor-pointer"
          >
            <FiX size={18} />
          </button>
        </div>

        {/* Body Content */}
        <div className="flex-1 overflow-y-auto p-5 bg-[#FAF9F6]">
          {wishlistItems.length === 0 ? (
            <div className="h-full flex flex-col items-center justify-center text-gray-400">
              <FiHeart size={48} className="mb-4 opacity-20 text-[#031D44]" />
              <p className="text-sm font-medium text-gray-500">
                Your wishlist is empty.
              </p>
            </div>
          ) : (
            <div className="flex flex-col gap-4">
              {wishlistItems.map((item, index) => (
                <div
                  key={index}
                  className="flex gap-4 bg-white p-4 rounded-2xl border border-gray-100 shadow-sm items-center relative group"
                >
                  <div
                    onClick={() => handleProductClick(item.id)}
                    className="w-20 h-20 bg-gray-50 rounded-xl overflow-hidden flex-shrink-0 cursor-pointer hover:opacity-80 transition-opacity border border-gray-100"
                  >
                    <img
                      src={item.image || (item.gallery && item.gallery[0])}
                      alt={item.name}
                      className="w-full h-full object-cover"
                    />
                  </div>

                  <div className="flex-1 min-w-0 flex flex-col justify-between">
                    <div>
                      <div className="flex justify-between items-start">
                        <h3
                          onClick={() => handleProductClick(item.id)}
                          className="font-serif font-bold text-xs text-[#031D44] line-clamp-1 pr-6 cursor-pointer hover:text-[#B58E58] transition-colors"
                        >
                          {item.name}
                        </h3>
                      </div>
                      <p className="text-xs font-bold text-[#B58E58] mt-1">
                        CAD ${item.basePrice || item.price || "0.00"}
                      </p>
                    </div>

                    <div className="flex items-center gap-2 mt-3">
                      <button
                        onClick={() => handleProductClick(item.id)}
                        className="flex-1 py-1.5 px-2 bg-gray-100 text-[#031D44] text-[10px] font-bold rounded-lg hover:bg-[#031D44] hover:text-white transition-all cursor-pointer"
                      >
                        View Product
                      </button>
                      <button
                        onClick={() => handleMoveToCart(item)}
                        className="flex-1 py-1.5 px-2 bg-[#031D44] text-white text-[10px] font-bold rounded-lg hover:bg-[#B58E58] transition-all flex items-center justify-center gap-1 cursor-pointer shadow-sm"
                      >
                        <FiShoppingCart size={12} /> Add to Cart
                      </button>
                    </div>
                  </div>

                  <button
                    onClick={() => removeFromWishlist(item.id)}
                    className="absolute top-3 right-3 text-gray-300 hover:text-red-500 transition-colors cursor-pointer"
                    title="Remove from wishlist"
                  >
                    <FiTrash2 size={16} />
                  </button>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="p-5 bg-white border-t border-gray-100">
          <button
            onClick={toggleWishlist}
            className="w-full py-3.5 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
          >
            Close Wishlist
          </button>
        </div>
      </div>
    </div>
  );
}
