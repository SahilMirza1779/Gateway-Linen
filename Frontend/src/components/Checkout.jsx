import { useState, useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import {
  FiLock,
  FiCheckCircle,
  FiArrowLeft,
  FiTruck,
  FiX,
  FiInfo,
  FiPlus,
  FiMinus,
  FiMapPin,
} from "react-icons/fi";
import { useCart } from "../context/CartContext";

export default function Checkout() {
  const location = useLocation();
  const navigate = useNavigate();
  const { cartItems, getCartTotal, clearCart, updateCartQuantity } = useCart();

  // --- Addresses State ---
  const [addresses, setAddresses] = useState([]);
  const [selectedAddressId, setSelectedAddressId] = useState(null);
  const [showAddressForm, setShowAddressForm] = useState(false);
  const [loadingAddresses, setLoadingAddresses] = useState(true);

  const [newAddressData, setNewAddressData] = useState({
    recipientName: "",
    phone: "",
    addressLine1: "",
    city: "",
    stateProvince: "",
    postalCode: "",
  });

  // --- Custom Modal State ---
  const [showModal, setShowModal] = useState(false);
  const [modalTitle, setModalTitle] = useState("");
  const [modalMessage, setModalMessage] = useState("");

  // --- Product Popup State ---
  const [selectedProductPopup, setSelectedProductPopup] = useState(null);
  const [popupQuantity, setPopupQuantity] = useState(1);
  const [popupSize, setPopupSize] = useState("Standard");

  const isDirectBuy = location.state && location.state.product;
  const [directBuyItem, setDirectBuyItem] = useState(
    isDirectBuy
      ? {
          ...location.state.product,
          cartQuantity: location.state.quantity,
          selectedSize: location.state.selectedSize,
          price: location.state.currentPrice / location.state.quantity,
        }
      : null,
  );

  const checkoutItems = isDirectBuy ? [directBuyItem] : cartItems;
  const subtotal = isDirectBuy
    ? directBuyItem.price * directBuyItem.cartQuantity
    : getCartTotal();

  const tax = Number((subtotal * 0.13).toFixed(2));
  const shipping = subtotal > 100 ? 0 : 15.0;
  const grandTotal = (subtotal + tax + shipping).toFixed(2);
  const [paymentMethod, setPaymentMethod] = useState("credit_card");
  const [orderPlaced, setOrderPlaced] = useState(false);
  const [isPlacingOrder, setIsPlacingOrder] = useState(false);

  // --- Helper to get user ID ---
  const getActiveUserId = () => {
    try {
      const storedUser = localStorage.getItem("user");
      if (!storedUser) return null;
      const parsed = JSON.parse(storedUser);
      return (
        parsed.UserId || parsed.userId || parsed.id || parsed.user_id || null
      );
    } catch (err) {
      console.error(err);
      return null;
    }
  };

  // --- API Calls for Address ---
  const fetchAddresses = async (userId) => {
    try {
      const response = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/users/address_api.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-API-KEY": "GatewayLinen@2026",
          },
          body: JSON.stringify({ action: "get_addresses", userId: userId }),
        },
      );
      const result = await response.json();
      if (result.success && result.data && result.data.length > 0) {
        setAddresses(result.data);
        setSelectedAddressId(result.data[0].AddressId);
        setShowAddressForm(false); // Addresses hain toh list dikhegi
      } else {
        setAddresses([]);
        setShowAddressForm(true); // Agar koi address nahi hai tabhi form khulega
      }
    } catch (err) {
      console.error("Error fetching addresses:", err);
      setAddresses([]);
      setShowAddressForm(true);
    } finally {
      setLoadingAddresses(false);
    }
  };

  // --- Fetch Data on Mount ---
  useEffect(() => {
    if (!isDirectBuy && cartItems.length === 0) {
      navigate("/");
      return;
    }

    const userId = getActiveUserId();
    if (!userId) {
      setTimeout(() => {
        setModalTitle("Please Login");
        setModalMessage("Please login to proceed to checkout.");
        setShowModal(true);
      }, 0);
      return;
    }

    setTimeout(() => {
      fetchAddresses(userId);
    }, 0);

    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const handleAddNewAddress = async (e) => {
    e.preventDefault();
    const currentUserId = getActiveUserId();

    if (!currentUserId) {
      setModalTitle("Session Error");
      setModalMessage("User session not found. Please log in again.");
      setShowModal(true);
      return;
    }

    if (
      !newAddressData.recipientName ||
      !newAddressData.phone ||
      !newAddressData.addressLine1 ||
      !newAddressData.city ||
      !newAddressData.stateProvince ||
      !newAddressData.postalCode
    ) {
      setModalTitle("Validation Error");
      setModalMessage("Please fill in all required fields.");
      setShowModal(true);
      return;
    }

    try {
      const payload = {
        action: "add_address",
        userId: currentUserId,
        recipientName: newAddressData.recipientName,
        phone: newAddressData.phone,
        addressLine1: newAddressData.addressLine1,
        city: newAddressData.city,
        stateProvince: newAddressData.stateProvince,
        postalCode: newAddressData.postalCode,
      };

      const response = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/users/address_api.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-API-KEY": "GatewayLinen@2026",
          },
          body: JSON.stringify(payload),
        },
      );
      const result = await response.json();

      if (result.success) {
        setModalTitle("Address Added");
        setModalMessage("Address added successfully!");
        setShowModal(true);
        setNewAddressData({
          recipientName: "",
          phone: "",
          addressLine1: "",
          city: "",
          stateProvince: "",
          postalCode: "",
        });
        setShowAddressForm(false); // Form band ho jayega aur purana+naya dono addresses list mein dikhenge
        fetchAddresses(currentUserId);
      } else {
        setModalTitle("Error");
        setModalMessage(result.message || "Failed to add address.");
        setShowModal(true);
      }
    } catch (err) {
      console.error("Error adding address:", err);
      setModalTitle("Server Error");
      setModalMessage("Server error while adding address.");
      setShowModal(true);
    }
  };

  const handleNewAddressChange = (e) => {
    setNewAddressData({ ...newAddressData, [e.target.name]: e.target.value });
  };

  const handleOpenPopup = (item) => {
    setSelectedProductPopup(item);
    setPopupQuantity(item.cartQuantity || item.quantity || 1);
    setPopupSize(item.selectedSize || "Standard");
  };

  const handleSavePopupChanges = () => {
    if (selectedProductPopup) {
      if (!isDirectBuy && updateCartQuantity) {
        updateCartQuantity(selectedProductPopup.id, popupQuantity, popupSize);
      } else if (isDirectBuy) {
        setDirectBuyItem((prev) => ({
          ...prev,
          cartQuantity: popupQuantity,
          selectedSize: popupSize,
        }));
      }
    }
    setSelectedProductPopup(null);
  };

  const handlePlaceOrder = (e) => {
    e.preventDefault();

    if (addresses.length === 0 || !selectedAddressId) {
      setModalTitle("Missing Information");
      setModalMessage("Please add and select a delivery address first.");
      setShowModal(true);
      return;
    }

    setIsPlacingOrder(true);

    const newOrder = {
      id: "GW-" + Math.floor(10000 + Math.random() * 90000),
      date: new Date().toISOString().split("T")[0],
      total: grandTotal,
      status: "Processing",
      addressId: selectedAddressId,
      items: checkoutItems.map((item) => ({
        name: item.name,
        cartQuantity: item.cartQuantity || item.quantity || 1,
        selectedSize: item.selectedSize || "Standard",
      })),
    };

    setTimeout(() => {
      const existingOrders =
        JSON.parse(localStorage.getItem("userOrders")) || [];
      localStorage.setItem(
        "userOrders",
        JSON.stringify([newOrder, ...existingOrders]),
      );
      if (!isDirectBuy) {
        clearCart();
      }
      setIsPlacingOrder(false);
      setOrderPlaced(true);
    }, 1500);
  };

  const handleCloseModal = () => {
    if (modalTitle === "Please Login") {
      navigate("/login");
    }
    setShowModal(false);
  };

  if (orderPlaced) {
    return (
      <div className="min-h-screen bg-[#F0EAE1] flex flex-col items-center justify-center px-4 font-sans">
        <div className="bg-[#F7F2EB] border border-[#E5DCD0] p-10 rounded-[36px] shadow-2xl max-w-md w-full text-center">
          <div className="w-20 h-20 bg-green-100 text-green-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-sm">
            <FiCheckCircle size={40} />
          </div>
          <span className="text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
            Order Confirmed
          </span>
          <h1 className="text-2xl md:text-3xl font-serif font-bold text-[#031D44] mt-1 mb-3">
            Order Placed Successfully!
          </h1>
          <p className="text-xs text-gray-600 font-light mb-8 leading-relaxed">
            Thank you for your purchase. We have received your order and will
            send a confirmation email shortly.
          </p>
          <button
            onClick={() => navigate("/")}
            className="w-full py-4 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
          >
            Continue Shopping
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#F0EAE1] py-12 px-4 md:px-10 font-sans relative">
      <div className="max-w-[1300px] mx-auto">
        <button
          onClick={() => navigate(-1)}
          className="mb-6 inline-flex items-center gap-2 px-4 py-2 bg-[#F7F2EB] border border-[#E5DCD0] text-xs font-bold uppercase tracking-wider rounded-xl text-[#031D44] hover:border-[#B58E58] transition-all cursor-pointer shadow-2xs"
        >
          <FiArrowLeft size={14} /> Back
        </button>

        <div className="mb-8">
          <h1 className="text-3xl md:text-4xl font-serif font-bold text-[#031D44] tracking-wide">
            Secure Checkout
          </h1>
          <p className="text-xs text-gray-600 font-light mt-1 flex items-center gap-1.5">
            <FiLock className="text-[#B58E58]" size={12} /> 256-Bit Encrypted
            Secure Commercial Checkout
          </p>
        </div>

        <div className="flex flex-col lg:flex-row gap-8 items-start">
          <div className="lg:w-2/3 w-full">
            <div className="bg-[#F7F2EB] p-8 md:p-10 rounded-[32px] border border-[#E5DCD0] shadow-xl mb-8">
              <div className="flex justify-between items-center mb-6 pb-4 border-b border-[#E5DCD0]">
                <h2 className="text-lg font-serif font-bold text-[#031D44] flex items-center gap-2">
                  <FiTruck className="text-[#B58E58]" size={20} /> Shipping
                  Address
                </h2>
                {addresses.length > 0 && !showAddressForm && (
                  <button
                    onClick={() => setShowAddressForm(true)}
                    className="text-xs font-bold text-[#B58E58] hover:text-[#031D44] flex items-center gap-1 cursor-pointer transition-colors"
                  >
                    <FiPlus /> Add New Address
                  </button>
                )}
              </div>

              {loadingAddresses ? (
                <div className="text-center py-10 text-xs text-gray-500 font-bold uppercase tracking-widest">
                  Loading Addresses...
                </div>
              ) : (
                <>
                  {/* 1. SAVED ADDRESSES LIST (Hamesha dikhegi jab tak addresses hain) */}
                  {addresses.length > 0 && (
                    <div className="space-y-4 mb-6">
                      <p className="text-xs text-gray-600 font-medium mb-3">
                        Select a delivery address below:
                      </p>
                      {addresses.map((addr) => (
                        <label
                          key={addr.AddressId}
                          className={`flex items-start gap-4 p-5 rounded-2xl border transition-all cursor-pointer shadow-2xs
                            ${selectedAddressId === addr.AddressId ? "bg-white border-[#B58E58]" : "bg-[#FAF7F2] border-[#E5DCD0] hover:border-gray-400"}`}
                        >
                          <input
                            type="radio"
                            name="selectedAddress"
                            checked={selectedAddressId === addr.AddressId}
                            onChange={() =>
                              setSelectedAddressId(addr.AddressId)
                            }
                            className="mt-1 w-4 h-4 text-[#031D44] focus:ring-[#B58E58] cursor-pointer"
                          />
                          <div className="flex-1">
                            <div className="flex items-center gap-2 mb-1">
                              <span className="font-bold text-sm text-[#031D44]">
                                {addr.RecipientName}
                              </span>
                              <span className="text-[9px] bg-gray-200 text-gray-700 px-2 py-0.5 rounded uppercase font-bold tracking-wider">
                                {addr.AddressType || "Shipping"}
                              </span>
                            </div>
                            <p className="text-xs text-gray-600 font-light mb-1">
                              {addr.AddressLine1}, {addr.City},{" "}
                              {addr.StateProvince} {addr.PostalCode}
                            </p>
                            <p className="text-xs text-gray-600 font-light flex items-center gap-1.5">
                              Phone:{" "}
                              <span className="font-medium text-gray-800">
                                {addr.Phone}
                              </span>
                            </p>
                          </div>
                        </label>
                      ))}
                    </div>
                  )}

                  {/* 2. NO ADDRESS WARNING (Agar koi address nahi hai) */}
                  {addresses.length === 0 && !showAddressForm && (
                    <div className="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mb-6 text-sm flex items-start gap-3">
                      <FiInfo className="mt-0.5 flex-shrink-0" size={16} />
                      <p>
                        You haven't registered any delivery address yet. Please
                        add a new address to proceed with checkout.
                      </p>
                    </div>
                  )}

                  {/* 3. ADD NEW ADDRESS FORM (Jab user 'Add New Address' click karega ya naya user hoga, tab list ke sath niche form dikhega) */}
                  {(showAddressForm || addresses.length === 0) && (
                    <form
                      onSubmit={handleAddNewAddress}
                      className="bg-white p-6 rounded-2xl border border-[#E5DCD0] shadow-2xs mb-6 relative"
                    >
                      <h3 className="text-sm font-bold text-[#031D44] uppercase tracking-widest mb-6 flex items-center gap-2 pb-4 border-b border-[#E5DCD0]">
                        <FiMapPin className="text-[#B58E58]" size={18} /> ADD
                        NEW ADDRESS
                      </h3>

                      <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                        <div>
                          <label className="block text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                            Recipient Name
                          </label>
                          <input
                            required
                            type="text"
                            name="recipientName"
                            value={newAddressData.recipientName}
                            onChange={handleNewAddressChange}
                            className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                            placeholder="Full Name"
                          />
                        </div>
                        <div>
                          <label className="block text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                            Phone Number
                          </label>
                          <input
                            required
                            type="text"
                            name="phone"
                            value={newAddressData.phone}
                            onChange={handleNewAddressChange}
                            className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                            placeholder="+1 234 567 8900"
                          />
                        </div>
                      </div>

                      <div className="mb-4">
                        <label className="block text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                          Street Address
                        </label>
                        <input
                          required
                          type="text"
                          name="addressLine1"
                          value={newAddressData.addressLine1}
                          onChange={handleNewAddressChange}
                          className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                          placeholder="House / Flat No, Street, Landmark"
                        />
                      </div>

                      <div className="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4 mb-6">
                        <div>
                          <label className="block text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                            City
                          </label>
                          <input
                            required
                            type="text"
                            name="city"
                            value={newAddressData.city}
                            onChange={handleNewAddressChange}
                            className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                            placeholder="City"
                          />
                        </div>
                        <div>
                          <label className="block text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                            State/Province
                          </label>
                          <input
                            required
                            type="text"
                            name="stateProvince"
                            value={newAddressData.stateProvince}
                            onChange={handleNewAddressChange}
                            className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                            placeholder="State"
                          />
                        </div>
                        <div>
                          <label className="block text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                            Postal Code
                          </label>
                          <input
                            required
                            type="text"
                            name="postalCode"
                            value={newAddressData.postalCode}
                            onChange={handleNewAddressChange}
                            className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                            placeholder="Postal Code"
                          />
                        </div>
                      </div>

                      <div className="flex gap-4">
                        {addresses.length > 0 && (
                          <button
                            type="button"
                            onClick={() => setShowAddressForm(false)}
                            className="w-1/3 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-[11px] font-bold tracking-widest uppercase rounded-xl transition-all cursor-pointer"
                          >
                            Cancel
                          </button>
                        )}
                        <button
                          type="submit"
                          className="flex-1 py-3 bg-[#031D44] hover:bg-[#B58E58] text-white text-[11px] font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
                        >
                          Save & Use This Address
                        </button>
                      </div>
                    </form>
                  )}
                </>
              )}

              <h2 className="text-lg font-serif font-bold text-[#031D44] mt-10 mb-6 pb-4 border-b border-[#E5DCD0]">
                Payment Method (Test Mode)
              </h2>
              <div className="flex flex-col gap-4 mb-8">
                <label className="flex items-center gap-3 p-4 bg-white border border-[#E5DCD0] rounded-2xl cursor-pointer hover:border-[#B58E58] shadow-2xs transition-all">
                  <input
                    type="radio"
                    name="paymentMethod"
                    value="credit_card"
                    checked={paymentMethod === "credit_card"}
                    onChange={(e) => setPaymentMethod(e.target.value)}
                    className="w-4 h-4 text-[#031D44] focus:ring-[#B58E58]"
                  />
                  <span className="font-semibold text-xs text-[#031D44]">
                    Credit / Debit Card (Simulated)
                  </span>
                </label>

                {paymentMethod === "credit_card" && (
                  <div className="bg-white p-5 rounded-2xl border border-[#E5DCD0] flex flex-col gap-3 shadow-2xs ml-7 animate-in fade-in duration-300">
                    <div>
                      <label className="block text-[10.5px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                        Card Number
                      </label>
                      <input
                        type="text"
                        placeholder="4242 •••• •••• 4242"
                        maxLength="19"
                        className="w-full px-3.5 py-2.5 rounded-xl border border-[#E5DCD0] bg-gray-50 text-xs focus:outline-none focus:border-[#B58E58]"
                      />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                      <div>
                        <label className="block text-[10.5px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                          Expiry Date
                        </label>
                        <input
                          type="text"
                          placeholder="MM/YY"
                          maxLength="5"
                          className="w-full px-3.5 py-2.5 rounded-xl border border-[#E5DCD0] bg-gray-50 text-xs focus:outline-none focus:border-[#B58E58]"
                        />
                      </div>
                      <div>
                        <label className="block text-[10.5px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                          CVV
                        </label>
                        <input
                          type="password"
                          placeholder="123"
                          maxLength="4"
                          className="w-full px-3.5 py-2.5 rounded-xl border border-[#E5DCD0] bg-gray-50 text-xs focus:outline-none focus:border-[#B58E58]"
                        />
                      </div>
                    </div>
                  </div>
                )}

                <label className="flex items-center gap-3 p-4 bg-white border border-[#E5DCD0] rounded-2xl cursor-pointer hover:border-[#B58E58] shadow-2xs transition-all">
                  <input
                    type="radio"
                    name="paymentMethod"
                    value="cash_on_delivery"
                    checked={paymentMethod === "cash_on_delivery"}
                    onChange={(e) => setPaymentMethod(e.target.value)}
                    className="w-4 h-4 text-[#031D44] focus:ring-[#B58E58]"
                  />
                  <span className="font-semibold text-xs text-[#031D44]">
                    Cash on Delivery / Wholesale Invoice
                  </span>
                </label>
              </div>

              <button
                onClick={handlePlaceOrder}
                disabled={isPlacingOrder || addresses.length === 0}
                className={`w-full py-4 text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-lg transition-all cursor-pointer flex justify-center items-center gap-2 
                  ${isPlacingOrder || addresses.length === 0 ? "bg-gray-400 cursor-not-allowed" : "bg-[#031D44] hover:bg-[#B58E58]"}`}
              >
                <FiLock size={15} />
                {isPlacingOrder
                  ? "Processing..."
                  : `Place Order — CAD $${grandTotal}`}
              </button>
            </div>
          </div>

          <div className="lg:w-1/3 w-full lg:sticky lg:top-28">
            <div className="bg-[#F7F2EB] p-8 rounded-[32px] border border-[#E5DCD0] shadow-xl relative">
              <h2 className="text-lg font-serif font-bold text-[#031D44] mb-6 pb-4 border-b border-[#E5DCD0] flex items-center justify-between">
                <span>Order Summary</span>
                <span className="text-[10px] text-[#B58E58] font-sans font-semibold tracking-wider uppercase">
                  Click item to edit
                </span>
              </h2>

              <div className="flex flex-col gap-4 mb-6 max-h-[300px] overflow-y-auto pr-1">
                {checkoutItems.map((item, index) => (
                  <div
                    key={index}
                    onClick={() => handleOpenPopup(item)}
                    className="flex items-center gap-4 p-3 bg-white rounded-2xl border border-[#E5DCD0] hover:border-[#B58E58] shadow-2xs cursor-pointer transition-all group"
                    title="Click to edit item quantity or size"
                  >
                    <div className="w-14 h-14 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0 border border-gray-100 relative">
                      <img
                        src={item.image || item.gallery?.[0]}
                        alt={item.name}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform"
                      />
                    </div>
                    <div className="flex-1 min-w-0">
                      <h4 className="text-xs font-serif font-bold text-[#031D44] truncate group-hover:text-[#B58E58] transition-colors">
                        {item.name}
                      </h4>
                      <p className="text-[10px] text-gray-500 font-light mt-0.5">
                        Size: {item.selectedSize || "Standard"} | Qty:{" "}
                        {item.cartQuantity || item.quantity}
                      </p>
                    </div>
                    <div className="text-xs font-bold text-[#031D44]">
                      CAD $
                      {(
                        item.price * (item.cartQuantity || item.quantity)
                      ).toFixed(2)}
                    </div>
                  </div>
                ))}
              </div>

              <div className="border-t border-[#E5DCD0] pt-4 flex flex-col gap-3 text-xs text-gray-600 font-light">
                <div className="flex justify-between">
                  <span>Subtotal</span>
                  <span className="font-semibold text-gray-800">
                    CAD ${subtotal.toFixed(2)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span>Tax (13%)</span>
                  <span className="font-semibold text-gray-800">
                    CAD ${tax.toFixed(2)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span>Shipping</span>
                  <span className="font-semibold text-gray-800">
                    {shipping === 0 ? "Free" : `CAD $${shipping.toFixed(2)}`}
                  </span>
                </div>
                <div className="border-t border-[#E5DCD0] pt-4 flex justify-between items-center mt-2">
                  <span className="text-sm font-serif font-bold text-[#031D44]">
                    Total
                  </span>
                  <span className="text-xl font-serif font-bold text-[#031D44]">
                    CAD ${grandTotal}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {selectedProductPopup && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-in fade-in duration-300">
          <div className="bg-[#F7F2EB] border border-[#E5DCD0] rounded-[28px] max-w-md w-full p-6 shadow-2xl relative animate-in zoom-in duration-300">
            <button
              onClick={() => setSelectedProductPopup(null)}
              className="absolute top-5 right-5 text-gray-400 hover:text-gray-800 bg-white p-2 rounded-full transition-colors cursor-pointer border border-gray-200"
            >
              <FiX size={18} />
            </button>
            <div className="flex items-center gap-2 text-[#B58E58] text-[10px] font-bold tracking-widest uppercase mb-3">
              <FiInfo size={14} /> Edit Item Details
            </div>
            <div className="w-full h-40 bg-white rounded-2xl overflow-hidden mb-4 border border-[#E5DCD0]">
              <img
                src={
                  selectedProductPopup.image ||
                  selectedProductPopup.gallery?.[0]
                }
                alt={selectedProductPopup.name}
                className="w-full h-full object-cover"
              />
            </div>
            <h3 className="text-lg font-serif font-bold text-[#031D44] mb-1">
              {selectedProductPopup.name}
            </h3>

            <div className="bg-white p-3.5 rounded-2xl border border-[#E5DCD0] mb-4 flex justify-between items-center">
              <div>
                <p className="text-[10px] uppercase font-bold text-gray-400">
                  Unit Price
                </p>
                <p className="text-xs font-bold text-gray-700">
                  CAD ${selectedProductPopup.price?.toFixed(2)}
                </p>
              </div>
              <div className="text-right">
                <p className="text-[10px] uppercase font-bold text-[#B58E58]">
                  Updated Total
                </p>
                <p className="text-sm font-serif font-bold text-[#031D44]">
                  CAD ${(selectedProductPopup.price * popupQuantity).toFixed(2)}
                </p>
              </div>
            </div>

            <div className="bg-white p-4 rounded-2xl border border-[#E5DCD0] mb-6 space-y-4 text-xs text-gray-700">
              <div className="flex justify-between items-center">
                <span className="font-bold text-[#031D44]">Select Size:</span>
                <select
                  value={popupSize}
                  onChange={(e) => setPopupSize(e.target.value)}
                  className="px-3 py-1.5 bg-gray-50 border border-[#E5DCD0] rounded-xl text-xs font-bold text-[#031D44] focus:outline-none focus:border-[#B58E58] cursor-pointer"
                >
                  <option value="Standard">Standard</option>
                  <option value="Queen">Queen</option>
                  <option value="King">King</option>
                  <option value="Commercial Pack">Commercial Pack</option>
                </select>
              </div>
              <div className="flex justify-between items-center pt-2 border-t border-gray-100">
                <span className="font-bold text-[#031D44]">Quantity:</span>
                <div className="flex items-center gap-3 bg-gray-50 border border-[#E5DCD0] rounded-xl px-3 py-1.5">
                  <button
                    type="button"
                    onClick={() =>
                      setPopupQuantity(Math.max(1, popupQuantity - 1))
                    }
                    className="text-gray-600 hover:text-[#031D44] cursor-pointer"
                  >
                    <FiMinus size={14} />
                  </button>
                  <span className="font-bold text-xs text-[#031D44] w-6 text-center">
                    {popupQuantity}
                  </span>
                  <button
                    type="button"
                    onClick={() => setPopupQuantity(popupQuantity + 1)}
                    className="text-gray-600 hover:text-[#031D44] cursor-pointer"
                  >
                    <FiPlus size={14} />
                  </button>
                </div>
              </div>
            </div>

            <div className="flex gap-3">
              <button
                type="button"
                onClick={() => setSelectedProductPopup(null)}
                className="w-1/2 py-3 bg-white border border-[#E5DCD0] hover:bg-gray-50 text-[#031D44] text-xs font-bold tracking-widest uppercase rounded-xl transition-all cursor-pointer shadow-2xs"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleSavePopupChanges}
                className="w-1/2 py-3 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
              >
                Save Changes
              </button>
            </div>
          </div>
        </div>
      )}

      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-in fade-in duration-300">
          <div className="bg-[#F7F2EB] border border-[#E5DCD0] rounded-[24px] max-w-sm w-full p-8 shadow-2xl relative animate-in zoom-in duration-300 text-center">
            {modalTitle === "Validation Error" ||
            modalTitle === "Missing Information" ? (
              <div className="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
                <FiX size={32} />
              </div>
            ) : modalTitle === "Please Login" ? (
              <div className="w-16 h-16 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
                <FiLock size={32} />
              </div>
            ) : (
              <div className="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
                <FiCheckCircle size={32} />
              </div>
            )}

            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-2">
              {modalTitle}
            </h3>
            <p className="text-xs text-gray-600 font-light mb-8 leading-relaxed">
              {modalMessage}
            </p>
            <button
              onClick={handleCloseModal}
              className="w-full py-3 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
            >
              {modalTitle === "Please Login" ? "Go to Login" : "OK, Got It"}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
