import { useState, useEffect } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import {
  FiSearch,
  FiHeart,
  FiShoppingCart,
  FiChevronDown,
  FiMapPin,
  FiPhone,
  FiMail,
  FiX,
  FiUser,
  FiLogOut,
  FiMenu,
  FiLogIn,
  FiUserPlus,
  FiAlertCircle,
} from "react-icons/fi";
import logo from "../assets/GatewayLinen-logo.png";
import CartDrawer from "./CartDrawer";
import WishlistDrawer from "./WishlistDrawer";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

const searchProducts = [
  {
    id: 1,
    name: "Luxury Hotel Bath Towel",
    category: "TOWELS",
    price: "CAD 24.99",
    image:
      "https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?q=80&w=500&auto=format&fit=crop",
  },
  {
    id: 2,
    name: "Premium Spa Pool Towel",
    category: "TOWELS",
    price: "CAD 29.99",
    image:
      "https://images.unsplash.com/photo-1556911220-e15b29be8c8f?q=80&w=500&auto=format&fit=crop",
  },
  {
    id: 3,
    name: "Ultra-Plush Hand Towel",
    category: "TOWELS",
    price: "CAD 12.99",
    image:
      "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=500&auto=format&fit=crop",
  },
  {
    id: 4,
    name: "Egyptian Cotton King Sheet Set",
    category: "BED SHEETS",
    price: "CAD 89.99",
    image:
      "https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=500&auto=format&fit=crop",
  },
  {
    id: 5,
    name: "Commercial Grade White Fitted Sheet",
    category: "BED SHEETS",
    price: "CAD 45.00",
    image:
      "https://images.unsplash.com/photo-1616046229478-9901c5536a45?q=80&w=500&auto=format&fit=crop",
  },
  {
    id: 6,
    name: "Waterproof Hospitality Mattress Pad",
    category: "MATTRESS PADS",
    price: "CAD 54.99",
    image:
      "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=500&auto=format&fit=crop",
  },
  {
    id: 8,
    name: "Down-Alternative Hotel Pillow",
    category: "PILLOWS",
    price: "CAD 34.99",
    image:
      "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=500&auto=format&fit=crop",
  },
  {
    id: 10,
    name: "Thermal Waffle Weave Blanket",
    category: "BLANKETS",
    price: "CAD 49.99",
    image:
      "https://images.unsplash.com/photo-1555041469-a586c61ea9bc?q=80&w=500&auto=format&fit=crop",
  },
];

