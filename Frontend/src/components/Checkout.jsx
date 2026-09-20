import { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { FiMapPin, FiPlus, FiCheckCircle, FiShield } from "react-icons/fi";

export default function Checkout() {
  const location = useLocation();
  const navigate = useNavigate();

  // ProductDetail se bheja gaya data nikal rahe hain
  const { product, quantity, selectedSize, currentPrice } =
    location.state || {};

  // Mock Addresses (Backend aane par yeh SQL Server se aayenge)
  const [addresses] = useState([
    {
      id: 1,
      type: "Home",
      fullName: "Sahil Mirza",
      phone: "+91 9876543210",
      fullAddress: "123 VIP Road, Vesu, Surat, Gujarat, 395007",
    },
    {
      id: 2,
      type: "Office",
      fullName: "Sahil Mirza",
      phone: "+91 9876543210",
      fullAddress: "Tech Park, Ring Road, Surat, Gujarat, 395002",
    },
  ]);

  const [selectedAddress, setSelectedAddress] = useState(addresses[0]?.id);

  // Agar direct URL hit kare bina product ke, toh wapas bhej do
  if (!product) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center bg-white">
        <h2 className="text-2xl font-bold text-[#031D44] mb-4">
          Cart is Empty
        </h2>
        <button
          onClick={() => navigate("/")}
          className="px-6 py-3 bg-[#031D44] text-white rounded-xl"
        >
          Go to Home
        </button>
      </div>
    );
  }

  const subtotal = currentPrice * quantity;
  const shipping = 10.0; // Flat shipping rate
  const total = subtotal + shipping;

  const handlePlaceOrder = () => {
    alert(`Order Placed Successfully for CAD $${total.toFixed(2)}!`);
    navigate("/"); // Order place hone ke baad dashboard ya home par bhej sakte hain
  };

  return (
    <div className="w-full min-h-screen bg-gray-50 py-10 px-4 md:px-10 font-sans text-gray-800">
      <div className="max-w-[1200px] mx-auto">
        <div className="mb-8">
          <h1 className="text-3xl font-serif font-bold text-[#031D44]">
            Checkout
          </h1>
          <p className="text-gray-500 mt-2 text-sm">
            Review your order details and select a delivery address.
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Column: Address Selection */}
          <div className="lg:col-span-2 flex flex-col gap-6">
            <div className="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-lg font-bold text-[#031D44] flex items-center gap-2">
                  <FiMapPin className="text-[#B58E58]" /> Delivery Address
                </h2>
                <button className="text-xs font-bold text-[#B58E58] uppercase tracking-wider flex items-center gap-1 hover:text-[#031D44] transition-colors">
                  <FiPlus size={16} /> Add New
                </button>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {addresses.map((addr) => (
                  <div
                    key={addr.id}
                    onClick={() => setSelectedAddress(addr.id)}
                    className={`relative p-5 rounded-xl border-2 cursor-pointer transition-all ${selectedAddress === addr.id ? "border-[#031D44] bg-[#F8FAFC]" : "border-gray-200 hover:border-gray-300 bg-white"}`}
                  >
                    {selectedAddress === addr.id && (
                      <FiCheckCircle
                        className="absolute top-4 right-4 text-[#031D44]"
                        size={20}
                      />
                    )}
                    <span className="inline-block px-3 py-1 bg-gray-200 text-gray-700 text-[10px] font-bold uppercase tracking-widest rounded-md mb-3">
                      {addr.type}
                    </span>
                    <h3 className="font-bold text-gray-900 mb-1">
                      {addr.fullName}
                    </h3>
                    <p className="text-sm text-gray-600 mb-2">
                      {addr.fullAddress}
                    </p>
                    <p className="text-sm text-gray-900 font-semibold">
                      {addr.phone}
                    </p>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Right Column: Order Summary */}
          <div className="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100 h-fit">
            <h2 className="text-lg font-bold text-[#031D44] mb-6">
              Order Summary
            </h2>

            <div className="flex gap-4 mb-6 pb-6 border-b border-gray-100">
              <div className="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                <img
                  src={product.image}
                  alt={product.name}
                  className="w-full h-full object-cover"
                />
              </div>
              <div>
                <h3 className="font-bold text-sm text-gray-900 line-clamp-2">
                  {product.name}
                </h3>
                <p className="text-xs text-gray-500 mt-1">
                  Size: {selectedSize}
                </p>
                <p className="text-xs text-gray-500 mt-1">Qty: {quantity}</p>
                <p className="text-sm font-bold text-[#031D44] mt-2">
                  CAD ${currentPrice}
                </p>
              </div>
            </div>

            <div className="flex flex-col gap-3 mb-6 border-b border-gray-100 pb-6">
              <div className="flex justify-between text-sm text-gray-600">
                <span>Subtotal</span>
                <span className="font-semibold text-gray-900">
                  CAD ${subtotal.toFixed(2)}
                </span>
              </div>
              <div className="flex justify-between text-sm text-gray-600">
                <span>Shipping</span>
                <span className="font-semibold text-gray-900">
                  CAD ${shipping.toFixed(2)}
                </span>
              </div>
            </div>

            <div className="flex justify-between items-center mb-8">
              <span className="text-base font-bold text-gray-900">Total</span>
              <span className="text-2xl font-bold text-[#031D44]">
                CAD ${total.toFixed(2)}
              </span>
            </div>

            <button
              onClick={handlePlaceOrder}
              className="w-full py-4 bg-[#031D44] text-white rounded-xl text-xs font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all flex justify-center items-center gap-2"
            >
              <FiShield size={16} /> Place Order
            </button>
            <p className="text-center text-[10px] text-gray-400 mt-4 uppercase tracking-widest">
              Secure Encrypted Checkout
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
