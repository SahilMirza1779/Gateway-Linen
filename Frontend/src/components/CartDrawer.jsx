import { useCart } from "../context/CartContext";
import { FiX, FiTrash2 } from "react-icons/fi";
import { useNavigate } from "react-router-dom";

const CartDrawer = () => {
  const { cartItems, isCartOpen, toggleCart, removeFromCart, updateQuantity } =
    useCart();
  const navigate = useNavigate();

  if (!isCartOpen) return null;

  const subtotal = cartItems.reduce(
    (acc, item) => acc + item.price * item.quantity,
    0,
  );

  return (
    <div className="fixed inset-0 z-[250] flex justify-end font-sans">
      <div
        onClick={toggleCart}
        className="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
      ></div>

      <div className="relative w-full max-w-md bg-[#FAF7F2] h-full shadow-2xl flex flex-col z-10 border-l border-[#E5DCD0] animate-in slide-in-from-right duration-300">
        {/* Header */}
        <div className="bg-[#031D44] text-white px-6 py-5 flex justify-between items-center shadow-md">
          <h2 className="text-base font-serif font-bold tracking-wide">
            Your Shopping Cart
          </h2>
          <button
            onClick={toggleCart}
            className="text-gray-300 hover:text-white bg-white/10 p-1.5 rounded-full transition-colors cursor-pointer"
          >
            <FiX size={18} />
          </button>
        </div>

        {/* Cart Items List */}
        <div className="flex-1 overflow-y-auto p-6 space-y-4">
          {cartItems.length === 0 ? (
            <div className="text-center py-24 text-gray-500 font-light text-sm">
              Your shopping cart is empty.
            </div>
          ) : (
            cartItems.map((item, idx) => (
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
                  <p className="text-[10px] text-gray-500 mt-0.5">
                    Size: {item.size || "Standard"}
                  </p>
                  <div className="flex items-center gap-3 mt-2">
                    <div className="flex items-center border border-[#E5DCD0] rounded-lg bg-gray-50 overflow-hidden">
                      <button
                        onClick={() =>
                          updateQuantity(item.id, item.quantity - 1)
                        }
                        className="px-2 py-0.5 text-xs font-bold hover:bg-gray-200 cursor-pointer"
                      >
                        -
                      </button>
                      <span className="px-3 text-xs font-bold text-[#031D44]">
                        {item.quantity}
                      </span>
                      <button
                        onClick={() =>
                          updateQuantity(item.id, item.quantity + 1)
                        }
                        className="px-2 py-0.5 text-xs font-bold hover:bg-gray-200 cursor-pointer"
                      >
                        +
                      </button>
                    </div>
                  </div>
                </div>
                <div className="text-right">
                  <p className="text-xs font-bold text-[#B58E58] mb-2">
                    ${(item.price * item.quantity).toFixed(2)}
                  </p>
                  <button
                    onClick={() => removeFromCart(item.id)}
                    className="text-gray-400 hover:text-red-500 transition-colors p-1 cursor-pointer"
                  >
                    <FiTrash2 size={14} />
                  </button>
                </div>
              </div>
            ))
          )}
        </div>

        {/* Footer & Checkout */}
        {cartItems.length > 0 && (
          <div className="p-6 bg-white border-t border-[#E5DCD0] shadow-inner space-y-4">
            <div className="flex justify-between items-center text-sm">
              <span className="font-bold text-gray-600 uppercase tracking-widest text-xs">
                Subtotal
              </span>
              <span className="font-serif font-bold text-lg text-[#031D44]">
                ${subtotal.toFixed(2)}
              </span>
            </div>
            <p className="text-[10px] text-gray-400 font-light">
              Shipping and commercial taxes calculated at checkout.
            </p>
            <button
              onClick={() => {
                toggleCart();
                navigate("/checkout");
              }}
              className="w-full py-3.5 bg-[#031D44] hover:bg-[#B58E58] text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-md transition-all cursor-pointer"
            >
              Proceed to Checkout
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default CartDrawer;
