import { useState, useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import {
  FiLock,
  FiCheckCircle,
  FiArrowLeft,
  FiTruck,
  FiPlus,
  FiMapPin,
  FiTag,
  FiArrowRight,
  FiCreditCard,
} from "react-icons/fi";
import { useCart } from "../context/CartContext";

export default function Checkout() {
  const location = useLocation();
  const navigate = useNavigate();
  const { cartItems, getCartTotal, clearCart } = useCart();

  const [checkoutStep, setCheckoutStep] = useState(1);
  const [couponCode, setCouponCode] = useState("");
  const [discountAmount, setDiscountAmount] = useState(0);
  const [couponError, setCouponError] = useState("");
  const [couponSuccess, setCouponSuccess] = useState("");

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

  const [showModal, setShowModal] = useState(false);
  const [modalTitle, setModalTitle] = useState("");
  const [modalMessage, setModalMessage] = useState("");

  const isDirectBuy = Boolean(location.state && location.state.product);
  const [directBuyItem] = useState(
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

  const grandTotal = Math.max(
    0,
    subtotal + tax + shipping - discountAmount,
  ).toFixed(2);

  const [paymentMethod, setPaymentMethod] = useState("credit_card");
  const [orderPlaced, setOrderPlaced] = useState(false);
  const [isPlacingOrder, setIsPlacingOrder] = useState(false);

  const getActiveUserId = () => {
    try {
      const storedUser = localStorage.getItem("user");
      if (!storedUser) return 11;
      const parsed = JSON.parse(storedUser);
      return (
        parsed.UserId || parsed.userId || parsed.id || parsed.user_id || 11
      );
    } catch (err) {
      console.error(err);
      return 11;
    }
  };

  useEffect(() => {
    if (!isDirectBuy && cartItems.length === 0) {
      navigate("/");
      return;
    }

    const userId = getActiveUserId();
    if (!userId) {
      navigate("/login");
      return;
    }

    const loadInitialAddresses = async () => {
      setLoadingAddresses(true);
      try {
        const response = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/users/address_api.php",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-API-KEY": "GatewayLinen@2026",
            },
            body: JSON.stringify({
              action: "get_addresses",
              userId: Number(userId),
            }),
          },
        );
        const result = await response.json();
        if (result.success && result.data && result.data.length > 0) {
          setAddresses(result.data);
          setSelectedAddressId((prev) => prev || result.data[0].AddressId);
          setShowAddressForm(false);
        } else {
          setAddresses([]);
          setShowAddressForm(true);
        }
      } catch (err) {
        console.error("Error fetching addresses:", err);
        setAddresses([]);
        setShowAddressForm(true);
      } finally {
        setLoadingAddresses(false);
      }
    };

    loadInitialAddresses();
  }, [isDirectBuy, cartItems.length, navigate]);

  const reloadAddresses = async (userId) => {
    try {
      const response = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/users/address_api.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-API-KEY": "GatewayLinen@2026",
          },
          body: JSON.stringify({
            action: "get_addresses",
            userId: Number(userId),
          }),
        },
      );
      const result = await response.json();
      if (result.success && result.data && result.data.length > 0) {
        setAddresses(result.data);
        setSelectedAddressId((prev) => prev || result.data[0].AddressId);
        setShowAddressForm(false);
      } else {
        setAddresses([]);
        setShowAddressForm(true);
      }
    } catch (err) {
      console.error("Error reloading addresses:", err);
    }
  };

  const handleAddNewAddress = async (e) => {
    e.preventDefault();
    const currentUserId = getActiveUserId();

    try {
      const payload = {
        action: "add_address",
        userId: Number(currentUserId),
        ...newAddressData,
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
        setShowAddressForm(false);
        reloadAddresses(currentUserId);
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

  const handleApplyCoupon = async (e) => {
    e.preventDefault();
    setCouponError("");
    setCouponSuccess("");

    const code = couponCode.trim().toUpperCase();
    if (!code) {
      setCouponError("Please enter a coupon code.");
      return;
    }

    try {
      const response = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/coupons/api.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-API-KEY": "GatewayLinen@2026",
          },
          body: JSON.stringify({
            coupon_code: code,
            cart_total: subtotal,
          }),
        },
      );

      const result = await response.json();

      if (result.success) {
        setDiscountAmount(result.data.discount_amount);
        setCouponSuccess(
          result.message || `Coupon '${code}' applied successfully!`,
        );
      } else {
        setDiscountAmount(0);
        setCouponError(result.message || "Invalid or expired coupon.");
      }
    } catch (err) {
      console.error("Error applying coupon:", err);
      setCouponError("Failed to apply coupon. Please try again.");
      setDiscountAmount(0);
    }
  };

  const handlePlaceOrder = async (e) => {
    e.preventDefault();

    if (addresses.length === 0 || !selectedAddressId) {
      setModalTitle("Missing Information");
      setModalMessage("Please add and select a delivery address first.");
      setShowModal(true);
      return;
    }

    setIsPlacingOrder(true);
    const userId = getActiveUserId();
    const selectedAddr =
      addresses.find((a) => a.AddressId === selectedAddressId) || {};

    const orderData = {
      action: "create_order",
      userId: Number(userId),
      addressId: selectedAddressId,
      shippingAddress: {
        addressLine1: selectedAddr.AddressLine1 || "",
        city: selectedAddr.City || "",
        stateProvince: selectedAddr.StateProvince || "",
        postalCode: selectedAddr.PostalCode || "",
      },
      totalAmount: grandTotal,
      subtotal: subtotal,
      tax: tax,
      shipping: shipping,
      discount: discountAmount,
      paymentMethod: paymentMethod,
      items: checkoutItems.map((item) => ({
        name: item.name || item.Name,
        quantity: item.cartQuantity || item.quantity || 1,
        price: item.price,
        size: item.selectedSize || item.size || "Standard",
        image:
          item.image ||
          item.gallery?.[0] ||
          item.ImageUrl ||
          item.imageUrl ||
          "",
      })),
    };

    try {
      const response = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/orders/user_orders_api.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-API-KEY": "GatewayLinen@2026",
          },
          body: JSON.stringify(orderData),
        },
      );

      const result = await response.json();
      console.log("Order API Response:", result);

      if (!result.success) {
        let errorDetail = result.message || "Failed to save order.";
        if (result.errors && result.errors.length > 0) {
          errorDetail += " | DB Error: " + result.errors[0].message;
        }
        throw new Error(errorDetail);
      }

      if (!isDirectBuy) {
        clearCart();
      }
      setIsPlacingOrder(false);
      setOrderPlaced(true);
    } catch (err) {
      console.error("Error placing order:", err);
      setIsPlacingOrder(false);
      setModalTitle("Order Error");
      setModalMessage(err.message);
      setShowModal(true);
    }
  };

  if (orderPlaced) {
    return (
      <div className="min-h-screen bg-[#F0EAE1] flex flex-col items-center justify-center px-4 font-sans">
        <div className="bg-[#F7F2EB] border border-[#E5DCD0] p-8 sm:p-10 rounded-[28px] sm:rounded-[36px] shadow-2xl max-w-md w-full text-center">
          <div className="w-16 h-16 sm:w-20 sm:h-20 bg-green-100 text-green-600 rounded-2xl sm:rounded-3xl flex items-center justify-center mx-auto mb-4 sm:mb-6 shadow-sm">
            <FiCheckCircle size={36} />
          </div>
          <span className="text-[9px] sm:text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
            Order Confirmed
          </span>
          <h1 className="text-xl sm:text-3xl font-serif font-bold text-[#031D44] mt-1 mb-2 sm:mb-3">
            Order Placed Successfully!
          </h1>
          <p className="text-xs text-gray-600 font-light mb-4 leading-relaxed">
            Thank you for your purchase. Your order has been successfully saved
            to your dashboard buying history.
          </p>
          <p className="text-xs text-blue-800 font-semibold mb-6 sm:mb-8 leading-relaxed bg-blue-50 border border-blue-200 p-3 rounded-xl">
            If you want an invoice, it will automatically generate in your
            dashboard. You can download it from there.
          </p>
          <button
            onClick={() => navigate("/dashboard")}
            className="w-full py-3.5 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
          >
            Go to Dashboard
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#F0EAE1] py-6 sm:py-12 px-3 sm:px-6 md:px-10 font-sans relative">
      <div className="max-w-[1300px] mx-auto">
        <button
          onClick={() => {
            if (checkoutStep > 1) {
              setCheckoutStep(checkoutStep - 1);
            } else {
              navigate(-1);
            }
          }}
          className="mb-4 sm:mb-6 inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#F7F2EB] border border-[#E5DCD0] text-[11px] font-bold uppercase tracking-wider rounded-xl text-[#031D44] hover:border-[#B58E58] transition-all cursor-pointer shadow-2xs"
        >
          <FiArrowLeft size={13} />{" "}
          {checkoutStep > 1 ? "Previous Step" : "Back"}
        </button>

        <div className="mb-6 sm:mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h1 className="text-2xl sm:text-4xl font-serif font-bold text-[#031D44] tracking-wide">
              Secure Checkout
            </h1>
            <p className="text-[11px] sm:text-xs text-gray-600 font-light mt-0.5 sm:mt-1 flex items-center gap-1.5">
              <FiLock className="text-[#B58E58]" size={12} /> 256-Bit Encrypted
              Secure Commercial Checkout
            </p>
          </div>

          <div className="grid grid-cols-3 gap-1 bg-[#F7F2EB] p-1.5 sm:p-2 rounded-2xl border border-[#E5DCD0]">
            <div
              className={`px-2 sm:px-3 py-1.5 rounded-xl text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-center truncate ${checkoutStep === 1 ? "bg-[#031D44] text-white" : "text-gray-500"}`}
            >
              1. Address
            </div>
            <div
              className={`px-2 sm:px-3 py-1.5 rounded-xl text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-center truncate ${checkoutStep === 2 ? "bg-[#031D44] text-white" : "text-gray-500"}`}
            >
              2. Summary
            </div>
            <div
              className={`px-2 sm:px-3 py-1.5 rounded-xl text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-center truncate ${checkoutStep === 3 ? "bg-[#031D44] text-white" : "text-gray-500"}`}
            >
              3. Payment
            </div>
          </div>
        </div>

        <div className="flex flex-col lg:flex-row gap-6 sm:gap-8 items-start">
          <div className="lg:w-2/3 w-full">
            {checkoutStep === 1 && (
              <div className="bg-[#F7F2EB] p-5 sm:p-8 md:p-10 rounded-[24px] sm:rounded-[32px] border border-[#E5DCD0] shadow-xl mb-6 sm:mb-8">
                <div className="flex justify-between items-center mb-4 sm:mb-6 pb-3 sm:pb-4 border-b border-[#E5DCD0]">
                  <h2 className="text-base sm:text-lg font-serif font-bold text-[#031D44] flex items-center gap-2">
                    <FiTruck className="text-[#B58E58]" size={18} /> Select
                    Shipping Address
                  </h2>
                  {addresses.length > 0 && !showAddressForm && (
                    <button
                      onClick={() => setShowAddressForm(true)}
                      className="text-[11px] sm:text-xs font-bold text-[#B58E58] hover:text-[#031D44] flex items-center gap-1 cursor-pointer transition-colors"
                    >
                      <FiPlus /> Add New
                    </button>
                  )}
                </div>

                {loadingAddresses ? (
                  <div className="text-center py-10 text-xs text-gray-500 font-bold uppercase tracking-widest">
                    Loading Addresses...
                  </div>
                ) : (
                  <>
                    {addresses.length > 0 && (
                      <div className="space-y-3 sm:space-y-4 mb-5 sm:mb-6">
                        {addresses.map((addr) => (
                          <label
                            key={addr.AddressId}
                            className={`flex items-start gap-3 sm:gap-4 p-4 sm:p-5 rounded-2xl border transition-all cursor-pointer shadow-2xs
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
                            <div className="flex-1 min-w-0">
                              <div className="flex items-center gap-2 mb-1">
                                <span className="font-bold text-xs sm:text-sm text-[#031D44] truncate">
                                  {addr.RecipientName}
                                </span>
                                <span className="text-[8.5px] bg-gray-200 text-gray-700 px-1.5 py-0.5 rounded uppercase font-bold tracking-wider">
                                  {addr.AddressType || "Shipping"}
                                </span>
                              </div>
                              <p className="text-[11px] sm:text-xs text-gray-600 font-light mb-1 break-words">
                                {addr.AddressLine1}, {addr.City},{" "}
                                {addr.StateProvince} {addr.PostalCode}
                              </p>
                              <p className="text-[11px] sm:text-xs text-gray-600 font-light flex items-center gap-1.5">
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

                    {(showAddressForm || addresses.length === 0) && (
                      <form
                        onSubmit={handleAddNewAddress}
                        className="bg-white p-4 sm:p-6 rounded-2xl border border-[#E5DCD0] shadow-2xs mb-5"
                      >
                        <h3 className="text-xs sm:text-sm font-bold text-[#031D44] uppercase tracking-widest mb-4 sm:mb-6 flex items-center gap-2 pb-3 border-b border-[#E5DCD0]">
                          <FiMapPin className="text-[#B58E58]" size={16} /> Add
                          New Address
                        </h3>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5 mb-3.5">
                          <div>
                            <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                              Recipient Name
                            </label>
                            <input
                              required
                              type="text"
                              name="recipientName"
                              value={newAddressData.recipientName}
                              onChange={handleNewAddressChange}
                              className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                              placeholder="Full Name"
                            />
                          </div>
                          <div>
                            <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                              Phone Number
                            </label>
                            <input
                              required
                              type="text"
                              name="phone"
                              value={newAddressData.phone}
                              onChange={handleNewAddressChange}
                              className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                              placeholder="+1 234 567 8900"
                            />
                          </div>
                        </div>

                        <div className="mb-3.5">
                          <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                            Street Address
                          </label>
                          <input
                            required
                            type="text"
                            name="addressLine1"
                            value={newAddressData.addressLine1}
                            onChange={handleNewAddressChange}
                            className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                            placeholder="House / Flat No, Street, Landmark"
                          />
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3.5 mb-5">
                          <div>
                            <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                              City
                            </label>
                            <input
                              required
                              type="text"
                              name="city"
                              value={newAddressData.city}
                              onChange={handleNewAddressChange}
                              className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                              placeholder="City"
                            />
                          </div>
                          <div>
                            <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                              State/Province
                            </label>
                            <input
                              required
                              type="text"
                              name="stateProvince"
                              value={newAddressData.stateProvince}
                              onChange={handleNewAddressChange}
                              className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                              placeholder="State"
                            />
                          </div>
                          <div>
                            <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                              Postal Code
                            </label>
                            <input
                              required
                              type="text"
                              name="postalCode"
                              value={newAddressData.postalCode}
                              onChange={handleNewAddressChange}
                              className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                              placeholder="Postal Code"
                            />
                          </div>
                        </div>

                        <div className="flex gap-3">
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
                            Save & Use Address
                          </button>
                        </div>
                      </form>
                    )}

                    <button
                      type="button"
                      onClick={() => {
                        if (!selectedAddressId) {
                          setModalTitle("Address Required");
                          setModalMessage(
                            "Please select a delivery address to proceed.",
                          );
                          setShowModal(true);
                          return;
                        }
                        setCheckoutStep(2);
                      }}
                      className="w-full py-3.5 sm:py-4 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-lg transition-all cursor-pointer flex justify-center items-center gap-2 mt-4"
                    >
                      <span>Proceed to Summary & Coupons</span>
                      <FiArrowRight size={15} />
                    </button>
                  </>
                )}
              </div>
            )}

            {checkoutStep === 2 && (
              <div className="bg-[#F7F2EB] p-5 sm:p-8 md:p-10 rounded-[24px] sm:rounded-[32px] border border-[#E5DCD0] shadow-xl mb-6 sm:mb-8">
                <h2 className="text-base sm:text-lg font-serif font-bold text-[#031D44] mb-4 sm:mb-6 pb-3 sm:pb-4 border-b border-[#E5DCD0] flex items-center gap-2">
                  <FiTag className="text-[#B58E58]" size={18} /> Apply Coupon &
                  Review Totals
                </h2>

                <div className="bg-white p-4 sm:p-6 rounded-2xl border border-[#E5DCD0] mb-5 shadow-2xs">
                  <label className="block text-[10px] sm:text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-2">
                    Have a Coupon Code?
                  </label>
                  <form
                    onSubmit={handleApplyCoupon}
                    className="flex flex-col sm:flex-row gap-2.5"
                  >
                    <input
                      type="text"
                      value={couponCode}
                      onChange={(e) => setCouponCode(e.target.value)}
                      placeholder="e.g. LINEN10"
                      className="flex-1 px-3.5 py-2.5 sm:py-3 bg-[#FAF7F2] border border-[#E5DCD0] rounded-xl text-xs uppercase tracking-wider focus:outline-none focus:border-[#B58E58]"
                    />
                    <button
                      type="submit"
                      className="px-5 py-2.5 sm:py-3 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl transition-all cursor-pointer"
                    >
                      Apply
                    </button>
                  </form>
                  {couponError && (
                    <p className="text-xs text-red-600 mt-2 font-medium">
                      {couponError}
                    </p>
                  )}
                  {couponSuccess && (
                    <p className="text-xs text-green-600 mt-2 font-medium">
                      {couponSuccess}
                    </p>
                  )}
                </div>

                <div className="bg-white p-4 sm:p-6 rounded-2xl border border-[#E5DCD0] mb-6 space-y-2.5 text-xs text-gray-700">
                  <div className="flex justify-between">
                    <span>Subtotal</span>
                    <span className="font-semibold text-gray-900">
                      CAD ${subtotal.toFixed(2)}
                    </span>
                  </div>
                  {discountAmount > 0 && (
                    <div className="flex justify-between text-green-700 font-medium">
                      <span>Discount Applied</span>
                      <span>- CAD ${discountAmount.toFixed(2)}</span>
                    </div>
                  )}
                  <div className="flex justify-between">
                    <span>Estimated Tax (13%)</span>
                    <span className="font-semibold text-gray-900">
                      CAD ${tax.toFixed(2)}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span>Shipping</span>
                    <span className="font-semibold text-gray-900">
                      {shipping === 0 ? "Free" : `CAD $${shipping.toFixed(2)}`}
                    </span>
                  </div>
                  <div className="border-t border-[#E5DCD0] pt-3 flex justify-between items-center text-xs sm:text-sm font-serif font-bold text-[#031D44]">
                    <span>Final Calculated Amount</span>
                    <span className="text-base sm:text-lg text-[#031D44]">
                      CAD ${grandTotal}
                    </span>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => setCheckoutStep(3)}
                  className="w-full py-3.5 sm:py-4 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-lg transition-all cursor-pointer flex justify-center items-center gap-2"
                >
                  <span>Proceed to Payment Gateway</span>
                  <FiArrowRight size={15} />
                </button>
              </div>
            )}

            {checkoutStep === 3 && (
              <div className="bg-[#F7F2EB] p-5 sm:p-8 md:p-10 rounded-[24px] sm:rounded-[32px] border border-[#E5DCD0] shadow-xl mb-6 sm:mb-8">
                <h2 className="text-base sm:text-lg font-serif font-bold text-[#031D44] mb-4 sm:mb-6 pb-3 sm:pb-4 border-b border-[#E5DCD0] flex items-center gap-2">
                  <FiCreditCard className="text-[#B58E58]" size={18} /> Select
                  Payment Gateway
                </h2>

                <div className="flex flex-col gap-3.5 mb-6">
                  <label className="flex items-center gap-3 p-3.5 sm:p-4 bg-white border border-[#E5DCD0] rounded-2xl cursor-pointer hover:border-[#B58E58] shadow-2xs transition-all">
                    <input
                      type="radio"
                      name="paymentMethod"
                      value="credit_card"
                      checked={paymentMethod === "credit_card"}
                      onChange={(e) => setPaymentMethod(e.target.value)}
                      className="w-4 h-4 text-[#031D44] focus:ring-[#B58E58]"
                    />
                    <span className="font-semibold text-xs text-[#031D44]">
                      Credit / Debit Card (Simulated Gateway)
                    </span>
                  </label>

                  {paymentMethod === "credit_card" && (
                    <div className="bg-white p-4 rounded-2xl border border-[#E5DCD0] flex flex-col gap-3 shadow-2xs ml-4 sm:ml-7">
                      <div>
                        <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                          Card Number
                        </label>
                        <input
                          type="text"
                          placeholder="4242 •••• •••• 4242"
                          maxLength="19"
                          className="w-full px-3.5 py-2.5 rounded-xl border border-[#E5DCD0] bg-gray-50 text-xs focus:outline-none focus:border-[#B58E58]"
                        />
                      </div>
                      <div className="grid grid-cols-2 gap-2.5">
                        <div>
                          <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
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
                          <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
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

                  <label className="flex items-center gap-3 p-3.5 sm:p-4 bg-white border border-[#E5DCD0] rounded-2xl cursor-pointer hover:border-[#B58E58] shadow-2xs transition-all">
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
                  className={`w-full py-3.5 sm:py-4 text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-lg transition-all cursor-pointer flex justify-center items-center gap-2 
                    ${isPlacingOrder || addresses.length === 0 ? "bg-gray-400 cursor-not-allowed" : "bg-[#031D44] hover:bg-[#B58E58]"}`}
                >
                  <FiLock size={15} />
                  {isPlacingOrder
                    ? "Processing..."
                    : `Pay & Place Order — CAD $${grandTotal}`}
                </button>
              </div>
            )}
          </div>

          <div className="lg:w-1/3 w-full lg:sticky lg:top-24">
            <div className="bg-[#F7F2EB] p-5 sm:p-8 rounded-[24px] sm:rounded-[32px] border border-[#E5DCD0] shadow-xl relative">
              <h2 className="text-base sm:text-lg font-serif font-bold text-[#031D44] mb-4 sm:mb-6 pb-3 sm:pb-4 border-b border-[#E5DCD0] flex items-center justify-between">
                <span>Order Summary</span>
              </h2>

              <div className="flex flex-col gap-3 mb-5 max-h-[250px] overflow-y-auto pr-1">
                {checkoutItems.map((item, index) => {
                  const itemPrice = item.price || 0;
                  const itemQty = item.cartQuantity || item.quantity || 1;
                  return (
                    <div
                      key={index}
                      className="flex items-center gap-3 p-2.5 sm:p-3 bg-white rounded-2xl border border-[#E5DCD0] shadow-2xs"
                    >
                      <div className="w-12 h-12 sm:w-14 sm:h-14 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0 border border-gray-100 relative">
                        <img
                          src={item.image || item.gallery?.[0]}
                          alt={item.name}
                          className="w-full h-full object-cover"
                        />
                      </div>
                      <div className="flex-1 min-w-0">
                        <h4 className="text-xs font-serif font-bold text-[#031D44] truncate">
                          {item.name}
                        </h4>
                        <p className="text-[10px] text-gray-500 font-light mt-0.5">
                          Size: {item.selectedSize || "Standard"} | Qty:{" "}
                          {itemQty}
                        </p>
                      </div>
                      <div className="text-xs font-bold text-[#031D44]">
                        CAD ${(itemPrice * itemQty).toFixed(2)}
                      </div>
                    </div>
                  );
                })}
              </div>

              <div className="border-t border-[#E5DCD0] pt-3 sm:pt-4 flex flex-col gap-2.5 text-xs text-gray-600 font-light">
                <div className="flex justify-between">
                  <span>Subtotal</span>
                  <span className="font-semibold text-gray-800">
                    CAD ${subtotal.toFixed(2)}
                  </span>
                </div>
                {discountAmount > 0 && (
                  <div className="flex justify-between text-green-700 font-medium">
                    <span>Coupon Discount</span>
                    <span>- CAD ${discountAmount.toFixed(2)}</span>
                  </div>
                )}
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
                <div className="border-t border-[#E5DCD0] pt-3 flex justify-between items-center mt-1">
                  <span className="text-xs sm:text-sm font-serif font-bold text-[#031D44]">
                    Total
                  </span>
                  <span className="text-lg sm:text-xl font-serif font-bold text-[#031D44]">
                    CAD ${grandTotal}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-3 sm:p-4">
          <div className="bg-[#F7F2EB] border border-[#E5DCD0] rounded-[24px] max-w-sm w-full p-6 sm:p-8 shadow-2xl relative text-center">
            <div className="w-14 h-14 sm:w-16 sm:h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-inner">
              <FiCheckCircle size={28} />
            </div>
            <h3 className="text-lg sm:text-xl font-serif font-bold text-[#031D44] mb-2">
              {modalTitle}
            </h3>
            <p className="text-xs text-gray-600 font-light mb-6 leading-relaxed">
              {modalMessage}
            </p>
            <button
              onClick={() => setShowModal(false)}
              className="w-full py-3 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
            >
              OK, Got It
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
