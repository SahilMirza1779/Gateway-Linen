import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import {
  FiSearch,
  FiMapPin,
  FiHeart,
  FiUser,
  FiShoppingCart,
  FiChevronDown,
  FiLogOut,
  FiX,
  FiClock,
  FiPhoneCall,
} from "react-icons/fi";
import logo from "../assets/GatewayLinen-logo.png";
import { useCart } from "../context/CartContext";
import { useWishlist } from "../context/WishlistContext";

const Navbar = () => {
  const navigate = useNavigate();
  const [searchQuery, setSearchQuery] = useState("");
  const [isLoginOpen, setIsLoginOpen] = useState(false);

  // Modals ke states
  const [showLoginModal, setShowLoginModal] = useState(false);
  const [showLocationModal, setShowLocationModal] = useState(false); // NAYA: Location modal ka state

  const { cartCount, toggleCart } = useCart();
  const { wishlistItems, toggleWishlist } = useWishlist();

  const [user, setUser] = useState(() => {
    const loggedInUser = localStorage.getItem("user");
    return loggedInUser ? JSON.parse(loggedInUser) : null;
  });

  const handleSearch = (e) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      alert(`Searching for: ${searchQuery}`);
    }
  };

  // NAYA: Location icon par click karne se modal khulega
  const handleLocationClick = () => {
    setShowLocationModal(true);
  };

  const handleLogout = () => {
    localStorage.removeItem("user");
    setUser(null);
    setIsLoginOpen(false);
    navigate("/login");
  };

  const handleCartClick = () => {
    if (!user) {
      setShowLoginModal(true);
    } else {
      toggleCart();
    }
  };

  const handleWishlistClick = () => {
    if (!user) {
      setShowLoginModal(true);
    } else {
      toggleWishlist();
    }
  };

  return (
    <>
      <div className="w-full bg-[#031D44] text-white text-[11px] font-light py-2 px-4 md:px-10 flex justify-between items-center tracking-wider">
        <div>Professional Hospitality Linen Supply</div>
        <div className="hidden md:flex space-x-6 text-gray-300">
          <span className="cursor-pointer hover:text-white transition-colors">
            Canada • CAD
          </span>
          <span className="cursor-pointer hover:text-white transition-colors">
            Wholesale & Bulk Orders
          </span>
        </div>
      </div>

      <header className="w-full font-sans bg-white sticky top-0 z-50 shadow-sm border-b border-gray-100 transition-all duration-300">
        <div className="max-w-[1536px] mx-auto px-4 md:px-10 h-[75px] flex items-center justify-between">
          <Link
            to="/"
            className="flex items-center gap-2.5 cursor-pointer shrink-0"
          >
            <img
              src={logo}
              alt="Gateway Linen Logo"
              className="h-10 w-auto object-contain mix-blend-multiply"
            />
            <div className="flex flex-col justify-center">
              <span className="font-serif text-[12.5px] font-bold tracking-[0.2em] leading-none text-black">
                GATEWAY
              </span>
              <span className="font-sans text-[7.5px] font-semibold tracking-[0.45em] text-[#B58E58] mt-0.5">
                LINEN
              </span>
            </div>
          </Link>

          {/* Center Links */}
          <div className="hidden xl:flex items-center space-x-6 text-[13px] text-gray-700 font-medium">
            <Link to="/" className="hover:text-black transition-colors">
              Home
            </Link>

            <div className="relative group py-6">
              <div className="flex items-center gap-1 cursor-pointer hover:text-black transition-colors">
                <span>Products</span>{" "}
                <FiChevronDown className="mt-0.5 text-gray-400 group-hover:text-black transition-transform duration-300 group-hover:rotate-180" />
              </div>
              <div className="absolute top-[50px] left-0 w-48 bg-white shadow-xl border border-gray-100 rounded-md opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 flex flex-col py-2">
                <Link
                  to="/category/all"
                  className="px-5 py-2 hover:bg-gray-50 hover:text-black text-gray-600"
                >
                  All Products
                </Link>
                <Link
                  to="/category/new-arrivals"
                  className="px-5 py-2 hover:bg-gray-50 hover:text-black text-gray-600"
                >
                  New Arrivals
                </Link>
                <Link
                  to="/category/best-sellers"
                  className="px-5 py-2 hover:bg-gray-50 hover:text-black text-gray-600"
                >
                  Best Sellers
                </Link>
              </div>
            </div>

            <Link
              to="/category/towels"
              className="hover:text-black transition-colors"
            >
              Towels
            </Link>
            <Link
              to="/category/bed-sheets"
              className="hover:text-black transition-colors"
            >
              Bed Sheets
            </Link>
            <Link
              to="/category/mattress-pads"
              className="hover:text-black transition-colors"
            >
              Mattress Pads
            </Link>
            <Link
              to="/category/pillows"
              className="hover:text-black transition-colors"
            >
              Pillows
            </Link>
            <Link
              to="/category/blankets"
              className="hover:text-black transition-colors"
            >
              Blankets
            </Link>
            <Link
              to="/category/others"
              className="hover:text-black transition-colors"
            >
              Others
            </Link>
            <Link to="/contact" className="hover:text-black transition-colors">
              Contact
            </Link>
          </div>

          {/* Right Icons */}
          <div className="hidden lg:flex items-center space-x-5 text-gray-600 shrink-0">
            <div
              onClick={handleLocationClick}
              className="flex items-center gap-1.5 text-[13px] cursor-pointer hover:text-black transition-colors bg-gray-50 px-3 py-1.5 rounded-full border border-gray-100"
            >
              <FiMapPin size={16} className="text-[#B58E58]" />
              <span className="font-medium text-[#031D44]">Canada</span>
            </div>

            <form onSubmit={handleSearch} className="relative">
              <FiSearch
                className="absolute left-3.5 top-1/2 transform -translate-y-1/2 text-gray-400"
                size={15}
              />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search products, brands..."
                className="pl-10 pr-4 py-1.5 text-[12.5px] bg-[#F4F4F5] border border-transparent rounded-full focus:outline-none focus:border-gray-300 focus:bg-white w-[240px] transition-all"
              />
            </form>

            <div
              onClick={handleWishlistClick}
              className="relative cursor-pointer text-gray-600 hover:text-black transition-colors"
            >
              <FiHeart size={20} />
              {wishlistItems.length > 0 && (
                <span className="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-medium w-4 h-4 flex items-center justify-center rounded-full">
                  {wishlistItems.length}
                </span>
              )}
            </div>

            {/* USER LOGIN / PROFILE AREA */}
            <div className="relative">
              <div
                onClick={() => setIsLoginOpen(!isLoginOpen)}
                className="flex items-center gap-1 cursor-pointer hover:text-black transition-colors text-[14px]"
              >
                {user ? (
                  <div className="flex items-center gap-1.5 bg-gray-50 px-2.5 py-1.5 rounded-full border border-gray-100">
                    <div className="w-5 h-5 bg-[#031D44] text-white rounded-full flex items-center justify-center text-[10px] font-bold">
                      {user.fullName
                        ? user.fullName.charAt(0).toUpperCase()
                        : "U"}
                    </div>
                    <span className="text-[12.5px] font-semibold text-[#031D44] max-w-[80px] truncate">
                      {user.fullName || "User"}
                    </span>
                  </div>
                ) : (
                  <>
                    <FiUser size={20} className="text-gray-600" />
                    <span>Login</span>
                  </>
                )}
                <FiChevronDown
                  className={`mt-0.5 text-gray-400 transition-transform duration-300 ${isLoginOpen ? "rotate-180" : ""}`}
                />
              </div>

              {isLoginOpen && (
                <div className="absolute top-[45px] right-0 w-48 bg-white shadow-xl border border-gray-100 rounded-xl z-50 flex flex-col py-2 transition-all">
                  {user ? (
                    <>
                      <div className="px-5 py-3 border-b border-gray-50">
                        <p className="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">
                          Signed in as
                        </p>
                        <p className="text-[13px] font-semibold text-[#031D44] truncate">
                          {user.email}
                        </p>
                      </div>
                      <Link
                        to="/dashboard"
                        onClick={() => setIsLoginOpen(false)}
                        className="px-5 py-2.5 hover:bg-gray-50 hover:text-[#B58E58] text-[13.5px] font-medium text-gray-600 flex items-center gap-2 mt-1"
                      >
                        <FiUser size={16} /> My Dashboard
                      </Link>
                      <button
                        onClick={handleLogout}
                        className="px-5 py-2.5 hover:bg-red-50 hover:text-red-600 text-[13.5px] font-medium text-gray-600 flex items-center gap-2 text-left w-full transition-colors"
                      >
                        <FiLogOut size={16} /> Logout
                      </button>
                    </>
                  ) : (
                    <>
                      <Link
                        to="/login"
                        onClick={() => setIsLoginOpen(false)}
                        className="px-5 py-2 hover:bg-gray-50 hover:text-black text-[13px] text-gray-600"
                      >
                        Sign In
                      </Link>
                      <Link
                        to="/register"
                        onClick={() => setIsLoginOpen(false)}
                        className="px-5 py-2 hover:bg-gray-50 hover:text-black text-[13px] text-gray-600"
                      >
                        Register
                      </Link>
                    </>
                  )}
                </div>
              )}
            </div>

            <div
              onClick={handleCartClick}
              className="relative cursor-pointer text-gray-600 hover:text-black transition-colors"
            >
              <FiShoppingCart size={20} />
              {cartCount > 0 && (
                <span className="absolute -top-1.5 -right-1.5 bg-black text-white text-[10px] font-medium w-4 h-4 flex items-center justify-center rounded-full">
                  {cartCount}
                </span>
              )}
            </div>
          </div>
        </div>
      </header>

      {/* Login Required Modal */}
      {showLoginModal && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm transition-opacity">
          <div className="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-sm text-center relative transform transition-all scale-100">
            <button
              onClick={() => setShowLoginModal(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors"
            >
              <FiX size={18} />
            </button>
            <div className="w-16 h-16 bg-blue-50 text-[#031D44] rounded-full flex items-center justify-center mx-auto mb-5">
              <FiUser size={30} />
            </div>
            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-2">
              Login Required
            </h3>
            <p className="text-sm text-gray-500 mb-8 px-2">
              Please login first to view your cart, wishlist, or proceed to
              checkout.
            </p>
            <button
              onClick={() => {
                setShowLoginModal(false);
                navigate("/login");
              }}
              className="w-full py-3.5 bg-[#031D44] text-white rounded-xl text-xs font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all"
            >
              Login Now
            </button>
          </div>
        </div>
      )}

      {/* NAYA: Location Modal */}
      {showLocationModal && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm transition-opacity">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden relative transform transition-all scale-100 mx-4">
            {/* Modal Header */}
            <div className="bg-[#031D44] p-5 text-white flex justify-between items-center">
              <div className="flex items-center gap-2">
                <FiMapPin size={20} className="text-[#B58E58]" />
                <h3 className="text-lg font-serif font-bold">Our Location</h3>
              </div>
              <button
                onClick={() => setShowLocationModal(false)}
                className="text-gray-300 hover:text-white transition-colors bg-white/10 p-1.5 rounded-full"
              >
                <FiX size={18} />
              </button>
            </div>

            {/* Modal Body */}
            <div className="p-6">
              <div className="flex flex-col gap-5">
                {/* Google Maps Embed */}
                <div className="w-full h-44 bg-gray-100 rounded-xl overflow-hidden border border-gray-200 shadow-inner">
                  <iframe
                    title="Gateway Linen Location"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2886.436738981152!2d-79.38924558450203!3d43.65991897912128!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x882b34b8f331fd9b%3A0x8d1d916fb2c56470!2sToronto%2C%20ON%2C%20Canada!5e0!3m2!1sen!2sin!4v1695642234027!5m2!1sen!2sin"
                    width="100%"
                    height="100%"
                    style={{ border: 0 }}
                    allowFullScreen=""
                    loading="lazy"
                    referrerPolicy="no-referrer-when-downgrade"
                  ></iframe>
                </div>

                {/* Details Section */}
                <div className="bg-gray-50 p-5 rounded-xl border border-gray-100">
                  <h4 className="text-sm font-bold text-[#031D44] mb-2">
                    Gateway Linen Distribution Center
                  </h4>
                  <p className="text-[13px] text-gray-600 mb-4 leading-relaxed flex gap-2">
                    <FiMapPin className="mt-1 text-[#B58E58] shrink-0" />
                    <span>
                      123 Hospitality Blvd, Suite 400
                      <br />
                      Toronto, ON M5V 2T6, Canada
                    </span>
                  </p>

                  <div className="grid grid-cols-2 gap-4 text-[12px] pt-4 border-t border-gray-200">
                    <div>
                      <span className="font-bold text-[#031D44] flex items-center gap-1.5 mb-1.5">
                        <FiClock className="text-[#B58E58]" /> Hours
                      </span>
                      <span className="text-gray-500 block">
                        Mon - Fri: 9am - 6pm
                      </span>
                      <span className="text-gray-500 block">
                        Sat - Sun: Closed
                      </span>
                    </div>
                    <div>
                      <span className="font-bold text-[#031D44] flex items-center gap-1.5 mb-1.5">
                        <FiPhoneCall className="text-[#B58E58]" /> Contact
                      </span>
                      <span className="text-gray-500 block">
                        support@gatewaylinen.ca
                      </span>
                      <span className="text-gray-500 block">
                        +1 (555) 123-4567
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <button
                onClick={() => setShowLocationModal(false)}
                className="w-full mt-6 py-3 bg-[#031D44] text-white rounded-xl text-xs font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default Navbar;
