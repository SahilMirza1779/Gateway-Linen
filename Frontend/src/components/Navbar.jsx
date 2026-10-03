import { useState, useEffect, useRef } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import {
  FiSearch,
  FiHeart,
  FiShoppingCart,
  FiChevronDown,
  FiPhone,
  FiMail,
  FiX,
  FiUser,
  FiLogOut,
  FiMenu,
  FiMapPin,
  FiLayout,
  FiHeadphones,
  FiTruck,
  FiPercent,
  FiPackage,
} from "react-icons/fi";
import logo from "../assets/GatewayLinen-logo.png";
import CartDrawer from "./CartDrawer";
import WishlistDrawer from "./WishlistDrawer";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

const Navbar = () => {
  const location = useLocation();
  const navigate = useNavigate();

  const [showContactModal, setShowContactModal] = useState(false);
  const [showUserDropdown, setShowUserDropdown] = useState(false);
  const [showLoginModal, setShowLoginModal] = useState(false);
  const [showLogoutConfirmModal, setShowLogoutConfirmModal] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [showMobileSearch, setShowMobileSearch] = useState(false);

  const dropdownRef = useRef(null);

  const [categories, setCategories] = useState([
    { id: 1006, name: "Bed Sheets", slug: "bed-sheets" },
    { id: 1007, name: "Fitted Bed Sheets", slug: "fitted-bed-sheets" },
    { id: 1008, name: "Duvet & Duvet Covers", slug: "duvet-duvet-covers" },
    { id: 1009, name: "Mattress Protectors", slug: "mattress-protectors" },
    {
      id: 1010,
      name: "Pillows & Pillow Covers",
      slug: "pillows-pillow-covers",
    },
    { id: 1011, name: "Bathroom Accessories", slug: "bathroom-accessories" },
    { id: 1012, name: "Blankets", slug: "blankets" },
  ]);

  const [products, setProducts] = useState([
    {
      id: 6,
      name: "Mattress Pad",
      category: "Mattress Pad",
      image:
        "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=500&auto=format&fit=crop",
    },
  ]);

  const [activeMegaMenu, setActiveMegaMenu] = useState(null);
  const [megaMenuData, setMegaMenuData] = useState([]);
  const [megaMenuImage, setMegaMenuImage] = useState(
    "https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=1200&auto=format&fit=crop"
  );
  const [loadingMega, setLoadingMega] = useState(false);

  const [currentUser, setCurrentUser] = useState(() => {
    const storedUser = localStorage.getItem("user");
    return storedUser ? JSON.parse(storedUser) : null;
  });

  const { wishlistItems, toggleWishlistDrawer } = useWishlist();
  const { cartItems, toggleCart } = useCart();

  useEffect(() => {
    const checkUserStatus = () => {
      const storedUser = localStorage.getItem("user");
      const parsedUser = storedUser ? JSON.parse(storedUser) : null;
      setCurrentUser((prev) => {
        if (JSON.stringify(prev) !== JSON.stringify(parsedUser)) {
          return parsedUser;
        }
        return prev;
      });
    };

    const timer = setTimeout(() => {
      checkUserStatus();
    }, 0);

    window.addEventListener("storage", checkUserStatus);

    return () => {
      clearTimeout(timer);
      window.removeEventListener("storage", checkUserStatus);
    };
  }, [location.pathname]);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setShowUserDropdown(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, []);

  useEffect(() => {
    fetch(
      "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/categories/api.php?action=get_categories"
    )
      .then((res) => res.json())
      .then((data) => {
        if (data.success && data.categories && data.categories.length > 0) {
          setCategories(data.categories);
        } else if (Array.isArray(data) && data.length > 0) {
          setCategories(data);
        }
      })
      .catch((err) => console.error("Using fallback categories:", err));

    fetch(
      "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/products/api.php?action=get_products"
    )
      .then((res) => res.json())
      .then((data) => {
        if (data.success && data.products && data.products.length > 0) {
          setProducts(data.products);
        } else if (Array.isArray(data) && data.length > 0) {
          setProducts(data);
        }
      })
      .catch((err) => console.error("Using fallback products:", err));
  }, []);

  const handleMenuHover = (menuType) => {
    setActiveMegaMenu(menuType);
    setLoadingMega(true);

    if (menuType === "categories") {
      setMegaMenuData(
        categories.map((c) => ({
          id: c.CategoryId || c.id,
          name: c.Name || c.name,
          slug: `category/${c.Slug || c.slug || (c.Name || c.name || "").toLowerCase().replace(/\s+/g, "-")}`,
        }))
      );
      setMegaMenuImage(
        "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=1200&auto=format&fit=crop"
      );
      setLoadingMega(false);
    } else if (menuType === "products") {
      setMegaMenuData(
        products.map((p) => ({
          id: p.ProductId || p.id,
          name: p.Name || p.name,
          slug: `product/${p.ProductId || p.id}`,
        }))
      );
      setMegaMenuImage(
        "https://images.unsplash.com/photo-1540555700478-4be289fbecef?q=80&w=1200&auto=format&fit=crop"
      );
      setLoadingMega(false);
    } else if (menuType === "bedding") {
      const filtered = products.filter(
        (p) =>
          (p.Name || p.name || "").toLowerCase().includes("bed") ||
          (p.Name || p.name || "").toLowerCase().includes("sheet") ||
          (p.Name || p.name || "").toLowerCase().includes("duvet") ||
          (p.Name || p.name || "").toLowerCase().includes("mattress")
      );
      setMegaMenuData(
        filtered.length > 0
          ? filtered.map((p) => ({
              id: p.ProductId || p.id,
              name: p.Name || p.name,
              slug: `product/${p.ProductId || p.id}`,
            }))
          : categories.map((c) => ({
              id: c.CategoryId || c.id,
              name: c.Name || c.name,
              slug: `category/${c.Slug || c.slug || "bed-sheets"}`,
            }))
      );
      setMegaMenuImage(
        "https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=1200&auto=format&fit=crop"
      );
      setLoadingMega(false);
    } else if (menuType === "towel") {
      const filtered = products.filter(
        (p) =>
          (p.Name || p.name || "").toLowerCase().includes("towel") ||
          (p.Name || p.name || "").toLowerCase().includes("bath")
      );
      setMegaMenuData(
        filtered.length > 0
          ? filtered.map((p) => ({
              id: p.ProductId || p.id,
              name: p.Name || p.name,
              slug: `product/${p.ProductId || p.id}`,
            }))
          : [
              {
                id: 1,
                name: "Luxury Hotel Bath Towel",
                slug: "category/towels",
              },
            ]
      );
      setMegaMenuImage(
        "https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?q=80&w=1200&auto=format&fit=crop"
      );
      setLoadingMega(false);
    } else if (menuType === "others") {
      setMegaMenuData(
        categories.slice(4).map((c) => ({
          id: c.CategoryId || c.id,
          name: c.Name || c.name,
          slug: `category/${c.Slug || c.slug || "others"}`,
        }))
      );
      setMegaMenuImage(
        "https://images.unsplash.com/photo-1616046229478-9901c5536a45?q=80&w=1200&auto=format&fit=crop"
      );
      setLoadingMega(false);
    } else {
      setMegaMenuData([]);
      setLoadingMega(false);
    }
  };

  if (location.pathname === "/login" || location.pathname === "/register")
    return null;

  const totalCartCount = cartItems
    ? cartItems.reduce((acc, item) => acc + item.quantity, 0)
    : 0;

  const handleAuthAction = (actionCallback) => {
    if (!localStorage.getItem("user")) setShowLoginModal(true);
    else actionCallback();
  };

  const handleUserIconClick = () => {
    if (currentUser) {
      setShowUserDropdown(!showUserDropdown);
    } else {
      navigate("/login");
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
    navigate("/login");
  };

  const filteredSearchProducts =
    searchQuery.trim() === ""
      ? []
      : products.filter(
          (item) =>
            (item.Name || item.name || "")
              .toLowerCase()
              .includes(searchQuery.toLowerCase()) ||
            (item.Category || item.category || "")
              .toLowerCase()
              .includes(searchQuery.toLowerCase())
        );

  const handleProductSelect = (id) => {
    setSearchQuery("");
    setShowMobileSearch(false);
    navigate(`/product/${id}`);
  };

  return (
    <>
      <header className="w-full font-sans bg-white shadow-sm z-50">
        {/* ============ DESKTOP TOP BAR (Flipkart-style) ============ */}
        <div className="hidden md:flex bg-[#F1F3F6] border-b border-gray-200 text-[11px] py-1.5 px-4 md:px-8 justify-between items-center">
          <div className="flex gap-5 font-medium text-gray-600">
            <a
              href="mailto:gatewaylinen@gmail.com"
              className="flex items-center gap-1.5 hover:text-[#2874F0] transition-colors"
            >
              <FiMail size={12} className="text-[#2874F0]" />
              gatewaylinen@gmail.com
            </a>
            <span className="text-gray-300">|</span>
            <a
              href="tel:+12049794044"
              className="flex items-center gap-1.5 hover:text-[#2874F0] transition-colors"
            >
              <FiPhone size={12} className="text-[#2874F0]" />
              +1 (204) 979-4044
            </a>
          </div>

          <div className="flex items-center gap-2 text-gray-700 font-medium">
            <FiTruck size={12} className="text-[#FB641B]" />
            <span>
              Free Shipping on orders above{" "}
              <span className="font-bold text-[#031D44]">$350</span>
            </span>
            <Link
              to="/products"
              className="text-[#2874F0] font-bold underline hover:text-[#FB641B] transition-colors ml-1"
            >
              Learn More
            </Link>
          </div>

          <button
            onClick={() => setShowContactModal(true)}
            className="bg-[#2874F0] hover:bg-[#1e5bc7] text-white px-4 py-1 rounded text-[10px] font-bold uppercase tracking-wider shadow-sm cursor-pointer transition-colors flex items-center gap-1.5"
          >
            <FiHeadphones size={11} />
            Need Help?
          </button>
        </div>

        {/* ============ MAIN HEADER (Flipkart Blue) ============ */}
        <div className="bg-[#2874F0] px-4 md:px-8 py-3 flex items-center justify-between gap-4">
          {/* Mobile Menu + Search Toggles */}
          <div className="flex items-center gap-2 lg:hidden shrink-0">
            <button
              onClick={() => setMobileMenuOpen(true)}
              className="text-white p-2 rounded hover:bg-white/10 transition-colors cursor-pointer"
              aria-label="Open menu"
            >
              <FiMenu size={20} />
            </button>
            <button
              onClick={() => setShowMobileSearch(!showMobileSearch)}
              className="text-white p-2 rounded hover:bg-white/10 transition-colors cursor-pointer"
              aria-label="Search"
            >
              <FiSearch size={20} />
            </button>
          </div>

          {/* Desktop Logo */}
          <Link
            to="/"
            className="hidden lg:flex items-center gap-2.5 shrink-0"
          >
            <div className="w-10 h-10 bg-white rounded-lg flex items-center justify-center p-1.5 shadow-md">
              <img
                src={logo}
                alt="Gateway Linen"
                className="w-full h-full object-contain"
              />
            </div>
            <div className="leading-none">
              <p className="text-[17px] font-bold text-white italic">
                Gateway<span className="text-[#FFE500]">Linen</span>
              </p>
              <p className="text-[9px] uppercase tracking-[0.18em] text-white/85 italic mt-0.5 font-medium">
                Explore <span className="text-[#FFE500]">Plus</span> ✦
              </p>
            </div>
          </Link>

          {/* Mobile Logo (Centered) */}
          <div className="flex-1 lg:hidden flex justify-center">
            <Link to="/" className="flex items-center gap-2">
              <div className="w-8 h-8 bg-white rounded-md flex items-center justify-center p-1">
                <img
                  src={logo}
                  alt="Gateway Linen"
                  className="w-full h-full object-contain"
                />
              </div>
              <span className="text-[15px] font-bold text-white italic">
                Gateway<span className="text-[#FFE500]">Linen</span>
              </span>
            </Link>
          </div>

          {/* Desktop Search Bar (Flipkart-style) */}
          <div className="hidden lg:flex flex-1 max-w-[600px] relative">
            <div className="relative w-full">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search for products, brands and more..."
                className="w-full bg-white rounded-md py-2.5 pl-4 pr-12 text-[13px] text-gray-800 placeholder:text-gray-400 focus:outline-none shadow-sm border-2 border-transparent focus:border-[#FFE500] transition-all"
              />
              <button
                type="button"
                className="absolute right-0 top-0 h-full px-4 flex items-center justify-center text-[#2874F0] hover:text-[#FB641B] transition-colors"
                aria-label="Search"
              >
                <FiSearch size={18} />
              </button>

              {/* Search Dropdown */}
              {searchQuery.trim() !== "" && (
                <div className="absolute top-full left-0 right-0 mt-1 bg-white shadow-2xl border border-gray-200 max-h-96 overflow-y-auto z-50 rounded-md">
                  {filteredSearchProducts.length > 0 ? (
                    filteredSearchProducts.map((item) => (
                      <div
                        key={item.ProductId || item.id}
                        onClick={() =>
                          handleProductSelect(item.ProductId || item.id)
                        }
                        className="flex items-center gap-3 p-3 hover:bg-[#F1F3F6] cursor-pointer border-b border-gray-100 last:border-0 transition-colors"
                      >
                        <img
                          src={
                            item.ImageUrl ||
                            item.image ||
                            "https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?q=80&w=500&auto=format&fit=crop"
                          }
                          alt={item.Name || item.name}
                          className="w-12 h-12 object-cover rounded border border-gray-200"
                        />
                        <div className="flex-1 min-w-0">
                          <p className="text-[9px] font-bold text-[#FB641B] uppercase tracking-wider mb-0.5">
                            {item.Category || item.category || "General"}
                          </p>
                          <h4 className="text-[13px] font-semibold text-gray-800 truncate">
                            {item.Name || item.name}
                          </h4>
                        </div>
                      </div>
                    ))
                  ) : (
                    <div className="p-6 text-center text-[12px] text-gray-500">
                      No products found for "{searchQuery}"
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>

          {/* Right Action Icons */}
          <div className="flex items-center gap-1 sm:gap-2 shrink-0">
            {/* User Button */}
            <div className="relative hidden lg:block" ref={dropdownRef}>
              <button
                onClick={handleUserIconClick}
                className="flex items-center gap-2 text-white px-3 py-2 rounded hover:bg-white/10 transition-colors cursor-pointer"
              >
                <FiUser size={18} />
                <span className="text-[13px] font-semibold hidden xl:inline">
                  {currentUser
                    ? currentUser.fullName?.split(" ")[0] || "Account"
                    : "Login"}
                </span>
                {currentUser && (
                  <FiChevronDown
                    size={12}
                    className={`transition-transform ${
                      showUserDropdown ? "rotate-180" : ""
                    }`}
                  />
                )}
              </button>

              {showUserDropdown && currentUser && (
                <div className="absolute right-0 top-full mt-2 w-64 bg-white shadow-2xl border border-gray-200 py-2 z-50 rounded-md animate-in fade-in slide-in-from-top-2 duration-150">
                  <div className="px-5 py-3 border-b border-gray-100 bg-[#F1F3F6]">
                    <p className="text-[9px] text-gray-500 font-bold uppercase tracking-widest mb-0.5">
                      Signed in as
                    </p>
                    <p className="text-[13px] font-bold text-gray-800 truncate">
                      {currentUser.fullName || "Customer"}
                    </p>
                    <p className="text-[10px] text-gray-500 truncate">
                      {currentUser.email}
                    </p>
                  </div>
                  <div className="p-1.5">
                    <Link
                      to="/dashboard"
                      onClick={() => setShowUserDropdown(false)}
                      className="flex items-center gap-3 px-3 py-2.5 text-[13px] font-semibold text-gray-700 hover:bg-[#F1F3F6] rounded transition-colors"
                    >
                      <FiLayout size={14} className="text-[#2874F0]" />
                      My Account
                    </Link>
                    <Link
                      to="/orders"
                      onClick={() => setShowUserDropdown(false)}
                      className="flex items-center gap-3 px-3 py-2.5 text-[13px] font-semibold text-gray-700 hover:bg-[#F1F3F6] rounded transition-colors"
                    >
                      <FiPackage size={14} className="text-[#2874F0]" />
                      My Orders
                    </Link>
                    <button
                      onClick={handleLogoutClick}
                      className="w-full flex items-center gap-3 px-3 py-2.5 text-[13px] font-semibold text-red-600 hover:bg-red-50 rounded transition-colors text-left cursor-pointer"
                    >
                      <FiLogOut size={14} />
                      Logout
                    </button>
                  </div>
                </div>
              )}
            </div>

            {/* Mobile User Icon */}
            <button
              onClick={handleUserIconClick}
              className="lg:hidden text-white p-2 rounded hover:bg-white/10 transition-colors cursor-pointer"
              aria-label="Account"
            >
              <FiUser size={20} />
            </button>

            {/* Wishlist Button */}
            <button
              onClick={() => handleAuthAction(() => toggleWishlistDrawer())}
              className="relative text-white p-2 sm:px-3 rounded hover:bg-white/10 transition-colors cursor-pointer flex items-center gap-1.5"
              aria-label="Wishlist"
            >
              <FiHeart size={19} />
              <span className="hidden xl:inline text-[13px] font-semibold">
                Wishlist
              </span>
              {currentUser && wishlistItems?.length > 0 && (
                <span className="absolute top-0 right-0 sm:right-1 bg-[#FFE500] text-[#031D44] text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                  {wishlistItems.length}
                </span>
              )}
            </button>

            {/* Cart Button */}
            <button
              onClick={() => handleAuthAction(() => toggleCart())}
              className="relative text-white p-2 sm:px-3 rounded hover:bg-white/10 transition-colors cursor-pointer flex items-center gap-1.5"
              aria-label="Cart"
            >
              <FiShoppingCart size={19} />
              <span className="hidden xl:inline text-[13px] font-semibold">
                Cart
              </span>
              {currentUser && totalCartCount > 0 && (
                <span className="absolute top-0 right-0 sm:right-1 bg-[#FFE500] text-[#031D44] text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                  {totalCartCount}
                </span>
              )}
            </button>
          </div>
        </div>

        {/* ============ DESKTOP MEGA MENU NAVIGATION ============ */}
        <div
          className="hidden lg:flex bg-white border-b border-gray-200 px-4 md:px-8 justify-between items-center relative"
          onMouseLeave={() => setActiveMegaMenu(null)}
        >
          <div className="flex items-center">
            <Link
              to="/"
              className="px-4 py-3.5 block font-semibold text-[13px] text-gray-700 hover:text-[#2874F0] transition-colors uppercase tracking-wide"
            >
              Home
            </Link>
            <div onMouseEnter={() => handleMenuHover("products")}>
              <Link
                to="/products"
                className={`px-4 py-3.5 block font-semibold text-[13px] transition-colors uppercase tracking-wide ${
                  activeMegaMenu === "products"
                    ? "text-[#2874F0] border-b-2 border-[#2874F0]"
                    : "text-gray-700 hover:text-[#2874F0]"
                }`}
              >
                All Products
              </Link>
            </div>
            <div onMouseEnter={() => handleMenuHover("categories")}>
              <button
                className={`px-4 py-3.5 flex items-center gap-1.5 font-semibold text-[13px] transition-colors cursor-pointer uppercase tracking-wide ${
                  activeMegaMenu === "categories"
                    ? "text-[#2874F0] border-b-2 border-[#2874F0]"
                    : "text-gray-700 hover:text-[#2874F0]"
                }`}
              >
                Categories
                <FiChevronDown size={13} />
              </button>
            </div>
            <div onMouseEnter={() => handleMenuHover("bedding")}>
              <Link
                to="/category/bed-sheets"
                className={`px-4 py-3.5 block font-semibold text-[13px] transition-colors uppercase tracking-wide ${
                  activeMegaMenu === "bedding"
                    ? "text-[#2874F0] border-b-2 border-[#2874F0]"
                    : "text-gray-700 hover:text-[#2874F0]"
                }`}
              >
                Bedding
              </Link>
            </div>
            <div onMouseEnter={() => handleMenuHover("towel")}>
              <Link
                to="/category/towels"
                className={`px-4 py-3.5 block font-semibold text-[13px] transition-colors uppercase tracking-wide ${
                  activeMegaMenu === "towel"
                    ? "text-[#2874F0] border-b-2 border-[#2874F0]"
                    : "text-gray-700 hover:text-[#2874F0]"
                }`}
              >
                Towels
              </Link>
            </div>
            <div onMouseEnter={() => handleMenuHover("others")}>
              <Link
                to="/products"
                className={`px-4 py-3.5 block font-semibold text-[13px] transition-colors uppercase tracking-wide ${
                  activeMegaMenu === "others"
                    ? "text-[#2874F0] border-b-2 border-[#2874F0]"
                    : "text-gray-700 hover:text-[#2874F0]"
                }`}
              >
                Others
              </Link>
            </div>
            <Link
              to="/contact"
              className="px-4 py-3.5 block font-semibold text-[13px] text-gray-700 hover:text-[#2874F0] transition-colors uppercase tracking-wide"
            >
              Contact
            </Link>

            {/* Mega Menu Dropdown */}
            {activeMegaMenu && (
              <div className="absolute top-full left-0 w-full bg-white shadow-2xl border-t-2 border-[#2874F0] z-50 flex p-6 min-h-[320px] text-gray-800 animate-in fade-in slide-in-from-top-2 duration-200">
                <div className="w-1/4 pr-6 border-r border-gray-200">
                  <div className="text-[#FB641B] text-[10px] uppercase tracking-widest mb-4 font-bold flex items-center gap-1.5">
                    <FiPackage size={12} />
                    {activeMegaMenu.toUpperCase()} ({megaMenuData.length})
                  </div>
                  <ul className="flex flex-col gap-2.5 max-h-64 overflow-y-auto pr-2">
                    {loadingMega ? (
                      <li className="text-gray-400 text-xs font-medium">
                        Loading...
                      </li>
                    ) : megaMenuData.length > 0 ? (
                      megaMenuData.map((item) => (
                        <li key={item.id} className="group">
                          <Link
                            to={`/${item.slug}`}
                            onClick={() => setActiveMegaMenu(null)}
                            className="text-gray-700 hover:text-[#2874F0] text-[13px] font-medium truncate block py-1 transition-colors flex items-center gap-2"
                          >
                            <span className="w-1 h-1 rounded-full bg-gray-300 group-hover:bg-[#2874F0] transition-colors"></span>
                            {item.name}
                          </Link>
                        </li>
                      ))
                    ) : (
                      <li className="text-gray-400 text-xs font-medium">
                        No items found
                      </li>
                    )}
                  </ul>
                </div>
                <div className="w-3/4 pl-6 flex">
                  <div
                    className="w-full h-full max-h-[260px] rounded-lg overflow-hidden shadow-md relative group cursor-pointer"
                    onClick={() => setActiveMegaMenu(null)}
                  >
                    <img
                      src={megaMenuImage}
                      alt="Featured Collection"
                      className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    <div className="absolute bottom-4 left-5 right-5">
                      <span className="inline-block bg-[#FFE500] text-[#031D44] text-[9px] font-bold uppercase tracking-widest px-2 py-0.5 rounded mb-1.5">
                        Featured
                      </span>
                      <h3 className="text-[18px] font-bold text-white capitalize">
                        {activeMegaMenu} Essentials
                      </h3>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Special Offers Button */}
          <div>
            <button className="bg-[#FB641B] hover:bg-[#e55a15] text-white px-4 py-2 rounded text-[11px] font-bold tracking-wider uppercase flex items-center gap-1.5 transition-colors cursor-pointer shadow-sm">
              <FiPercent size={13} />
              Special Offers
            </button>
          </div>
        </div>

        {/* ============ MOBILE SEARCH BAR ============ */}
        {showMobileSearch && (
          <div className="lg:hidden bg-white border-b border-gray-200 px-4 pb-3 pt-2">
            <div className="relative">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search for products..."
                className="w-full border-2 border-[#2874F0] rounded-md py-2.5 pl-4 pr-10 text-[13px] focus:outline-none text-gray-800"
                autoFocus
              />
              <FiSearch
                className="absolute right-3 top-1/2 -translate-y-1/2 text-[#2874F0]"
                size={18}
              />
              {searchQuery.trim() !== "" && (
                <div className="absolute top-full left-0 right-0 mt-1 bg-white shadow-2xl border border-gray-200 max-h-80 overflow-y-auto z-50 rounded-md">
                  {filteredSearchProducts.length > 0 ? (
                    filteredSearchProducts.map((item) => (
                      <div
                        key={item.ProductId || item.id}
                        onClick={() =>
                          handleProductSelect(item.ProductId || item.id)
                        }
                        className="flex items-center gap-3 p-3 hover:bg-[#F1F3F6] cursor-pointer border-b border-gray-100 last:border-0"
                      >
                        <img
                          src={
                            item.ImageUrl ||
                            item.image ||
                            "https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?q=80&w=500&auto=format&fit=crop"
                          }
                          alt={item.Name || item.name}
                          className="w-11 h-11 object-cover rounded border border-gray-200"
                        />
                        <div className="flex-1 min-w-0">
                          <h4 className="text-[13px] font-semibold text-gray-800 truncate">
                            {item.Name || item.name}
                          </h4>
                          <p className="text-[10px] text-gray-500">
                            {item.Category || item.category || "General"}
                          </p>
                        </div>
                      </div>
                    ))
                  ) : (
                    <div className="p-5 text-center text-[12px] text-gray-500">
                      No products found
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>
        )}
      </header>

      <CartDrawer />
      <WishlistDrawer />

      {/* ============ LOGOUT CONFIRMATION MODAL ============ */}
      {showLogoutConfirmModal && (
        <div className="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-white rounded-lg w-full max-w-sm p-6 text-center shadow-2xl border border-gray-200">
            <div className="w-14 h-14 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-red-100">
              <FiLogOut size={24} />
            </div>
            <h3 className="text-[18px] font-bold text-gray-800 mb-2">
              Confirm Logout
            </h3>
            <p className="text-[12px] text-gray-600 mb-6">
              Are you sure you want to sign out of your account?
            </p>
            <div className="flex gap-3">
              <button
                onClick={() => setShowLogoutConfirmModal(false)}
                className="w-1/2 py-3 bg-white border border-gray-300 rounded-md font-semibold text-[12px] text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer"
              >
                Cancel
              </button>
              <button
                onClick={confirmLogout}
                className="w-1/2 py-3 bg-[#FB641B] rounded-md font-semibold text-[12px] text-white hover:bg-[#e55a15] transition-colors cursor-pointer"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ============ LOGIN REQUIRED MODAL ============ */}
      {showLoginModal && (
        <div className="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-white p-7 rounded-lg shadow-2xl w-full max-w-sm text-center relative border border-gray-200">
            <button
              onClick={() => setShowLoginModal(false)}
              className="absolute top-3 right-3 text-gray-400 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 p-1.5 rounded-full transition-colors cursor-pointer"
              aria-label="Close"
            >
              <FiX size={16} />
            </button>
            <div className="w-14 h-14 bg-[#2874F0] text-white rounded-full flex items-center justify-center mx-auto mb-4">
              <FiUser size={26} />
            </div>
            <h3 className="text-[18px] font-bold text-gray-800 mb-2">
              Login Required
            </h3>
            <p className="text-[12px] text-gray-600 mb-6 leading-relaxed">
              Please sign in to access your cart, wishlist, or account features.
            </p>
            <button
              onClick={() => {
                setShowLoginModal(false);
                navigate("/login");
              }}
              className="w-full py-3.5 bg-[#FB641B] text-white text-[12px] font-bold uppercase tracking-wider rounded-md hover:bg-[#e55a15] transition-all shadow-md cursor-pointer"
            >
              Login Now
            </button>
          </div>
        </div>
      )}

      {/* ============ MOBILE MENU DRAWER ============ */}
      {mobileMenuOpen && (
        <div className="fixed inset-0 z-[200] flex lg:hidden">
          <div
            onClick={() => setMobileMenuOpen(false)}
            className="absolute inset-0 bg-black/60 backdrop-blur-sm"
          ></div>

          <div className="relative w-[85%] max-w-sm bg-white h-full shadow-2xl flex flex-col z-[210] animate-in slide-in-from-left duration-300">
            {/* Header */}
            <div className="flex justify-between items-center p-4 bg-[#2874F0]">
              <div className="flex items-center gap-2">
                <div className="w-9 h-9 bg-white rounded-md flex items-center justify-center p-1">
                  <img
                    src={logo}
                    alt="Gateway Linen"
                    className="w-full h-full object-contain"
                  />
                </div>
                <span className="text-[15px] font-bold text-white italic">
                  Gateway<span className="text-[#FFE500]">Linen</span>
                </span>
              </div>
              <button
                onClick={() => setMobileMenuOpen(false)}
                className="text-white p-2 rounded hover:bg-white/10 transition-colors cursor-pointer"
                aria-label="Close menu"
              >
                <FiX size={20} />
              </button>
            </div>

            {/* Auth Section */}
            <div className="p-4 border-b border-gray-200 bg-[#F1F3F6]">
              {currentUser ? (
                <div className="bg-white border border-gray-200 rounded-lg p-4">
                  <div className="flex items-center gap-3 mb-3">
                    <div className="w-11 h-11 bg-[#2874F0] text-white rounded-full flex items-center justify-center font-bold text-lg">
                      {currentUser.fullName
                        ? currentUser.fullName.charAt(0).toUpperCase()
                        : "U"}
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-[9px] text-gray-500 font-bold uppercase tracking-widest mb-0.5">
                        Welcome Back
                      </p>
                      <p className="text-[14px] font-bold text-gray-800 truncate">
                        {currentUser.fullName || "Customer"}
                      </p>
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <button
                      onClick={() => {
                        setMobileMenuOpen(false);
                        navigate("/dashboard");
                      }}
                      className="flex-1 bg-[#2874F0] py-2.5 rounded-md text-[11px] font-bold text-white uppercase tracking-wider cursor-pointer hover:bg-[#1e5bc7] transition-colors"
                    >
                      Dashboard
                    </button>
                    <button
                      onClick={() => {
                        setMobileMenuOpen(false);
                        handleLogoutClick();
                      }}
                      className="flex-1 bg-red-50 text-red-600 border border-red-200 py-2.5 rounded-md text-[11px] font-bold uppercase tracking-wider cursor-pointer hover:bg-red-100 transition-colors"
                    >
                      Logout
                    </button>
                  </div>
                </div>
              ) : (
                <div className="flex gap-2">
                  <button
                    onClick={() => {
                      setMobileMenuOpen(false);
                      navigate("/login");
                    }}
                    className="flex-1 bg-[#FB641B] hover:bg-[#e55a15] text-white py-3 rounded-md text-[12px] font-bold uppercase tracking-wider transition-colors cursor-pointer"
                  >
                    Login
                  </button>
                  <button
                    onClick={() => {
                      setMobileMenuOpen(false);
                      navigate("/register");
                    }}
                    className="flex-1 bg-white border-2 border-[#2874F0] hover:bg-[#2874F0] hover:text-white text-[#2874F0] py-3 rounded-md text-[12px] font-bold uppercase tracking-wider transition-colors cursor-pointer"
                  >
                    Sign Up
                  </button>
                </div>
              )}
            </div>

            {/* Navigation Links */}
            <div className="flex-1 overflow-y-auto bg-white">
              <div className="flex flex-col text-[13px] font-semibold text-gray-700 p-4">
                <Link
                  to="/"
                  onClick={() => setMobileMenuOpen(false)}
                  className="py-3 flex items-center justify-between border-b border-gray-100 hover:text-[#2874F0] transition-colors"
                >
                  Home
                  <FiChevronDown size={14} className="-rotate-90" />
                </Link>
                <Link
                  to="/products"
                  onClick={() => setMobileMenuOpen(false)}
                  className="py-3 flex items-center justify-between border-b border-gray-100 hover:text-[#2874F0] transition-colors"
                >
                  All Products
                  <FiChevronDown size={14} className="-rotate-90" />
                </Link>
                <Link
                  to="/category/bed-sheets"
                  onClick={() => setMobileMenuOpen(false)}
                  className="py-3 flex items-center justify-between border-b border-gray-100 hover:text-[#2874F0] transition-colors"
                >
                  Bedding
                  <FiChevronDown size={14} className="-rotate-90" />
                </Link>
                <Link
                  to="/category/towels"
                  onClick={() => setMobileMenuOpen(false)}
                  className="py-3 flex items-center justify-between border-b border-gray-100 hover:text-[#2874F0] transition-colors"
                >
                  Towels
                  <FiChevronDown size={14} className="-rotate-90" />
                </Link>
                <Link
                  to="/contact"
                  onClick={() => setMobileMenuOpen(false)}
                  className="py-3 flex items-center justify-between border-b border-gray-100 hover:text-[#2874F0] transition-colors"
                >
                  Contact
                  <FiChevronDown size={14} className="-rotate-90" />
                </Link>

                <div className="mt-6 mb-3 flex items-center gap-2">
                  <span className="text-[#FB641B] font-bold text-[10px] tracking-[0.2em]">
                    ALL CATEGORIES
                  </span>
                  <div className="h-px bg-gray-200 flex-1"></div>
                </div>

                <div className="grid grid-cols-1 gap-1">
                  {categories.map((cat) => (
                    <Link
                      key={cat.CategoryId || cat.id}
                      to={`/category/${cat.Slug || cat.slug || (cat.Name || cat.name || "").toLowerCase().replace(/\s+/g, "-")}`}
                      onClick={() => setMobileMenuOpen(false)}
                      className="text-gray-600 text-[13px] font-medium hover:text-[#2874F0] hover:bg-[#F1F3F6] pl-3 py-2.5 rounded transition-all capitalize"
                    >
                      {cat.Name || cat.name}
                    </Link>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ============ CONTACT MODAL ============ */}
      {showContactModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-[200] flex items-center justify-center p-4">
          <div className="bg-white border border-gray-200 max-w-md w-full p-7 shadow-2xl relative rounded-lg">
            <button
              onClick={() => setShowContactModal(false)}
              className="absolute top-3 right-3 text-gray-400 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 p-1.5 rounded-full transition-colors cursor-pointer"
              aria-label="Close"
            >
              <FiX size={16} />
            </button>
            <div className="w-14 h-14 bg-[#2874F0] text-white rounded-full flex items-center justify-center mb-4">
              <FiHeadphones size={24} />
            </div>
            <h3 className="text-[18px] font-bold text-gray-800 mb-1">
              Gateway Linen HQ
            </h3>
            <p className="text-[12px] text-gray-500 mb-5">
              Official contact information and location.
            </p>
            <div className="space-y-3.5 mb-6 bg-[#F1F3F6] p-4 rounded-lg border border-gray-200 text-[12px]">
              <div className="flex items-start gap-3 text-gray-700">
                <FiMapPin
                  className="text-[#2874F0] mt-0.5 flex-shrink-0"
                  size={16}
                />
                <span className="leading-relaxed">
                  <strong className="text-gray-800 block mb-0.5">
                    Address:
                  </strong>
                  9 Mapleridge crescent, Brandon R7A6P8, Manitoba, Canada
                </span>
              </div>
              <div className="w-full h-px bg-gray-200"></div>
              <div className="flex items-center gap-3 text-gray-700">
                <FiPhone className="text-[#2874F0] flex-shrink-0" size={16} />
                <span>
                  <strong className="text-gray-800 mr-1">Phone:</strong>
                  <a
                    href="tel:+12049794044"
                    className="hover:text-[#FB641B] transition-colors"
                  >
                    +1 (204) 979-4044
                  </a>
                </span>
              </div>
              <div className="w-full h-px bg-gray-200"></div>
              <div className="flex items-start gap-3 text-gray-700">
                <FiMail
                  className="text-[#2874F0] mt-0.5 flex-shrink-0"
                  size={16}
                />
                <span>
                  <strong className="text-gray-800 mr-1">Email:</strong>
                  <a
                    href="mailto:gatewaylinen@gmail.com"
                    className="hover:text-[#FB641B] transition-colors"
                  >
                    gatewaylinen@gmail.com
                  </a>
                </span>
              </div>
            </div>
            <button
              onClick={() => setShowContactModal(false)}
              className="w-full py-3 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[12px] font-bold uppercase tracking-wider rounded-md transition-all cursor-pointer"
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