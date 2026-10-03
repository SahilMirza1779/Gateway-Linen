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
  FiPackage,
  FiShield,
  FiChevronRight,
  FiHeart,
  FiShoppingCart,
  FiTrendingUp,
  FiAward,
  FiClock,
  FiPercent,
  FiBell,
} from "react-icons/fi";

// Product images pool
const PRODUCT_IMAGES = {
  "Bath Mats":
    "https://images.pexels.com/photos/6585757/pexels-photo-6585757.jpeg?auto=compress&cs=tinysrgb&w=400",
  "Bath Towels":
    "https://images.pexels.com/photos/5591664/pexels-photo-5591664.jpeg?auto=compress&cs=tinysrgb&w=400",
  "Bed Sheets":
    "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=400",
  Default:
    "https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?w=400&q=80",
};

const RECOMMENDED = [
  {
    id: 1,
    name: "Luxury Hotel White Bath Towel",
    category: "Bath Towels",
    price: 24.99,
    originalPrice: 39.99,
    rating: 4.8,
    reviews: 234,
    image: PRODUCT_IMAGES["Bath Towels"],
    badge: "Best Seller",
  },
  {
    id: 2,
    name: "Premium Cotton Bed Sheet Set - Queen",
    category: "Bed Sheets",
    price: 89.99,
    originalPrice: 129.99,
    rating: 4.9,
    reviews: 512,
    image: PRODUCT_IMAGES["Bed Sheets"],
    badge: "Top Rated",
  },
  {
    id: 3,
    name: "Soft Cotton Face Washer Towels",
    category: "Bath Towels",
    price: 19.99,
    originalPrice: 29.99,
    rating: 4.7,
    reviews: 189,
    image: PRODUCT_IMAGES["Bath Towels"],
    badge: "Deal",
  },
  {
    id: 4,
    name: "Non-Slip Round Bath Mat - Charcoal Grey",
    category: "Bath Mats",
    price: 34.99,
    originalPrice: 49.99,
    rating: 4.6,
    reviews: 145,
    image: PRODUCT_IMAGES["Bath Mats"],
    badge: "New",
  },
];

