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

  // Naya function: Image ya Title par click karne se detail page par bhejna
  const handleProductClick = (productId) => {
    setIsCartOpen(false); // Drawer close karega
    navigate(`/product/${productId}`); // Product detail page par bhej dega
  };

  return (
    <div className="fixed inset-0 z-50 flex justify-end">
      <div
        className="absolute inset-0 bg-black bg-opacity-50 transition-opacity"
        onClick={toggleCart}
      ></div>

      <div className="relative w-full max-w-md bg-white h-full shadow-2xl flex flex-col transform transition-transform duration-300">
        <div className="flex items-center justify-between p-5 border-b border-gray-100">
          <h2 className="text-xl font-serif font-bold text-[#031D44] flex items-center gap-2">
            <FiShoppingBag /> Your Cart
          </h2>
          <button
            onClick={toggleCart}
            className="p-2 text-gray-400 hover:text-gray-700 bg-gray-50 rounded-full"
          >
            <FiX size={20} />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto p-5">
          {cartItems.length === 0 ? (
            <div className="h-full flex flex-col items-center justify-center text-gray-400">
              <FiShoppingBag size={48} className="mb-4 opacity-20" />
              <p>Your cart is empty.</p>
            </div>
          ) : (
            <div className="flex flex-col gap-6">
              {cartItems.map((item, index) => (
                <div
                  key={index}
                  className="flex gap-4 border-b border-gray-50 pb-4"
                >
                  {/* Image ko clickable banaya gaya hai */}
                  <div
                    onClick={() => handleProductClick(item.id)}
                    className="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 cursor-pointer hover:opacity-80 transition-opacity"
                    title="View Product"
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
                        {/* Title ko clickable banaya gaya hai */}
                        <h3
                          onClick={() => handleProductClick(item.id)}
                          className="font-bold text-sm text-[#031D44] line-clamp-1 pr-2 cursor-pointer hover:text-[#B58E58] transition-colors"
                          title="View Product"
                        >
                          {item.name}
                        </h3>
                        <button
                          onClick={() => removeFromCart(item.id, item.size)}
                          className="text-gray-400 hover:text-red-500 transition-colors"
                          title="Remove item"
                        >
                          <FiTrash2 size={16} />
                        </button>
                      </div>
                      <p className="text-xs text-gray-500 mt-1">
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
                          className="w-7 h-7 flex items-center justify-center text-gray-600 hover:bg-gray-200 font-bold transition-all"
                        >
                          -
                        </button>
                        <span className="w-8 text-center text-xs font-bold text-[#031D44]">
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
                          className="w-7 h-7 flex items-center justify-center text-gray-600 hover:bg-gray-200 font-bold transition-all"
                        >
                          +
                        </button>
                      </div>
                      <p className="text-sm font-bold text-[#B58E58]">
                        CAD ${(item.price * item.quantity).toFixed(2)}
                      </p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {cartItems.length > 0 && (
          <div className="p-5 border-t border-gray-100 bg-gray-50">
            <div className="flex justify-between items-center mb-4">
              <span className="text-sm font-bold text-gray-600 uppercase tracking-wider">
                Subtotal
              </span>
              <span className="text-xl font-bold text-[#031D44]">
                CAD ${cartTotal.toFixed(2)}
              </span>
            </div>
            <p className="text-xs text-gray-500 mb-4">
              Shipping and taxes calculated at checkout.
            </p>
            <button
              onClick={handleCheckout}
              className="w-full py-4 bg-[#031D44] text-white rounded-xl text-xs font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all"
            >
              Checkout / Buy Now
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
