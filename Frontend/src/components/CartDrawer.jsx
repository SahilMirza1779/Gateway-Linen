import { FiX, FiTrash2, FiMinus, FiPlus, FiShoppingBag } from "react-icons/fi";
import { useNavigate } from "react-router-dom";
import { useCart } from "../context/CartContext";

const CartDrawer = () => {
  const navigate = useNavigate();
  const { isCartOpen, toggleCart, cartItems, updateQuantity, removeFromCart } =
    useCart();

  if (!isCartOpen) return null;

  const subtotal = cartItems.reduce(
    (total, item) =>
      total + parseFloat(item.price || item.basePrice || 0) * item.quantity,
    0,
  );

  const handleCheckout = () => {
    toggleCart();
    navigate("/checkout");
  };

  return (
    <div className="fixed inset-0 z-[300] flex justify-end font-sans">
      {/* Dark Overlay */}
      <div
        onClick={toggleCart}
        className="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity cursor-pointer"
      ></div>

      {/* Drawer Panel */}
      <div className="relative w-full sm:w-[400px] h-full bg-white shadow-2xl flex flex-col animate-in slide-in-from-right duration-300">
        {/* Header */}
        <div className="flex justify-between items-center p-5 border-b border-[#E5DCD0] bg-[#FAF7F2]">
          <h2 className="text-lg font-serif font-bold text-[#031D44] flex items-center gap-2">
            <FiShoppingBag className="text-[#B58E58]" /> Shopping Cart
            <span className="bg-[#031D44] text-white text-[10px] w-5 h-5 flex items-center justify-center rounded-full ml-1 font-sans">
              {cartItems.length}
            </span>
          </h2>
          <button
            onClick={toggleCart}
            className="p-2 bg-white border border-[#E5DCD0] text-gray-500 hover:text-[#031D44] hover:bg-gray-50 rounded-full transition-colors cursor-pointer shadow-sm"
          >
            <FiX size={18} />
          </button>
        </div>

        {/* Cart Items (Scrollable Body) */}
        <div className="flex-1 overflow-y-auto p-5 bg-white custom-scrollbar">
          {cartItems.length === 0 ? (
            <div className="flex flex-col items-center justify-center h-full text-center opacity-70">
              <div className="w-20 h-20 bg-[#FAF7F2] rounded-full flex items-center justify-center mb-4">
                <FiShoppingBag size={32} className="text-[#B58E58]" />
              </div>
              <p className="text-[#031D44] font-bold text-lg mb-2">
                Your cart is empty
              </p>
              <p className="text-xs text-gray-500 mb-6 font-light">
                Looks like you haven't added any premium linens yet.
              </p>
              <button
                onClick={() => {
                  toggleCart();
                  navigate("/products");
                }}
                className="px-6 py-3 bg-[#031D44] text-white text-[11px] font-bold uppercase tracking-widest rounded-xl hover:bg-[#B58E58] transition-colors shadow-md cursor-pointer"
              >
                Start Shopping
              </button>
            </div>
          ) : (
            <div className="space-y-4">
              {cartItems.map((item, index) => (
                <div
                  key={item.id || index}
                  className="flex gap-4 p-3 bg-white border border-[#E5DCD0] rounded-2xl shadow-sm relative group"
                >
                  {/* Product Image */}
                  <div className="w-20 h-24 bg-gray-50 rounded-xl overflow-hidden flex-shrink-0 border border-[#E5DCD0]">
                    <img
                      src={
                        item.image ||
                        item.resolvedImages?.[0] ||
                        "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=300&auto=format&fit=crop"
                      }
                      alt={item.name}
                      className="w-full h-full object-cover"
                    />
                  </div>

                  {/* Product Details */}
                  <div className="flex-1 flex flex-col justify-between py-1">
                    <div className="pr-6">
                      <h4 className="text-[13px] font-bold text-[#031D44] line-clamp-2 leading-tight">
                        {item.name}
                      </h4>
                      <p className="text-[10px] text-gray-500 mt-1 uppercase tracking-wider font-medium">
                        Unit: {item.unit || "EACH"}
                      </p>
                    </div>

                    <div className="flex items-end justify-between mt-3">
                      {/* Quantity Selector */}
                      <div className="flex items-center bg-[#FAF7F2] border border-[#E5DCD0] rounded-lg">
                        <button
                          onClick={() =>
                            updateQuantity(item.id, item.quantity - 1)
                          }
                          className="w-8 h-8 flex items-center justify-center text-gray-600 hover:text-[#031D44] hover:bg-gray-100 rounded-l-lg transition-colors cursor-pointer"
                        >
                          <FiMinus size={12} />
                        </button>
                        <span className="w-8 text-center text-xs font-bold text-[#031D44]">
                          {item.quantity}
                        </span>
                        <button
                          onClick={() =>
                            updateQuantity(item.id, item.quantity + 1)
                          }
                          className="w-8 h-8 flex items-center justify-center text-gray-600 hover:text-[#031D44] hover:bg-gray-100 rounded-r-lg transition-colors cursor-pointer"
                        >
                          <FiPlus size={12} />
                        </button>
                      </div>

                      {/* Price */}
                      <div className="text-right">
                        <span className="text-sm font-bold text-[#B58E58]">
                          ${" "}
                          {(
                            parseFloat(item.price || item.basePrice || 0) *
                            item.quantity
                          ).toFixed(2)}
                        </span>
                      </div>
                    </div>
                  </div>

                  {/* Remove Button */}
                  <button
                    onClick={() => removeFromCart(item.id)}
                    className="absolute top-3 right-3 text-gray-300 hover:text-red-500 transition-colors cursor-pointer bg-white rounded-full p-1 shadow-xs border border-transparent hover:border-red-100"
                  >
                    <FiTrash2 size={14} />
                  </button>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Footer (Sticky Checkout Box) */}
        {cartItems.length > 0 && (
          <div className="border-t border-[#E5DCD0] bg-[#FAF7F2] p-5">
            <div className="flex justify-between items-center mb-2">
              <span className="text-sm font-medium text-gray-600">
                Subtotal
              </span>
              <span className="text-xl font-serif font-bold text-[#031D44]">
                ${subtotal.toFixed(2)}
              </span>
            </div>
            <p className="text-[10px] text-gray-500 mb-5 font-light">
              Taxes and shipping calculated at checkout.
            </p>
            <button
              onClick={handleCheckout}
              className="w-full py-4 bg-[#031D44] hover:bg-[#B58E58] text-white text-[11px] font-bold uppercase tracking-widest rounded-xl transition-all shadow-md cursor-pointer"
            >
              Proceed To Checkout
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default CartDrawer;