const Dashboard = () => {
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState("profile");
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
  const [quotes, setQuotes] = useState([]);
  const [loadingQuotes, setLoadingQuotes] = useState(true);

  const [showModal, setShowModal] = useState(false);
  const [modalTitle, setModalTitle] = useState("");
  const [modalMessage, setModalMessage] = useState("");

  const [isReviewModalOpen, setIsReviewModalOpen] = useState(false);
  const [selectedProductToReview, setSelectedProductToReview] = useState(null);
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState("");
  const [reviewLoading, setReviewLoading] = useState(false);
  const [reviewMessage, setReviewMessage] = useState({ type: "", text: "" });

  const getProductImage = (name) => {
    if (!name) return PRODUCT_IMAGES["Default"];
    const lower = name.toLowerCase();
    if (lower.includes("bath mat")) return PRODUCT_IMAGES["Bath Mats"];
    if (lower.includes("towel")) return PRODUCT_IMAGES["Bath Towels"];
    if (lower.includes("bed") || lower.includes("sheet"))
      return PRODUCT_IMAGES["Bed Sheets"];
    return PRODUCT_IMAGES["Default"];
  };

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
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            product_id: Number(productId),
            user_id: Number(userId),
            rating: Number(rating),
            comment: comment.trim(),
          }),
        }
      );
      const result = await response.json();
      if (result.success) {
        setReviewMessage({ type: "success", text: result.message });
        setTimeout(() => setIsReviewModalOpen(false), 2000);
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
          }
        );
        const addrResult = await addrResponse.json();
        if (addrResult.success) setAddresses(addrResult.data || []);
        else setAddresses([]);
      } catch (err) {
        console.error("Error fetching addresses:", err);
      } finally {
        setLoadingAddresses(false);
      }

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
          }
        );
        const orderResult = await orderResponse.json();
        if (orderResult.success) setOrders(orderResult.data || []);
        else setOrders([]);
      } catch (err) {
        console.error("Error fetching orders:", err);
      } finally {
        setLoadingOrders(false);
      }

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
          }
        );
        const quoteResult = await quoteResponse.json();
        if (quoteResult.success) setQuotes(quoteResult.data || []);
        else setQuotes([]);
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
        }
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
        }
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
        }
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
        }
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
        }
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
        }
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
        }
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
      <html>
        <head>
          <title>Invoice_${order.id}</title>
          <style>
            body { font-family: Arial, sans-serif; color: #333; padding: 40px; max-width: 800px; margin: 0 auto; }
            .header { display: flex; justify-content: space-between; border-bottom: 2px solid #2874F0; padding-bottom: 20px; margin-bottom: 30px; }
            .logo { font-size: 28px; font-weight: bold; color: #2874F0; }
            .invoice-title { font-size: 24px; font-weight: bold; color: #FB641B; text-transform: uppercase; }
            .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .table th { background: #2874F0; color: white; padding: 12px; text-align: left; }
            .table td { padding: 12px; border-bottom: 1px solid #eee; }
            .grand-total { font-size: 20px; font-weight: bold; color: #2874F0; }
            .footer { margin-top: 60px; text-align: center; color: #888; border-top: 1px solid #eee; padding-top: 20px; }
          </style>
        </head>
        <body>
          <div class="header">
            <div class="logo">GATEWAY LINEN</div>
            <div class="invoice-title">Invoice</div>
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:30px;">
            <div><strong>Billed To:</strong><br/>${formData.fullName}<br/>${formData.email}<br/>${formData.phone}</div>
            <div style="text-align:right;"><strong>Order #:</strong> ${order.id}<br/><strong>Date:</strong> ${order.date}<br/><strong>Status:</strong> ${order.status}</div>
          </div>
          <table class="table">
            <thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
            <tbody>
              ${order.items.map(item => `<tr><td>${item.name}</td><td>${item.cartQuantity}</td><td>CAD $${Number(item.price).toFixed(2)}</td><td>CAD $${(item.cartQuantity * item.price).toFixed(2)}</td></tr>`).join("")}
            </tbody>
          </table>
          <div style="text-align:right;"><p class="grand-total">Total: CAD $${order.total}</p></div>
          <div class="footer">Thank you for your business!<br/>Gateway Linen - Professional Hospitality Linen Supply</div>
          <script>window.onload = () => { setTimeout(() => { window.print(); window.close(); }, 500); }</script>
        </body>
      </html>
    `;
    printWindow.document.write(invoiceHtml);
    printWindow.document.close();
  };

  // Stats
  const totalSpent = orders.reduce(
    (sum, o) => sum + (Number(o.total) || 0),
    0
  );
  const pendingQuotes = quotes.filter(
    (q) => (q.status || "").toLowerCase() === "pending"
  ).length;

  // Recently purchased
  const recentlyPurchased =
    orders.length > 0
      ? orders
          .flatMap((o) => o.items || [])
          .slice(0, 4)
          .map((item, idx) => ({
            ...item,
            image: item.image || getProductImage(item.name),
            id: item.id || item.productId || idx,
          }))
      : [];

  const tabs = [
    { id: "profile", label: "Profile", icon: FiUser },
    { id: "addresses", label: "Addresses", icon: FiMapPin },
    { id: "orders", label: "Orders", icon: FiPackage },
    { id: "quotes", label: "Bulk Quotes", icon: FiFileText },
  ];

  return (
    <div className="min-h-screen bg-[#F1F3F6] font-sans pb-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-6 md:py-8">
        {/* ============ HERO BANNER ============ */}
        <div className="bg-gradient-to-r from-[#2874F0] via-[#1e5bc7] to-[#0d47a1] rounded-xl shadow-md mb-6 overflow-hidden relative">
          <div
            className="absolute inset-0 opacity-[0.08]"
            style={{
              backgroundImage:
                "radial-gradient(circle, white 1px, transparent 1px)",
              backgroundSize: "20px 20px",
            }}
          />
          <div className="absolute -top-20 -right-20 w-80 h-80 rounded-full bg-[#FFE500]/10 blur-3xl" />

          <div className="px-5 md:px-8 py-6 md:py-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 relative">
            <div className="flex items-center gap-4">
              <div className="relative">
                <div className="w-16 h-16 md:w-20 md:h-20 bg-white text-[#2874F0] rounded-full flex items-center justify-center text-2xl md:text-3xl font-bold shadow-lg border-4 border-white/20">
                  {formData.fullName
                    ? formData.fullName.charAt(0).toUpperCase()
                    : "U"}
                </div>
                <div className="absolute -bottom-1 -right-1 w-6 h-6 bg-[#FFE500] rounded-full flex items-center justify-center border-2 border-white">
                  <FiAward size={11} className="text-[#031D44]" />
                </div>
              </div>
              <div>
                <div className="inline-flex items-center gap-1.5 bg-white/15 backdrop-blur border border-white/20 px-2.5 py-0.5 rounded-full mb-1.5">
                  <FiShield size={10} className="text-[#FFE500]" />
                  <span className="text-[9px] font-bold tracking-widest text-white uppercase">
                    Gold Member
                  </span>
                </div>
                <h1 className="text-xl md:text-2xl font-bold text-white">
                  Hello, {formData.fullName?.split(" ")[0] || "User"}!
                </h1>
                <p className="text-[12px] text-white/80 mt-0.5">
                  {formData.email}
                </p>
              </div>
            </div>
            <button
              onClick={handleLogout}
              className="inline-flex items-center gap-2 px-4 py-2.5 bg-white/15 hover:bg-white/25 backdrop-blur border border-white/20 text-white text-[11px] font-bold uppercase tracking-wider rounded transition-all"
            >
              <FiLogOut size={14} /> Logout
            </button>
          </div>
        </div>

        {/* ============ STATS OVERVIEW ============ */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6">
          {[
            {
              label: "Total Orders",
              value: orders.length,
              icon: FiPackage,
              color: "#2874F0",
              bg: "#EAF2FF",
            },
            {
              label: "Total Spent",
              value: `$${totalSpent.toFixed(0)}`,
              icon: FiDollarSign,
              color: "#FB641B",
              bg: "#FFF1E8",
            },
            {
              label: "Addresses",
              value: addresses.length,
              icon: FiMapPin,
              color: "#FF9F00",
              bg: "#FFF8E6",
            },
            {
              label: "Pending Quotes",
              value: pendingQuotes,
              icon: FiFileText,
              color: "#10B981",
              bg: "#ECFDF5",
            },
          ].map((stat, i) => (
            <div
              key={i}
              className="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition-all cursor-pointer"
              onClick={() => {
                if (stat.label === "Total Orders") setActiveTab("orders");
                else if (stat.label === "Addresses") setActiveTab("addresses");
                else if (stat.label === "Pending Quotes")
                  setActiveTab("quotes");
              }}
            >
              <div className="flex items-center gap-3">
                <div
                  className="w-11 h-11 rounded-full flex items-center justify-center shrink-0"
                  style={{ backgroundColor: stat.bg }}
                >
                  <stat.icon size={18} style={{ color: stat.color }} />
                </div>
                <div className="min-w-0">
                  <p className="text-[10px] font-bold text-gray-500 uppercase tracking-wider">
                    {stat.label}
                  </p>
                  <p className="text-[20px] font-bold text-gray-800">
                    {stat.value}
                  </p>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* ============ NOTIFICATION BANNER ============ */}
        {pendingQuotes > 0 && (
          <div className="bg-gradient-to-r from-[#FFF8E6] to-[#FFF1E8] border border-[#FF9F00]/30 rounded-lg p-4 mb-6 flex items-center gap-3">
            <div className="w-10 h-10 bg-[#FF9F00] rounded-full flex items-center justify-center shrink-0">
              <FiBell size={18} className="text-white" />
            </div>
            <div className="flex-1">
              <p className="text-[13px] font-bold text-gray-800">
                You have {pendingQuotes} pending bulk{" "}
                {pendingQuotes === 1 ? "quote" : "quotes"}
              </p>
              <p className="text-[11px] text-gray-600">
                Our team is reviewing your request — you'll hear back within
                24hrs
              </p>
            </div>
            <button
              onClick={() => setActiveTab("quotes")}
              className="text-[11px] font-bold uppercase tracking-wider text-[#FB641B] hover:text-[#031D44] transition-colors flex items-center gap-1 shrink-0"
            >
              View <FiChevronRight size={12} />
            </button>
          </div>
        )}

        {/* ============ TABS NAVIGATION ============ */}
        <div className="bg-white rounded-lg border border-gray-200 shadow-sm mb-6 overflow-hidden">
          <div className="flex overflow-x-auto scrollbar-hide">
            {tabs.map((tab) => {
              const isActive = activeTab === tab.id;
              return (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`flex-1 min-w-[140px] px-5 py-4 flex items-center justify-center gap-2 text-[12px] font-bold uppercase tracking-wider transition-all border-b-3 ${
                    isActive
                      ? "text-[#2874F0] border-[#2874F0] bg-[#EAF2FF]"
                      : "text-gray-600 border-transparent hover:bg-[#F1F3F6]"
                  }`}
                  style={{ borderBottomWidth: isActive ? "3px" : "3px" }}
                >
                  <tab.icon size={14} />
                  {tab.label}
                </button>
              );
            })}
          </div>
        </div>

        {/* ============ TAB CONTENT ============ */}

        {/* PROFILE TAB */}
        {activeTab === "profile" && (
          <div className="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden mb-6">
            <div className="bg-[#F1F3F6] px-5 md:px-6 py-4 border-b border-gray-200 flex items-center gap-3">
              <div className="w-9 h-9 bg-[#EAF2FF] text-[#2874F0] rounded-full flex items-center justify-center">
                <FiUser size={16} />
              </div>
              <div>
                <h2 className="text-[15px] font-bold text-gray-800">
                  Personal Information
                </h2>
                <p className="text-[11px] text-gray-500">
                  Update your profile details
                </p>
              </div>
            </div>
            <div className="p-5 md:p-6">
              {message.text && (
                <div
                  className={`mb-4 p-3 rounded text-[12px] font-medium border-l-4 flex items-center gap-2 ${
                    message.type === "error"
                      ? "bg-red-50 border-red-500 text-red-700"
                      : "bg-green-50 border-green-500 text-green-700"
                  }`}
                >
                  {message.type === "error" ? (
                    <FiAlertTriangle size={14} />
                  ) : (
                    <FiCheckCircle size={14} />
                  )}
                  {message.text}
                </div>
              )}
              <form onSubmit={handleUpdate} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                      Full Name *
                    </label>
                    <div className="relative group">
                      <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <FiUser
                          className="text-gray-400 group-focus-within:text-[#2874F0] transition-colors"
                          size={14}
                        />
                      </div>
                      <input
                        type="text"
                        name="fullName"
                        value={formData.fullName}
                        onChange={handleChange}
                        className="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded text-[13px] focus:outline-none focus:border-[#2874F0] focus:ring-2 focus:ring-[#2874F0]/10 transition-all"
                        required
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                      Email *
                    </label>
                    <div className="relative group">
                      <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <FiMail
                          className="text-gray-400 group-focus-within:text-[#2874F0] transition-colors"
                          size={14}
                        />
                      </div>
                      <input
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        className="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded text-[13px] focus:outline-none focus:border-[#2874F0] focus:ring-2 focus:ring-[#2874F0]/10 transition-all"
                        required
                      />
                    </div>
                  </div>
                  <div className="md:col-span-2">
                    <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                      Phone
                    </label>
                    <div className="relative group">
                      <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <FiPhone
                          className="text-gray-400 group-focus-within:text-[#2874F0] transition-colors"
                          size={14}
                        />
                      </div>
                      <input
                        type="tel"
                        name="phone"
                        value={formData.phone}
                        onChange={handleChange}
                        className="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded text-[13px] focus:outline-none focus:border-[#2874F0] focus:ring-2 focus:ring-[#2874F0]/10 transition-all"
                      />
                    </div>
                  </div>
                </div>
                <div className="flex justify-end">
                  <button
                    disabled={loading}
                    type="submit"
                    className="w-full sm:w-auto px-6 py-3 rounded text-[11px] font-bold tracking-wider uppercase text-white transition-all cursor-pointer shadow-sm bg-[#FB641B] hover:bg-[#e55a15] hover:shadow-md disabled:bg-gray-400 flex items-center justify-center gap-2"
                  >
                    <FiSave size={14} />
                    {loading ? "Saving..." : "Save Changes"}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* ADDRESSES TAB */}
        {activeTab === "addresses" && (
          <div className="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden mb-6">
            <div className="bg-[#F1F3F6] px-5 md:px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 bg-[#FFF1E8] text-[#FB641B] rounded-full flex items-center justify-center shrink-0">
                  <FiMapPin size={16} />
                </div>
                <div>
                  <h2 className="text-[15px] font-bold text-gray-800">
                    Saved Addresses
                  </h2>
                  <p className="text-[11px] text-gray-500">
                    Manage delivery locations
                  </p>
                </div>
              </div>
              <button
                onClick={handleOpenAddAddressModal}
                className="w-full sm:w-auto px-4 py-2.5 bg-[#FB641B] hover:bg-[#e55a15] text-white rounded text-[11px] font-bold uppercase tracking-wider transition-all cursor-pointer shadow-sm flex items-center justify-center gap-2"
              >
                <FiPlus size={14} /> Add Address
              </button>
            </div>
            <div className="p-5 md:p-6">
              {loadingAddresses ? (
                <div className="text-center py-10 text-[12px] text-gray-500 font-medium">
                  Loading...
                </div>
              ) : addresses.length === 0 ? (
                <div className="text-center py-12 bg-[#F1F3F6] rounded border-2 border-dashed border-gray-300">
                  <FiMapPin size={32} className="mx-auto text-gray-300 mb-2" />
                  <p className="text-[13px] font-bold text-gray-700 mb-1">
                    No addresses yet
                  </p>
                  <p className="text-[11px] text-gray-500 mb-4">
                    Add your first delivery address to continue
                  </p>
                  <button
                    onClick={handleOpenAddAddressModal}
                    className="px-5 py-2.5 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[11px] font-bold uppercase tracking-wider rounded transition-all"
                  >
                    Add Address
                  </button>
                </div>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {addresses.map((addr) => (
                    <div
                      key={addr.AddressId}
                      className="p-4 bg-white rounded border border-gray-200 hover:border-[#2874F0] hover:shadow-md transition-all"
                    >
                      <div className="flex justify-between items-start mb-2">
                        <span className="font-bold text-[14px] text-gray-800">
                          {addr.RecipientName}
                        </span>
                        <span className="text-[9px] bg-[#EAF2FF] text-[#2874F0] border border-[#2874F0]/20 px-2 py-0.5 rounded-full uppercase font-bold tracking-wider">
                          {addr.AddressType || "Standard"}
                        </span>
                      </div>
                      <p className="text-[12px] text-gray-600 mb-2 leading-relaxed">
                        {addr.AddressLine1}, {addr.City}, {addr.StateProvince}{" "}
                        {addr.PostalCode}
                      </p>
                      <p className="text-[11px] text-gray-500 mb-3 flex items-center gap-1.5">
                        <FiPhone size={11} className="text-[#FB641B]" />
                        <span className="font-medium">{addr.Phone}</span>
                      </p>
                      <div className="flex gap-2 pt-3 border-t border-gray-100">
                        <button
                          onClick={() => handleOpenEditAddressModal(addr)}
                          className="flex-1 py-2 bg-white hover:bg-[#EAF2FF] text-[#2874F0] text-[10px] font-bold tracking-wider uppercase rounded border border-[#2874F0] transition-all cursor-pointer flex items-center justify-center gap-1.5"
                        >
                          <FiEdit2 size={12} /> Edit
                        </button>
                        <button
                          onClick={() => handleDeleteAddress(addr.AddressId)}
                          className="py-2 px-3 bg-red-50 hover:bg-red-500 hover:text-white text-red-600 text-[10px] font-bold rounded border border-red-200 transition-all cursor-pointer"
                        >
                          <FiTrash2 size={13} />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}

        {/* ORDERS TAB */}
        {activeTab === "orders" && (
          <div className="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden mb-6">
            <div className="bg-[#F1F3F6] px-5 md:px-6 py-4 border-b border-gray-200 flex items-center gap-3">
              <div className="w-9 h-9 bg-[#ECFDF5] text-[#10B981] rounded-full flex items-center justify-center">
                <FiPackage size={16} />
              </div>
              <div>
                <h2 className="text-[15px] font-bold text-gray-800">
                  Order History
                </h2>
                <p className="text-[11px] text-gray-500">
                  Track, download invoices & review
                </p>
              </div>
            </div>
            <div className="p-5 md:p-6">
              {loadingOrders ? (
                <div className="text-center py-10 text-[12px] text-gray-500 font-medium">
                  Loading...
                </div>
              ) : orders.length === 0 ? (
                <div className="text-center py-12 bg-[#F1F3F6] rounded border-2 border-dashed border-gray-300">
                  <FiShoppingBag
                    size={32}
                    className="mx-auto text-gray-300 mb-2"
                  />
                  <p className="text-[13px] font-bold text-gray-700 mb-1">
                    No orders yet
                  </p>
                  <p className="text-[11px] text-gray-500 mb-4">
                    Start shopping to see your orders here
                  </p>
                  <button
                    onClick={() => navigate("/products")}
                    className="px-5 py-2 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[11px] font-bold uppercase tracking-wider rounded transition-all"
                  >
                    Start Shopping
                  </button>
                </div>
              ) : (
                <div className="space-y-4">
                  {orders.map((order, idx) => (
                    <div
                      key={idx}
                      className="bg-white rounded border border-gray-200 hover:shadow-md transition-all overflow-hidden"
                    >
                      <div className="bg-[#F1F3F6] px-4 py-3 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-gray-200">
                        <div>
                          <div className="flex items-center gap-2 mb-1">
                            <span className="text-[13px] font-bold text-gray-800">
                              Order #{order.id}
                            </span>
                            <span className="text-[9px] bg-green-100 text-green-800 px-2 py-0.5 rounded font-bold uppercase tracking-wider">
                              {order.status}
                            </span>
                          </div>
                          <p className="text-[11px] text-gray-500">
                            {order.date} •{" "}
                            <span className="font-semibold text-gray-700">
                              {order.paymentMethod || "Card"}
                            </span>
                          </p>
                        </div>
                        <div className="flex items-center gap-3">
                          <span className="text-[15px] font-bold text-[#2874F0]">
                            CAD ${order.total}
                          </span>
                          <button
                            onClick={() => handleDownloadInvoice(order)}
                            className="flex items-center gap-1.5 px-3 py-2 bg-[#2874F0] hover:bg-[#1e5bc7] text-white text-[10px] font-bold uppercase tracking-wider rounded transition-all shadow-sm"
                          >
                            <FiDownload size={12} /> Invoice
                          </button>
                        </div>
                      </div>
                      <div className="p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        {order.items &&
                          order.items.map((item, i) => (
                            <div
                              key={i}
                              className="flex items-center gap-3 bg-white p-3 rounded border border-gray-100 hover:border-[#2874F0] transition-colors"
                            >
                              <div className="w-16 h-16 bg-[#F1F3F6] rounded overflow-hidden border border-gray-200 shrink-0">
                                <img
                                  src={
                                    item.image || getProductImage(item.name)
                                  }
                                  alt={item.name}
                                  className="w-full h-full object-cover"
                                  onError={(e) => {
                                    e.target.src = PRODUCT_IMAGES["Default"];
                                  }}
                                />
                              </div>
                              <div className="flex-1 min-w-0">
                                <p className="text-[12px] font-bold text-gray-800 line-clamp-2">
                                  {item.name}
                                </p>
                                <p className="text-[10px] text-gray-500 mt-0.5">
                                  Qty: {item.cartQuantity} × CAD $
                                  {Number(item.price).toFixed(2)}
                                </p>
                                <button
                                  onClick={() => handleOpenReview(item)}
                                  className="text-[10px] text-[#FB641B] hover:text-[#e55a15] underline font-bold mt-1 inline-flex items-center gap-1"
                                >
                                  <FiStar size={10} /> Review
                                </button>
                              </div>
                              <div className="text-[13px] font-bold text-[#2874F0] shrink-0">
                                ${(item.cartQuantity * item.price).toFixed(2)}
                              </div>
                            </div>
                          ))}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}

        {/* QUOTES TAB */}
        {activeTab === "quotes" && (
          <div className="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden mb-6">
            <div className="bg-[#F1F3F6] px-5 md:px-6 py-4 border-b border-gray-200 flex items-center gap-3">
              <div className="w-9 h-9 bg-[#FFF8E6] text-[#FF9F00] rounded-full flex items-center justify-center">
                <FiFileText size={16} />
              </div>
              <div>
                <h2 className="text-[15px] font-bold text-gray-800">
                  Bulk Commercial Quotes
                </h2>
                <p className="text-[11px] text-gray-500">
                  Track status & proceed to payment
                </p>
              </div>
            </div>
            <div className="p-5 md:p-6">
              {loadingQuotes ? (
                <div className="text-center py-10 text-[12px] text-gray-500 font-medium">
                  Loading...
                </div>
              ) : quotes.length === 0 ? (
                <div className="text-center py-12 bg-[#F1F3F6] rounded border-2 border-dashed border-gray-300">
                  <FiFileText size={32} className="mx-auto text-gray-300 mb-2" />
                  <p className="text-[13px] font-bold text-gray-700 mb-1">
                    No bulk quotes yet
                  </p>
                  <p className="text-[11px] text-gray-500 mb-4">
                    Request a custom quote for bulk orders
                  </p>
                  <button
                    onClick={() => navigate("/contact")}
                    className="px-5 py-2 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[11px] font-bold uppercase tracking-wider rounded transition-all"
                  >
                    Request Quote
                  </button>
                </div>
              ) : (
                <div className="space-y-3">
                  {quotes.map((quote, idx) => {
                    const status = (quote.status || "Pending").toLowerCase();
                    let statusStyle = "bg-yellow-100 text-yellow-800";
                    if (status === "approved")
                      statusStyle = "bg-green-100 text-green-800";
                    else if (status === "rejected")
                      statusStyle = "bg-red-100 text-red-800";
                    else if (status === "converted")
                      statusStyle = "bg-blue-100 text-blue-800";

                    return (
                      <div
                        key={idx}
                        className="p-4 bg-white rounded border border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 hover:border-[#2874F0] transition-all"
                      >
                        <div className="flex items-center gap-3">
                          <div className="w-11 h-11 bg-[#FFF8E6] rounded-lg flex items-center justify-center shrink-0">
                            <FiFileText size={18} className="text-[#FF9F00]" />
                          </div>
                          <div>
                            <div className="flex items-center gap-2 mb-1">
                              <span className="text-[13px] font-bold text-gray-800">
                                Quote #{quote.quoteNumber}
                              </span>
                              <span
                                className={`text-[9px] px-2 py-0.5 rounded font-bold uppercase tracking-wider ${statusStyle}`}
                              >
                                {quote.status}
                              </span>
                            </div>
                            <p className="text-[11px] text-gray-500">
                              Submitted: {quote.date}
                            </p>
                          </div>
                        </div>
                        <div className="flex items-center gap-3 self-end sm:self-center">
                          <span className="text-[15px] font-bold text-[#2874F0]">
                            CAD ${Number(quote.totalAmount).toFixed(2)}
                          </span>
                          {status === "approved" && (
                            <button
                              onClick={() =>
                                handleProceedToPayment(quote.quoteId)
                              }
                              className="flex items-center gap-1.5 px-4 py-2 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[10px] font-bold uppercase tracking-wider rounded transition-all shadow-sm"
                            >
                              <FiDollarSign size={12} /> Pay Now
                            </button>
                          )}
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </div>
        )}

        {/* ============ RECOMMENDED FOR YOU ============ */}
        <div className="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden mb-6">
          <div className="bg-[#F1F3F6] px-5 md:px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 bg-[#EAF2FF] text-[#2874F0] rounded-full flex items-center justify-center">
                <FiTrendingUp size={16} />
              </div>
              <div>
                <h2 className="text-[15px] font-bold text-gray-800">
                  Recommended For You
                </h2>
                <p className="text-[11px] text-gray-500">
                  Based on your interests
                </p>
              </div>
            </div>
            <button
              onClick={() => navigate("/products")}
              className="text-[11px] font-bold text-[#2874F0] hover:text-[#FB641B] transition-colors uppercase tracking-wider flex items-center gap-1"
            >
              See All <FiChevronRight size={14} />
            </button>
          </div>
          <div className="p-5 md:p-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            {RECOMMENDED.map((product) => (
              <div
                key={product.id}
                onClick={() => navigate(`/product/${product.id}`)}
                className="group bg-white rounded border border-gray-200 hover:border-[#2874F0] hover:shadow-md transition-all overflow-hidden cursor-pointer"
              >
                <div className="relative aspect-square bg-[#F1F3F6] overflow-hidden">
                  <img
                    src={product.image}
                    alt={product.name}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                  {product.badge && (
                    <span className="absolute top-2 left-2 bg-[#FB641B] text-white text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded">
                      {product.badge}
                    </span>
                  )}
                </div>
                <div className="p-3">
                  <p className="text-[9px] font-bold text-[#FB641B] uppercase tracking-wider mb-1">
                    {product.category}
                  </p>
                  <h3 className="text-[12px] font-semibold text-gray-800 line-clamp-2 mb-1.5 min-h-[30px]">
                    {product.name}
                  </h3>
                  <div className="flex items-center gap-1 mb-1.5">
                    <div className="flex items-center gap-0.5 bg-[#10B981] text-white text-[9px] font-bold px-1.5 py-0.5 rounded">
                      {product.rating} <FiStar size={8} fill="white" />
                    </div>
                    <span className="text-[9px] text-gray-500">
                      ({product.reviews})
                    </span>
                  </div>
                  <div className="flex items-center gap-1.5 mb-2">
                    <span className="text-[14px] font-bold text-gray-800">
                      ${product.price}
                    </span>
                    <span className="text-[10px] text-gray-400 line-through">
                      ${product.originalPrice}
                    </span>
                  </div>
                  <button
                    onClick={(e) => e.stopPropagation()}
                    className="w-full py-2 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[10px] font-bold uppercase tracking-wider rounded transition-colors flex items-center justify-center gap-1.5"
                  >
                    <FiShoppingCart size={11} /> Add
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* ============ DANGER ZONE ============ */}
        <div className="bg-white rounded-lg border border-red-200 shadow-sm overflow-hidden">
          <div className="bg-red-50 px-5 md:px-6 py-4 border-b border-red-100 flex items-center gap-3">
            <div className="w-9 h-9 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
              <FiAlertTriangle size={16} />
            </div>
            <div>
              <h2 className="text-[15px] font-bold text-red-700">
                Danger Zone
              </h2>
              <p className="text-[11px] text-red-600/80">
                Irreversible account actions
              </p>
            </div>
          </div>
          <div className="p-5 md:p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
              <p className="text-[13px] font-bold text-gray-800 mb-0.5">
                Delete Account Permanently
              </p>
              <p className="text-[11px] text-gray-500">
                All your data will be erased permanently
              </p>
            </div>
            <button
              onClick={() => setShowDeleteModal(true)}
              className="w-full sm:w-auto px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-[11px] font-bold tracking-wider uppercase rounded transition-all cursor-pointer shadow-sm flex items-center justify-center gap-2"
            >
              <FiTrash2 size={13} /> Delete Account
            </button>
          </div>
        </div>
      </div>

      {/* ============ REVIEW MODAL ============ */}
      {isReviewModalOpen && selectedProductToReview && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-white rounded-lg max-w-md w-full p-6 md:p-7 shadow-2xl relative">
            <button
              onClick={() => setIsReviewModalOpen(false)}
              className="absolute top-3 right-3 text-gray-400 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 p-1.5 rounded-full transition-colors cursor-pointer"
            >
              <FiX size={16} />
            </button>
            <h3 className="text-[18px] font-bold text-gray-800 mb-1">
              Write a Review
            </h3>
            <p className="text-[11px] text-gray-500 mb-5 pb-3 border-b border-gray-100">
              Product:{" "}
              <span className="font-bold text-[#2874F0]">
                {selectedProductToReview.name}
              </span>
            </p>
            {reviewMessage.text && (
              <div
                className={`mb-4 p-3 rounded text-[12px] font-medium flex items-center gap-2 border-l-4 ${
                  reviewMessage.type === "success"
                    ? "bg-green-50 text-green-700 border-green-500"
                    : "bg-red-50 text-red-700 border-red-500"
                }`}
              >
                {reviewMessage.type === "success" ? (
                  <FiCheckCircle size={14} />
                ) : (
                  <FiAlertTriangle size={14} />
                )}
                {reviewMessage.text}
              </div>
            )}
            <form onSubmit={handleSubmitReview} className="space-y-4">
              <div>
                <label className="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">
                  Your Rating *
                </label>
                <div className="flex gap-1">
                  {[1, 2, 3, 4, 5].map((star) => (
                    <button
                      key={star}
                      type="button"
                      onClick={() => setRating(star)}
                      className="focus:outline-none transition-transform hover:scale-110 cursor-pointer"
                    >
                      <FiStar
                        size={30}
                        className={`${
                          star <= rating
                            ? "fill-yellow-400 text-yellow-400"
                            : "text-gray-300"
                        } transition-colors`}
                      />
                    </button>
                  ))}
                </div>
              </div>
              <div>
                <label className="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">
                  Your Feedback *
                </label>
                <textarea
                  required
                  rows="4"
                  value={comment}
                  onChange={(e) => setComment(e.target.value)}
                  className="w-full px-3 py-2.5 bg-white border border-gray-300 rounded text-[13px] focus:outline-none focus:border-[#2874F0] focus:ring-2 focus:ring-[#2874F0]/10 resize-none transition-all"
                  placeholder="Tell us about fabric quality, comfort, durability..."
                />
              </div>
              <div className="flex gap-3 pt-1">
                <button
                  type="button"
                  onClick={() => setIsReviewModalOpen(false)}
                  className="w-1/2 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-[11px] font-bold uppercase tracking-wider rounded transition-all"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={reviewLoading}
                  className="w-1/2 py-2.5 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[11px] font-bold uppercase tracking-wider rounded transition-all shadow-sm disabled:bg-gray-400"
                >
                  {reviewLoading ? "Submitting..." : "Submit"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ============ ADDRESS MODAL ============ */}
      {showAddressModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-lg max-w-lg w-full p-6 md:p-7 shadow-2xl relative max-h-[90vh] overflow-y-auto">
            <div className="flex justify-between items-center mb-5 pb-3 border-b border-gray-100">
              <h3 className="text-[17px] font-bold text-gray-800">
                {isEditing ? "Edit Address" : "Add New Address"}
              </h3>
              <button
                onClick={() => setShowAddressModal(false)}
                className="text-gray-400 hover:text-gray-700 p-1.5 rounded-full bg-gray-100"
              >
                <FiX size={16} />
              </button>
            </div>
            <form onSubmit={handleSaveAddress} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    Recipient Name *
                  </label>
                  <input
                    type="text"
                    required
                    name="recipientName"
                    value={addressForm.recipientName}
                    onChange={handleFormChange}
                    className="w-full px-3 py-2.5 border border-gray-300 rounded text-[13px] focus:outline-none focus:border-[#2874F0]"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    Phone *
                  </label>
                  <input
                    type="text"
                    required
                    name="phone"
                    value={addressForm.phone}
                    onChange={handleFormChange}
                    className="w-full px-3 py-2.5 border border-gray-300 rounded text-[13px] focus:outline-none focus:border-[#2874F0]"
                  />
                </div>
              </div>
              <div>
                <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                  Street Address *
                </label>
                <input
                  type="text"
                  required
                  name="addressLine1"
                  value={addressForm.addressLine1}
                  onChange={handleFormChange}
                  className="w-full px-3 py-2.5 border border-gray-300 rounded text-[13px] focus:outline-none focus:border-[#2874F0]"
                />
              </div>
              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    City *
                  </label>
                  <input
                    type="text"
                    required
                    name="city"
                    value={addressForm.city}
                    onChange={handleFormChange}
                    className="w-full px-3 py-2.5 border border-gray-300 rounded text-[13px] focus:outline-none focus:border-[#2874F0]"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    State *
                  </label>
                  <input
                    type="text"
                    required
                    name="stateProvince"
                    value={addressForm.stateProvince}
                    onChange={handleFormChange}
                    className="w-full px-3 py-2.5 border border-gray-300 rounded text-[13px] focus:outline-none focus:border-[#2874F0]"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    Postal *
                  </label>
                  <input
                    type="text"
                    required
                    name="postalCode"
                    value={addressForm.postalCode}
                    onChange={handleFormChange}
                    className="w-full px-3 py-2.5 border border-gray-300 rounded text-[13px] uppercase focus:outline-none focus:border-[#2874F0]"
                  />
                </div>
              </div>
              <div className="flex gap-3 pt-2">
                <button
                  type="button"
                  onClick={() => setShowAddressModal(false)}
                  className="w-1/2 py-3 bg-white border border-gray-300 text-gray-700 text-[11px] font-bold uppercase tracking-wider rounded"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="w-1/2 py-3 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[11px] font-bold uppercase tracking-wider rounded shadow-sm"
                >
                  {isEditing ? "Update" : "Save"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* SUCCESS MODAL */}
      {showModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-lg max-w-sm w-full p-7 shadow-2xl text-center">
            <div className="w-14 h-14 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
              <FiCheckCircle size={26} />
            </div>
            <h3 className="text-[17px] font-bold text-gray-800 mb-2">
              {modalTitle}
            </h3>
            <p className="text-[12px] text-gray-600 mb-5">{modalMessage}</p>
            <button
              onClick={() => setShowModal(false)}
              className="w-full py-3 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[11px] font-bold uppercase tracking-wider rounded"
            >
              OK
            </button>
          </div>
        </div>
      )}

      {/* DELETE MODAL */}
      {showDeleteModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-lg max-w-md w-full p-7 shadow-2xl">
            <div className="flex justify-between items-center mb-4">
              <div className="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                <FiTrash2 size={20} />
              </div>
              <button
                onClick={() => setShowDeleteModal(false)}
                className="text-gray-400 hover:text-gray-700 p-1.5 rounded-full bg-gray-100"
              >
                <FiX size={16} />
              </button>
            </div>
            <h3 className="text-[17px] font-bold text-gray-800 mb-2">
              Delete Account?
            </h3>
            <p className="text-[12px] text-gray-600 mb-5">
              This is irreversible. All your data will be erased permanently.
            </p>
            <div className="flex gap-3">
              <button
                onClick={() => setShowDeleteModal(false)}
                className="w-1/2 py-3 bg-white border border-gray-300 text-gray-700 text-[11px] font-bold uppercase tracking-wider rounded"
              >
                Cancel
              </button>
              <button
                onClick={handleDeleteAccount}
                disabled={deleteLoading}
                className="w-1/2 py-3 bg-red-600 hover:bg-red-700 text-white text-[11px] font-bold uppercase tracking-wider rounded disabled:bg-gray-400"
              >
                {deleteLoading ? "Deleting..." : "Delete"}
              </button>
            </div>
          </div>
        </div>
      )}

      <style>{`
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
      `}</style>
    </div>
  );
};

export default Dashboard;