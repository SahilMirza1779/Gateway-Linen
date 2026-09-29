import { useState, useEffect } from "react";
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
  FiAlertCircle,
  FiMapPin,
} from "react-icons/fi";
import logo from "../assets/Gatewaylinen-logo.png";
import CartDrawer from "./CartDrawer";
import WishlistDrawer from "./WishlistDrawer";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

const Navbar = () => {
  const location = useLocation();
  const navigate = useNavigate();

  const [showContactModal, setShowContactModal] = useState(false);
  const [showUserDropdown, setShowUserDropdown] = useState(false);
  const [showAuthDropdown, setShowAuthDropdown] = useState(false);
  const [showLoginModal, setShowLoginModal] = useState(false);
  const [showLogoutConfirmModal, setShowLogoutConfirmModal] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [showMobileSearch, setShowMobileSearch] = useState(false);

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
      // Bedding Image
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
      // Towel Image
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
      // Other Quality Image
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

  const handleLogoutClick = () => {
    setShowUserDropdown(false);
    setShowLogoutConfirmModal(true);
  };

  const confirmLogout = () => {
    localStorage.removeItem("user");
    setCurrentUser(null);
    setShowLogoutConfirmModal(false);
    navigate("/");
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
      <a
        href="https://wa.me/12049794044"
        target="_blank"
        rel="noopener noreferrer"
        className="fixed bottom-6 right-6 z-[100] bg-[#25D366] text-white p-3.5 md:p-4 rounded-full shadow-2xl hover:scale-110 transition-transform flex items-center justify-center cursor-pointer"
        title="Chat with us on WhatsApp"
      >
        <svg
          viewBox="0 0 24 24"
          width="32"
          height="32"
          fill="currentColor"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51h-.57c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
        </svg>
      </a>

      <header className="w-full font-sans bg-white">
        {/* TIER 1: Top Bar - UPDATED (Bada padding, dark font, removed tapu_parikh) */}
        <div className="hidden md:flex border-b border-gray-200 bg-[#F8F9FA] text-[12px] py-3 px-4 md:px-8 justify-between items-center shadow-sm">
          <div className="flex gap-6 font-bold text-gray-900 tracking-wide">
            <a
              href="mailto:gatewaylinen@gmail.com"
              className="flex items-center gap-2 cursor-pointer hover:text-[#031D44] transition-colors"
            >
              <FiMail size={15} className="text-[#B58E58]" />{" "}
              gatewaylinen@gmail.com
            </a>
            <a
              href="tel:+12049794044"
              className="flex items-center gap-2 cursor-pointer hover:text-[#031D44] transition-colors"
            >
              <FiPhone size={15} className="text-[#B58E58]" /> +1 (204) 979-4044
            </a>
          </div>
          <div className="bg-[#F0F2F0] px-3 py-1 rounded-full text-gray-700 font-medium text-[11px]">
            Free Shipping on orders above $350 -{" "}
            <Link
              to="/products"
              className="font-bold underline hover:text-[#B58E58]"
            >
              Learn More
            </Link>
          </div>
          <div>
            <button
              onClick={() => setShowContactModal(true)}
              className="bg-[#031D44] hover:bg-[#B58E58] text-white px-5 py-1.5 rounded-full font-bold transition-colors text-[11px] shadow-sm cursor-pointer"
            >
              Need Help?
            </button>
          </div>
        </div>

        {/* TIER 2: Middle Bar */}
        <div className="px-4 md:px-8 py-3 flex items-center justify-between border-b border-gray-100">
          <div className="flex items-center gap-2.5 w-1/3">
            <button
              onClick={() => setMobileMenuOpen(true)}
              className="lg:hidden text-gray-500 hover:text-[#B58E58] p-1"
            >
              <FiMenu size={24} />
            </button>
            <button
              onClick={() => setShowMobileSearch(!showMobileSearch)}
              className="lg:hidden text-gray-500 hover:text-[#B58E58] p-1 ml-1"
            >
              <FiSearch size={20} />
            </button>

            <Link to="/" className="flex items-center gap-2">
              <img src={logo} alt="Gateway Linen" className="h-8 md:h-10" />
              <span className="font-serif font-bold text-base md:text-lg text-[#031D44] tracking-wide">
                Gateway Linen
              </span>
            </Link>
          </div>

          <div className="hidden lg:flex flex-1 justify-center relative px-4">
            <div className="relative w-full max-w-[380px]">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search..."
                className="w-full border border-gray-300 bg-white rounded-full py-1.5 px-4 text-xs text-gray-700 focus:outline-none focus:border-[#B58E58] transition-colors"
              />
              <FiSearch
                className="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400"
                size={15}
              />

              {searchQuery.trim() !== "" && (
                <div className="absolute top-full left-0 right-0 mt-2 bg-white shadow-2xl border border-gray-100 max-h-80 overflow-y-auto z-50 p-2 rounded-xl">
                  {filteredSearchProducts.length > 0 ? (
                    filteredSearchProducts.map((item) => (
                      <div
                        key={item.ProductId || item.id}
                        onClick={() =>
                          handleProductSelect(item.ProductId || item.id)
                        }
                        className="flex items-center gap-3 p-2.5 hover:bg-gray-50 cursor-pointer rounded-lg"
                      >
                        <img
                          src={
                            item.ImageUrl ||
                            item.image ||
                            "https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?q=80&w=500&auto=format&fit=crop"
                          }
                          alt={item.Name || item.name}
                          className="w-9 h-9 object-cover rounded border border-gray-100"
                        />
                        <div>
                          <p className="text-[9px] font-bold text-[#B58E58] uppercase">
                            {item.Category || item.category || "General"}
                          </p>
                          <h4 className="text-xs font-semibold text-[#031D44]">
                            {item.Name || item.name}
                          </h4>
                        </div>
                      </div>
                    ))
                  ) : (
                    <div className="p-4 text-center text-xs text-gray-400">
                      No products found
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>

          <div className="flex items-center justify-end gap-5 text-gray-500 w-1/3">
            {currentUser ? (
              <div className="relative hidden sm:block">
                <button
                  onClick={() => setShowUserDropdown(!showUserDropdown)}
                  className="hover:text-[#031D44] transition-colors"
                >
                  <FiUser size={22} />
                </button>
                {showUserDropdown && (
                  <div className="absolute right-0 mt-3 w-48 bg-white shadow-xl border border-gray-100 py-2 z-50 rounded-lg">
                    <Link
                      to="/dashboard"
                      onClick={() => setShowUserDropdown(false)}
                      className="block px-4 py-2 text-xs font-semibold text-[#031D44] hover:bg-gray-50"
                    >
                      Dashboard
                    </Link>
                    <button
                      onClick={handleLogoutClick}
                      className="w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 flex items-center gap-2"
                    >
                      <FiLogOut size={13} /> Logout
                    </button>
                  </div>
                )}
              </div>
            ) : (
              <div className="relative hidden sm:block">
                <button
                  onClick={() => setShowAuthDropdown(!showAuthDropdown)}
                  className="hover:text-[#031D44] transition-colors"
                >
                  <FiUser size={22} />
                </button>
                {showAuthDropdown && (
                  <div className="absolute right-0 mt-3 w-44 bg-white shadow-xl border border-gray-100 py-2 z-50 rounded-lg">
                    <Link
                      to="/login"
                      onClick={() => setShowAuthDropdown(false)}
                      className="block px-4 py-2 text-xs font-semibold text-[#031D44] hover:bg-gray-50 transition-colors"
                    >
                      Sign In
                    </Link>
                    <Link
                      to="/register"
                      onClick={() => setShowAuthDropdown(false)}
                      className="block px-4 py-2 text-xs font-semibold text-[#031D44] hover:bg-gray-50 transition-colors border-t border-gray-100"
                    >
                      Register
                    </Link>
                  </div>
                )}
              </div>
            )}

            <button
              onClick={() => handleAuthAction(() => toggleWishlistDrawer())}
              className="relative hover:text-[#031D44] transition-colors"
            >
              <FiHeart size={22} />
              {currentUser && wishlistItems?.length > 0 && (
                <span className="absolute -top-1 -right-1.5 bg-[#B58E58] text-white text-[9px] font-bold w-3.5 h-3.5 rounded-full flex items-center justify-center">
                  {wishlistItems.length}
                </span>
              )}
            </button>

            <button
              onClick={() => handleAuthAction(() => toggleCart())}
              className="relative hover:text-[#031D44] transition-colors"
            >
              <FiShoppingCart size={22} />
              {currentUser && totalCartCount > 0 && (
                <span className="absolute -top-1 -right-1.5 bg-[#B58E58] text-white text-[9px] font-bold w-3.5 h-3.5 rounded-full flex items-center justify-center">
                  {totalCartCount}
                </span>
              )}
            </button>
          </div>
        </div>

        {/* TIER 3: Category Bar with Dynamic Images for Bedding, Towel & Others */}
        <div
          className="hidden lg:flex bg-[#031D44] text-white px-8 justify-between items-center text-xs font-medium relative"
          onMouseLeave={() => setActiveMegaMenu(null)}
        >
          <div className="flex items-center">
            <Link
              to="/"
              className="px-4 py-2.5 block hover:bg-white hover:text-[#031D44] transition-colors"
            >
              Home
            </Link>

            <div onMouseEnter={() => handleMenuHover("products")}>
              <Link
                to="/products"
                className={`px-4 py-2.5 block transition-colors ${activeMegaMenu === "products" ? "bg-white text-[#031D44]" : "hover:bg-white hover:text-[#031D44]"}`}
              >
                All Products
              </Link>
            </div>

            <div onMouseEnter={() => handleMenuHover("categories")}>
              <button
                className={`px-4 py-2.5 flex items-center gap-1 transition-colors cursor-pointer ${activeMegaMenu === "categories" ? "bg-white text-[#031D44]" : "hover:bg-white hover:text-[#031D44]"}`}
              >
                Categories <FiChevronDown size={13} className="opacity-70" />
              </button>
            </div>

            <div onMouseEnter={() => handleMenuHover("bedding")}>
              <Link
                to="/category/bed-sheets"
                className={`px-4 py-2.5 block transition-colors ${activeMegaMenu === "bedding" ? "bg-white text-[#031D44]" : "hover:bg-white hover:text-[#031D44]"}`}
              >
                Bedding
              </Link>
            </div>

            <div onMouseEnter={() => handleMenuHover("towel")}>
              <Link
                to="/category/towels"
                className={`px-4 py-2.5 block transition-colors ${activeMegaMenu === "towel" ? "bg-white text-[#031D44]" : "hover:bg-white hover:text-[#031D44]"}`}
              >
                Towel
              </Link>
            </div>

            <div onMouseEnter={() => handleMenuHover("others")}>
              <Link
                to="/products"
                className={`px-4 py-2.5 block transition-colors ${activeMegaMenu === "others" ? "bg-white text-[#031D44]" : "hover:bg-white hover:text-[#031D44]"}`}
              >
                Others
              </Link>
            </div>

            <Link
              to="/contact"
              className="px-4 py-2.5 block hover:bg-white hover:text-[#031D44] transition-colors"
            >
              Contact
            </Link>

            {activeMegaMenu && (
              <div className="absolute top-full left-0 w-full bg-white shadow-2xl border-t border-gray-200 z-50 flex p-6 min-h-[300px] text-gray-800">
                <div className="w-1/4 pr-6 border-r border-gray-100">
                  <div className="border border-gray-300 text-gray-500 text-[9px] uppercase px-2.5 py-0.5 inline-block mb-3 font-bold rounded">
                    {activeMegaMenu.toUpperCase()} DATA ({megaMenuData.length})
                  </div>
                  <ul className="flex flex-col gap-2.5 max-h-60 overflow-y-auto">
                    {loadingMega ? (
                      <li className="text-gray-400 text-xs">Loading...</li>
                    ) : megaMenuData.length > 0 ? (
                      megaMenuData.map((item) => (
                        <li key={item.id}>
                          <Link
                            to={`/${item.slug}`}
                            onClick={() => setActiveMegaMenu(null)}
                            className="text-gray-700 hover:text-[#B58E58] text-xs font-medium truncate block py-0.5"
                          >
                            {item.name}
                          </Link>
                        </li>
                      ))
                    ) : (
                      <li className="text-gray-400 text-xs">No items found</li>
                    )}
                  </ul>
                </div>

                {/* Dynamic Image for Bedding, Towel and Others */}
                <div className="w-3/4 pl-6 flex justify-center items-center">
                  <img
                    src={megaMenuImage}
                    alt="Featured Collection"
                    className="w-full h-60 object-cover rounded-sm shadow-md transition-all duration-300"
                  />
                </div>
              </div>
            )}
          </div>

          <div className="py-1">
            <button className="bg-[#F0EAE1] text-[#031D44] px-4 py-1 rounded-full font-bold text-xs flex items-center gap-1 hover:bg-white transition-colors">
              Special Offers <FiChevronDown size={12} />
            </button>
          </div>
        </div>

        {/* MOBILE SEARCH BAR */}
        {showMobileSearch && (
          <div className="lg:hidden px-4 pb-3 bg-white border-b border-gray-100">
            <div className="relative mt-2">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search..."
                className="w-full border border-gray-300 bg-white rounded-full py-2 px-4 text-xs focus:outline-none focus:border-[#031D44]"
              />
              <FiSearch
                className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"
                size={15}
              />
              {searchQuery.trim() !== "" && (
                <div className="absolute top-full left-0 right-0 mt-2 bg-white shadow-2xl border border-gray-100 max-h-60 overflow-y-auto z-50 p-2 rounded-xl">
                  {filteredSearchProducts.length > 0 ? (
                    filteredSearchProducts.map((item) => (
                      <div
                        key={item.ProductId || item.id}
                        onClick={() =>
                          handleProductSelect(item.ProductId || item.id)
                        }
                        className="flex items-center gap-2.5 p-2 hover:bg-gray-50 cursor-pointer rounded-lg"
                      >
                        <img
                          src={
                            item.ImageUrl ||
                            item.image ||
                            "https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?q=80&w=500&auto=format&fit=crop"
                          }
                          alt={item.Name || item.name}
                          className="w-8 h-8 object-cover rounded border border-gray-100"
                        />
                        <div>
                          <h4 className="text-[11px] font-semibold text-[#031D44]">
                            {item.Name || item.name}
                          </h4>
                        </div>
                      </div>
                    ))
                  ) : (
                    <div className="p-4 text-center text-[10px] text-gray-400">
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

      {/* MODALS */}
      {showLogoutConfirmModal && (
        <div className="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4">
          <div className="bg-white rounded-xl w-full max-w-xs p-5 text-center shadow-2xl">
            <div className="w-10 h-10 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-3">
              <FiAlertCircle size={20} />
            </div>
            <h3 className="text-base font-bold text-[#031D44] mb-3">
              Confirm Logout
            </h3>
            <div className="flex gap-2.5">
              <button
                onClick={() => setShowLogoutConfirmModal(false)}
                className="w-1/2 py-2 bg-gray-100 rounded-lg font-bold text-xs text-[#031D44]"
              >
                Cancel
              </button>
              <button
                onClick={confirmLogout}
                className="w-1/2 py-2 bg-red-600 rounded-lg font-bold text-xs text-white"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      )}

      {showLoginModal && (
        <div className="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4">
          <div className="bg-white p-6 rounded-xl shadow-2xl w-full max-w-xs text-center relative">
            <button
              onClick={() => setShowLoginModal(false)}
              className="absolute top-3 right-3 text-gray-400 hover:text-gray-800"
            >
              <FiX size={18} />
            </button>
            <h3 className="text-base font-bold text-[#031D44] mb-4">
              Login Required
            </h3>
            <p className="text-xs text-gray-500 mb-5">
              Please log in to continue using this feature.
            </p>
            <button
              onClick={() => {
                setShowLoginModal(false);
                navigate("/login");
              }}
              className="w-full py-2.5 bg-[#031D44] text-white text-xs font-bold rounded-lg hover:bg-[#B58E58]"
            >
              Login Now
            </button>
          </div>
        </div>
      )}

      {/* MOBILE MENU */}
      {mobileMenuOpen && (
        <div className="fixed inset-0 z-[200] flex lg:hidden">
          <div
            onClick={() => setMobileMenuOpen(false)}
            className="absolute inset-0 bg-black/60"
          ></div>
          <div className="relative w-72 bg-white h-full shadow-2xl flex flex-col p-5 overflow-y-auto">
            <div className="flex justify-between pb-4 border-b border-gray-100 mb-4">
              <img src={logo} alt="Gateway Linen" className="h-7" />
              <button onClick={() => setMobileMenuOpen(false)}>
                <FiX size={20} className="text-gray-500" />
              </button>
            </div>
            <div className="flex flex-col gap-4 text-xs font-medium text-[#031D44]">
              <Link to="/" onClick={() => setMobileMenuOpen(false)}>
                Home
              </Link>
              <Link to="/products" onClick={() => setMobileMenuOpen(false)}>
                All Products
              </Link>
              <Link
                to="/category/bed-sheets"
                onClick={() => setMobileMenuOpen(false)}
              >
                Bedding
              </Link>
              <Link
                to="/category/towels"
                onClick={() => setMobileMenuOpen(false)}
              >
                Towel
              </Link>
              <Link to="/contact" onClick={() => setMobileMenuOpen(false)}>
                Contact
              </Link>
              <div className="text-[#B58E58] font-bold pt-2">
                All Categories
              </div>
              {categories.map((cat) => (
                <Link
                  key={cat.CategoryId || cat.id}
                  to={`/category/${cat.Slug || cat.slug || (cat.Name || cat.name || "").toLowerCase().replace(/\s+/g, "-")}`}
                  onClick={() => setMobileMenuOpen(false)}
                  className="text-gray-600 pl-3"
                >
                  - {cat.Name || cat.name}
                </Link>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* CONTACT MODAL - UPDATED (tapu_parikh removed here too) */}
      {showContactModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-[200] flex items-center justify-center p-3">
          <div className="bg-white border border-gray-200 max-w-md w-full p-6 shadow-2xl relative rounded-xl">
            <button
              onClick={() => setShowContactModal(false)}
              className="absolute top-3 right-3 text-gray-400 hover:text-gray-800 bg-gray-50 p-1 rounded-full"
            >
              <FiX size={16} />
            </button>
            <h3 className="text-lg font-bold text-[#031D44] mb-1">
              Gateway Linen HQ
            </h3>
            <p className="text-xs text-gray-500 mb-4">
              Official contact information and location.
            </p>
            <div className="space-y-3 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-100 text-xs">
              <div className="flex items-start gap-2.5 text-gray-700">
                <FiMapPin className="text-[#B58E58] mt-0.5" size={15} />
                <span>
                  <strong>Address:</strong> 9 Mapleridge crescent, Brandon
                  R7A6P8, Manitoba, Canada
                </span>
              </div>
              <div className="flex items-center gap-2.5 text-gray-700">
                <FiPhone className="text-[#B58E58]" size={15} />
                <span>
                  <strong>Phone:</strong> +1 (204) 979-4044
                </span>
              </div>
              <div className="flex items-start gap-2.5 text-gray-700">
                <FiMail className="text-[#B58E58] mt-0.5" size={15} />
                <span>
                  <strong>Email:</strong> gatewaylinen@gmail.com
                </span>
              </div>
            </div>
            <button
              onClick={() => setShowContactModal(false)}
              className="w-full py-2.5 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold rounded-lg transition-colors"
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
