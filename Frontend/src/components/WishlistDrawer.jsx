import { useWishlist } from "../context/WishlistContext";
import { FiX, FiTrash2, FiShoppingCart } from "react-icons/fi";
import { useCart } from "../context/CartContext";

const WishlistDrawer = () => {
  const {
    wishlistItems,
    isWishlistOpen,
    toggleWishlistDrawer,
    removeFromWishlist,
  } = useWishlist();
  const { addToCart } = useCart();

  if (!isWishlistOpen) return null;

  return (
    <div className="fixed inset-0 z-[250] flex justify-end font-sans">
      <div
        onClick={toggleWishlistDrawer}
        className="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
      ></div>

      <div className="relative w-full max-w-md bg-[#FAF7F2] h-full shadow-2xl flex flex-col z-10 border-l border-[#E5DCD0] animate-in slide-in-from-right duration-300">
        {/* Header */}
        <div className="bg-[#031D44] text-white px-6 py-5 flex justify-between items-center shadow-md">
          <h2 className="text-base font-serif font-bold tracking-wide">
            Your Saved Wishlist
          </h2>
          <button
            onClick={toggleWishlistDrawer}
            className="text-gray-300 hover:text-white bg-white/10 p-1.5 rounded-full transition-colors cursor-pointer"
          >
            <FiX size={18} />
          </button>
        </div>

        {/* Wishlist Items List */}
        <div className="flex-1 overflow-y-auto p-6 space-y-4">
          {wishlistItems.length === 0 ? (
            <div className="text-center py-24 text-gray-500 font-light text-sm">
              Your wishlist is currently empty.
            </div>
          ) : (
            wishlistItems.map((item, idx) => (
              <div
                key={idx}
                className="bg-white p-4 rounded-2xl border border-[#E5DCD0] shadow-2xs flex gap-4 items-center"
              >
                <img
                  src={item.image}
                  alt={item.name}
                  className="w-16 h-16 object-cover rounded-xl border border-[#E5DCD0] shrink-0"
                />
                <div className="flex-1 min-w-0">
                  <h4 className="text-xs font-bold text-[#031D44] truncate">
                    {item.name}
                  </h4>
                  <p className="text-xs font-bold text-[#B58E58] mt-1">
                    {item.price}
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => {
                      addToCart(
                        item,
                        1,
                        "Standard",
                        Number(item.price.replace(/[^0-9.]/g, "")) || 20,
                      );
                      removeFromWishlist(item.id);
                    }}
                    className="p-2 bg-[#031D44] text-white rounded-xl hover:bg-[#B58E58] transition-colors cursor-pointer shadow-2xs"
                    title="Move to Cart"
                  >
                    <FiShoppingCart size={14} />
                  </button>
                  <button
                    onClick={() => removeFromWishlist(item.id)}
                    className="p-2 text-gray-400 hover:text-red-500 transition-colors cursor-pointer"
                  >
                    <FiTrash2 size={14} />
                  </button>
                </div>
              </div>
            ))
          )}
        </div>

        {/* Footer */}
        <div className="p-6 bg-white border-t border-[#E5DCD0] shadow-inner">
          <button
            onClick={toggleWishlistDrawer}
            className="w-full py-3.5 bg-[#031D44] hover:bg-[#B58E58] text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-md transition-all cursor-pointer"
          >
            Close Wishlist
          </button>
        </div>
      </div>
    </div>
  );
};

export default WishlistDrawer;
