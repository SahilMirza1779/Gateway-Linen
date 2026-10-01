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
    "https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=1200&auto=format&fit=crop",
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
      "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/categories/api.php?action=get_categories",
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
      "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/products/api.php?action=get_products",
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
        })),
      );
      setMegaMenuImage(
        "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=1200&auto=format&fit=crop",
      );
      setLoadingMega(false);
    } else if (menuType === "products") {
      setMegaMenuData(
        products.map((p) => ({
          id: p.ProductId || p.id,
          name: p.Name || p.name,
          slug: `product/${p.ProductId || p.id}`,
        })),
      );
      setMegaMenuImage(
        "https://images.unsplash.com/photo-1540555700478-4be289fbecef?q=80&w=1200&auto=format&fit=crop",
      );
      setLoadingMega(false);
    } else if (menuType === "bedding") {
      const filtered = products.filter(
        (p) =>
          (p.Name || p.name || "").toLowerCase().includes("bed") ||
          (p.Name || p.name || "").toLowerCase().includes("sheet") ||
          (p.Name || p.name || "").toLowerCase().includes("duvet") ||
          (p.Name || p.name || "").toLowerCase().includes("mattress"),
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
            })),
      );
      setMegaMenuImage(
        "https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=1200&auto=format&fit=crop",
      );
      setLoadingMega(false);
    } else if (menuType === "towel") {
      const filtered = products.filter(
        (p) =>
          (p.Name || p.name || "").toLowerCase().includes("towel") ||
          (p.Name || p.name || "").toLowerCase().includes("bath"),
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
            ],
      );
      setMegaMenuImage(
        "https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?q=80&w=1200&auto=format&fit=crop",
      );
      setLoadingMega(false);
    } else if (menuType === "others") {
      setMegaMenuData(
        categories.slice(4).map((c) => ({
          id: c.CategoryId || c.id,
          name: c.Name || c.name,
          slug: `category/${c.Slug || c.slug || "others"}`,
        })),
      );
      setMegaMenuImage(
        "https://images.unsplash.com/photo-1616046229478-9901c5536a45?q=80&w=1200&auto=format&fit=crop",
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
              .includes(searchQuery.toLowerCase()),
        );

  const handleProductSelect = (id) => {
    setSearchQuery("");
    setShowMobileSearch(false);
    navigate(`/product/${id}`);
  };

  return (
    <>
      <header className="w-full font-sans bg-white shadow-sm z-50">
        {/* Desktop Top Bar */}
        <div className="hidden md:flex border-b border-[#E5DCD0] bg-[#FAF7F2] text-[12px] py-2.5 px-4 md:px-8 justify-between items-center">
          <div className="flex gap-6 font-medium text-gray-600 tracking-wide">
            <a
              href="mailto:gatewaylinen@gmail.com"
              className="flex items-center gap-2 cursor-pointer hover:text-[#031D44] transition-colors"
            >
              <FiMail size={14} className="text-[#B58E58]" />{" "}
              gatewaylinen@gmail.com
            </a>
            <a
              href="tel:+12049794044"
              className="flex items-center gap-2 cursor-pointer hover:text-[#031D44] transition-colors"
            >
              <FiPhone size={14} className="text-[#B58E58]" /> +1 (204) 979-4044
            </a>
          </div>
          <div className="bg-white border border-[#E5DCD0] px-4 py-1 rounded-full text-gray-700 font-medium text-[11px] shadow-2xs">
            Free Shipping on orders above $350 -{" "}
            <Link
              to="/products"
              className="font-bold underline hover:text-[#B58E58] text-[#031D44]"
            >
              Learn More
            </Link>
          </div>
          <div>
            <button
              onClick={() => setShowContactModal(true)}
              className="bg-[#031D44] hover:bg-[#B58E58] text-white px-5 py-1.5 rounded-full font-bold transition-colors text-[10px] shadow-sm cursor-pointer uppercase tracking-widest"
            >
              Need Help?
            </button>
          </div>
        </div>

        {/* Main Header */}
        <div className="px-4 md:px-8 py-3.5 md:py-4 flex items-center justify-between border-b border-[#E5DCD0]">
          {/* Mobile Menu & Search Toggles */}
          <div className="flex items-center gap-2 w-1/3 md:w-1/4 lg:w-1/3">
            <button
              onClick={() => setMobileMenuOpen(true)}
              className="lg:hidden text-[#031D44] bg-[#FAF7F2] border border-[#E5DCD0] hover:bg-[#E5DCD0] transition-colors p-2 rounded-lg cursor-pointer"
            >
              <FiMenu size={20} />
            </button>
            <button
              onClick={() => setShowMobileSearch(!showMobileSearch)}
              className="lg:hidden text-[#031D44] bg-white border border-[#E5DCD0] hover:bg-[#FAF7F2] transition-colors p-2 rounded-lg cursor-pointer"
            >
              <FiSearch size={20} />
            </button>
            <Link to="/" className="items-center gap-2 hidden lg:flex">
              <img src={logo} alt="Gateway Linen" className="h-10 md:h-12" />
              <span className="font-serif font-bold text-xl text-[#031D44] tracking-wide">
                Gateway Linen
              </span>
            </Link>
          </div>

          {/* Mobile Logo (Centered) */}
          <div className="flex-1 lg:hidden flex justify-center">
            <Link to="/">
              <img
                src={logo}
                alt="Gateway Linen"
                className="h-9 object-contain"
              />
            </Link>
          </div>

          {/* Desktop Search */}
          <div className="hidden lg:flex flex-1 justify-center relative px-4">
            <div className="relative w-full max-w-[450px]">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search premium products..."
                className="w-full border border-[#E5DCD0] bg-[#FAF7F2]/50 rounded-full py-2.5 px-5 text-[13px] text-[#031D44] focus:outline-none focus:border-[#B58E58] focus:bg-white transition-all shadow-sm"
              />
              <FiSearch
                className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"
                size={16}
              />
              {searchQuery.trim() !== "" && (
                <div className="absolute top-full left-0 right-0 mt-2 bg-white shadow-2xl border border-[#E5DCD0] max-h-80 overflow-y-auto z-50 p-2 rounded-2xl">
                  {filteredSearchProducts.length > 0 ? (
                    filteredSearchProducts.map((item) => (
                      <div
                        key={item.ProductId || item.id}
                        onClick={() =>
                          handleProductSelect(item.ProductId || item.id)
                        }
                        className="flex items-center gap-3 p-2.5 hover:bg-[#FAF7F2] cursor-pointer rounded-xl transition-colors"
                      >
                        <img
                          src={
                            item.ImageUrl ||
                            item.image ||
                            "https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?q=80&w=500&auto=format&fit=crop"
                          }
                          alt={item.Name || item.name}
                          className="w-10 h-10 object-cover rounded-md border border-[#E5DCD0]"
                        />
                        <div>
                          <p className="text-[9px] font-bold text-[#B58E58] uppercase tracking-wider mb-0.5">
                            {item.Category || item.category || "General"}
                          </p>
                          <h4 className="text-xs font-bold text-[#031D44]">
                            {item.Name || item.name}
                          </h4>
                        </div>
                      </div>
                    ))
                  ) : (
                    <div className="p-5 text-center text-xs text-gray-500 font-medium">
                      No products found
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>

          {/* Right Action Icons */}
          <div className="flex items-center justify-end gap-3 sm:gap-5 w-1/3 md:w-1/4 lg:w-1/3">
            <div className="relative hidden lg:block" ref={dropdownRef}>
              <button
                onClick={handleUserIconClick}
                className={`transition-all p-2.5 rounded-full cursor-pointer border ${currentUser ? "bg-[#031D44] text-[#B58E58] border-[#031D44] hover:bg-[#B58E58] hover:text-white" : "bg-[#FAF7F2] text-[#031D44] border-[#E5DCD0] hover:text-[#B58E58] hover:bg-white shadow-sm"}`}
              >
                <FiUser size={18} />
              </button>
              {showUserDropdown && currentUser && (
                <div className="absolute right-0 top-[110%] w-56 bg-white shadow-2xl border border-[#E5DCD0] py-2 z-50 rounded-[16px] animate-in fade-in slide-in-from-top-2 duration-200">
                  <div className="px-5 py-3 border-b border-[#E5DCD0] bg-[#FAF7F2] rounded-t-[14px]">
                    <p className="text-[9.5px] text-gray-400 font-bold uppercase tracking-widest mb-0.5">
                      Signed in as
                    </p>
                    <p className="text-[13px] font-bold text-[#031D44] truncate">
                      {currentUser.fullName || "Customer"}
                    </p>
                    <p className="text-[10px] text-gray-500 truncate">
                      {currentUser.email}
                    </p>
                  </div>
                  <div className="p-2 space-y-1">
                    <Link
                      to="/dashboard"
                      onClick={() => setShowUserDropdown(false)}
                      className="flex items-center gap-3 px-3 py-2.5 text-[12px] font-bold text-[#031D44] hover:bg-[#FAF7F2] rounded-xl transition-colors"
                    >
                      <FiLayout size={14} className="text-[#B58E58]" /> Account
                      Dashboard
                    </Link>
                    <button
                      onClick={handleLogoutClick}
                      className="w-full flex items-center gap-3 px-3 py-2.5 text-[12px] font-bold text-red-500 hover:bg-red-50 rounded-xl transition-colors text-left cursor-pointer"
                    >
                      <FiLogOut size={14} /> Secure Logout
                    </button>
                  </div>
                </div>
              )}
            </div>

            <button
              onClick={() => handleAuthAction(() => toggleWishlistDrawer())}
              className="relative text-[#031D44] bg-[#FAF7F2] border border-[#E5DCD0] hover:bg-white hover:text-[#B58E58] transition-colors p-2.5 rounded-full cursor-pointer shadow-sm"
            >
              <FiHeart size={18} />
              {currentUser && wishlistItems?.length > 0 && (
                <span className="absolute -top-1 -right-1.5 bg-[#B58E58] text-white text-[9px] font-bold w-4.5 h-4.5 rounded-full flex items-center justify-center border border-white">
                  {wishlistItems.length}
                </span>
              )}
            </button>

            <button
              onClick={() => handleAuthAction(() => toggleCart())}
              className="relative text-[#031D44] bg-[#FAF7F2] border border-[#E5DCD0] hover:bg-white hover:text-[#B58E58] transition-colors p-2.5 rounded-full cursor-pointer shadow-sm"
            >
              <FiShoppingCart size={18} />
              {currentUser && totalCartCount > 0 && (
                <span className="absolute -top-1 -right-1.5 bg-[#031D44] text-white text-[9px] font-bold w-4.5 h-4.5 rounded-full flex items-center justify-center border border-white">
                  {totalCartCount}
                </span>
              )}
            </button>
          </div>
        </div>

        {/* Desktop Mega Menu Navigation */}
        <div
          className="hidden lg:flex bg-[#031D44] text-white px-8 justify-between items-center relative shadow-inner"
          onMouseLeave={() => setActiveMegaMenu(null)}
        >
          <div className="flex items-center">
            <Link
              to="/"
              className="px-5 py-3.5 block font-bold uppercase tracking-widest text-[11px] hover:bg-[#B58E58] hover:text-white transition-colors"
            >
              Home
            </Link>
            <div onMouseEnter={() => handleMenuHover("products")}>
              <Link
                to="/products"
                className={`px-5 py-3.5 block font-bold uppercase tracking-widest text-[11px] transition-colors ${activeMegaMenu === "products" ? "bg-white text-[#031D44]" : "hover:bg-[#B58E58] hover:text-white"}`}
              >
                All Products
              </Link>
            </div>
            <div onMouseEnter={() => handleMenuHover("categories")}>
              <button
                className={`px-5 py-3.5 flex items-center gap-1.5 font-bold uppercase tracking-widest text-[11px] transition-colors cursor-pointer ${activeMegaMenu === "categories" ? "bg-white text-[#031D44]" : "hover:bg-[#B58E58] hover:text-white"}`}
              >
                Categories{" "}
                <FiChevronDown
                  size={14}
                  className={
                    activeMegaMenu === "categories"
                      ? "text-[#031D44]"
                      : "text-[#B58E58]"
                  }
                />
              </button>
            </div>
            <div onMouseEnter={() => handleMenuHover("bedding")}>
              <Link
                to="/category/bed-sheets"
                className={`px-5 py-3.5 block font-bold uppercase tracking-widest text-[11px] transition-colors ${activeMegaMenu === "bedding" ? "bg-white text-[#031D44]" : "hover:bg-[#B58E58] hover:text-white"}`}
              >
                Bedding
              </Link>
            </div>
            <div onMouseEnter={() => handleMenuHover("towel")}>
              <Link
                to="/category/towels"
                className={`px-5 py-3.5 block font-bold uppercase tracking-widest text-[11px] transition-colors ${activeMegaMenu === "towel" ? "bg-white text-[#031D44]" : "hover:bg-[#B58E58] hover:text-white"}`}
              >
                Towel
              </Link>
            </div>
            <div onMouseEnter={() => handleMenuHover("others")}>
              <Link
                to="/products"
                className={`px-5 py-3.5 block font-bold uppercase tracking-widest text-[11px] transition-colors ${activeMegaMenu === "others" ? "bg-white text-[#031D44]" : "hover:bg-[#B58E58] hover:text-white"}`}
              >
                Others
              </Link>
            </div>
            <Link
              to="/contact"
              className="px-5 py-3.5 block font-bold uppercase tracking-widest text-[11px] hover:bg-[#B58E58] hover:text-white transition-colors"
            >
              Contact
            </Link>

            {activeMegaMenu && (
              <div className="absolute top-full left-0 w-full bg-white shadow-2xl border-t border-[#E5DCD0] z-50 flex p-8 min-h-[350px] text-gray-800 animate-in fade-in slide-in-from-top-2 duration-300">
                <div className="w-1/4 pr-8 border-r border-[#E5DCD0]">
                  <div className="border border-[#B58E58]/30 bg-[#FAF7F2] text-[#B58E58] text-[9.5px] uppercase tracking-widest px-3 py-1.5 inline-block mb-4 font-bold rounded-lg shadow-sm">
                    {activeMegaMenu.toUpperCase()} CATALOG (
                    {megaMenuData.length})
                  </div>
                  <ul className="flex flex-col gap-3 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                    {loadingMega ? (
                      <li className="text-gray-400 text-xs font-medium">
                        Loading collection...
                      </li>
                    ) : megaMenuData.length > 0 ? (
                      megaMenuData.map((item) => (
                        <li
                          key={item.id}
                          className="group flex items-center gap-2"
                        >
                          <div className="w-1.5 h-1.5 rounded-full bg-[#E5DCD0] group-hover:bg-[#B58E58] transition-colors"></div>
                          <Link
                            to={`/${item.slug}`}
                            onClick={() => setActiveMegaMenu(null)}
                            className="text-[#031D44] hover:text-[#B58E58] text-[13px] font-bold truncate block py-0.5 transition-colors"
                          >
                            {item.name}
                          </Link>
                        </li>
                      ))
                    ) : (
                      <li className="text-gray-400 text-xs font-medium">
                        No items found in this category
                      </li>
                    )}
                  </ul>
                </div>
                <div className="w-3/4 pl-8 flex justify-center items-center">
                  <div
                    className="w-full h-full max-h-[280px] rounded-2xl overflow-hidden shadow-md border border-[#E5DCD0] relative group cursor-pointer"
                    onClick={() => setActiveMegaMenu(null)}
                  >
                    <img
                      src={megaMenuImage}
                      alt="Featured Collection"
                      className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                    />
                    <div className="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
                    <div className="absolute bottom-6 left-6 right-6">
                      <div className="bg-white/90 backdrop-blur-sm p-4 rounded-xl shadow-lg border border-white/50 inline-block">
                        <span className="text-[10px] font-bold text-[#B58E58] uppercase tracking-widest block mb-1">
                          Featured Collection
                        </span>
                        <h3 className="text-xl font-serif font-bold text-[#031D44]">
                          {activeMegaMenu.charAt(0).toUpperCase() +
                            activeMegaMenu.slice(1)}{" "}
                          Essentials
                        </h3>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          <div className="py-1.5 pr-2">
            <button className="bg-[#B58E58] text-white px-5 py-2 rounded-full font-bold text-[10px] tracking-widest uppercase flex items-center gap-1.5 hover:bg-white hover:text-[#031D44] transition-colors shadow-sm cursor-pointer">
              Special Offers <FiChevronDown size={14} />
            </button>
          </div>
        </div>

        {/* Mobile Search Bar Toggle */}
        {showMobileSearch && (
          <div className="lg:hidden px-4 pb-4 pt-2 bg-white border-b border-[#E5DCD0]">
            <div className="relative mt-2">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search products..."
                className="w-full border border-[#E5DCD0] bg-[#FAF7F2] rounded-xl py-3 px-5 text-sm focus:outline-none focus:border-[#B58E58] shadow-inner text-[#031D44]"
              />
              <FiSearch
                className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"
                size={18}
              />
              {searchQuery.trim() !== "" && (
                <div className="absolute top-full left-0 right-0 mt-2 bg-white shadow-2xl border border-[#E5DCD0] max-h-60 overflow-y-auto z-50 p-2 rounded-xl">
                  {filteredSearchProducts.length > 0 ? (
                    filteredSearchProducts.map((item) => (
                      <div
                        key={item.ProductId || item.id}
                        onClick={() =>
                          handleProductSelect(item.ProductId || item.id)
                        }
                        className="flex items-center gap-3 p-2.5 hover:bg-[#FAF7F2] cursor-pointer rounded-lg"
                      >
                        <img
                          src={
                            item.ImageUrl ||
                            item.image ||
                            "https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?q=80&w=500&auto=format&fit=crop"
                          }
                          alt={item.Name || item.name}
                          className="w-10 h-10 object-cover rounded border border-[#E5DCD0]"
                        />
                        <div>
                          <h4 className="text-xs font-bold text-[#031D44]">
                            {item.Name || item.name}
                          </h4>
                        </div>
                      </div>
                    ))
                  ) : (
                    <div className="p-4 text-center text-xs text-gray-500">
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

      {/* Logout Confirmation Modal */}
      {showLogoutConfirmModal && (
        <div className="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-[#FAF7F2] rounded-[24px] w-full max-w-sm p-8 text-center shadow-2xl border border-[#E5DCD0]">
            <div className="w-14 h-14 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-red-200">
              <FiLogOut size={24} />
            </div>
            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-2">
              Confirm Logout
            </h3>
            <p className="text-[12px] text-gray-600 mb-6 font-light">
              Are you sure you want to securely sign out of your account?
            </p>
            <div className="flex gap-3">
              <button
                onClick={() => setShowLogoutConfirmModal(false)}
                className="w-1/2 py-3 bg-white border border-[#E5DCD0] rounded-xl font-bold text-[11px] text-gray-700 hover:bg-gray-50 transition-colors uppercase tracking-wider cursor-pointer shadow-sm"
              >
                Cancel
              </button>
              <button
                onClick={confirmLogout}
                className="w-1/2 py-3 bg-red-600 rounded-xl font-bold text-[11px] text-white hover:bg-red-700 transition-colors shadow-md uppercase tracking-wider cursor-pointer"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Login Requirement Modal */}
      {showLoginModal && (
        <div className="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-[#FAF7F2] p-8 rounded-[24px] shadow-2xl w-full max-w-sm text-center relative border border-[#E5DCD0]">
            <button
              onClick={() => setShowLoginModal(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-[#031D44] bg-white border border-[#E5DCD0] p-1.5 rounded-full transition-colors cursor-pointer shadow-sm"
            >
              <FiX size={16} />
            </button>
            <div className="w-14 h-14 bg-[#031D44] text-[#B58E58] rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-md">
              <FiUser size={26} />
            </div>
            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-2">
              Login Required
            </h3>
            <p className="text-[12px] text-gray-600 mb-6 font-light leading-relaxed">
              Please sign in securely to access your cart, wishlist, or account
              features.
            </p>
            <button
              onClick={() => {
                setShowLoginModal(false);
                navigate("/login");
              }}
              className="w-full py-3.5 bg-[#031D44] text-white text-[11px] font-bold uppercase tracking-widest rounded-xl hover:bg-[#B58E58] transition-all shadow-md cursor-pointer"
            >
              Secure Login
            </button>
          </div>
        </div>
      )}

      {/* MOBILE FULL-SCREEN MENU DRAWER */}
      {mobileMenuOpen && (
        <div className="fixed inset-0 z-[200] flex lg:hidden">
          {/* Overlay */}
          <div
            onClick={() => setMobileMenuOpen(false)}
            className="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
          ></div>

          {/* Drawer Container */}
          <div className="relative w-[85%] max-w-sm bg-[#FAF7F2] h-full shadow-2xl flex flex-col z-[210] animate-in slide-in-from-left duration-300 border-r border-[#E5DCD0]">
            {/* Header */}
            <div className="flex justify-between items-center p-5 border-b border-[#E5DCD0] bg-white">
              <img
                src={logo}
                alt="Gateway Linen"
                className="h-9 object-contain"
              />
              <button
                onClick={() => setMobileMenuOpen(false)}
                className="bg-[#FAF7F2] hover:bg-[#E5DCD0] p-2 rounded-xl border border-[#E5DCD0] transition-colors cursor-pointer text-[#031D44]"
              >
                <FiX size={20} />
              </button>
            </div>

            {/* Auth Section */}
            <div className="p-5 border-b border-[#E5DCD0] bg-white shadow-sm">
              {currentUser ? (
                <div className="bg-[#FAF7F2] border border-[#E5DCD0] rounded-2xl p-4 shadow-sm">
                  <div className="flex items-center gap-3.5 mb-4">
                    <div className="w-11 h-11 bg-[#031D44] text-[#B58E58] rounded-xl flex items-center justify-center font-bold text-lg shadow-inner">
                      {currentUser.fullName ? (
                        currentUser.fullName.charAt(0).toUpperCase()
                      ) : (
                        <FiUser />
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-[9px] text-gray-500 font-bold uppercase tracking-widest mb-0.5">
                        Welcome Back
                      </p>
                      <p className="text-sm font-serif font-bold text-[#031D44] truncate">
                        {currentUser.fullName || "Customer"}
                      </p>
                    </div>
                  </div>
                  <div className="flex gap-2.5">
                    <button
                      onClick={() => {
                        setMobileMenuOpen(false);
                        navigate("/dashboard");
                      }}
                      className="flex-1 bg-white border border-[#E5DCD0] py-2.5 rounded-xl text-[10px] font-bold text-[#031D44] uppercase tracking-wider shadow-2xs cursor-pointer hover:border-[#031D44] transition-colors"
                    >
                      Dashboard
                    </button>
                    <button
                      onClick={() => {
                        setMobileMenuOpen(false);
                        handleLogoutClick();
                      }}
                      className="flex-1 bg-red-50 text-red-600 border border-red-200 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider cursor-pointer hover:bg-red-100 transition-colors shadow-2xs"
                    >
                      Logout
                    </button>
                  </div>
                </div>
              ) : (
                <div className="flex gap-3">
                  <button
                    onClick={() => {
                      setMobileMenuOpen(false);
                      navigate("/login");
                    }}
                    className="flex-1 bg-[#031D44] hover:bg-[#B58E58] text-white py-3.5 rounded-xl text-[11px] font-bold uppercase tracking-widest shadow-md transition-colors cursor-pointer"
                  >
                    Login
                  </button>
                  <button
                    onClick={() => {
                      setMobileMenuOpen(false);
                      navigate("/register");
                    }}
                    className="flex-1 bg-[#FAF7F2] border border-[#E5DCD0] hover:bg-white text-[#031D44] py-3.5 rounded-xl text-[11px] font-bold uppercase tracking-widest transition-colors shadow-2xs cursor-pointer"
                  >
                    Sign Up
                  </button>
                </div>
              )}
            </div>

            {/* Navigation Links Scrollable Area */}
            <div className="flex-1 overflow-y-auto bg-[#FAF7F2]">
              <div className="flex flex-col text-[12px] font-bold text-[#031D44] uppercase tracking-widest p-5">
                <Link
                  to="/"
                  onClick={() => setMobileMenuOpen(false)}
                  className="py-3.5 flex items-center gap-3.5 border-b border-[#E5DCD0]/60 hover:text-[#B58E58] transition-colors"
                >
                  <div className="w-1.5 h-1.5 bg-[#B58E58] rounded-full"></div>{" "}
                  Home
                </Link>
                <Link
                  to="/products"
                  onClick={() => setMobileMenuOpen(false)}
                  className="py-3.5 flex items-center gap-3.5 border-b border-[#E5DCD0]/60 hover:text-[#B58E58] transition-colors"
                >
                  <div className="w-1.5 h-1.5 bg-[#B58E58] rounded-full"></div>{" "}
                  All Products
                </Link>
                <Link
                  to="/category/bed-sheets"
                  onClick={() => setMobileMenuOpen(false)}
                  className="py-3.5 flex items-center gap-3.5 border-b border-[#E5DCD0]/60 hover:text-[#B58E58] transition-colors"
                >
                  <div className="w-1.5 h-1.5 bg-[#B58E58] rounded-full"></div>{" "}
                  Bedding
                </Link>
                <Link
                  to="/category/towels"
                  onClick={() => setMobileMenuOpen(false)}
                  className="py-3.5 flex items-center gap-3.5 border-b border-[#E5DCD0]/60 hover:text-[#B58E58] transition-colors"
                >
                  <div className="w-1.5 h-1.5 bg-[#B58E58] rounded-full"></div>{" "}
                  Towel
                </Link>
                <Link
                  to="/contact"
                  onClick={() => setMobileMenuOpen(false)}
                  className="py-3.5 flex items-center gap-3.5 hover:text-[#B58E58] transition-colors"
                >
                  <div className="w-1.5 h-1.5 bg-[#B58E58] rounded-full"></div>{" "}
                  Contact
                </Link>

                <div className="mt-8 mb-3 flex items-center gap-2">
                  <span className="text-[#B58E58] font-bold text-[10px] tracking-[0.2em]">
                    ALL CATEGORIES
                  </span>
                  <div className="h-px bg-[#E5DCD0] flex-1"></div>
                </div>

                <div className="grid grid-cols-1 gap-1.5">
                  {categories.map((cat) => (
                    <Link
                      key={cat.CategoryId || cat.id}
                      to={`/category/${cat.Slug || cat.slug || (cat.Name || cat.name || "").toLowerCase().replace(/\s+/g, "-")}`}
                      onClick={() => setMobileMenuOpen(false)}
                      className="text-gray-600 text-xs font-medium hover:text-[#031D44] hover:bg-white pl-3.5 py-2.5 rounded-lg border-l-2 border-transparent hover:border-[#B58E58] transition-all capitalize shadow-sm"
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

      {/* Contact Info Modal */}
      {showContactModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-[200] flex items-center justify-center p-4">
          <div className="bg-[#FAF7F2] border border-[#E5DCD0] max-w-md w-full p-8 shadow-2xl relative rounded-[24px]">
            <button
              onClick={() => setShowContactModal(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-[#031D44] bg-white border border-[#E5DCD0] p-1.5 rounded-full transition-colors cursor-pointer shadow-sm"
            >
              <FiX size={16} />
            </button>
            <div className="w-14 h-14 bg-[#031D44] text-[#B58E58] rounded-2xl flex items-center justify-center mb-4 shadow-md">
              <FiHeadphones size={24} />
            </div>
            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-1">
              Gateway Linen HQ
            </h3>
            <p className="text-[12px] text-gray-500 mb-6 font-light">
              Official wholesale contact information and location.
            </p>
            <div className="space-y-4 mb-8 bg-white p-5 rounded-xl border border-[#E5DCD0] shadow-sm text-[12px]">
              <div className="flex items-start gap-3 text-gray-700">
                <FiMapPin
                  className="text-[#B58E58] mt-0.5 flex-shrink-0"
                  size={16}
                />
                <span className="leading-relaxed">
                  <strong className="text-[#031D44] block mb-0.5">
                    Address:
                  </strong>{" "}
                  9 Mapleridge crescent, Brandon R7A6P8, Manitoba, Canada
                </span>
              </div>
              <div className="w-full h-px bg-[#E5DCD0]/50"></div>
              <div className="flex items-center gap-3 text-gray-700">
                <FiPhone className="text-[#B58E58] flex-shrink-0" size={16} />
                <span>
                  <strong className="text-[#031D44] mr-1">Phone:</strong>{" "}
                  <a
                    href="tel:+12049794044"
                    className="hover:text-[#B58E58] transition-colors"
                  >
                    +1 (204) 979-4044
                  </a>
                </span>
              </div>
              <div className="w-full h-px bg-[#E5DCD0]/50"></div>
              <div className="flex items-start gap-3 text-gray-700">
                <FiMail
                  className="text-[#B58E58] mt-0.5 flex-shrink-0"
                  size={16}
                />
                <span>
                  <strong className="text-[#031D44] mr-1">Email:</strong>{" "}
                  <a
                    href="mailto:gatewaylinen@gmail.com"
                    className="hover:text-[#B58E58] transition-colors"
                  >
                    gatewaylinen@gmail.com
                  </a>
                </span>
              </div>
            </div>
            <button
              onClick={() => setShowContactModal(false)}
              className="w-full py-3.5 bg-[#031D44] hover:bg-[#B58E58] text-white text-[11px] font-bold uppercase tracking-widest rounded-xl transition-all shadow-md cursor-pointer"
            >
              Close Window
            </button>
          </div>
        </div>
      )}
    </>
  );
};

export default Navbar;
