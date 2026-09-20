import { useState, useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { FiLock, FiCheckCircle, FiArrowLeft } from "react-icons/fi";
import { useCart } from "../context/CartContext";

export default function Checkout() {
  const location = useLocation();
  const navigate = useNavigate();
  const { cartItems, getCartTotal, clearCart } = useCart();

  // Buy Now se direct data aaya hai ya Cart se?
  const isDirectBuy = location.state && location.state.product;

  const checkoutItems = isDirectBuy
    ? [
        {
          ...location.state.product,
          cartQuantity: location.state.quantity,
          selectedSize: location.state.selectedSize,
          price: location.state.currentPrice / location.state.quantity,
        },
      ]
    : cartItems;

  const subtotal = isDirectBuy ? location.state.currentPrice : getCartTotal();

  // Sirf redirect logic ke liye effect use karenge
  useEffect(() => {
    if (!isDirectBuy && cartItems.length === 0) {
      navigate("/"); // Cart empty hai toh home par bhej do
    }
  }, [isDirectBuy, cartItems.length, navigate]);

  const tax = Number((subtotal * 0.13).toFixed(2)); // Example 13% tax
  const shipping = subtotal > 100 ? 0 : 15.0; // Free shipping over $100
  const grandTotal = (subtotal + tax + shipping).toFixed(2);

  const [formData, setFormData] = useState({
    firstName: "",
    lastName: "",
    email: "",
    address: "",
    city: "",
    postalCode: "",
    paymentMethod: "credit_card",
  });

  const [orderPlaced, setOrderPlaced] = useState(false);

  const handleInputChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handlePlaceOrder = (e) => {
    e.preventDefault();
    setOrderPlaced(true);
    if (!isDirectBuy) {
      clearCart(); // Order place hone par cart clear kar do
    }
  };

  if (orderPlaced) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4">
        <FiCheckCircle className="text-green-500 mb-6" size={80} />
        <h1 className="text-3xl font-serif font-bold text-[#031D44] mb-4">
          Order Placed Successfully!
        </h1>
        <p className="text-gray-600 mb-8 text-center max-w-md">
          Thank you for your purchase, {formData.firstName}. We have received
          your order and will send a confirmation email shortly.
        </p>
        <button
          onClick={() => navigate("/")}
          className="px-8 py-3 bg-[#031D44] text-white text-xs font-bold tracking-widest uppercase rounded-xl hover:bg-[#B58E58] transition-all"
        >
          Continue Shopping
        </button>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-10 px-4 md:px-10 font-sans">
      <div className="max-w-[1200px] mx-auto">
        <button
          onClick={() => navigate(-1)}
          className="mb-6 flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-[#031D44] transition-colors"
        >
          <FiArrowLeft size={16} /> Back
        </button>

        <h1 className="text-3xl md:text-4xl font-serif font-bold text-[#031D44] mb-10">
          Secure Checkout
        </h1>

        <div className="flex flex-col lg:flex-row gap-10">
          {/* Left Side - Form */}
          <div className="lg:w-2/3">
            <form
              onSubmit={handlePlaceOrder}
              className="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100"
            >
              <h2 className="text-lg font-bold text-[#031D44] mb-6 border-b pb-4">
                Shipping Information
              </h2>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label className="block text-xs font-bold text-gray-500 uppercase mb-2">
                    First Name
                  </label>
                  <input
                    required
                    type="text"
                    name="firstName"
                    onChange={handleInputChange}
                    className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58]"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-gray-500 uppercase mb-2">
                    Last Name
                  </label>
                  <input
                    required
                    type="text"
                    name="lastName"
                    onChange={handleInputChange}
                    className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58]"
                  />
                </div>
              </div>

              <div className="mb-4">
                <label className="block text-xs font-bold text-gray-500 uppercase mb-2">
                  Email Address
                </label>
                <input
                  required
                  type="email"
                  name="email"
                  onChange={handleInputChange}
                  className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58]"
                />
              </div>

              <div className="mb-4">
                <label className="block text-xs font-bold text-gray-500 uppercase mb-2">
                  Street Address
                </label>
                <input
                  required
                  type="text"
                  name="address"
                  onChange={handleInputChange}
                  className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58]"
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                <div>
                  <label className="block text-xs font-bold text-gray-500 uppercase mb-2">
                    City
                  </label>
                  <input
                    required
                    type="text"
                    name="city"
                    onChange={handleInputChange}
                    className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58]"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-gray-500 uppercase mb-2">
                    Postal Code
                  </label>
                  <input
                    required
                    type="text"
                    name="postalCode"
                    onChange={handleInputChange}
                    className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58]"
                  />
                </div>
              </div>

              <h2 className="text-lg font-bold text-[#031D44] mb-6 border-b pb-4">
                Payment Method (Test Mode)
              </h2>
              <div className="flex flex-col gap-4 mb-8">
                <label className="flex items-center gap-3 p-4 border rounded-xl cursor-pointer hover:border-[#B58E58]">
                  <input
                    type="radio"
                    name="paymentMethod"
                    value="credit_card"
                    defaultChecked
                    onChange={handleInputChange}
                    className="w-4 h-4 text-[#031D44]"
                  />
                  <span className="font-semibold text-gray-700">
                    Credit / Debit Card (Simulated)
                  </span>
                </label>

                {/* Fake Card Details Box */}
                <div className="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col gap-3">
                  <div>
                    <label className="block text-[11px] font-bold text-gray-500 uppercase mb-1">
                      Card Number
                    </label>
                    <input
                      type="text"
                      placeholder="4242 •••• •••• 4242"
                      maxLength="19"
                      className="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm focus:outline-none focus:border-[#B58E58]"
                    />
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-[11px] font-bold text-gray-500 uppercase mb-1">
                        Expiry Date
                      </label>
                      <input
                        type="text"
                        placeholder="MM/YY"
                        maxLength="5"
                        className="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm focus:outline-none focus:border-[#B58E58]"
                      />
                    </div>
                    <div>
                      <label className="block text-[11px] font-bold text-gray-500 uppercase mb-1">
                        CVV
                      </label>
                      <input
                        type="password"
                        placeholder="123"
                        maxLength="4"
                        className="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm focus:outline-none focus:border-[#B58E58]"
                      />
                    </div>
                  </div>
                </div>

                <label className="flex items-center gap-3 p-4 border rounded-xl cursor-pointer hover:border-[#B58E58]">
                  <input
                    type="radio"
                    name="paymentMethod"
                    value="cash_on_delivery"
                    onChange={handleInputChange}
                    className="w-4 h-4 text-[#031D44]"
                  />
                  <span className="font-semibold text-gray-700">
                    Cash on Delivery / Wholesale Invoice
                  </span>
                </label>
              </div>

              <button
                type="submit"
                className="w-full py-4 bg-[#031D44] text-white rounded-xl text-sm font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all flex justify-center items-center gap-2"
              >
                <FiLock /> Place Order — CAD ${grandTotal}
              </button>
            </form>
          </div>

          {/* Right Side - Order Summary */}
          <div className="lg:w-1/3">
            <div className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-10">
              <h2 className="text-lg font-bold text-[#031D44] mb-6 border-b pb-4">
                Order Summary
              </h2>

              <div className="flex flex-col gap-4 mb-6 max-h-[300px] overflow-y-auto pr-2 scrollbar-thin">
                {checkoutItems.map((item, index) => (
                  <div key={index} className="flex items-center gap-4">
                    <div className="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                      <img
                        src={item.image || item.gallery?.[0]}
                        alt={item.name}
                        className="w-full h-full object-cover"
                      />
                    </div>
                    <div className="flex-1">
                      <h4 className="text-xs font-bold text-[#031D44] line-clamp-2">
                        {item.name}
                      </h4>
                      <p className="text-[10px] text-gray-500 mt-1">
                        Size: {item.selectedSize} | Qty: {item.cartQuantity}
                      </p>
                    </div>
                    <div className="text-sm font-bold text-gray-900">
                      ${(item.price * item.cartQuantity).toFixed(2)}
                    </div>
                  </div>
                ))}
              </div>

              <div className="border-t border-gray-100 pt-4 flex flex-col gap-3 text-sm text-gray-600">
                <div className="flex justify-between">
                  <span>Subtotal</span>
                  <span className="font-semibold text-gray-900">
                    ${subtotal.toFixed(2)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span>Tax (13%)</span>
                  <span className="font-semibold text-gray-900">
                    ${tax.toFixed(2)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span>Shipping</span>
                  <span className="font-semibold text-gray-900">
                    {shipping === 0 ? "Free" : `$${shipping.toFixed(2)}`}
                  </span>
                </div>
                <div className="border-t border-gray-200 pt-4 flex justify-between items-center mt-2">
                  <span className="text-base font-bold text-[#031D44]">
                    Total
                  </span>
                  <span className="text-xl font-bold text-[#B58E58]">
                    CAD ${grandTotal}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
