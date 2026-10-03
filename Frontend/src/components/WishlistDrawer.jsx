import {
  FiX,
  FiTrash2,
  FiHeart,
  FiShoppingCart,
  FiShield,
  FiTruck,
  FiArrowRight,
  FiPercent,
  FiStar,
  FiLock,
  FiCheckCircle,
  FiShare2,
  FiAward,
  FiChevronRight,
  FiGift,
  FiBookmark,
} from "react-icons/fi";
import { useNavigate } from "react-router-dom";
import { useState } from "react";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

const FREE_SHIPPING_THRESHOLD = 350;

const WishlistDrawer = () => {
  const navigate = useNavigate();
  const {
    isWishlistOpen,
    toggleWishlistDrawer,
    wishlistItems,
    removeFromWishlist,
  } = useWishlist();
  const { addToCart, toggleCart } = useCart();
  const [justAdded, setJustAdded] = useState(null);

  if (!isWishlistOpen) return null;

  const totalValue = wishlistItems.reduce(
    (sum, item) => sum + parseFloat(item.price || item.basePrice || 0),
    0
  );

  const handleMoveToCart = (item) => {
    addToCart({ ...item, quantity: 1 });
    removeFromWishlist(item.id);
    setJustAdded(item.id);
    setTimeout(() => setJustAdded(null), 1500);
  };

  const handleAddAllToCart = () => {
    wishlistItems.forEach((item) => addToCart({ ...item, quantity: 1 }));
    wishlistItems.forEach((item) => removeFromWishlist(item.id));
    toggleWishlistDrawer();
    setTimeout(() => toggleCart(), 300);
  };

  const suggestions = [
    {
      id: "s1",
      name: "Bath Towel Premium",
      price: 24.99,
      image:
        "https://images.pexels.com/photos/5591664/pexels-photo-5591664.jpeg?auto=compress&cs=tinysrgb&w=200",
    },
    {
      id: "s2",
      name: "Bath Mat Soft",
      price: 34.99,
      image:
        "https://images.pexels.com/photos/6585757/pexels-photo-6585757.jpeg?auto=compress&cs=tinysrgb&w=200",
    },
    {
      id: "s3",
      name: "Bed Sheet Set",
      price: 89.99,
      image:
        "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=200",
    },
  ];

  return (
    <div className="fixed inset-0 z-[300] flex justify-end font-sans">
      {/* ============ DARK OVERLAY ============ */}
      <div
        onClick={toggleWishlistDrawer}
        className="absolute inset-0 bg-black/70 backdrop-blur-md transition-all animate-in fade-in duration-200 cursor-pointer"
      />

      {/* ============ DRAWER PANEL ============ */}
      <div className="relative w-full sm:w-[460px] h-full bg-[#F1F3F6] shadow-2xl flex flex-col animate-in slide-in-from-right duration-300">
        {/* ============ HEADER (Flipkart Blue — matches navbar) ============ */}
        <div className="relative bg-gradient-to-r from-[#2874F0] via-[#1e5bc7] to-[#0d47a1] text-white shadow-md overflow-hidden">
          <div
            className="absolute inset-0 opacity-[0.08]"
            style={{
              backgroundImage:
                "radial-gradient(circle, white 1px, transparent 1px)",
              backgroundSize: "18px 18px",
            }}
          />
          <div className="relative px-5 py-4 flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-white/15 backdrop-blur-sm rounded-xl flex items-center justify-center border border-white/20 shadow-inner">
                <FiHeart size={18} fill="white" className="text-white" />
              </div>
              <div>
                <h2 className="text-[15px] font-bold leading-tight flex items-center gap-2">
                  My Wishlist
                  <span className="bg-[#FFE500] text-[#031D44] text-[10px] font-bold px-2 py-0.5 rounded-full">
                    {wishlistItems.length}
                  </span>
                </h2>
                <p className="text-[10px] text-white/75 mt-0.5 flex items-center gap-1">
                  <FiBookmark size={9} />
                  Saved favorites
                </p>
              </div>
            </div>
            <button
              onClick={toggleWishlistDrawer}
              className="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur flex items-center justify-center transition-colors cursor-pointer border border-white/20"
              aria-label="Close wishlist"
            >
              <FiX size={17} />
            </button>
          </div>
        </div>

        {/* ============ SAVINGS / TOTAL VALUE BAR ============ */}
        {wishlistItems.length > 0 && (
          <div className="bg-white border-b border-gray-200 px-5 py-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <div className="w-6 h-6 rounded-full bg-[#EAF2FF] flex items-center justify-center shrink-0">
                  <FiPercent size={11} className="text-[#2874F0]" />
                </div>
                <p className="text-[11px] text-gray-700">
                  Wishlist value:{" "}
                  <span className="font-bold text-[#2874F0]">
                    ${totalValue.toFixed(2)}
                  </span>
                </p>
              </div>
              {wishlistItems.length > 1 && (
                <button
                  onClick={handleAddAllToCart}
                  className="text-[10px] font-bold uppercase tracking-wider text-[#FB641B] hover:text-[#e55a15] flex items-center gap-1 transition-colors"
                >
                  <FiShoppingCart size={11} /> Add All
                </button>
              )}
            </div>
          </div>
        )}

        {/* ============ WISHLIST ITEMS ============ */}
        <div className="flex-1 overflow-y-auto px-4 py-4">
          {wishlistItems.length === 0 ? (
            /* ===== EMPTY STATE ===== */
            <div className="flex flex-col items-center justify-center h-full text-center px-4">
              <div className="relative mb-6">
                <div className="w-28 h-28 bg-white rounded-full flex items-center justify-center shadow-md border border-gray-200 relative z-10">
                  <FiHeart
                    size={44}
                    className="text-[#2874F0]"
                    fill="#EAF2FF"
                  />
                </div>
                <div className="absolute inset-0 rounded-full bg-[#2874F0]/10 animate-ping" />
              </div>

              <p className="text-gray-800 font-bold text-[18px] mb-2">
                Your wishlist is empty
              </p>
              <p className="text-[12px] text-gray-500 mb-6 max-w-[260px] leading-relaxed">
                Save your favorite hotel linens here for later — quick access
                anytime you need them.
              </p>

              <button
                onClick={() => {
                  toggleWishlistDrawer();
                  navigate("/products");
                }}
                className="px-6 py-3.5 bg-gradient-to-r from-[#FB641B] to-[#e55a15] hover:from-[#e55a15] hover:to-[#d94e0e] text-white text-[12px] font-bold uppercase tracking-wider rounded-lg transition-all shadow-lg shadow-[#FB641B]/30 hover:shadow-[#FB641B]/50 hover:-translate-y-0.5 flex items-center gap-2"
              >
                Browse Products <FiArrowRight size={14} />
              </button>

              {/* Trust badges */}
              <div className="grid grid-cols-3 gap-3 mt-10 w-full max-w-[320px]">
                {[
                  { icon: FiShield, label: "Secure" },
                  { icon: FiTruck, label: "Fast Ship" },
                  { icon: FiAward, label: "Premium" },
                ].map((b, i) => (
                  <div
                    key={i}
                    className="flex flex-col items-center text-center p-3 bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all"
                  >
                    <b.icon size={16} className="text-[#2874F0] mb-1.5" />
                    <span className="text-[10px] font-bold text-gray-600 uppercase tracking-wider">
                      {b.label}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          ) : (
            /* ===== WISHLIST ITEMS ===== */
            <div className="space-y-3">
              {wishlistItems.map((item, index) => {
                const itemPrice = parseFloat(item.price || item.basePrice || 0);
                const originalPrice = itemPrice * 1.35;
                const discount = Math.round(
                  ((originalPrice - itemPrice) / originalPrice) * 100
                );
                const seed = Number(String(item.id || index).slice(-2)) || 50;
                const rating = (3.8 + (seed % 12) / 10).toFixed(1);
                const reviews = 20 + ((seed * 7) % 200);
                const isJustAdded = justAdded === item.id;

                return (
                  <div
                    key={item.id || index}
                    className="bg-white rounded-xl border border-gray-200 hover:border-[#2874F0] hover:shadow-md transition-all overflow-hidden group relative"
                  >
                    {/* Discount ribbon */}
                    {discount > 5 && (
                      <div className="absolute top-0 left-0 z-10">
                        <div className="bg-[#FB641B] text-white text-[9px] font-bold px-2 py-0.5 rounded-br-lg uppercase tracking-wider">
                          {discount}% OFF
                        </div>
                      </div>
                    )}

                    {/* Heart icon top-right */}
                    <button
                      onClick={() => removeFromWishlist(item.id)}
                      className="absolute top-3 right-3 z-10 w-7 h-7 rounded-full flex items-center justify-center text-[#E91E63] hover:bg-pink-50 transition-all cursor-pointer"
                      aria-label="Remove from wishlist"
                    >
                      <FiHeart size={13} fill="#E91E63" />
                    </button>

                    <div className="flex gap-3 p-3">
                      {/* Product Image */}
                      <div
                        onClick={() => {
                          toggleWishlistDrawer();
                          navigate(`/product/${item.id}`);
                        }}
                        className="relative w-[88px] h-[108px] bg-[#F1F3F6] rounded-lg overflow-hidden flex-shrink-0 border border-gray-100 cursor-pointer group/img"
                      >
                        <img
                          src={
                            item.image ||
                            item.resolvedImages?.[0] ||
                            "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=300"
                          }
                          alt={item.name}
                          className="w-full h-full object-cover group-hover/img:scale-105 transition-transform duration-500"
                          onError={(e) => {
                            e.target.onerror = null;
                            e.target.src =
                              "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=300";
                          }}
                        />
                      </div>

                      {/* Product Details */}
                      <div className="flex-1 flex flex-col min-w-0 pr-6">
                        {/* Name */}
                        <h4
                          onClick={() => {
                            toggleWishlistDrawer();
                            navigate(`/product/${item.id}`);
                          }}
                          className="text-[13px] font-semibold text-gray-800 line-clamp-2 leading-tight cursor-pointer hover:text-[#2874F0] transition-colors"
                        >
                          {item.name}
                        </h4>

                        {/* Rating + unit */}
                        <div className="flex items-center gap-2 mt-1.5">
                          <div className="flex items-center gap-0.5 bg-[#10B981] text-white text-[9px] font-bold px-1.5 py-0.5 rounded">
                            {rating}
                            <FiStar size={7} fill="white" />
                          </div>
                          <span className="text-[9px] text-gray-500">
                            ({reviews})
                          </span>
                          <span className="text-[9px] text-gray-400 uppercase tracking-wider">
                            · {item.unit || "EACH"}
                          </span>
                        </div>

                        {/* Price */}
                        <div className="flex items-baseline gap-1.5 mt-2">
                          <span className="text-[15px] font-bold text-[#2874F0]">
                            ${itemPrice.toFixed(2)}
                          </span>
                          <span className="text-[10px] text-gray-400 line-through">
                            ${originalPrice.toFixed(2)}
                          </span>
                        </div>

                        {/* Stock + Add button */}
                        <div className="flex items-center justify-between mt-3 pt-2.5 border-t border-gray-100">
                          <span className="text-[9.5px] font-bold text-green-600 uppercase tracking-wider flex items-center gap-1">
                            <span className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse" />
                            In Stock
                          </span>

                          <button
                            onClick={() => handleMoveToCart(item)}
                            disabled={isJustAdded}
                            className={`text-[10px] font-bold uppercase tracking-wider px-3 py-1.5 rounded transition-all flex items-center gap-1.5 ${
                              isJustAdded
                                ? "bg-green-100 text-green-700 cursor-default"
                                : "bg-[#FB641B] hover:bg-[#e55a15] text-white shadow-sm"
                            }`}
                          >
                            {isJustAdded ? (
                              <>
                                <FiCheckCircle size={11} /> Added
                              </>
                            ) : (
                              <>
                                <FiShoppingCart size={11} /> Add
                              </>
                            )}
                          </button>
                        </div>
                      </div>
                    </div>

                    {/* Quick actions strip (on hover) */}
                    <div className="flex border-t border-gray-100 opacity-0 group-hover:opacity-100 transition-opacity max-h-0 group-hover:max-h-12 overflow-hidden">
                      <button
                        onClick={() => removeFromWishlist(item.id)}
                        className="flex-1 py-2 text-[10px] font-bold uppercase tracking-wider text-gray-500 hover:text-red-600 hover:bg-red-50 transition-colors flex items-center justify-center gap-1.5"
                      >
                        <FiTrash2 size={11} /> Remove
                      </button>
                      <span className="w-px bg-gray-200" />
                      <button className="flex-1 py-2 text-[10px] font-bold uppercase tracking-wider text-gray-500 hover:text-[#2874F0] hover:bg-[#EAF2FF] transition-colors flex items-center justify-center gap-1.5">
                        <FiShare2 size={11} /> Share
                      </button>
                    </div>
                  </div>
                );
              })}

              {/* Trust ribbon */}
              <div className="bg-white rounded-xl border border-gray-200 p-3 flex items-center gap-3 shadow-sm">
                <div className="w-9 h-9 bg-[#EAF2FF] rounded-full flex items-center justify-center shrink-0">
                  <FiShield size={15} className="text-[#2874F0]" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-[11.5px] font-bold text-gray-800">
                    Your saved items are secure
                  </p>
                  <p className="text-[10px] text-gray-500">
                    Access anytime · Auto-synced across devices
                  </p>
                </div>
                <FiChevronRight size={14} className="text-gray-400 shrink-0" />
              </div>

              {/* ============ SUGGESTIONS ============ */}
              <div className="pt-4">
                <div className="flex items-center justify-between mb-3 px-1">
                  <h3 className="text-[12px] font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
                    <FiGift size={12} className="text-[#FB641B]" />
                    You May Also Like
                  </h3>
                  <button
                    onClick={() => {
                      toggleWishlistDrawer();
                      navigate("/products");
                    }}
                    className="text-[10px] font-bold text-[#2874F0] hover:text-[#FB641B] transition-colors uppercase tracking-wider flex items-center gap-0.5"
                  >
                    See All <FiChevronRight size={11} />
                  </button>
                </div>

                <div className="flex gap-3 overflow-x-auto pb-2 -mx-4 px-4 scrollbar-hide">
                  {suggestions.map((s) => (
                    <div
                      key={s.id}
                      onClick={() => {
                        toggleWishlistDrawer();
                        navigate(`/product/${s.id}`);
                      }}
                      className="flex-shrink-0 w-[120px] bg-white rounded-lg border border-gray-200 hover:border-[#2874F0] hover:shadow-md transition-all cursor-pointer overflow-hidden"
                    >
                      <div className="aspect-square bg-[#F1F3F6] overflow-hidden">
                        <img
                          src={s.image}
                          alt={s.name}
                          className="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
                        />
                      </div>
                      <div className="p-2">
                        <p className="text-[10.5px] font-medium text-gray-800 line-clamp-2 leading-tight mb-1 min-h-[26px]">
                          {s.name}
                        </p>
                        <p className="text-[11px] font-bold text-[#2874F0]">
                          ${s.price}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}
        </div>

        {/* ============ FOOTER (STICKY CTA) ============ */}
        {wishlistItems.length > 0 && (
          <div className="bg-white border-t border-gray-200 shadow-[0_-8px_20px_rgba(0,0,0,0.06)]">
            <div className="p-4">
              <button
                onClick={handleAddAllToCart}
                className="w-full py-4 bg-gradient-to-r from-[#FB641B] to-[#e55a15] hover:from-[#e55a15] hover:to-[#d94e0e] text-white text-[13px] font-bold uppercase tracking-widest rounded-lg transition-all shadow-lg shadow-[#FB641B]/30 hover:shadow-[#FB641B]/50 hover:-translate-y-0.5 flex items-center justify-center gap-2 group"
              >
                <FiShoppingCart size={15} />
                Add All to Cart
                <FiArrowRight
                  size={15}
                  className="group-hover:translate-x-1 transition-transform"
                />
              </button>

              <p className="text-[10px] text-gray-400 text-center mt-3 flex items-center justify-center gap-1.5">
                <FiLock size={10} className="text-green-600" />
                <span>
                  {wishlistItems.length}{" "}
                  {wishlistItems.length === 1 ? "item" : "items"} · Total $
                  {totalValue.toFixed(2)} · Free shipping over $
                  {FREE_SHIPPING_THRESHOLD}
                </span>
              </p>
            </div>
          </div>
        )}
      </div>

      <style>{`
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
      `}</style>
    </div>
  );
};

export default WishlistDrawer;