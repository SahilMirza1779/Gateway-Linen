import { FiX, FiTrash2, FiHeart, FiShoppingCart } from "react-icons/fi";
import { useNavigate } from "react-router-dom";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

const WishlistDrawer = () => {
  const navigate = useNavigate();
  const {
    isWishlistOpen,
    toggleWishlistDrawer,
    wishlistItems,
    removeFromWishlist,
  } = useWishlist();
  const { addToCart, toggleCart } = useCart();

  if (!isWishlistOpen) return null;

  const handleMoveToCart = (item) => {
    // Add to cart with default quantity 1
    addToCart({ ...item, quantity: 1 });
    // Remove from wishlist
    removeFromWishlist(item.id);
    // Close wishlist and open cart optionally (if you want seamless flow)
    toggleWishlistDrawer();
    setTimeout(() => {
      toggleCart();
    }, 300);
  };

  return (
    <div className="fixed inset-0 z-[300] flex justify-end font-sans">
      {/* Dark Overlay */}
      <div
        onClick={toggleWishlistDrawer}
        className="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity cursor-pointer"
      ></div>

      {/* Drawer Panel */}
      <div className="relative w-full sm:w-[400px] h-full bg-white shadow-2xl flex flex-col animate-in slide-in-from-right duration-300">
        {/* Header */}
        <div className="flex justify-between items-center p-5 border-b border-[#E5DCD0] bg-[#FAF7F2]">
          <h2 className="text-lg font-serif font-bold text-[#031D44] flex items-center gap-2">
            <FiHeart className="text-[#B58E58]" /> Your Wishlist
            <span className="bg-[#B58E58] text-white text-[10px] w-5 h-5 flex items-center justify-center rounded-full ml-1 font-sans">
              {wishlistItems.length}
            </span>
          </h2>
          <button
            onClick={toggleWishlistDrawer}
            className="p-2 bg-white border border-[#E5DCD0] text-gray-500 hover:text-[#031D44] hover:bg-gray-50 rounded-full transition-colors cursor-pointer shadow-sm"
          >
            <FiX size={18} />
          </button>
        </div>

        {/* Wishlist Items (Scrollable Body) */}
        <div className="flex-1 overflow-y-auto p-5 bg-white custom-scrollbar">
          {wishlistItems.length === 0 ? (
            <div className="flex flex-col items-center justify-center h-full text-center opacity-70">
              <div className="w-20 h-20 bg-[#FAF7F2] rounded-full flex items-center justify-center mb-4">
                <FiHeart size={32} className="text-[#B58E58]" />
              </div>
              <p className="text-[#031D44] font-bold text-lg mb-2">
                Wishlist is empty
              </p>
              <p className="text-xs text-gray-500 mb-6 font-light">
                Save your favorite hotel linens here for later.
              </p>
              <button
                onClick={() => {
                  toggleWishlistDrawer();
                  navigate("/products");
                }}
                className="px-6 py-3 bg-[#031D44] text-white text-[11px] font-bold uppercase tracking-widest rounded-xl hover:bg-[#B58E58] transition-colors shadow-md cursor-pointer"
              >
                Browse Products
              </button>
            </div>
          ) : (
            <div className="space-y-4">
              {wishlistItems.map((item, index) => (
                <div
                  key={item.id || index}
                  className="flex gap-4 p-3 bg-white border border-[#E5DCD0] rounded-2xl shadow-sm relative group"
                >
                  {/* Product Image */}
                  <div
                    onClick={() => {
                      toggleWishlistDrawer();
                      navigate(`/product/${item.id}`);
                    }}
                    className="w-20 h-24 bg-gray-50 rounded-xl overflow-hidden flex-shrink-0 border border-[#E5DCD0] cursor-pointer"
                  >
                    <img
                      src={
                        item.image ||
                        item.resolvedImages?.[0] ||
                        "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=300&auto=format&fit=crop"
                      }
                      alt={item.name}
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    />
                  </div>

                  {/* Product Details */}
                  <div className="flex-1 flex flex-col justify-between py-1">
                    <div
                      className="pr-6 cursor-pointer"
                      onClick={() => {
                        toggleWishlistDrawer();
                        navigate(`/product/${item.id}`);
                      }}
                    >
                      <h4 className="text-[13px] font-bold text-[#031D44] line-clamp-2 leading-tight hover:text-[#B58E58] transition-colors">
                        {item.name}
                      </h4>
                      <p className="text-sm font-bold text-[#B58E58] mt-1.5">
                        ${" "}
                        {parseFloat(item.price || item.basePrice || 0).toFixed(
                          2,
                        )}
                      </p>
                    </div>

                    <div className="mt-3">
                      <button
                        onClick={() => handleMoveToCart(item)}
                        className="w-full py-2 bg-[#FAF7F2] border border-[#031D44] text-[#031D44] hover:bg-[#031D44] hover:text-white text-[10px] font-bold uppercase tracking-wider rounded-lg transition-colors flex items-center justify-center gap-1.5 cursor-pointer shadow-sm"
                      >
                        <FiShoppingCart size={12} /> Add to Cart
                      </button>
                    </div>
                  </div>

                  {/* Remove Button */}
                  <button
                    onClick={() => removeFromWishlist(item.id)}
                    className="absolute top-3 right-3 text-gray-300 hover:text-red-500 transition-colors cursor-pointer bg-white rounded-full p-1 shadow-xs border border-transparent hover:border-red-100"
                  >
                    <FiTrash2 size={14} />
                  </button>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default WishlistDrawer;
