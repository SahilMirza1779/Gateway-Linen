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
} from "react-icons/fi";

const Dashboard = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [message, setMessage] = useState({ type: "", text: "" });

  const [formData, setFormData] = useState(() => {
    const userData = localStorage.getItem("user");
    if (userData) {
      const parsedUser = JSON.parse(userData);
      return {
        userId: parsedUser.userId || "",
        fullName: parsedUser.fullName || "",
        email: parsedUser.email || "",
        phone: parsedUser.phone || "",
      };
    }
    return { userId: "", fullName: "", email: "", phone: "" };
  });

  // Multiple Addresses State (LocalStorage supported)
  const [addresses, setAddresses] = useState(() => {
    return (
      JSON.parse(localStorage.getItem("userAddresses")) || [
        {
          id: 1,
          address: "123 Hospitality Lane",
          city: "Surat",
          postalCode: "395006",
          isDefault: true,
        },
      ]
    );
  });

  const [showAddressModal, setShowAddressModal] = useState(false);
  const [editingAddressId, setEditingAddressId] = useState(null);
  const [addressForm, setAddressForm] = useState({
    address: "",
    city: "",
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

  useEffect(() => {
    if (!localStorage.getItem("user")) {
      navigate("/login");
    }
  }, [navigate]);

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
        "http://localhost/GatewayLinen/GatewayLinenadmin-main/users/api.php",
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
    } catch (error) {
      console.error("API Error: ", error);
      setMessage({
        type: "error",
        text: "Server error. Please try again later.",
      });
    } finally {
      setLoading(false);
    }
  };

  // Address Management Handlers
  const handleOpenAddAddressModal = () => {
    setEditingAddressId(null);
    setAddressForm({ address: "", city: "", postalCode: "" });
    setShowAddressModal(true);
  };

  const handleOpenEditAddressModal = (addr) => {
    setEditingAddressId(addr.id);
    setAddressForm({
      address: addr.address,
      city: addr.city,
      postalCode: addr.postalCode,
    });
    setShowAddressModal(true);
  };

  const handleSaveAddress = (e) => {
    e.preventDefault();
    let updatedAddresses;

    if (editingAddressId !== null) {
      // Edit existing address
      updatedAddresses = addresses.map((item) =>
        item.id === editingAddressId ? { ...item, ...addressForm } : item,
      );
    } else {
      // Add new address (if first, make it default)
      const newAddr = {
        id: Date.now(),
        ...addressForm,
        isDefault: addresses.length === 0,
      };
      updatedAddresses = [...addresses, newAddr];
    }

    setAddresses(updatedAddresses);
    localStorage.setItem("userAddresses", JSON.stringify(updatedAddresses));
    setShowAddressModal(false);
  };

  const handleDeleteAddress = (id) => {
    const updatedAddresses = addresses.filter((item) => item.id !== id);
    // If deleted address was default and others exist, make the first one default
    if (
      updatedAddresses.length > 0 &&
      !updatedAddresses.some((i) => i.isDefault)
    ) {
      updatedAddresses[0].isDefault = true;
    }
    setAddresses(updatedAddresses);
    localStorage.setItem("userAddresses", JSON.stringify(updatedAddresses));
  };

  const handleSetDefaultAddress = (id) => {
    const updatedAddresses = addresses.map((item) => ({
      ...item,
      isDefault: item.id === id,
    }));
    setAddresses(updatedAddresses);
    localStorage.setItem("userAddresses", JSON.stringify(updatedAddresses));
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
        "http://localhost/GatewayLinen/GatewayLinenadmin-main/users/api.php",
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
    } catch (error) {
      console.error("Delete API Error: ", error);
      setMessage({
        type: "error",
        text: "Server error during account deletion.",
      });
      setShowDeleteModal(false);
    } finally {
      setDeleteLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#FAFAFA] py-12 px-4 sm:px-6 lg:px-8 font-sans relative">
      <div className="max-w-4xl mx-auto">
        <div className="mb-8">
          <h1 className="text-3xl font-serif font-bold text-[#031D44]">
            My Dashboard
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            Manage your profile, multiple shipping addresses, and buying
            history.
          </p>
        </div>

        <div className="bg-white rounded-3xl p-8 shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 mb-8">
          <div className="flex items-center gap-4 mb-8 pb-6 border-b border-gray-100">
            <div className="w-16 h-16 bg-[#031D44] text-[#B58E58] rounded-full flex items-center justify-center text-2xl font-bold shadow-md">
              {formData.fullName
                ? formData.fullName.charAt(0).toUpperCase()
                : "U"}
            </div>
            <div>
              <h2 className="text-xl font-bold text-gray-900">
                {formData.fullName || "User Profile"}
              </h2>
              <p className="text-sm text-gray-500 flex items-center gap-1.5 mt-0.5">
                <FiCheckCircle className="text-green-500" /> Verified Member
              </p>
            </div>
          </div>

          {message.text && (
            <div
              className={`mb-6 text-center text-xs font-bold p-3 rounded-lg ${message.type === "error" ? "bg-red-50 text-red-600 border border-red-100" : "bg-green-50 text-green-600 border border-green-100"}`}
            >
              {message.text}
            </div>
          )}

          <form onSubmit={handleUpdate} className="space-y-5">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-[11px] font-bold text-gray-700 uppercase tracking-widest mb-2">
                  Full Name
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <FiUser className="text-gray-400" size={15} />
                  </div>
                  <input
                    type="text"
                    name="fullName"
                    value={formData.fullName}
                    onChange={handleChange}
                    className="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl text-[14px] text-gray-800 focus:outline-none focus:ring-1 focus:ring-[#B58E58] focus:border-[#B58E58] bg-gray-50 focus:bg-white transition-all"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-[11px] font-bold text-gray-700 uppercase tracking-widest mb-2">
                  Email Address
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <FiMail className="text-gray-400" size={15} />
                  </div>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    className="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl text-[14px] text-gray-800 focus:outline-none focus:ring-1 focus:ring-[#B58E58] focus:border-[#B58E58] bg-gray-50 focus:bg-white transition-all"
                    required
                  />
                </div>
              </div>

              <div className="md:col-span-2">
                <label className="block text-[11px] font-bold text-gray-700 uppercase tracking-widest mb-2">
                  Phone Number
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <FiPhone className="text-gray-400" size={15} />
                  </div>
                  <input
                    type="tel"
                    name="phone"
                    value={formData.phone}
                    onChange={handleChange}
                    className="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl text-[14px] text-gray-800 focus:outline-none focus:ring-1 focus:ring-[#B58E58] focus:border-[#B58E58] bg-gray-50 focus:bg-white transition-all"
                  />
                </div>
              </div>
            </div>

            <div className="pt-6 flex justify-end">
              <button
                disabled={loading}
                type="submit"
                className={`flex items-center gap-2 py-3 px-8 rounded-xl shadow-md shadow-[#B58E58]/20 text-[14px] font-semibold text-white ${loading ? "bg-gray-400" : "bg-[#B58E58] hover:bg-[#9E7A4A]"} focus:outline-none transition-all`}
              >
                <FiSave size={16} />
                {loading ? "Saving..." : "Save Changes"}
              </button>
            </div>
          </form>
        </div>

        {/* SAVED ADDRESSES SECTION WITH ADD/EDIT/DELETE */}
        <div className="bg-white rounded-3xl p-8 shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 mb-8">
          <div className="flex items-center justify-between mb-6">
            <div className="flex items-center gap-2 text-[#031D44]">
              <FiMapPin size={20} className="text-[#B58E58]" />
              <h2 className="text-lg font-bold">Saved Shipping Addresses</h2>
            </div>
            <button
              onClick={handleOpenAddAddressModal}
              className="flex items-center gap-1.5 px-4 py-2 bg-[#031D44] text-white rounded-xl text-xs font-semibold hover:bg-[#B58E58] transition-all"
            >
              <FiPlus size={14} /> Add New Address
            </button>
          </div>

          {addresses.length === 0 ? (
            <p className="text-sm text-gray-500">
              No saved addresses found. Add one above!
            </p>
          ) : (
            <div className="space-y-4">
              {addresses.map((addr) => (
                <div
                  key={addr.id}
                  className="p-5 bg-gray-50 rounded-2xl border border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"
                >
                  <div>
                    <div className="flex items-center gap-3 mb-1">
                      <p className="text-sm font-semibold text-gray-800">
                        {addr.address}
                      </p>
                      {addr.isDefault && (
                        <span className="text-[10px] font-bold text-[#B58E58] bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200/50">
                          Default
                        </span>
                      )}
                    </div>
                    <p className="text-xs text-gray-500">
                      {addr.city} - {addr.postalCode}
                    </p>
                  </div>

                  <div className="flex items-center gap-2 self-end sm:self-center">
                    {!addr.isDefault && (
                      <button
                        onClick={() => handleSetDefaultAddress(addr.id)}
                        className="px-3 py-1.5 bg-white border border-gray-200 text-gray-700 rounded-lg text-xs font-semibold hover:bg-gray-100 transition-all"
                      >
                        Set Default
                      </button>
                    )}
                    <button
                      onClick={() => handleOpenEditAddressModal(addr)}
                      className="p-2 bg-white border border-gray-200 text-gray-600 rounded-lg hover:text-[#B58E58] transition-all"
                      title="Edit Address"
                    >
                      <FiEdit2 size={14} />
                    </button>
                    <button
                      onClick={() => handleDeleteAddress(addr.id)}
                      className="p-2 bg-white border border-gray-200 text-red-500 rounded-lg hover:bg-red-50 transition-all"
                      title="Delete Address"
                    >
                      <FiTrash2 size={14} />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* BUYING HISTORY SECTION */}
        <div className="bg-white rounded-3xl p-8 shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 mb-8">
          <div className="flex items-center gap-2 mb-6 text-[#031D44]">
            <FiShoppingBag size={20} className="text-[#B58E58]" />
            <h2 className="text-lg font-bold">Buying History (Orders)</h2>
          </div>

          {orders.length === 0 ? (
            <p className="text-sm text-gray-500">No past orders found.</p>
          ) : (
            <div className="space-y-4">
              {orders.map((order, idx) => (
                <div
                  key={idx}
                  className="p-4 rounded-2xl border border-gray-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
                >
                  <div>
                    <div className="flex items-center gap-3">
                      <span className="text-xs font-bold text-[#031D44]">
                        Order #{order.id}
                      </span>
                      <span className="text-[10px] bg-green-100 text-green-700 px-2.5 py-0.5 rounded-full font-semibold">
                        {order.status}
                      </span>
                    </div>
                    <p className="text-xs text-gray-500 mt-1">
                      Date: {order.date}
                    </p>
                    <p className="text-xs font-semibold text-gray-700 mt-2">
                      {order.items
                        .map(
                          (i) =>
                            `${i.name} (${i.selectedSize} x ${i.cartQuantity})`,
                        )
                        .join(", ")}
                    </p>
                  </div>
                  <div className="text-right">
                    <span className="text-sm font-bold text-gray-900">
                      CAD ${order.total}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* DANGER ZONE - DELETE ACCOUNT */}
        <div className="pt-2 border-t border-red-100">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-red-50/50 border border-red-100 p-6 rounded-2xl">
            <div>
              <h3 className="text-base font-bold text-red-700 flex items-center gap-2">
                <FiAlertTriangle size={18} /> Delete Account
              </h3>
              <p className="text-xs text-red-600/80 mt-1">
                Once you delete your account, there is no going back. All your
                data will be permanently removed from the database.
              </p>
            </div>
            <button
              onClick={() => setShowDeleteModal(true)}
              className="shrink-0 flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-all focus:outline-none"
            >
              <FiTrash2 size={14} />
              Permanently Delete
            </button>
          </div>
        </div>
      </div>

      {/* ADD / EDIT ADDRESS MODAL */}
      {showAddressModal && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-gray-100">
            <div className="flex justify-between items-center mb-5">
              <h3 className="text-xl font-serif font-bold text-[#031D44]">
                {editingAddressId !== null ? "Edit Address" : "Add New Address"}
              </h3>
              <button
                onClick={() => setShowAddressModal(false)}
                className="text-gray-400 hover:text-gray-600 p-1 rounded-full transition-colors"
              >
                <FiX size={20} />
              </button>
            </div>

            <form onSubmit={handleSaveAddress} className="space-y-4">
              <div>
                <label className="block text-[11px] font-bold text-gray-700 uppercase tracking-widest mb-1.5">
                  Street Address
                </label>
                <input
                  type="text"
                  required
                  value={addressForm.address}
                  onChange={(e) =>
                    setAddressForm({ ...addressForm, address: e.target.value })
                  }
                  placeholder="e.g. 123 Hospitality Lane"
                  className="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#B58E58]"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-[11px] font-bold text-gray-700 uppercase tracking-widest mb-1.5">
                    City
                  </label>
                  <input
                    type="text"
                    required
                    value={addressForm.city}
                    onChange={(e) =>
                      setAddressForm({ ...addressForm, city: e.target.value })
                    }
                    placeholder="e.g. Surat"
                    className="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#B58E58]"
                  />
                </div>
                <div>
                  <label className="block text-[11px] font-bold text-gray-700 uppercase tracking-widest mb-1.5">
                    Postal Code
                  </label>
                  <input
                    type="text"
                    required
                    value={addressForm.postalCode}
                    onChange={(e) =>
                      setAddressForm({
                        ...addressForm,
                        postalCode: e.target.value,
                      })
                    }
                    placeholder="e.g. 395006"
                    className="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#B58E58]"
                  />
                </div>
              </div>

              <div className="flex items-center gap-3 pt-4">
                <button
                  type="button"
                  onClick={() => setShowAddressModal(false)}
                  className="flex-1 py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition-all"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 py-3 px-4 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-semibold rounded-xl shadow-md transition-all"
                >
                  Save Address
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* CUSTOM MODERN DELETE CONFIRMATION MODAL */}
      {showDeleteModal && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-gray-100">
            <div className="flex justify-between items-center mb-5">
              <div className="w-12 h-12 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center">
                <FiTrash2 size={24} />
              </div>
              <button
                onClick={() => setShowDeleteModal(false)}
                className="text-gray-400 hover:text-gray-600 p-1 rounded-full transition-colors"
              >
                <FiX size={20} />
              </button>
            </div>

            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-2">
              Delete Account Permanently?
            </h3>
            <p className="text-sm text-gray-500 mb-6">
              Are you sure you want to delete your account? This action is
              irreversible and all your data will be erased from our database.
            </p>

            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={() => setShowDeleteModal(false)}
                className="flex-1 py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition-all"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleDeleteAccount}
                disabled={deleteLoading}
                className="flex-1 py-3 px-4 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-xl shadow-md shadow-red-600/20 transition-all flex items-center justify-center gap-1.5"
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
