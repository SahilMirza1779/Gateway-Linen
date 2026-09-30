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
  FiDownload,
  FiFileText,
  FiDollarSign,
  FiStar,
} from "react-icons/fi";

const Dashboard = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [message, setMessage] = useState({ type: "", text: "" });

  const getActiveUserId = () => {
    try {
      const storedUser = localStorage.getItem("user");
      if (storedUser) {
        const parsed = JSON.parse(storedUser);
        const id =
          parsed.UserId || parsed.userId || parsed.id || parsed.user_id;
        if (id) return Number(id);
      }
    } catch (err) {
      console.error(err);
    }
    return 11;
  };

  const [formData, setFormData] = useState(() => {
    const userData = localStorage.getItem("user");
    if (userData) {
      const parsedUser = JSON.parse(userData);
      return {
        userId: parsedUser.UserId || parsedUser.userId || parsedUser.id || 11,
        fullName:
          parsedUser.FullName || parsedUser.fullName || parsedUser.name || "",
        email: parsedUser.Email || parsedUser.email || "",
        phone: parsedUser.Phone || parsedUser.phone || "",
      };
    }
    return { userId: 11, fullName: "", email: "", phone: "" };
  });

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

  const [orders, setOrders] = useState([]);
  const [loadingOrders, setLoadingOrders] = useState(true);

  // Quotes State
  const [quotes, setQuotes] = useState([]);
  const [loadingQuotes, setLoadingQuotes] = useState(true);

  const [showModal, setShowModal] = useState(false);
  const [modalTitle, setModalTitle] = useState("");
  const [modalMessage, setModalMessage] = useState("");

  // PRODUCT REVIEW MODAL STATES & FUNCTIONS
  const [isReviewModalOpen, setIsReviewModalOpen] = useState(false);
  const [selectedProductToReview, setSelectedProductToReview] = useState(null);
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState("");
  const [reviewLoading, setReviewLoading] = useState(false);
  const [reviewMessage, setReviewMessage] = useState({ type: "", text: "" });

  const handleOpenReview = (item) => {
    const pId =
      item.productId || item.ProductId || item.id || item.product_id || 1;
    const pName = item.name || item.Name || item.product_name || "Product";

    setSelectedProductToReview({ id: Number(pId), name: pName });
    setRating(5);
    setComment("");
    setReviewMessage({ type: "", text: "" });
    setIsReviewModalOpen(true);
  };

  const handleSubmitReview = async (e) => {
    e.preventDefault();
    if (!comment.trim()) {
      setReviewMessage({
        type: "error",
        text: "Please write a review comment.",
      });
      return;
    }

    setReviewLoading(true);
    setReviewMessage({ type: "", text: "" });

    const userId = getActiveUserId();
    const productId = selectedProductToReview ? selectedProductToReview.id : 0;

    try {
      const response = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/reviews/api.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            product_id: Number(productId),
            user_id: Number(userId),
            rating: Number(rating),
            comment: comment.trim(),
          }),
        },
      );

      const result = await response.json();

      if (result.success) {
        setReviewMessage({ type: "success", text: result.message });
        setTimeout(() => {
          setIsReviewModalOpen(false);
        }, 2000);
      } else {
        setReviewMessage({
          type: "error",
          text: result.message || "Failed to submit review.",
        });
      }
    } catch (error) {
      console.error("Review Submit Error:", error);
      setReviewMessage({
        type: "error",
        text: "Server error. Please try again later.",
      });
    } finally {
      setReviewLoading(false);
    }
  };

  useEffect(() => {
    const loadDashboardData = async () => {
      const userId = getActiveUserId();

      // Fetch Addresses
      setLoadingAddresses(true);
      try {
        const addrResponse = await fetch(
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
        const addrResult = await addrResponse.json();
        if (addrResult.success) {
          setAddresses(addrResult.data || []);
        } else {
          setAddresses([]);
        }
      } catch (err) {
        console.error("Error fetching addresses:", err);
      } finally {
        setLoadingAddresses(false);
      }

      // Fetch Orders
      setLoadingOrders(true);
      try {
        const orderResponse = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/orders/user_orders_api.php",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-API-KEY": "GatewayLinen@2026",
            },
            body: JSON.stringify({
              action: "get_user_orders",
              userId: Number(userId),
            }),
          },
        );
        const orderResult = await orderResponse.json();
        if (orderResult.success) {
          setOrders(orderResult.data || []);
        } else {
          setOrders([]);
        }
      } catch (err) {
        console.error("Error fetching orders:", err);
      } finally {
        setLoadingOrders(false);
      }

      // Fetch Bulk Quotes
      setLoadingQuotes(true);
      try {
        const quoteResponse = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/quotes/user_quotes_api.php",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-API-KEY": "GatewayLinen@2026",
            },
            body: JSON.stringify({
              action: "get_user_quotes",
              userId: Number(userId),
            }),
          },
        );
        const quoteResult = await quoteResponse.json();
        if (quoteResult.success) {
          setQuotes(quoteResult.data || []);
        } else {
          setQuotes([]);
        }
      } catch (err) {
        console.error("Error fetching quotes:", err);
      } finally {
        setLoadingQuotes(false);
      }
    };

    loadDashboardData();
  }, []);

  const refreshData = async () => {
    const userId = getActiveUserId();
    try {
      const addrRes = await fetch(
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
      const addrData = await addrRes.json();
      if (addrData.success) setAddresses(addrData.data || []);

      const orderRes = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/orders/user_orders_api.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-API-KEY": "GatewayLinen@2026",
          },
          body: JSON.stringify({
            action: "get_user_orders",
            userId: Number(userId),
          }),
        },
      );
      const orderData = await orderRes.json();
      if (orderData.success) setOrders(orderData.data || []);

      const quoteRes = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/quotes/user_quotes_api.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-API-KEY": "GatewayLinen@2026",
          },
          body: JSON.stringify({
            action: "get_user_quotes",
            userId: Number(userId),
          }),
        },
      );
      const quoteData = await quoteRes.json();
      if (quoteData.success) setQuotes(quoteData.data || []);
    } catch (err) {
      console.error("Error refreshing data:", err);
    }
  };

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
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/users/api.php",
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
        userId: Number(userId),
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
        refreshData();
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
        refreshData();
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
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/users/api.php",
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

  const handleProceedToPayment = (quoteId) => {
    alert(`Proceeding to secure payment gateway for Quote ID: ${quoteId}`);
  };

  const handleDownloadInvoice = (order) => {
    const printWindow = window.open("", "_blank");
    if (!printWindow) {
      alert("Please allow pop-ups to download the invoice.");
      return;
    }

    const invoiceHtml = `
      <!DOCTYPE html>
      <html lang="en">
        <head>
          <meta charset="UTF-8">
          <title>Invoice_${order.id}</title>
          <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; padding: 40px; background: #fff; max-width: 800px; margin: 0 auto; }
            .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #031D44; padding-bottom: 20px; margin-bottom: 30px; }
            .logo { font-size: 28px; font-weight: bold; color: #031D44; letter-spacing: 2px; }
            .invoice-title { font-size: 24px; font-weight: bold; color: #B58E58; text-transform: uppercase; letter-spacing: 4px; }
            .details-container { display: flex; justify-content: space-between; margin-bottom: 40px; font-size: 14px; line-height: 1.6; }
            .bill-to strong { color: #031D44; font-size: 16px; display: inline-block; margin-bottom: 5px; }
            .order-info { text-align: right; }
            .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .table th { background: #031D44; color: white; padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
            .table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
            .totals { width: 100%; display: flex; justify-content: flex-end; }
            .totals-box { width: 320px; text-align: right; }
            .totals-box p { font-size: 14px; margin: 8px 0; color: #555; }
            .grand-total { font-size: 20px !important; font-weight: bold; color: #031D44 !important; border-top: 2px solid #031D44; padding-top: 10px; margin-top: 10px !important; }
            .footer { margin-top: 60px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 20px; }
          </style>
        </head>
        <body>
          <div class="header">
            <div class="logo">GATEWAY LINEN</div>
            <div class="invoice-title">Invoice</div>
          </div>
          
          <div class="details-container">
            <div class="bill-to">
              <strong>Billed To:</strong><br/>
              ${formData.fullName || "Customer"}<br/>
              ${formData.email || ""}<br/>
              ${formData.phone || ""}
            </div>
            <div class="order-info">
              <strong>Order #:</strong> ${order.id}<br/>
              <strong>Date:</strong> ${order.date}<br/>
              <strong>Status:</strong> ${order.status}<br/>
              <strong>Payment Method:</strong> <span style="color: #031D44; font-weight: bold;">${order.paymentMethod || "Credit / Debit Card"}</span>
            </div>
          </div>

          <table class="table">
            <thead>
              <tr>
                <th>Item Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              ${order.items
                .map(
                  (item) => `
                <tr>
                  <td>${item.name}</td>
                  <td>${item.cartQuantity}</td>
                  <td>CAD $${Number(item.price).toFixed(2)}</td>
                  <td>CAD $${(item.cartQuantity * item.price).toFixed(2)}</td>
                </tr>
              `,
                )
                .join("")}
            </tbody>
          </table>

          <div class="totals">
            <div class="totals-box">
              <p>Subtotal: CAD $${order.total}</p>
              <p class="grand-total">Total Paid: CAD $${order.total}</p>
            </div>
          </div>

          <div class="footer">
            Thank you for your business!<br/>
            Gateway Linen - Professional Hospitality Linen Supply
          </div>

          <script>
            window.onload = function() {
              setTimeout(() => {
                window.print();
                window.close();
              }, 500);
            }
          </script>
        </body>
      </html>
    `;
    printWindow.document.write(invoiceHtml);
    printWindow.document.close();
  };

  return (
    <div className="min-h-screen bg-white py-10 md:py-16 px-4 md:px-12 font-sans relative">
      <div className="max-w-[1100px] mx-auto">
        {/* Dashboard Header */}
        <div className="mb-8 md:mb-10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-[#E5DCD0] pb-6">
          <div>
            <div className="inline-flex items-center gap-2 bg-[#B58E58]/10 px-3 py-1 rounded-full mb-2 border border-[#B58E58]/20">
              <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
              <span className="text-[#B58E58] text-[9.5px] font-bold tracking-[0.2em] uppercase">
                Customer Portal
              </span>
            </div>
            <h1 className="text-3xl md:text-4xl font-serif font-bold text-[#031D44]">
              Account Dashboard
            </h1>
            <p className="text-xs text-gray-500 font-light mt-1">
              Manage your personal profile, delivery locations, bulk quotes, and
              past orders.
            </p>
          </div>
          <button
            onClick={handleLogout}
            className="inline-flex items-center gap-2 px-5 py-2.5 bg-white hover:bg-red-50 text-red-600 border border-[#E5DCD0] hover:border-red-200 text-xs font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer shadow-2xs"
          >
            <FiLogOut size={15} /> Secure Logout
          </button>
        </div>

        {/* Profile Details Card */}
        <div className="bg-[#FAF7F2] rounded-[24px] md:rounded-[32px] p-6 md:p-10 border border-[#E5DCD0] shadow-sm mb-8">
          <div className="flex items-center gap-4 mb-8 pb-6 border-b border-[#E5DCD0]">
            <div className="w-14 h-14 md:w-16 md:h-16 bg-[#031D44] text-[#B58E58] rounded-2xl flex items-center justify-center text-xl md:text-2xl font-bold shadow-md">
              {formData.fullName
                ? formData.fullName.charAt(0).toUpperCase()
                : "U"}
            </div>
            <div>
              <h2 className="text-xl font-serif font-bold text-[#031D44]">
                {formData.fullName || "User Profile"}
              </h2>
              <p className="text-xs text-[#4A5D4E] font-medium flex items-center gap-1.5 mt-1">
                <FiCheckCircle size={14} /> Verified Member Account
              </p>
            </div>
          </div>

          {message.text && (
            <div
              className={`mb-6 text-center text-xs font-bold p-3.5 rounded-xl border ${message.type === "error" ? "bg-red-50 text-red-600 border-red-200" : "bg-green-50 text-green-700 border-green-200"}`}
            >
              {message.text}
            </div>
          )}

          <form onSubmit={handleUpdate} className="space-y-5">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-2">
                  Full Name <span className="text-red-500">*</span>
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
                    className="block w-full pl-10 pr-4 py-3 border border-[#E5DCD0] rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#B58E58] bg-white transition-all shadow-2xs"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-2">
                  Email Address <span className="text-red-500">*</span>
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
                    className="block w-full pl-10 pr-4 py-3 border border-[#E5DCD0] rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#B58E58] bg-white transition-all shadow-2xs"
                    required
                  />
                </div>
              </div>

              <div className="md:col-span-2">
                <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-2">
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
                    className="block w-full pl-10 pr-4 py-3 border border-[#E5DCD0] rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#B58E58] bg-white transition-all shadow-2xs"
                  />
                </div>
              </div>
            </div>

            <div className="pt-2 flex justify-end">
              <button
                disabled={loading}
                type="submit"
                className={`w-full sm:w-auto flex items-center justify-center gap-2 py-3.5 px-8 rounded-xl shadow-md text-xs font-bold tracking-widest uppercase text-white ${loading ? "bg-gray-400" : "bg-[#031D44] hover:bg-[#B58E58]"} transition-all cursor-pointer`}
              >
                <FiSave size={15} />
                {loading ? "Saving Changes..." : "Save Profile Changes"}
              </button>
            </div>
          </form>
        </div>

        {/* SAVED SHIPPING ADDRESSES SECTION */}
        <div className="bg-[#FAF7F2] rounded-[24px] md:rounded-[32px] p-6 md:p-10 border border-[#E5DCD0] shadow-sm mb-8">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-[#E5DCD0]">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-white text-[#031D44] rounded-xl flex items-center justify-center shrink-0 border border-[#E5DCD0] shadow-2xs">
                <FiMapPin size={18} className="text-[#B58E58]" />
              </div>
              <div>
                <h2 className="text-lg font-serif font-bold text-[#031D44]">
                  Saved Shipping Addresses
                </h2>
                <p className="text-[11px] text-gray-500 font-light">
                  Manage your delivery locations for fast wholesale and retail
                  checkout.
                </p>
              </div>
            </div>
            <button
              onClick={handleOpenAddAddressModal}
              className="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-2.5 bg-[#4A5D4E] hover:bg-[#031D44] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer shadow-sm"
            >
              <FiPlus size={15} /> Add New Address
            </button>
          </div>

          {loadingAddresses ? (
            <div className="text-center py-10 text-xs text-gray-500 font-bold uppercase tracking-widest">
              Loading Addresses...
            </div>
          ) : addresses.length === 0 ? (
            <div className="text-center py-12 bg-white rounded-2xl border border-dashed border-[#E5DCD0]">
              <FiMapPin size={32} className="mx-auto text-gray-300 mb-2" />
              <p className="text-xs font-bold text-[#031D44] mb-1">
                No saved shipping addresses found
              </p>
              <p className="text-[11px] text-gray-500 font-light">
                Add your first delivery location using the button above.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {addresses.map((addr) => (
                <div
                  key={addr.AddressId}
                  className="p-5 bg-white rounded-2xl border border-[#E5DCD0] hover:border-[#B58E58] flex flex-col justify-between shadow-2xs transition-all group relative overflow-hidden"
                >
                  <div className="absolute top-0 left-0 w-1.5 h-full bg-[#B58E58] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                  <div>
                    <div className="flex justify-between items-start mb-2">
                      <span className="font-bold text-sm text-[#031D44] tracking-wide">
                        {addr.RecipientName}
                      </span>
                      <span className="text-[9px] bg-[#B58E58]/10 text-[#B58E58] border border-[#B58E58]/30 px-2.5 py-0.5 rounded-full uppercase font-bold tracking-wider">
                        {addr.AddressType || "Standard"}
                      </span>
                    </div>
                    <p className="text-xs text-gray-600 font-light mb-1.5 leading-relaxed">
                      {addr.AddressLine1}, {addr.City}, {addr.StateProvince}{" "}
                      {addr.PostalCode}
                    </p>
                    <p className="text-xs text-gray-500 font-light mb-4 flex items-center gap-1.5">
                      <FiPhone size={12} className="text-gray-400" />
                      <span className="font-medium text-gray-800">
                        {addr.Phone}
                      </span>
                    </p>
                  </div>

                  <div className="flex gap-2.5 pt-3 border-t border-gray-100">
                    <button
                      onClick={() => handleOpenEditAddressModal(addr)}
                      className="flex-1 py-2 bg-[#FAF7F2] hover:bg-[#031D44] hover:text-white text-[#031D44] text-[10px] font-bold tracking-wider uppercase rounded-xl border border-[#E5DCD0] hover:border-[#031D44] transition-all cursor-pointer flex items-center justify-center gap-1.5 shadow-2xs"
                    >
                      <FiEdit2 size={13} /> Edit
                    </button>
                    <button
                      onClick={() => handleDeleteAddress(addr.AddressId)}
                      className="py-2 px-4 bg-red-50 hover:bg-red-600 hover:text-white text-red-600 text-[10px] font-bold rounded-xl border border-red-200 hover:border-red-600 transition-all cursor-pointer flex items-center justify-center shadow-2xs"
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

        {/* BULK QUOTES SECTION */}
        <div className="bg-[#FAF7F2] rounded-[24px] md:rounded-[32px] p-6 md:p-10 border border-[#E5DCD0] shadow-sm mb-8">
          <div className="flex items-center gap-3 mb-6 pb-4 border-b border-[#E5DCD0] text-[#031D44]">
            <div className="w-10 h-10 bg-white text-[#031D44] rounded-xl flex items-center justify-center shrink-0 border border-[#E5DCD0] shadow-2xs">
              <FiFileText size={18} className="text-[#B58E58]" />
            </div>
            <div>
              <h2 className="text-lg font-serif font-bold text-[#031D44]">
                My Bulk Commercial Quotes
              </h2>
              <p className="text-[11px] text-gray-500 font-light">
                Track status and proceed to payment for your wholesale
                inquiries.
              </p>
            </div>
          </div>

          {loadingQuotes ? (
            <div className="text-center py-10 text-xs text-gray-500 font-bold uppercase tracking-widest">
              Loading Quotes...
            </div>
          ) : quotes.length === 0 ? (
            <div className="text-center py-12 bg-white rounded-2xl border border-dashed border-[#E5DCD0]">
              <p className="text-xs text-gray-500 font-light">
                You haven't submitted any bulk quotes yet.
              </p>
            </div>
          ) : (
            <div className="space-y-4">
              {quotes.map((quote, idx) => {
                const status = (quote.status || "Pending").toLowerCase();
                let statusColor =
                  "bg-yellow-100 text-yellow-800 border border-yellow-200";
                if (status === "approved")
                  statusColor =
                    "bg-green-100 text-green-800 border border-green-200";
                else if (status === "rejected")
                  statusColor = "bg-red-100 text-red-800 border border-red-200";
                else if (status === "converted")
                  statusColor =
                    "bg-blue-100 text-blue-800 border border-blue-200";

                return (
                  <div
                    key={idx}
                    className="p-5 bg-white rounded-2xl border border-[#E5DCD0] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shadow-2xs transition-all hover:border-[#B58E58]"
                  >
                    <div>
                      <div className="flex items-center gap-3 mb-1">
                        <span className="text-sm font-bold text-[#031D44]">
                          Quote #{quote.quoteNumber}
                        </span>
                        <span
                          className={`text-[9px] px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider ${statusColor}`}
                        >
                          {quote.status}
                        </span>
                      </div>
                      <p className="text-[11px] text-gray-500 font-light">
                        Submitted on: {quote.date}
                      </p>
                    </div>

                    <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full sm:w-auto">
                      <span className="text-base font-serif font-bold text-[#031D44]">
                        CAD ${Number(quote.totalAmount).toFixed(2)}
                      </span>

                      {status === "approved" && (
                        <button
                          onClick={() => handleProceedToPayment(quote.quoteId)}
                          className="flex items-center gap-1.5 px-5 py-2.5 bg-[#4A5D4E] hover:bg-[#031D44] text-white text-[10px] font-bold tracking-wider uppercase rounded-xl transition-all cursor-pointer shadow-md"
                        >
                          <FiDollarSign size={14} /> Proceed to Payment
                        </button>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {/* BUYING HISTORY SECTION (ORDERS) */}
        <div className="bg-[#FAF7F2] rounded-[24px] md:rounded-[32px] p-6 md:p-10 border border-[#E5DCD0] shadow-sm mb-8">
          <div className="flex items-center gap-3 mb-6 pb-4 border-b border-[#E5DCD0] text-[#031D44]">
            <div className="w-10 h-10 bg-white text-[#031D44] rounded-xl flex items-center justify-center shrink-0 border border-[#E5DCD0] shadow-2xs">
              <FiShoppingBag size={18} className="text-[#B58E58]" />
            </div>
            <div>
              <h2 className="text-lg font-serif font-bold text-[#031D44]">
                Buying History & Orders
              </h2>
              <p className="text-[11px] text-gray-500 font-light">
                Review past purchases, download invoices, and leave product
                reviews.
              </p>
            </div>
          </div>

          {loadingOrders ? (
            <div className="text-center py-10 text-xs text-gray-500 font-bold uppercase tracking-widest">
              Loading Orders...
            </div>
          ) : orders.length === 0 ? (
            <div className="text-center py-12 bg-white rounded-2xl border border-dashed border-[#E5DCD0]">
              <p className="text-xs text-gray-500 font-light">
                No past orders found in your account.
              </p>
            </div>
          ) : (
            <div className="space-y-5">
              {orders.map((order, idx) => (
                <div
                  key={idx}
                  className="p-5 bg-white rounded-2xl border border-[#E5DCD0] shadow-2xs transition-all hover:border-[#B58E58]"
                >
                  <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-[#E5DCD0] pb-4 mb-4">
                    <div>
                      <div className="flex items-center gap-3 mb-1">
                        <span className="text-sm font-bold text-[#031D44]">
                          Order #{order.id}
                        </span>
                        <span className="text-[9px] bg-green-100 text-green-800 border border-green-200 px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider">
                          {order.status}
                        </span>
                      </div>
                      <p className="text-[11px] text-gray-500 font-light">
                        Placed on: {order.date} | Payment Method:{" "}
                        <span className="font-semibold text-gray-700">
                          {order.paymentMethod || "Credit / Debit Card"}
                        </span>
                      </p>
                    </div>

                    <div className="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-start">
                      <span className="text-base font-serif font-bold text-[#031D44]">
                        CAD ${order.total}
                      </span>
                      <button
                        onClick={() => handleDownloadInvoice(order)}
                        className="flex items-center gap-1.5 px-4 py-2 bg-[#031D44] hover:bg-[#B58E58] text-white text-[10px] font-bold tracking-wider uppercase rounded-xl transition-all cursor-pointer shadow-sm"
                        title="Download Invoice PDF"
                      >
                        <FiDownload size={13} /> PDF Invoice
                      </button>
                    </div>
                  </div>

                  <div className="space-y-3">
                    {order.items && order.items.length > 0 ? (
                      order.items.map((item, i) => (
                        <div
                          key={i}
                          className="flex items-center gap-4 bg-[#FAF7F2] p-3 rounded-xl border border-[#E5DCD0]"
                        >
                          <div className="w-12 h-12 bg-white rounded-lg overflow-hidden border border-[#E5DCD0] flex items-center justify-center shrink-0">
                            {item.image ? (
                              <img
                                src={item.image}
                                alt={item.name}
                                className="w-full h-full object-cover"
                              />
                            ) : (
                              <FiShoppingBag
                                className="text-gray-400"
                                size={18}
                              />
                            )}
                          </div>
                          <div className="flex-1 min-w-0">
                            <p className="text-xs font-bold text-[#031D44] truncate">
                              {item.name}
                            </p>
                            <p className="text-[10px] text-gray-500 font-light mt-0.5">
                              Qty: {item.cartQuantity} × CAD $
                              {Number(item.price).toFixed(2)}
                            </p>

                            <button
                              onClick={() => handleOpenReview(item)}
                              className="mt-1 text-[10px] text-[#B58E58] hover:text-[#031D44] underline font-bold transition-colors cursor-pointer inline-block"
                            >
                              Write a Review
                            </button>
                          </div>
                          <div className="text-xs font-bold text-[#031D44]">
                            CAD ${(item.cartQuantity * item.price).toFixed(2)}
                          </div>
                        </div>
                      ))
                    ) : (
                      <p className="text-[11px] text-gray-500 italic py-2">
                        No items listed in this order
                      </p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* DANGER ZONE - DELETE ACCOUNT */}
        <div className="pt-2">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-red-50 border border-red-200 p-6 rounded-[24px] shadow-sm">
            <div>
              <h3 className="text-sm font-bold text-red-700 flex items-center gap-2">
                <FiAlertTriangle size={18} /> Delete Account Permanently
              </h3>
              <p className="text-xs text-red-600/80 font-light mt-1">
                Once you delete your account, all your profile data and saved
                records will be permanently erased.
              </p>
            </div>
            <button
              onClick={() => setShowDeleteModal(true)}
              className="w-full sm:w-auto shrink-0 flex items-center justify-center gap-2 px-5 py-3 bg-red-600 hover:bg-red-700 text-white text-xs font-bold tracking-wider uppercase rounded-xl shadow-md transition-all cursor-pointer"
            >
              <FiTrash2 size={14} />
              Delete Account
            </button>
          </div>
        </div>
      </div>

      {/* --- WRITE A REVIEW MODAL --- */}
      {isReviewModalOpen && selectedProductToReview && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-[#FAF7F2] border border-[#E5DCD0] rounded-[24px] max-w-md w-full p-8 shadow-2xl relative animate-in zoom-in-95">
            <button
              onClick={() => setIsReviewModalOpen(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-[#031D44] bg-white p-2 rounded-full transition-colors cursor-pointer border border-[#E5DCD0] shadow-sm"
            >
              <FiX size={16} />
            </button>

            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-1">
              Write a Product Review
            </h3>
            <p className="text-xs text-gray-500 mb-6 pb-4 border-b border-[#E5DCD0]">
              Product:{" "}
              <span className="font-bold text-[#B58E58]">
                {selectedProductToReview.name}
              </span>
            </p>

            {reviewMessage.text && (
              <div
                className={`mb-5 p-3.5 rounded-xl text-xs flex items-center gap-2 font-bold ${reviewMessage.type === "success" ? "bg-green-50 text-green-700 border border-green-200" : "bg-red-50 text-red-700 border border-red-200"}`}
              >
                {reviewMessage.type === "success" ? (
                  <FiCheckCircle size={16} />
                ) : (
                  <FiAlertTriangle size={16} />
                )}
                <span>{reviewMessage.text}</span>
              </div>
            )}

            <form onSubmit={handleSubmitReview} className="space-y-5">
              <div>
                <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-2.5">
                  Your Rating <span className="text-red-500">*</span>
                </label>
                <div className="flex gap-1.5">
                  {[1, 2, 3, 4, 5].map((star) => (
                    <button
                      key={star}
                      type="button"
                      onClick={() => setRating(star)}
                      className="focus:outline-none transition-transform hover:scale-110 cursor-pointer"
                    >
                      <FiStar
                        size={28}
                        className={`${star <= rating ? "fill-yellow-400 text-yellow-400" : "text-gray-300"} transition-colors`}
                      />
                    </button>
                  ))}
                </div>
              </div>

              <div>
                <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-2">
                  Your Feedback <span className="text-red-500">*</span>
                </label>
                <textarea
                  required
                  rows="4"
                  value={comment}
                  onChange={(e) => setComment(e.target.value)}
                  className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#B58E58] resize-none shadow-2xs"
                  placeholder="Tell us about the fabric quality, comfort, and durability..."
                ></textarea>
              </div>

              <div className="flex gap-3 pt-2">
                <button
                  type="button"
                  onClick={() => setIsReviewModalOpen(false)}
                  className="w-1/2 py-3 bg-white border border-[#E5DCD0] hover:bg-gray-50 text-gray-700 text-[11px] font-bold tracking-widest uppercase rounded-xl transition-all cursor-pointer shadow-sm"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={reviewLoading}
                  className="w-1/2 py-3 bg-[#031D44] hover:bg-[#B58E58] text-white text-[11px] font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer flex items-center justify-center gap-2"
                >
                  {reviewLoading ? "Submitting..." : "Submit Review"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ADD / EDIT ADDRESS MODAL */}
      {showAddressModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-[#FAF7F2] rounded-[24px] max-w-lg w-full p-6 md:p-8 shadow-2xl border border-[#E5DCD0] relative">
            <div className="flex justify-between items-center mb-5 pb-4 border-b border-[#E5DCD0]">
              <h3 className="text-lg md:text-xl font-serif font-bold text-[#031D44]">
                {isEditing
                  ? "Edit Shipping Address"
                  : "Add New Shipping Address"}
              </h3>
              <button
                onClick={() => setShowAddressModal(false)}
                className="text-gray-400 hover:text-[#031D44] p-1.5 rounded-full transition-colors cursor-pointer bg-white border border-[#E5DCD0]"
              >
                <FiX size={18} />
              </button>
            </div>

            <form onSubmit={handleSaveAddress} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                    Recipient Name <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    name="recipientName"
                    value={addressForm.recipientName}
                    onChange={handleFormChange}
                    placeholder="Full Name"
                    className="w-full px-4 py-3 border border-[#E5DCD0] rounded-xl text-xs bg-white focus:outline-none focus:border-[#B58E58] shadow-2xs"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                    Phone Number <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    name="phone"
                    value={addressForm.phone}
                    onChange={handleFormChange}
                    placeholder="+1 234 567 8900"
                    className="w-full px-4 py-3 border border-[#E5DCD0] rounded-xl text-xs bg-white focus:outline-none focus:border-[#B58E58] shadow-2xs"
                  />
                </div>
              </div>

              <div>
                <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                  Street Address <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  required
                  name="addressLine1"
                  value={addressForm.addressLine1}
                  onChange={handleFormChange}
                  placeholder="House / Flat No, Street, Landmark"
                  className="w-full px-4 py-3 border border-[#E5DCD0] rounded-xl text-xs bg-white focus:outline-none focus:border-[#B58E58] shadow-2xs"
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                    City <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    name="city"
                    value={addressForm.city}
                    onChange={handleFormChange}
                    placeholder="City"
                    className="w-full px-4 py-3 border border-[#E5DCD0] rounded-xl text-xs bg-white focus:outline-none focus:border-[#B58E58] shadow-2xs"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                    State/Province <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    name="stateProvince"
                    value={addressForm.stateProvince}
                    onChange={handleFormChange}
                    placeholder="State"
                    className="w-full px-4 py-3 border border-[#E5DCD0] rounded-xl text-xs bg-white focus:outline-none focus:border-[#B58E58] shadow-2xs"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                    Postal Code <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    name="postalCode"
                    value={addressForm.postalCode}
                    onChange={handleFormChange}
                    placeholder="Postal Code"
                    className="w-full px-4 py-3 border border-[#E5DCD0] rounded-xl text-xs bg-white uppercase focus:outline-none focus:border-[#B58E58] shadow-2xs"
                  />
                </div>
              </div>

              <div className="flex gap-3 pt-3">
                <button
                  type="button"
                  onClick={() => setShowAddressModal(false)}
                  className="w-1/2 py-3 bg-white border border-[#E5DCD0] hover:bg-gray-50 text-gray-700 text-[11px] font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer shadow-sm"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="w-1/2 py-3 bg-[#031D44] hover:bg-[#B58E58] text-white text-[11px] font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
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
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-[#FAF7F2] rounded-[24px] max-w-sm w-full p-8 shadow-2xl border border-[#E5DCD0] text-center">
            <div className="w-14 h-14 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-inner">
              <FiCheckCircle size={28} />
            </div>
            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-2">
              {modalTitle}
            </h3>
            <p className="text-xs text-gray-600 font-light mb-6 leading-relaxed">
              {modalMessage}
            </p>
            <button
              onClick={() => setShowModal(false)}
              className="w-full py-3.5 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
            >
              OK, Got It
            </button>
          </div>
        </div>
      )}

      {/* DELETE ACCOUNT CONFIRMATION MODAL */}
      {showDeleteModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-[#FAF7F2] rounded-[24px] max-w-md w-full p-8 shadow-2xl border border-[#E5DCD0]">
            <div className="flex justify-between items-center mb-5">
              <div className="w-12 h-12 bg-red-100 text-red-600 rounded-xl flex items-center justify-center shadow-sm">
                <FiTrash2 size={20} />
              </div>
              <button
                onClick={() => setShowDeleteModal(false)}
                className="text-gray-400 hover:text-gray-800 p-1.5 rounded-full transition-colors cursor-pointer bg-white border border-[#E5DCD0]"
              >
                <FiX size={18} />
              </button>
            </div>

            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-2">
              Delete Account Permanently?
            </h3>
            <p className="text-xs text-gray-600 font-light mb-6 leading-relaxed">
              Are you sure you want to delete your account? This action is
              irreversible and all your data and records will be erased.
            </p>

            <div className="flex gap-3">
              <button
                type="button"
                onClick={() => setShowDeleteModal(false)}
                className="w-1/2 py-3 bg-white border border-[#E5DCD0] hover:bg-gray-50 text-gray-700 text-[11px] font-bold uppercase tracking-widest rounded-xl transition-all cursor-pointer shadow-sm"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleDeleteAccount}
                disabled={deleteLoading}
                className="w-1/2 py-3 bg-red-600 hover:bg-red-700 text-white text-[11px] font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer flex items-center justify-center"
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
