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
        userId: parsedUser.userId,
        fullName: parsedUser.fullName || "",
        email: parsedUser.email || "",
        phone: parsedUser.phone || "",
      };
    }
    return { userId: "", fullName: "", email: "", phone: "" };
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
        // Browser alert hata kar seedha login page par redirect kar diya hai
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
            Manage your profile and account details.
          </p>
        </div>

        <div className="bg-white rounded-3xl p-8 shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100">
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

          {/* DANGER ZONE - DELETE ACCOUNT */}
          <div className="mt-12 pt-8 border-t border-red-100">
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
      </div>

      {/* CUSTOM MODERN DELETE CONFIRMATION MODAL */}
      {showDeleteModal && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-gray-100 animate-in fade-in zoom-in duration-200">
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
