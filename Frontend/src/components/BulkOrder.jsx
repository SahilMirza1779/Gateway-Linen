import {
  FiX,
  FiTrash2,
  FiMinus,
  FiPlus,
  FiShoppingBag,
  FiShield,
  FiTruck,
  FiArrowRight,
  FiPercent,
  FiStar,
  FiLock,
  FiCheckCircle,
  FiTag,
  FiHeart,
  FiBookmark,
  FiClock,
  FiAward,
  FiRefreshCw,
  FiChevronRight,
  FiGift,
} from "react-icons/fi";
import { useNavigate } from "react-router-dom";
import { useState } from "react";
import { useCart } from "../context/CartContext";

const FREE_SHIPPING_THRESHOLD = 350;

const CartDrawer = () => {
  const navigate = useNavigate();
  const { isCartOpen, toggleCart, cartItems, updateQuantity, removeFromCart } =
    useCart();
  const [couponCode, setCouponCode] = useState("");
  const [couponApplied, setCouponApplied] = useState(false);

  if (!isCartOpen) return null;

  const subtotal = cartItems.reduce(
    (total, item) =>
      total + parseFloat(item.price || item.basePrice || 0) * item.quantity,
    0
  );

  const itemCount = cartItems.reduce((acc, item) => acc + item.quantity, 0);
  const shippingCost = subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : 15;
  const couponDiscount = couponApplied ? subtotal * 0.1 : 0;
  const total = subtotal + shippingCost - couponDiscount;
  const amountToFreeShipping = Math.max(
    0,
    FREE_SHIPPING_THRESHOLD - subtotal
  );
  const shippingProgress = Math.min(
    100,
    (subtotal / FREE_SHIPPING_THRESHOLD) * 100
  );

  const handleCheckout = () => {
    toggleCart();
    navigate("/checkout");
  };

  const handleApplyCoupon = () => {
    if (couponCode.trim().toUpperCase() === "SAVE10") {
      setCouponApplied(true);
    } else {
      setCouponApplied(false);
      alert("Invalid coupon. Try SAVE10 for 10% off!");
    }
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
        onClick={toggleCart}
        className="absolute inset-0 bg-black/70 backdrop-blur-md transition-all animate-in fade-in duration-200 cursor-pointer"
      />

      {/* ============ DRAWER PANEL ============ */}
      <div className="relative w-full sm:w-[460px] h-full bg-[#F1F3F6] shadow-2xl flex flex-col animate-in slide-in-from-right duration-300">
        {/* ============ HEADER (Glassmorphism gradient) ============ */}
        <div className="relative bg-gradient-to-r from-[#2874F0] via-[#1e5bc7] to-[#0d47a1] text-white shadow-md overflow-hidden">
          {/* Dot pattern */}
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
                <FiShoppingBag size={18} className="text-[#FFE500]" />
              </div>
              <div>
                <h2 className="text-[15px] font-bold leading-tight flex items-center gap-2">
                  My Cart
                  <span className="bg-[#FFE500] text-[#031D44] text-[10px] font-bold px-2 py-0.5 rounded-full">
                    {itemCount}
                  </span>
                </h2>
                <p className="text-[10px] text-white/75 mt-0.5 flex items-center gap-1">
                  <FiLock size={9} />
                  Secure B2B Checkout
                </p>
              </div>
            </div>
            <button
              onClick={toggleCart}
              className="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur flex items-center justify-center transition-colors cursor-pointer border border-white/20"
              aria-label="Close cart"
            >
              <FiX size={17} />
            </button>
          </div>
        </div>

        {/* ============ FREE SHIPPING PROGRESS (Amazon-style) ============ */}
        {cartItems.length > 0 && (
          <div className="bg-white border-b border-gray-200 px-5 py-3.5">
            {amountToFreeShipping > 0 ? (
              <>
                <div className="flex items-center justify-between mb-2">
                  <p className="text-[11px] text-gray-700 flex items-center gap-1.5">
                    <span className="w-5 h-5 rounded-full bg-[#FFF1E8] flex items-center justify-center shrink-0">
                      <FiTruck size={11} className="text-[#FB641B]" />
                    </span>
                    <span>
                      Add{" "}
                      <span className="font-bold text-[#FB641B]">
                        ${amountToFreeShipping.toFixed(2)}
                      </span>{" "}
                      for <strong>FREE shipping</strong>
                    </span>
                  </p>
                  <span className="text-[10px] font-bold text-[#FB641B]">
                    {Math.round(shippingProgress)}%
                  </span>
                </div>
                <div className="h-2 w-full bg-gray-100 rounded-full overflow-hidden relative">
                  <div
                    className="h-full bg-gradient-to-r from-[#FB641B] via-[#FF9F00] to-[#FB641B] rounded-full transition-all duration-700 relative overflow-hidden"
                    style={{ width: `${shippingProgress}%` }}
                  >
                    <div className="absolute inset-0 bg-white/20 animate-pulse" />
                  </div>
                </div>
              </>
            ) : (
              <div className="flex items-center gap-2">
                <div className="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                  <FiCheckCircle size={13} className="text-green-600" />
                </div>
                <p className="text-[11.5px] font-bold text-green-600">
                  Congrats! You unlocked{" "}
                  <span className="text-green-700">FREE shipping</span>
                </p>
              </div>
            )}
          </div>
        )}

        {/* ============ COUPON BAR ============ */}
        {cartItems.length > 0 && (
          <div className="bg-white border-b border-gray-200 px-5 py-3">
            <div className="flex items-center gap-2">
              <div className="w-7 h-7 rounded-full bg-[#EAF2FF] flex items-center justify-center shrink-0">
                <FiTag size={12} className="text-[#2874F0]" />
              </div>
              <input
                type="text"
                value={couponCode}
                onChange={(e) => setCouponCode(e.target.value)}
                placeholder="Enter coupon code (try SAVE10)"
                className="flex-1 text-[11.5px] px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-[#2874F0] focus:ring-2 focus:ring-[#2874F0]/10 transition-all"
              />
              <button
                onClick={handleApplyCoupon}
                className="text-[10px] font-bold uppercase tracking-wider px-3 py-2 bg-[#2874F0] hover:bg-[#1e5bc7] text-white rounded transition-colors whitespace-nowrap"
              >
                {couponApplied ? "Applied" : "Apply"}
              </button>
            </div>
            {couponApplied && (
              <p className="text-[10px] text-green-600 font-bold mt-2 flex items-center gap-1">
                <FiCheckCircle size={10} /> 10% discount applied
              </p>
            )}
          </div>
        )}

        {/* ============ CART ITEMS ============ */}
        <div className="flex-1 overflow-y-auto px-4 py-4">
          {cartItems.length === 0 ? (
            /* ===== EMPTY STATE (Premium) ===== */
            <div className="flex flex-col items-center justify-center h-full text-center px-4">
              <div className="relative mb-6">
                <div className="w-28 h-28 bg-white rounded-full flex items-center justify-center shadow-md border border-gray-200 relative z-10">
                  <FiShoppingBag size={44} className="text-[#2874F0]" />
                </div>
                <div className="absolute inset-0 rounded-full bg-[#2874F0]/10 animate-ping" />
              </div>

              <p className="text-gray-800 font-bold text-[18px] mb-2">
                Your cart is empty
              </p>
              <p className="text-[12px] text-gray-500 mb-6 max-w-[260px] leading-relaxed">
                Discover premium hospitality linens — trusted by 10,000+ partners
                across North America.
              </p>

              <button
                onClick={() => {
                  toggleCart();
                  navigate("/products");
                }}
                className="px-6 py-3.5 bg-gradient-to-r from-[#FB641B] to-[#e55a15] hover:from-[#e55a15] hover:to-[#d94e0e] text-white text-[12px] font-bold uppercase tracking-wider rounded-lg transition-all shadow-lg shadow-[#FB641B]/30 hover:shadow-[#FB641B]/50 hover:-translate-y-0.5 flex items-center gap-2"
              >
                Start Shopping <FiArrowRight size={14} />
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
            /* ===== CART ITEMS LIST (Premium cards) ===== */
            <div className="space-y-3">
              {cartItems.map((item, index) => {
                const itemPrice = parseFloat(item.price || item.basePrice || 0);
                const itemTotal = itemPrice * item.quantity;
                const originalPrice = itemPrice * 1.35;
                const discount = Math.round(
                  ((originalPrice - itemPrice) / originalPrice) * 100
                );
                const seed = Number(String(item.id || index).slice(-2)) || 50;
                const rating = (3.8 + (seed % 12) / 10).toFixed(1);
                const reviews = 20 + ((seed * 7) % 200);

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

                    <div className="flex gap-3 p-3">
                      {/* Product Image */}
                      <div className="relative w-[88px] h-[108px] bg-[#F1F3F6] rounded-lg overflow-hidden flex-shrink-0 border border-gray-100">
                        <img
                          src={
                            item.image ||
                            item.resolvedImages?.[0] ||
                            "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=300"
                          }
                          alt={item.name}
                          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                          onError={(e) => {
                            e.target.onerror = null;
                            e.target.src =
                              "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=300";
                          }}
                        />
                        {/* Hover overlay */}
                        <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                          <button className="w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md hover:scale-110 transition-transform">
                            <FiHeart size={14} className="text-red-500" />
                          </button>
                        </div>
                      </div>

                      {/* Product Details */}
                      <div className="flex-1 flex flex-col min-w-0">
                        {/* Name + remove */}
                        <div className="flex items-start justify-between gap-2">
                          <h4 className="text-[13px] font-semibold text-gray-800 line-clamp-2 leading-tight flex-1">
                            {item.name}
                          </h4>
                          <button
                            onClick={() => removeFromCart(item.id)}
                            className="w-6 h-6 -mr-1 rounded-full flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all cursor-pointer shrink-0"
                            aria-label="Remove item"
                          >
                            <FiTrash2 size={12} />
                          </button>
                        </div>

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

                        {/* Price row */}
                        <div className="flex items-baseline gap-1.5 mt-2">
                          <span className="text-[15px] font-bold text-[#2874F0]">
                            ${itemTotal.toFixed(2)}
                          </span>
                          <span className="text-[10px] text-gray-400 line-through">
                            ${(originalPrice * item.quantity).toFixed(2)}
                          </span>
                        </div>

                        {/* Quantity + stock */}
                        <div className="flex items-center justify-between mt-3 pt-2.5 border-t border-gray-100">
                          <div className="flex items-center border border-gray-300 rounded overflow-hidden shadow-sm">
                            <button
                              onClick={() =>
                                updateQuantity(item.id, item.quantity - 1)
                              }
                              className="w-7 h-7 flex items-center justify-center text-[#2874F0] hover:bg-[#EAF2FF] transition-colors cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                              disabled={item.quantity <= 1}
                              aria-label="Decrease quantity"
                            >
                              <FiMinus size={11} />
                            </button>
                            <span className="w-8 h-7 flex items-center justify-center text-[12px] font-bold text-gray-800 border-x border-gray-200 bg-white">
                              {item.quantity}
                            </span>
                            <button
                              onClick={() =>
                                updateQuantity(item.id, item.quantity + 1)
                              }
                              className="w-7 h-7 flex items-center justify-center text-[#2874F0] hover:bg-[#EAF2FF] transition-colors cursor-pointer"
                              aria-label="Increase quantity"
                            >
                              <FiPlus size={11} />
                            </button>
                          </div>

                          <span className="text-[9.5px] font-bold text-green-600 uppercase tracking-wider flex items-center gap-1">
                            <span className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse" />
                            In Stock
                          </span>
                        </div>
                      </div>
                    </div>

                    {/* Quick actions strip (on hover) */}
                    <div className="flex border-t border-gray-100 opacity-0 group-hover:opacity-100 transition-opacity max-h-0 group-hover:max-h-12 overflow-hidden">
                      <button className="flex-1 py-2 text-[10px] font-bold uppercase tracking-wider text-gray-500 hover:text-[#2874F0] hover:bg-[#F1F3F6] transition-colors flex items-center justify-center gap-1.5">
                        <FiBookmark size={11} /> Save for Later
                      </button>
                      <span className="w-px bg-gray-200" />
                      <button className="flex-1 py-2 text-[10px] font-bold uppercase tracking-wider text-gray-500 hover:text-[#2874F0] hover:bg-[#F1F3F6] transition-colors flex items-center justify-center gap-1.5">
                        <FiGift size={11} /> Gift Wrap
                      </button>
                    </div>
                  </div>
                );
              })}

              {/* Trust ribbon */}
              <div className="bg-white rounded-xl border border-gray-200 p-3 flex items-center gap-3 shadow-sm">
                <div className="w-9 h-9 bg-[#EAF2FF] rounded-full flex items-center justify-center shrink-0">
                  <FiLock size={15} className="text-[#2874F0]" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-[11.5px] font-bold text-gray-800">
                    100% Safe & Secure Payments
                  </p>
                  <p className="text-[10px] text-gray-500">
                    SSL encrypted · PCI DSS · Money-back guarantee
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
                      toggleCart();
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
                        toggleCart();
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

        {/* ============ FOOTER (STICKY CHECKOUT) ============ */}
        {cartItems.length > 0 && (
          <div className="bg-white border-t border-gray-200 shadow-[0_-8px_20px_rgba(0,0,0,0.06)]">
            <div className="p-4">
              {/* Price Breakdown */}
              <div className="space-y-2 mb-3 pb-3 border-b border-dashed border-gray-200">
                <div className="flex justify-between items-center text-[12px]">
                  <span className="text-gray-600 flex items-center gap-1.5">
                    Subtotal{" "}
                    <span className="text-gray-400 text-[10px]">
                      ({itemCount} items)
                    </span>
                  </span>
                  <span className="font-semibold text-gray-800">
                    ${subtotal.toFixed(2)}
                  </span>
                </div>
                <div className="flex justify-between items-center text-[12px]">
                  <span className="text-gray-600 flex items-center gap-1.5">
                    <FiTruck size={11} className="text-gray-400" />
                    Shipping
                  </span>
                  <span
                    className={`font-semibold ${
                      shippingCost === 0 ? "text-green-600" : "text-gray-800"
                    }`}
                  >
                    {shippingCost === 0 ? "FREE" : `$${shippingCost.toFixed(2)}`}
                  </span>
                </div>
                {couponApplied && (
                  <div className="flex justify-between items-center text-[12px]">
                    <span className="text-gray-600 flex items-center gap-1.5">
                      <FiPercent size={11} className="text-green-600" />
                      Coupon (SAVE10)
                    </span>
                    <span className="font-semibold text-green-600">
                      −${couponDiscount.toFixed(2)}
                    </span>
                  </div>
                )}
              </div>

              {/* Total */}
              <div className="flex justify-between items-center mb-4">
                <div>
                  <p className="text-[11px] text-gray-500 uppercase tracking-wider font-bold">
                    Total
                  </p>
                  <p className="text-[9.5px] text-gray-400">
                    Taxes calculated at checkout
                  </p>
                </div>
                <span className="text-[22px] font-bold text-[#2874F0] leading-none">
                  ${total.toFixed(2)}
                </span>
              </div>

              {/* CTA Button */}
              <button
                onClick={handleCheckout}
                className="w-full py-4 bg-gradient-to-r from-[#FB641B] to-[#e55a15] hover:from-[#e55a15] hover:to-[#d94e0e] text-white text-[13px] font-bold uppercase tracking-widest rounded-lg transition-all shadow-lg shadow-[#FB641B]/30 hover:shadow-[#FB641B]/50 hover:-translate-y-0.5 flex items-center justify-center gap-2 group"
              >
                Proceed to Checkout
                <FiArrowRight
                  size={16}
                  className="group-hover:translate-x-1 transition-transform"
                />
              </button>

              {/* Payment icons */}
              <div className="flex items-center justify-center gap-2 mt-4">
                <span className="text-[9.5px] text-gray-400 uppercase tracking-wider font-bold mr-1">
                  We Accept
                </span>
                <div className="flex items-center gap-1">
                  {["VISA", "MC", "AMEX", "PAY"].map((p) => (
                    <div
                      key={p}
                      className="h-5 px-1.5 bg-white border border-gray-200 rounded flex items-center justify-center text-[8px] font-bold text-gray-700"
                    >
                      {p}
                    </div>
                  ))}
                </div>
              </div>

              {/* Security line */}
              <p className="text-[10px] text-gray-400 text-center mt-3 flex items-center justify-center gap-1.5">
                <FiShield size={10} className="text-green-600" />
                <span>SSL Secured · 30-day returns · Authentic products</span>
              </p>
            </div>
          </div>
        )}
      </div>

      {/* Hide scrollbar CSS */}
      <style>{`
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
      `}</style>
    </div>
  );
};

export default CartDrawer;