const Navbar = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const [showContactModal, setShowContactModal] = useState(false);
  const [showUserDropdown, setShowUserDropdown] = useState(false);
  const [showAuthDropdown, setShowAuthDropdown] = useState(false);
  const [showLoginModal, setShowLoginModal] = useState(false);
  const [showLogoutConfirmModal, setShowLogoutConfirmModal] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [showMobileSearch, setShowMobileSearch] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");

  // Categories API State
  const [categories, setCategories] = useState([]);
  const [showCategoryDropdown, setShowCategoryDropdown] = useState(false);

  const [currentUser, setCurrentUser] = useState(() => {
    const storedUser = localStorage.getItem("user");
    if (storedUser) {
      try {
        return JSON.parse(storedUser);
      } catch {
        return null;
      }
    }
    return null;
  });

  const { wishlistItems, toggleWishlistDrawer } = useWishlist();
  const { cartItems, toggleCart } = useCart();

  // Fetch Categories from API
  useEffect(() => {
    fetch(
      "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/categories/api.php?action=get_categories",
    )
      .then((res) => res.json())
      .then((data) => {
        if (data.success && data.categories) {
          setCategories(data.categories);
        }
      })
      .catch((err) => console.error("Error fetching categories:", err));
  }, []);

  if (location.pathname === "/login" || location.pathname === "/register") {
    return null;
  }

  const totalCartCount = cartItems
    ? cartItems.reduce((acc, item) => acc + item.quantity, 0)
    : 0;

  const handleAuthAction = (actionCallback) => {
    const loggedInUser = localStorage.getItem("user");
    if (!loggedInUser) {
      setShowLoginModal(true);
    } else {
      actionCallback();
    }
  };

  const handleLogoutClick = () => {
    setShowUserDropdown(false);
    setShowLogoutConfirmModal(true);
  };

  const confirmLogout = () => {
    localStorage.removeItem("user");
    setCurrentUser(null);
    setShowLogoutConfirmModal(false);
    navigate("/"); // Redirects to Home Page after logout
  };

  const filteredSearchProducts =
    searchQuery.trim() === ""
      ? []
      : searchProducts.filter(
          (item) =>
            item.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            item.category.toLowerCase().includes(searchQuery.toLowerCase()),
        );

  const handleProductSelect = (id) => {
    setSearchQuery("");
    setShowMobileSearch(false);
    navigate(`/product/${id}`);
  };

  return (
    <>
      <header className="w-full font-sans sticky top-0 z-50 shadow-md bg-[#F0EAE1]">
        {/* Top Bar */}
        <div className="bg-[#031D44] text-white text-[10px] md:text-[11px] py-2 px-4 md:px-10 flex justify-between items-center">
          <div className="truncate mr-2">
            Professional Hospitality Linen Supply
          </div>
          <div className="flex items-center gap-3 flex-shrink-0">
            <span
              onClick={() => setShowContactModal(true)}
              className="hover:text-[#B58E58] cursor-pointer transition-colors flex items-center gap-1"
            >
              <FiMapPin className="text-[#B58E58]" size={12} /> Manitoba, Canada
            </span>
          </div>
        </div>

        {/* Main Navbar */}
        <div className="px-4 md:px-10 py-3 md:py-4 flex items-center justify-between relative">
          <div className="flex items-center gap-3">
            <button
              onClick={() => setMobileMenuOpen(true)}
              className="lg:hidden text-[#031D44] hover:text-[#B58E58] transition-colors cursor-pointer p-1"
            >
              <FiMenu size={24} />
            </button>

            <Link to="/" className="flex items-center gap-2">
              <img src={logo} alt="Gateway Linen" className="h-8 md:h-10" />
            </Link>
          </div>

          {/* Desktop Navigation Links */}
          <nav className="hidden lg:flex items-center gap-5 text-[13px] font-bold text-[#031D44]">
            <Link to="/" className="hover:text-[#B58E58] transition-colors">
              Home
            </Link>
            <Link
              to="/products"
              className="hover:text-[#B58E58] transition-colors"
            >
              Products
            </Link>

            {/* 🔴 MODIFIED: Category Text Redirects to Page, Arrow opens Dropdown */}
            <div
              className="relative flex items-center"
              onMouseLeave={() => setShowCategoryDropdown(false)}
            >
              <Link
                to="/categories"
                className="hover:text-[#B58E58] transition-colors cursor-pointer mr-1"
              >
                Categories
              </Link>
              <button
                onMouseEnter={() => setShowCategoryDropdown(true)}
                onClick={() => setShowCategoryDropdown(!showCategoryDropdown)}
                className="flex items-center hover:text-[#B58E58] transition-colors cursor-pointer p-1"
              >
                <FiChevronDown size={14} />
              </button>

              {showCategoryDropdown && (
                <div className="absolute top-full left-0 mt-4 w-48 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50">
                  {categories.length > 0 ? (
                    categories.map((cat) => (
                      <Link
                        key={cat.id || cat.name}
                        to={`/category/${cat.slug || cat.name.toLowerCase().replace(/\s+/g, "-")}`}
                        onClick={() => setShowCategoryDropdown(false)}
                        className="block px-4 py-2 text-xs font-semibold text-[#031D44] hover:bg-[#FAF9F6] hover:text-[#B58E58] transition-colors"
                      >
                        {cat.name}
                      </Link>
                    ))
                  ) : (
                    <div className="px-4 py-2 text-xs text-gray-400">
                      Loading...
                    </div>
                  )}
                </div>
              )}
            </div>

            <Link
              to="/contact"
              className="hover:text-[#B58E58] transition-colors"
            >
              Contacts
            </Link>
            <Link
              to="/bulk-order"
              className="hover:text-[#B58E58] transition-colors"
            >
              Bulk Order
            </Link>
            <Link
              to="/order-history"
              className="hover:text-[#B58E58] transition-colors"
            >
              Order History
            </Link>
          </nav>

          {/* Right Action Icons */}
          <div className="flex items-center gap-2 md:gap-4">
            <div className="relative hidden xl:block">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search products, brands..."
                className="bg-transparent text-[#031D44] placeholder-[#031D44]/60 text-[12px] px-4 py-2 pl-9 rounded-full border border-[#031D44]/20 focus:outline-none focus:border-[#B58E58] w-48 transition-colors"
              />
              <FiSearch
                className="absolute left-3 top-1/2 -translate-y-1/2 text-[#031D44]/60"
                size={14}
              />

              {searchQuery.trim() !== "" && (
                <div className="absolute left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-gray-100 max-h-80 overflow-y-auto z-50 p-2">
                  {filteredSearchProducts.length > 0 ? (
                    filteredSearchProducts.map((item) => (
                      <div
                        key={item.id}
                        onClick={() => handleProductSelect(item.id)}
                        className="flex items-center gap-3 p-2 hover:bg-[#FAF9F6] rounded-xl cursor-pointer transition-colors"
                      >
                        <img
                          src={item.image}
                          alt={item.name}
                          className="w-10 h-10 object-cover rounded-lg flex-shrink-0 border border-gray-100"
                        />
                        <div className="flex-1 min-w-0">
                          <p className="text-[10px] font-bold text-[#B58E58] uppercase tracking-wider">
                            {item.category}
                          </p>
                          <h4 className="text-xs font-serif font-bold text-[#031D44] truncate">
                            {item.name}
                          </h4>
                          <p className="text-[11px] font-bold text-gray-900">
                            {item.price}
                          </p>
                        </div>
                      </div>
                    ))
                  ) : (
                    <div className="p-4 text-center text-xs text-gray-400">
                      No products found matching "{searchQuery}"
                    </div>
                  )}
                </div>
              )}
            </div>

            <button
              onClick={() => setShowMobileSearch(!showMobileSearch)}
              className="xl:hidden text-[#031D44] hover:text-[#B58E58] transition-colors cursor-pointer p-1"
            >
              <FiSearch size={20} />
            </button>

            <button
              onClick={() => {
                handleAuthAction(() => {
                  if (toggleWishlistDrawer) toggleWishlistDrawer();
                });
              }}
              className="relative text-[#031D44] hover:text-[#B58E58] transition-colors cursor-pointer p-1"
            >
              <FiHeart size={20} />
              {currentUser && wishlistItems && wishlistItems.length > 0 && (
                <span className="absolute -top-1 -right-1 bg-[#B58E58] text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                  {wishlistItems.length}
                </span>
              )}
            </button>

            {currentUser ? (
              <div className="relative hidden sm:block">
                <button
                  onClick={() => setShowUserDropdown(!showUserDropdown)}
                  className="flex items-center gap-2 bg-transparent px-3 py-1.5 rounded-full border border-[#031D44]/20 hover:border-[#B58E58] transition-colors cursor-pointer"
                >
                  <div className="w-6 h-6 bg-[#031D44] text-[#F0EAE1] rounded-full flex items-center justify-center text-[10px] font-bold">
                    {currentUser.fullName
                      ? currentUser.fullName.charAt(0).toUpperCase()
                      : "U"}
                  </div>
                  <span className="text-[12px] font-bold text-[#031D44] max-w-[100px] truncate">
                    {currentUser.fullName || "User"}
                  </span>
                  <FiChevronDown size={14} className="text-[#031D44]" />
                </button>

                {showUserDropdown && (
                  <div className="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 py-3 px-2 z-50">
                    <div className="px-3 py-2 border-b border-gray-100 mb-2">
                      <p className="text-[10px] uppercase font-bold text-gray-400 tracking-wider">
                        Signed in as
                      </p>
                      <p className="text-xs font-bold text-[#031D44] truncate">
                        {currentUser.email}
                      </p>
                    </div>

                    <Link
                      to="/dashboard"
                      onClick={() => setShowUserDropdown(false)}
                      className="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-[#031D44] hover:bg-[#FAF9F6] rounded-xl transition-colors"
                    >
                      <FiUser size={14} className="text-[#B58E58]" /> User
                      Dashboard
                    </Link>

                    <button
                      onClick={handleLogoutClick}
                      className="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50 rounded-xl transition-colors mt-1 cursor-pointer"
                    >
                      <FiLogOut size={14} /> Logout
                    </button>
                  </div>
                )}
              </div>
            ) : (
              <div className="relative hidden sm:block">
                <button
                  onClick={() => setShowAuthDropdown(!showAuthDropdown)}
                  className="flex items-center gap-1.5 px-4 py-2 bg-[#031D44] text-white rounded-full text-xs font-bold tracking-wider uppercase hover:bg-[#B58E58] transition-all shadow-sm cursor-pointer"
                >
                  <FiUser size={14} /> Account <FiChevronDown size={12} />
                </button>

                {showAuthDropdown && (
                  <div className="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 px-1 z-50">
                    <Link
                      to="/login"
                      onClick={() => setShowAuthDropdown(false)}
                      className="w-full flex items-center gap-2 px-3 py-2.5 text-xs font-bold text-[#031D44] hover:bg-[#FAF9F6] rounded-xl transition-colors"
                    >
                      <FiLogIn size={14} className="text-[#B58E58]" /> Sign In
                    </Link>
                    <Link
                      to="/register"
                      onClick={() => setShowAuthDropdown(false)}
                      className="w-full flex items-center gap-2 px-3 py-2.5 text-xs font-bold text-[#031D44] hover:bg-[#FAF9F6] rounded-xl transition-colors mt-0.5"
                    >
                      <FiUserPlus size={14} className="text-[#B58E58]" />{" "}
                      Register
                    </Link>
                  </div>
                )}
              </div>
            )}

            <button
              onClick={() => {
                handleAuthAction(() => {
                  if (toggleCart) toggleCart();
                });
              }}
              className="relative text-[#031D44] hover:text-[#B58E58] transition-colors cursor-pointer p-1"
            >
              <FiShoppingCart size={22} />
              {currentUser && totalCartCount > 0 && (
                <span className="absolute -top-1 -right-1 bg-[#B58E58] text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                  {totalCartCount}
                </span>
              )}
            </button>
          </div>
        </div>

        {/* Mobile Search Input Expandable Bar */}
        {showMobileSearch && (
          <div className="xl:hidden px-4 pb-3 bg-[#F0EAE1] border-t border-[#031D44]/10">
            <div className="relative mt-2">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search products, brands..."
                className="w-full bg-white text-[#031D44] placeholder-gray-400 text-xs px-4 py-2.5 pl-9 rounded-xl border border-[#031D44]/20 focus:outline-none focus:border-[#B58E58]"
              />
              <FiSearch
                className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                size={14}
              />
            </div>
            {searchQuery.trim() !== "" && (
              <div className="mt-2 bg-white rounded-xl shadow-xl border border-gray-100 max-h-60 overflow-y-auto p-2">
                {filteredSearchProducts.length > 0 ? (
                  filteredSearchProducts.map((item) => (
                    <div
                      key={item.id}
                      onClick={() => handleProductSelect(item.id)}
                      className="flex items-center gap-3 p-2 hover:bg-[#FAF9F6] rounded-lg cursor-pointer"
                    >
                      <img
                        src={item.image}
                        alt={item.name}
                        className="w-9 h-9 object-cover rounded-md flex-shrink-0"
                      />
                      <div className="flex-1 min-w-0">
                        <p className="text-[9px] font-bold text-[#B58E58] uppercase">
                          {item.category}
                        </p>
                        <h4 className="text-xs font-serif font-bold text-[#031D44] truncate">
                          {item.name}
                        </h4>
                        <p className="text-[10px] font-bold text-gray-900">
                          {item.price}
                        </p>
                      </div>
                    </div>
                  ))
                ) : (
                  <div className="p-3 text-center text-xs text-gray-400">
                    No products found
                  </div>
                )}
              </div>
            )}
          </div>
        )}
      </header>

      {/* CUSTOM LOGOUT CONFIRMATION MODAL */}
      {showLogoutConfirmModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-in fade-in duration-300 font-sans">
          <div className="bg-[#F7F2EB] border border-[#E5DCD0] rounded-[28px] max-w-sm w-full p-6 sm:p-8 shadow-2xl text-center relative animate-in zoom-in duration-300">
            <div className="w-16 h-16 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm border border-red-100">
              <FiAlertCircle size={32} />
            </div>
            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-2">
              Confirm Logout
            </h3>
            <p className="text-xs text-gray-600 font-light mb-6 leading-relaxed">
              Are you sure you want to sign out from your Gateway Linen account?
            </p>
            <div className="flex gap-3">
              <button
                type="button"
                onClick={() => setShowLogoutConfirmModal(false)}
                className="w-1/2 py-3 bg-white border border-[#E5DCD0] hover:bg-gray-50 text-[#031D44] text-xs font-bold tracking-widest uppercase rounded-xl transition-all cursor-pointer shadow-2xs"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={confirmLogout}
                className="w-1/2 py-3 bg-red-600 hover:bg-red-700 text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
              >
                Yes, Logout
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Login Required Modal */}
      {showLoginModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity p-4">
          <div className="bg-[#F7F2EB] border border-[#E5DCD0] p-8 rounded-[28px] shadow-2xl w-full max-w-sm text-center relative">
            <button
              onClick={() => setShowLoginModal(false)}
              className="absolute top-5 right-5 text-gray-400 hover:text-gray-800 bg-white p-2 rounded-full transition-colors cursor-pointer border border-gray-200"
            >
              <FiX size={18} />
            </button>

            <div className="w-16 h-16 bg-[#031D44] text-[#B58E58] rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-md">
              <FiUser size={28} />
            </div>

            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-2">
              Login Required
            </h3>
            <p className="text-xs text-gray-600 mb-8 font-light leading-relaxed px-2">
              Please login first to add items to your cart, wishlist, or proceed
              to checkout.
            </p>

            <button
              onClick={() => {
                setShowLoginModal(false);
                navigate("/login");
              }}
              className="w-full py-3.5 bg-[#031D44] text-white rounded-xl text-xs font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all cursor-pointer"
            >
              Login Now
            </button>
          </div>
        </div>
      )}

      {/* Mobile Navigation Drawer (Theme Matched UI) */}
      {mobileMenuOpen && (
        <div className="fixed inset-0 z-50 flex font-sans lg:hidden">
          <div
            onClick={() => setMobileMenuOpen(false)}
            className="absolute inset-0 bg-black/50 backdrop-blur-xs"
          ></div>

          <div className="relative w-80 bg-[#F7F2EB] h-full shadow-2xl flex flex-col z-10 p-6 overflow-y-auto border-r border-[#E5DCD0]">
            <div className="flex items-center justify-between pb-4 border-b border-[#E5DCD0] mb-6">
              <img src={logo} alt="Gateway Linen" className="h-8" />
              <button
                onClick={() => setMobileMenuOpen(false)}
                className="p-2 text-[#031D44] hover:text-white bg-white hover:bg-[#031D44] rounded-full transition-all cursor-pointer border border-[#E5DCD0]"
              >
                <FiX size={16} />
              </button>
            </div>

            {/* Mobile User Section / Auth Links */}
            {currentUser ? (
              <div className="bg-white p-4 rounded-2xl border border-[#E5DCD0] mb-6 shadow-2xs">
                <p className="text-[10px] uppercase font-bold text-[#B58E58] tracking-wider">
                  Signed in as
                </p>
                <p className="text-xs font-bold text-[#031D44] truncate mt-0.5">
                  {currentUser.email}
                </p>
              </div>
            ) : (
              <div className="flex gap-2.5 mb-6">
                <Link
                  to="/login"
                  onClick={() => setMobileMenuOpen(false)}
                  className="flex-1 py-3 bg-[#031D44] text-white text-[11px] font-bold tracking-wider uppercase rounded-xl flex items-center justify-center gap-1.5 shadow-md hover:bg-[#B58E58] transition-all"
                >
                  <FiLogIn size={13} /> Sign In
                </Link>
                <Link
                  to="/register"
                  onClick={() => setMobileMenuOpen(false)}
                  className="flex-1 py-3 bg-[#B58E58] text-white text-[11px] font-bold tracking-wider uppercase rounded-xl flex items-center justify-center gap-1.5 shadow-md hover:bg-[#031D44] transition-all"
                >
                  <FiUserPlus size={13} /> Register
                </Link>
              </div>
            )}

            {/* Mobile Navigation Links */}
            <div className="flex flex-col gap-2.5 text-xs font-bold text-[#031D44]">
              <Link
                to="/"
                onClick={() => setMobileMenuOpen(false)}
                className="py-2.5 px-4 bg-white/60 hover:bg-white rounded-xl border border-transparent hover:border-[#E5DCD0] transition-all"
              >
                Home
              </Link>
              <Link
                to="/products"
                onClick={() => setMobileMenuOpen(false)}
                className="py-2.5 px-4 bg-white/60 hover:bg-white rounded-xl border border-transparent hover:border-[#E5DCD0] transition-all"
              >
                Products
              </Link>

              <div className="py-2 px-4 flex items-center justify-between">
                <Link
                  to="/categories"
                  onClick={() => setMobileMenuOpen(false)}
                  className="text-[10px] uppercase font-bold text-[#B58E58] tracking-wider"
                >
                  Categories
                </Link>
              </div>
              {categories.length > 0 ? (
                categories.map((cat) => (
                  <Link
                    key={cat.id || cat.name}
                    to={`/category/${cat.slug || cat.name.toLowerCase().replace(/\s+/g, "-")}`}
                    onClick={() => setMobileMenuOpen(false)}
                    className="py-2 px-6 bg-white/40 hover:bg-white rounded-xl text-[11px] transition-all"
                  >
                    {cat.name}
                  </Link>
                ))
              ) : (
                <div className="py-2 px-6 text-[11px] text-gray-400">
                  Loading...
                </div>
              )}

              <Link
                to="/contact"
                onClick={() => setMobileMenuOpen(false)}
                className="py-2.5 px-4 bg-white/60 hover:bg-white rounded-xl border border-transparent hover:border-[#E5DCD0] transition-all mt-2"
              >
                Contacts
              </Link>
              <Link
                to="/bulk-order"
                onClick={() => setMobileMenuOpen(false)}
                className="py-2.5 px-4 bg-white/60 hover:bg-white rounded-xl border border-transparent hover:border-[#E5DCD0] transition-all"
              >
                Bulk Order
              </Link>
              <Link
                to="/order-history"
                onClick={() => setMobileMenuOpen(false)}
                className="py-2.5 px-4 bg-white/60 hover:bg-white rounded-xl border border-transparent hover:border-[#E5DCD0] transition-all"
              >
                Order History
              </Link>
            </div>

            {currentUser && (
              <div className="mt-auto pt-6 border-t border-[#E5DCD0] space-y-3">
                <Link
                  to="/dashboard"
                  onClick={() => setMobileMenuOpen(false)}
                  className="w-full py-3 bg-[#031D44] text-white text-xs font-bold tracking-wider uppercase rounded-xl flex items-center justify-center gap-2 shadow-md hover:bg-[#B58E58] transition-all"
                >
                  <FiUser size={14} /> User Dashboard
                </Link>
                <button
                  onClick={() => {
                    setMobileMenuOpen(false);
                    setShowLogoutConfirmModal(true);
                  }}
                  className="w-full py-3 bg-red-50 text-red-600 border border-red-200 text-xs font-bold tracking-wider uppercase rounded-xl flex items-center justify-center gap-2 cursor-pointer hover:bg-red-100 transition-all"
                >
                  <FiLogOut size={14} /> Logout
                </button>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Cart and Wishlist Drawers */}
      <CartDrawer />
      <WishlistDrawer />

      {/* Contact & Google Map Modal */}
      {showContactModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-3">
          <div className="bg-[#F7F2EB] border border-[#E5DCD0] rounded-[24px] md:rounded-[28px] max-w-lg w-full p-4 sm:p-6 md:p-8 shadow-2xl relative max-h-[90vh] overflow-y-auto">
            <button
              onClick={() => setShowContactModal(false)}
              className="absolute top-4 right-4 md:top-6 md:right-6 text-gray-400 hover:text-gray-800 bg-white p-2 rounded-full transition-colors cursor-pointer border border-gray-200"
            >
              <FiX size={16} />
            </button>

            <h3 className="text-xl md:text-2xl font-serif font-bold text-[#031D44] mb-1">
              Gateway Linen HQ
            </h3>
            <p className="text-[11px] md:text-xs text-gray-500 mb-4 font-light">
              Official contact information and location.
            </p>

            <div className="space-y-2.5 mb-4 bg-white p-3.5 rounded-xl border border-[#E5DCD0]">
              <div className="flex items-start gap-2.5 text-xs text-gray-700">
                <FiMapPin
                  className="text-[#B58E58] mt-0.5 flex-shrink-0"
                  size={15}
                />
                <span className="leading-relaxed">
                  <strong>Address:</strong> 9 Mapleridge crescent, Brandon
                  R7A6P8, Manitoba, Canada
                </span>
              </div>
              <div className="flex items-center gap-2.5 text-xs text-gray-700">
                <FiPhone className="text-[#B58E58] flex-shrink-0" size={15} />
                <span>
                  <strong>Phone:</strong> +1 (204) 979-4044
                </span>
              </div>
              <div className="flex items-start gap-2.5 text-xs text-gray-700">
                <FiMail
                  className="text-[#B58E58] flex-shrink-0 mt-0.5"
                  size={15}
                />
                <div className="flex flex-col break-all">
                  <span>
                    <strong>Email:</strong> tapu_parikh@yahoo.com
                  </span>
                  <span>gatewaylinen@gmail.com</span>
                </div>
              </div>
            </div>

            <div className="w-full h-40 md:h-48 rounded-xl overflow-hidden border border-[#E5DCD0] mb-4">
              <iframe
                title="Brandon Manitoba Map"
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d81559.45876313715!2d-99.98816!3d49.8482!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x52c1e65e638b6d85%3A0x44614e758784d531!2sBrandon%2C%20MB%2C%20Canada!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin"
                width="100%"
                height="100%"
                style={{ border: 0 }}
                allowFullScreen=""
                loading="lazy"
                referrerPolicy="no-referrer-when-downgrade"
              ></iframe>
            </div>

            <button
              onClick={() => setShowContactModal(false)}
              className="w-full py-3.5 bg-[#031D44] hover:bg-[#B58E58] text-white rounded-xl text-xs font-bold tracking-widest uppercase transition-all cursor-pointer shadow-md"
            >
              Close
            </button>
          </div>
        </div>
      )}
    </>
  );
};

export default Navbar;
