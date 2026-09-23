import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import {
  FiUser,
  FiMail,
  FiPhone,
  FiSave,
  FiCheckCircle,
  FiTrash2,
  FiAlertTriangle,
  FiX,
  FiMapPin,
  FiShoppingBag,
  FiPlus,
  FiEdit2,
  FiLogOut,
} from "react-icons/fi";

const Dashboard = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [message, setMessage] = useState({ type: "", text: "" });

  // --- Safe User ID Helper ---
  const getActiveUserId = () => {
    try {
      const storedUser = localStorage.getItem("user");
      if (!storedUser) return "";
      const parsed = JSON.parse(storedUser);
      return (
        parsed.UserId || parsed.userId || parsed.id || parsed.user_id || ""
      );
    } catch (err) {
      console.error(err);
      return "";
    }
  };

  const [formData, setFormData] = useState(() => {
    const userData = localStorage.getItem("user");
    if (userData) {
      const parsedUser = JSON.parse(userData);
      return {
        userId: parsedUser.UserId || parsedUser.userId || parsedUser.id || "",
        fullName:
          parsedUser.FullName || parsedUser.fullName || parsedUser.name || "",
        email: parsedUser.Email || parsedUser.email || "",
        phone: parsedUser.Phone || parsedUser.phone || "",
      };
    }
    return { userId: "", fullName: "", email: "", phone: "" };
  });

  // --- Saved Addresses State ---
  const [addresses, setAddresses] = useState([]);
  const [loadingAddresses, setLoadingAddresses] = useState(true);
  const [showAddressModal, setShowAddressModal] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [currentAddressId, setCurrentAddressId] = useState(null);

  const [addressForm, setAddressForm] = useState({
    recipientName: "",
    phone: "",
    addressLine1: "",
    city: "",
    stateProvince: "",
    postalCode: "",
  });

  // Orders State
  const [orders] = useState(() => {
    return (
      JSON.parse(localStorage.getItem("userOrders")) || [
        {
          id: "GW-98213",
          date: "2026-06-20",
          total: "48.89",
          status: "Delivered",
          items: [
            {
              name: "Premium Spa Pool Towel",
              cartQuantity: 1,
              selectedSize: "Standard",
            },
          ],
        },
      ]
    );
  });

  // Modal / Alert State
  const [showModal, setShowModal] = useState(false);
  const [modalTitle, setModalTitle] = useState("");
  const [modalMessage, setModalMessage] = useState("");

  // --- Fetch Addresses from API ---
  const fetchUserAddresses = async (userId) => {
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
          body: JSON.stringify({ action: "get_addresses", userId: userId }),
        },
      );
      const result = await response.json();
      if (result.success) {
        setAddresses(result.data || []);
      }
    } catch (err) {
      console.error("Error fetching addresses:", err);
    } finally {
      setLoadingAddresses(false);
    }
  };

  useEffect(() => {
    const userId = getActiveUserId();
    if (!userId) {
      navigate("/login");
      return;
    }
    setTimeout(() => {
      fetchUserAddresses(userId);
    }, 0);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleUpdate = async (e) => {
    e.preventDefault();
    setMessage({ type: "", text: "" });
    setLoading(true);

    try {
      const payload = {
        entity: "user",
        action: "update",
        userId: formData.userId,
        fullName: formData.fullName,
        email: formData.email,
        phone: formData.phone,
      };

      const response = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenadmin-main/users/api.php",
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
        setMessage({ type: "success", text: "Profile updated successfully!" });
        localStorage.setItem("user", JSON.stringify(result.data));
      } else {
        setMessage({
          type: "error",
          text: result.message || "Failed to update profile.",
        });
      }
    } catch (err) {
      console.error("API Error: ", err);
      setMessage({
        type: "error",
        text: "Server error. Please try again later.",
      });
    } finally {
      setLoading(false);
    }
  };

  // --- Address Management Handlers ---
  const handleOpenAddAddressModal = () => {
    setIsEditing(false);
    setCurrentAddressId(null);
    setAddressForm({
      recipientName: formData.fullName,
      phone: formData.phone,
      addressLine1: "",
      city: "",
      stateProvince: "",
      postalCode: "",
    });
    setShowAddressModal(true);
  };

  const handleOpenEditAddressModal = (addr) => {
    setIsEditing(true);
    setCurrentAddressId(addr.AddressId);
    setAddressForm({
      recipientName: addr.RecipientName || "",
      phone: addr.Phone || "",
      addressLine1: addr.AddressLine1 || "",
      city: addr.City || "",
      stateProvince: addr.StateProvince || "",
      postalCode: addr.PostalCode || "",
    });
    setShowAddressModal(true);
  };

  const handleFormChange = (e) => {
    setAddressForm({ ...addressForm, [e.target.name]: e.target.value });
  };

  const handleSaveAddress = async (e) => {
    e.preventDefault();
    const userId = getActiveUserId();

    try {
      const endpointAction = isEditing ? "update_address" : "add_address";
      const payload = {
        action: endpointAction,
        userId: userId,
        addressId: currentAddressId,
        ...addressForm,
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
        setShowAddressModal(false);
        setModalTitle("Success");
        setModalMessage(result.message);
        setShowModal(true);
        fetchUserAddresses(userId);
      } else {
        setModalTitle("Error");
        setModalMessage(result.message || "Operation failed.");
        setShowModal(true);
      }
    } catch (err) {
      console.error("Error saving address:", err);
      setModalTitle("Server Error");
      setModalMessage("Could not connect to database API.");
      setShowModal(true);
    }
  };

  const handleDeleteAddress = async (addressId) => {
    if (!window.confirm("Are you sure you want to delete this address?"))
      return;

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
            action: "delete_address",
            addressId: addressId,
          }),
        },
      );
      const result = await response.json();

      if (result.success) {
        setModalTitle("Deleted");
        setModalMessage("Address removed successfully.");
        setShowModal(true);
        fetchUserAddresses(getActiveUserId());
      } else {
        alert(result.message || "Failed to delete.");
      }
    } catch (err) {
      console.error("Error deleting address:", err);
    }
  };

  const handleDeleteAccount = async () => {
    setDeleteLoading(true);
    setMessage({ type: "", text: "" });

    try {
      const payload = {
        entity: "user",
        action: "delete",
        userId: formData.userId,
      };

      const response = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenadmin-main/users/api.php",
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
        localStorage.removeItem("user");
        navigate("/login");
      } else {
        setMessage({
          type: "error",
          text: result.message || "Failed to delete account.",
        });
        setShowDeleteModal(false);
      }
    } catch (err) {
      console.error("Delete API Error: ", err);
      setMessage({
        type: "error",
        text: "Server error during account deletion.",
      });
      setShowDeleteModal(false);
    } finally {
      setDeleteLoading(false);
    }
  };

  const handleLogout = () => {
    localStorage.removeItem("user");
    navigate("/login");
  };

  return (
    <div className="min-h-screen bg-[#F0EAE1] py-6 md:py-12 px-3 md:px-10 font-sans relative">
      <div className="max-w-4xl mx-auto">
        <div className="mb-6 md:mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div>
            <h1 className="text-2xl md:text-3xl font-serif font-bold text-[#031D44]">
              My Dashboard
            </h1>
            <p className="text-xs text-gray-600 font-light mt-0.5">
              Manage your profile, shipping addresses, and buying history.
            </p>
          </div>
          <button
            onClick={handleLogout}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-[11px] font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer shadow-2xs"
          >
            <FiLogOut size={14} /> Logout
          </button>
        </div>

        {/* Profile Card */}
        <div className="bg-[#F7F2EB] rounded-[24px] md:rounded-[32px] p-5 sm:p-8 border border-[#E5DCD0] shadow-xl mb-6 md:mb-8">
          <div className="flex items-center gap-3.5 mb-6 pb-4 border-b border-[#E5DCD0]">
            <div className="w-12 h-12 md:w-16 md:h-16 bg-[#031D44] text-[#B58E58] rounded-full flex items-center justify-center text-xl md:text-2xl font-bold shadow-md">
              {formData.fullName
                ? formData.fullName.charAt(0).toUpperCase()
                : "U"}
            </div>
            <div>
              <h2 className="text-lg md:text-xl font-serif font-bold text-[#031D44]">
                {formData.fullName || "User Profile"}
              </h2>
              <p className="text-xs text-gray-600 flex items-center gap-1 mt-0.5">
                <FiCheckCircle className="text-green-600" size={13} /> Verified
                Member
              </p>
            </div>
          </div>

          {message.text && (
            <div
              className={`mb-5 text-center text-xs font-bold p-3 rounded-xl ${message.type === "error" ? "bg-red-50 text-red-600 border border-red-200" : "bg-green-50 text-green-700 border border-green-200"}`}
            >
              {message.text}
            </div>
          )}

          <form onSubmit={handleUpdate} className="space-y-4 md:space-y-5">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
              <div>
                <label className="block text-[10px] md:text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                  Full Name
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <FiUser className="text-gray-400" size={14} />
                  </div>
                  <input
                    type="text"
                    name="fullName"
                    value={formData.fullName}
                    onChange={handleChange}
                    className="block w-full pl-9 pr-3 py-2.5 md:py-3 border border-[#E5DCD0] rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#B58E58] bg-[#FFFDF9] transition-all shadow-2xs"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-[10px] md:text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                  Email Address
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <FiMail className="text-gray-400" size={14} />
                  </div>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    className="block w-full pl-9 pr-3 py-2.5 md:py-3 border border-[#E5DCD0] rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#B58E58] bg-[#FFFDF9] transition-all shadow-2xs"
                    required
                  />
                </div>
              </div>

              <div className="md:col-span-2">
                <label className="block text-[10px] md:text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                  Phone Number
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <FiPhone className="text-gray-400" size={14} />
                  </div>
                  <input
                    type="tel"
                    name="phone"
                    value={formData.phone}
                    onChange={handleChange}
                    className="block w-full pl-9 pr-3 py-2.5 md:py-3 border border-[#E5DCD0] rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#B58E58] bg-[#FFFDF9] transition-all shadow-2xs"
                  />
                </div>
              </div>
            </div>

            <div className="pt-4 flex justify-end">
              <button
                disabled={loading}
                type="submit"
                className={`w-full sm:w-auto flex items-center justify-center gap-2 py-3 px-6 md:px-8 rounded-xl shadow-md text-xs font-bold tracking-widest uppercase text-white ${loading ? "bg-gray-400" : "bg-[#031D44] hover:bg-[#B58E58]"} transition-all cursor-pointer`}
              >
                <FiSave size={14} />
                {loading ? "Saving..." : "Save Changes"}
              </button>
            </div>
          </form>
        </div>

        {/* SAVED ADDRESSES SECTION */}
        <div className="bg-[#F7F2EB] rounded-[24px] md:rounded-[32px] p-5 sm:p-8 border border-[#E5DCD0] shadow-xl mb-6 md:mb-8">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-[#E5DCD0]">
            <div className="flex items-center gap-2.5 text-[#031D44]">
              <div className="w-8 h-8 md:w-9 md:h-9 bg-[#031D44]/10 text-[#031D44] rounded-xl flex items-center justify-center shrink-0">
                <FiMapPin size={16} className="text-[#B58E58]" />
              </div>
              <div>
                <h2 className="text-base md:text-lg font-serif font-bold">
                  Saved Shipping Addresses
                </h2>
                <p className="text-[10px] md:text-[11px] text-gray-500 font-light">
                  Manage your delivery locations for fast checkout
                </p>
              </div>
            </div>
            <button
              onClick={handleOpenAddAddressModal}
              className="w-full sm:w-auto flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-[#031D44] text-white rounded-xl text-xs font-bold hover:bg-[#B58E58] transition-all cursor-pointer shadow-sm"
            >
              <FiPlus size={14} /> Add New Address
            </button>
          </div>

          {loadingAddresses ? (
            <div className="text-center py-8 text-xs text-gray-500 font-bold uppercase tracking-widest">
              Loading Addresses...
            </div>
          ) : addresses.length === 0 ? (
            <div className="text-center py-10 bg-[#FFFDF9] rounded-2xl border border-dashed border-[#E5DCD0]">
              <FiMapPin size={28} className="mx-auto text-gray-300 mb-2" />
              <p className="text-xs font-bold text-[#031D44] mb-1">
                No saved addresses found
              </p>
              <p className="text-[11px] text-gray-500 font-light">
                Add your first shipping address using the button above.
              </p>
            </div>
          ) : (
            <div className="max-h-[460px] overflow-y-auto pr-1 space-y-3">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                {addresses.map((addr) => (
                  <div
                    key={addr.AddressId}
                    className="p-4 bg-[#FFFDF9] rounded-xl border border-[#E5DCD0] hover:border-[#B58E58] flex flex-col justify-between shadow-2xs transition-all group relative overflow-hidden"
                  >
                    <div className="absolute top-0 left-0 w-1.5 h-full bg-[#B58E58] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div>
                      <div className="flex justify-between items-start mb-2">
                        <span className="font-bold text-xs md:text-sm text-[#031D44] tracking-wide">
                          {addr.RecipientName}
                        </span>
                        <span className="text-[8.5px] bg-[#B58E58]/10 text-[#B58E58] border border-[#B58E58]/30 px-2 py-0.5 rounded-full uppercase font-bold tracking-wider">
                          {addr.AddressType || "Shipping"}
                        </span>
                      </div>
                      <p className="text-[11px] md:text-xs text-gray-600 font-light mb-1 leading-relaxed">
                        {addr.AddressLine1}, {addr.City}, {addr.StateProvince}{" "}
                        {addr.PostalCode}
                      </p>
                      <p className="text-[11px] md:text-xs text-gray-500 font-light mb-3 flex items-center gap-1.5">
                        <FiPhone size={11} className="text-gray-400" />
                        <span className="font-medium text-gray-800">
                          {addr.Phone}
                        </span>
                      </p>
                    </div>

                    <div className="flex gap-2 pt-2.5 border-t border-gray-100">
                      <button
                        onClick={() => handleOpenEditAddressModal(addr)}
                        className="flex-1 py-2 bg-white hover:bg-[#031D44] hover:text-white text-[#031D44] text-[10px] font-bold tracking-wider uppercase rounded-lg border border-gray-200 hover:border-[#031D44] transition-all cursor-pointer flex items-center justify-center gap-1 shadow-2xs"
                      >
                        <FiEdit2 size={12} /> Edit
                      </button>
                      <button
                        onClick={() => handleDeleteAddress(addr.AddressId)}
                        className="py-2 px-3 bg-red-50 hover:bg-red-600 hover:text-white text-red-600 text-[10px] font-bold rounded-lg border border-red-200 hover:border-red-600 transition-all cursor-pointer flex items-center justify-center shadow-2xs"
                        title="Delete Address"
                      >
                        <FiTrash2 size={13} />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* BUYING HISTORY SECTION */}
        <div className="bg-[#F7F2EB] rounded-[24px] md:rounded-[32px] p-5 sm:p-8 border border-[#E5DCD0] shadow-xl mb-6 md:mb-8">
          <div className="flex items-center gap-2 mb-5 pb-3 border-b border-[#E5DCD0] text-[#031D44]">
            <FiShoppingBag size={18} className="text-[#B58E58]" />
            <h2 className="text-base md:text-lg font-serif font-bold">
              Buying History (Orders)
            </h2>
          </div>

          {orders.length === 0 ? (
            <div className="text-center py-6 text-xs text-gray-500 font-light">
              No past orders found.
            </div>
          ) : (
            <div className="space-y-3">
              {orders.map((order, idx) => (
                <div
                  key={idx}
                  className="p-4 bg-[#FFFDF9] rounded-xl border border-[#E5DCD0] flex flex-col md:flex-row justify-between items-start md:items-center gap-3 shadow-2xs"
                >
                  <div>
                    <div className="flex items-center gap-2.5">
                      <span className="text-xs font-bold text-[#031D44]">
                        Order #{order.id}
                      </span>
                      <span className="text-[9px] bg-green-100 text-green-800 px-2 py-0.5 rounded-full font-semibold">
                        {order.status}
                      </span>
                    </div>
                    <p className="text-[11px] text-gray-500 font-light mt-0.5">
                      Date: {order.date}
                    </p>
                    <p className="text-[11px] font-semibold text-gray-700 mt-1">
                      {order.items
                        .map(
                          (i) =>
                            `${i.name} (${i.selectedSize} x ${i.cartQuantity})`,
                        )
                        .join(", ")}
                    </p>
                  </div>
                  <div className="text-right">
                    <span className="text-xs md:text-sm font-bold text-[#031D44]">
                      CAD ${order.total}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* DANGER ZONE - DELETE ACCOUNT */}
        <div className="pt-1">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-red-50 border border-red-200 p-5 rounded-[20px] shadow-sm">
            <div>
              <h3 className="text-sm font-bold text-red-700 flex items-center gap-1.5">
                <FiAlertTriangle size={16} /> Delete Account
              </h3>
              <p className="text-[11px] text-red-600/80 font-light mt-0.5">
                Once you delete your account, all your data will be permanently
                removed.
              </p>
            </div>
            <button
              onClick={() => setShowDeleteModal(true)}
              className="w-full sm:w-auto shrink-0 flex items-center justify-center gap-1.5 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-[11px] font-bold tracking-wider uppercase rounded-xl shadow-md transition-all cursor-pointer"
            >
              <FiTrash2 size={13} />
              Permanently Delete
            </button>
          </div>
        </div>
      </div>

      {/* ADD / EDIT ADDRESS MODAL */}
      {showAddressModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-3">
          <div className="bg-[#F7F2EB] rounded-[24px] max-w-lg w-full p-5 md:p-8 shadow-2xl border border-[#E5DCD0]">
            <div className="flex justify-between items-center mb-4 pb-3 border-b border-[#E5DCD0]">
              <h3 className="text-lg md:text-xl font-serif font-bold text-[#031D44]">
                {isEditing
                  ? "Edit Shipping Address"
                  : "Add New Shipping Address"}
              </h3>
              <button
                onClick={() => setShowAddressModal(false)}
                className="text-gray-400 hover:text-gray-600 p-1.5 rounded-full transition-colors cursor-pointer bg-white border border-gray-200"
              >
                <FiX size={16} />
              </button>
            </div>

            <form onSubmit={handleSaveAddress} className="space-y-3.5">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                <div>
                  <label className="block text-[10px] md:text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                    Recipient Name
                  </label>
                  <input
                    type="text"
                    required
                    name="recipientName"
                    value={addressForm.recipientName}
                    onChange={handleFormChange}
                    placeholder="Full Name"
                    className="w-full px-3.5 py-2.5 border border-[#E5DCD0] rounded-xl text-xs bg-white focus:outline-none focus:border-[#B58E58] shadow-2xs"
                  />
                </div>
                <div>
                  <label className="block text-[10px] md:text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                    Phone Number
                  </label>
                  <input
                    type="text"
                    required
                    name="phone"
                    value={addressForm.phone}
                    onChange={handleFormChange}
                    placeholder="+1 234 567 8900"
                    className="w-full px-3.5 py-2.5 border border-[#E5DCD0] rounded-xl text-xs bg-white focus:outline-none focus:border-[#B58E58] shadow-2xs"
                  />
                </div>
              </div>

              <div>
                <label className="block text-[10px] md:text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                  Street Address
                </label>
                <input
                  type="text"
                  required
                  name="addressLine1"
                  value={addressForm.addressLine1}
                  onChange={handleFormChange}
                  placeholder="House / Flat No, Street, Landmark"
                  className="w-full px-3.5 py-2.5 border border-[#E5DCD0] rounded-xl text-xs bg-white focus:outline-none focus:border-[#B58E58] shadow-2xs"
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                <div>
                  <label className="block text-[10px] md:text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                    City
                  </label>
                  <input
                    type="text"
                    required
                    name="city"
                    value={addressForm.city}
                    onChange={handleFormChange}
                    placeholder="City"
                    className="w-full px-3.5 py-2.5 border border-[#E5DCD0] rounded-xl text-xs bg-white focus:outline-none focus:border-[#B58E58] shadow-2xs"
                  />
                </div>
                <div>
                  <label className="block text-[10px] md:text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                    State/Province
                  </label>
                  <input
                    type="text"
                    required
                    name="stateProvince"
                    value={addressForm.stateProvince}
                    onChange={handleFormChange}
                    placeholder="State"
                    className="w-full px-3.5 py-2.5 border border-[#E5DCD0] rounded-xl text-xs bg-white focus:outline-none focus:border-[#B58E58] shadow-2xs"
                  />
                </div>
                <div>
                  <label className="block text-[10px] md:text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                    Postal Code
                  </label>
                  <input
                    type="text"
                    required
                    name="postalCode"
                    value={addressForm.postalCode}
                    onChange={handleFormChange}
                    placeholder="Postal Code"
                    className="w-full px-3.5 py-2.5 border border-[#E5DCD0] rounded-xl text-xs bg-white focus:outline-none focus:border-[#B58E58] shadow-2xs"
                  />
                </div>
              </div>

              <div className="flex gap-2.5 pt-3">
                <button
                  type="button"
                  onClick={() => setShowAddressModal(false)}
                  className="flex-1 py-2.5 px-3 bg-white border border-[#E5DCD0] hover:bg-gray-100 text-gray-700 text-[11px] font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 py-2.5 px-3 bg-[#031D44] hover:bg-[#B58E58] text-white text-[11px] font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
                >
                  {isEditing ? "Update Address" : "Save Address"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* GENERAL SUCCESS/ERROR MODAL */}
      {showModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-3">
          <div className="bg-[#F7F2EB] rounded-[24px] max-w-sm w-full p-6 shadow-2xl border border-[#E5DCD0] text-center">
            <div className="w-14 h-14 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-inner">
              <FiCheckCircle size={28} />
            </div>
            <h3 className="text-lg font-serif font-bold text-[#031D44] mb-1.5">
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

      {/* DELETE ACCOUNT CONFIRMATION MODAL */}
      {showDeleteModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-3">
          <div className="bg-[#F7F2EB] rounded-[24px] max-w-md w-full p-5 sm:p-6 shadow-2xl border border-[#E5DCD0]">
            <div className="flex justify-between items-center mb-4">
              <div className="w-10 h-10 bg-red-100 text-red-600 rounded-xl flex items-center justify-center shadow-sm">
                <FiTrash2 size={18} />
              </div>
              <button
                onClick={() => setShowDeleteModal(false)}
                className="text-gray-400 hover:text-gray-600 p-1.5 rounded-full transition-colors cursor-pointer bg-white border border-gray-200"
              >
                <FiX size={16} />
              </button>
            </div>

            <h3 className="text-lg font-serif font-bold text-[#031D44] mb-1.5">
              Delete Account Permanently?
            </h3>
            <p className="text-xs text-gray-600 font-light mb-5 leading-relaxed">
              Are you sure you want to delete your account? This action is
              irreversible and all your data will be erased.
            </p>

            <div className="flex items-center gap-2.5">
              <button
                type="button"
                onClick={() => setShowDeleteModal(false)}
                className="flex-1 py-2.5 px-3 bg-white border border-[#E5DCD0] hover:bg-gray-100 text-gray-700 text-[11px] font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleDeleteAccount}
                disabled={deleteLoading}
                className="flex-1 py-2.5 px-3 bg-red-600 hover:bg-red-700 text-white text-[11px] font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer flex items-center justify-center"
              >
                {deleteLoading ? "Deleting..." : "Yes, Delete"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Dashboard;
