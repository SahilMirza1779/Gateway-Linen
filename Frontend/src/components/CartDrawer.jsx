import { FiX, FiShoppingBag, FiTrash2 } from "react-icons/fi";
import { useNavigate } from "react-router-dom";
import { useCart } from "../context/CartContext";

export default function CartDrawer() {
  const {
    isCartOpen,
    toggleCart,
    cartItems,
    setIsCartOpen,
    removeFromCart,
    updateQuantity,
  } = useCart();
  const navigate = useNavigate();

  if (!isCartOpen) return null;

  const cartTotal = cartItems.reduce(
    (total, item) => total + item.price * item.quantity,
    0,
  );

  const handleCheckout = () => {
    setIsCartOpen(false);
    navigate("/checkout", { state: { cartItems, total: cartTotal } });
  };

  const handleProductClick = (productId) => {
    setIsCartOpen(false);
    navigate(`/product/${productId}`);
  };

  return (
    <div className="fixed inset-0 z-50 flex justify-end font-sans">
      {/* Backdrop with normal dark and smooth blur */}
      <div
        className="absolute inset-0 bg-black/30 backdrop-blur-md transition-opacity"
        onClick={toggleCart}
      ></div>

      {/* Drawer Container */}
      <div className="relative w-full max-w-md bg-white h-full shadow-2xl flex flex-col transform transition-transform duration-300">
        {/* Header */}
        <div className="flex items-center justify-between p-5 bg-[#031D44] text-white border-b border-[#031D44]">
          <h2 className="text-lg font-serif font-bold flex items-center gap-2">
            <FiShoppingBag className="text-[#B58E58]" /> Your Cart
          </h2>
          <button
            onClick={toggleCart}
            className="p-2 text-white/80 hover:text-white bg-white/10 rounded-full transition-colors cursor-pointer"
          >
            <FiX size={18} />
          </button>
        </div>

        {/* Body Content */}
        <div className="flex-1 overflow-y-auto p-5 bg-[#FAF9F6]">
          {cartItems.length === 0 ? (
            <div className="h-full flex flex-col items-center justify-center text-gray-400">
              <FiShoppingBag
                size={48}
                className="mb-4 opacity-20 text-[#031D44]"
              />
              <p className="text-sm font-medium text-gray-500">
                Your cart is empty.
              </p>
            </div>
          ) : (
            <div className="flex flex-col gap-4">
              {cartItems.map((item, index) => (
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
                      <p className="text-[11px] text-gray-500 mt-0.5">
                        Size: {item.size}
                      </p>
                    </div>

                    <div className="flex items-center justify-between mt-3">
                      <div className="flex items-center border border-gray-200 rounded-lg overflow-hidden bg-gray-50">
                        <button
                          onClick={() =>
                            updateQuantity(
                              item.id,
                              item.size,
                              item.quantity - 1,
                            )
                          }
                          className="w-6 h-6 flex items-center justify-center text-gray-600 hover:bg-gray-200 font-bold transition-all cursor-pointer text-xs"
                        >
                          -
                        </button>
                        <span className="w-6 text-center text-xs font-bold text-[#031D44]">
                          {item.quantity}
                        </span>
                        <button
                          onClick={() =>
                            updateQuantity(
                              item.id,
                              item.size,
                              item.quantity + 1,
                            )
                          }
                          className="w-6 h-6 flex items-center justify-center text-gray-600 hover:bg-gray-200 font-bold transition-all cursor-pointer text-xs"
                        >
                          +
                        </button>
                      </div>
                      <p className="text-xs font-bold text-[#B58E58]">
                        CAD ${(item.price * item.quantity).toFixed(2)}
                      </p>
                    </div>
                  </div>

                  <button
                    onClick={() => removeFromCart(item.id, item.size)}
                    className="absolute top-3 right-3 text-gray-300 hover:text-red-500 transition-colors cursor-pointer"
                    title="Remove item"
                  >
                    <FiTrash2 size={16} />
                  </button>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Footer Checkout */}
        {cartItems.length > 0 && (
          <div className="p-5 border-t border-gray-100 bg-white">
            <div className="flex justify-between items-center mb-3">
              <span className="text-xs font-bold text-gray-600 uppercase tracking-wider">
                Subtotal
              </span>
              <span className="text-lg font-serif font-bold text-[#031D44]">
                CAD ${cartTotal.toFixed(2)}
              </span>
            </div>
            <p className="text-[10px] text-gray-400 mb-4">
              Shipping and taxes calculated at checkout.
            </p>
            <button
              onClick={handleCheckout}
              className="w-full py-3.5 bg-[#031D44] hover:bg-[#B58E58] text-white rounded-xl text-xs font-bold tracking-widest uppercase shadow-md transition-all cursor-pointer"
            >
              Checkout / Buy Now
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
