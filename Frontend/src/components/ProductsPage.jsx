import { useState, useEffect, useRef } from "react";
import { useNavigate } from "react-router-dom";
import {
  FiShoppingCart,
  FiFilter,
  FiGrid,
  FiList,
  FiChevronDown,
  FiSliders,
  FiStar,
  FiHeart,
  FiPackage,
  FiArrowRight,
  FiChevronRight,
  FiPercent,
  FiTruck,
  FiShield,
  FiAward,
  FiX,
  FiTrendingUp,
  FiEye,
  FiZap,
} from "react-icons/fi";
import { useCart } from "../context/CartContext";

// Trending showcase
const TRENDING_IMAGES = [
  {
    id: "t1",
    name: "Luxury Bath Towels",
    image:
      "https://images.pexels.com/photos/5591664/pexels-photo-5591664.jpeg?auto=compress&cs=tinysrgb&w=400",
    tag: "Best Seller",
  },
  {
    id: "t2",
    name: "Bed Sheet Sets",
    image:
      "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=400",
    tag: "Top Rated",
  },
  {
    id: "t3",
    name: "Bath Mats",
    image:
      "https://images.pexels.com/photos/6585757/pexels-photo-6585757.jpeg?auto=compress&cs=tinysrgb&w=400",
    tag: "New",
  },
  {
    id: "t4",
    name: "Face Washers",
    image:
      "https://images.unsplash.com/photo-1616627561950-9f746e330187?w=400&q=80",
    tag: "Deal",
  },
];

const ProductsPage = () => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedCategory, setSelectedCategory] = useState("ALL");
  const [sortBy, setSortBy] = useState("popularity");
  const [viewMode, setViewMode] = useState("grid");
  const [showSortMenu, setShowSortMenu] = useState(false);
  const [showMobileFilter, setShowMobileFilter] = useState(false);
  const [priceRange, setPriceRange] = useState([0, 1000]);
  const [quickView, setQuickView] = useState(null);
  const [wishlist, setWishlist] = useState({});
  const navigate = useNavigate();
  const { addToCart } = useCart();

  useEffect(() => {
    window.scrollTo(0, 0);
    const fetchAllProducts = async () => {
      try {
        const response = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/products/api.php",
          {
            method: "GET",
            headers: { "Content-Type": "application/json" },
          }
        );
        const result = await response.json();
        if (result.success && result.data && result.data.items) {
          const formattedProducts = result.data.items.map((item) => {
            let rawImg =
              item.imageUrl || item.ImageUrl || item.image || item.Image || "";
            if (!rawImg && item.images && item.images.length > 0) {
              rawImg =
                item.images[0].imageUrl || item.images[0].ImageUrl || "";
            }
            let finalImg =
              "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=600";
            if (rawImg) {
              if (rawImg.startsWith("http")) finalImg = rawImg;
              else if (rawImg.startsWith("/")) finalImg = `http://localhost${rawImg}`;
              else {
                const cleanPath = rawImg.replace(/^\/+/, "");
                if (cleanPath.includes("Gateway-Linen"))
                  finalImg = `http://localhost/${cleanPath}`;
                else
                  finalImg = `http://localhost/Gateway-Linen/GatewayLinenAdmin-main/${cleanPath}`;
              }
            }
            return { ...item, resolvedImage: finalImg };
          });
          setProducts(formattedProducts);
        }
      } catch (error) {
        console.error("Error fetching catalog products:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchAllProducts();
  }, []);

  const categories = [
    "ALL",
    "TOWELS",
    "BED SHEETS",
    "MATTRESS PADS",
    "PILLOWS",
    "BLANKETS",
    "OTHERS",
  ];

  const filteredProducts = products
    .filter(
      (item) =>
        selectedCategory === "ALL" ||
        item.categoryName?.toUpperCase() === selectedCategory.toUpperCase()
    )
    .filter((item) => {
      const price = Number(item.basePrice || 0);
      return price >= priceRange[0] && price <= priceRange[1];
    });

  const sortedProducts = [...filteredProducts].sort((a, b) => {
    const priceA = Number(a.basePrice || 0);
    const priceB = Number(b.basePrice || 0);
    switch (sortBy) {
      case "price-low":
        return priceA - priceB;
      case "price-high":
        return priceB - priceA;
      case "name":
        return (a.name || "").localeCompare(b.name || "");
      default:
        return 0;
    }
  });

  const getMockRating = (id) => {
    const seed = Number(String(id).slice(-2)) || 50;
    const rating = 3.8 + (seed % 12) / 10;
    const reviews = 20 + ((seed * 7) % 200);
    return { rating: Math.min(rating, 5), reviews };
  };

  const toggleWishlist = (id, e) => {
    e?.stopPropagation();
    setWishlist((prev) => ({ ...prev, [id]: !prev[id] }));
  };

  const clearFilters = () => {
    setSelectedCategory("ALL");
    setPriceRange([0, 1000]);
  };

  const hasActiveFilters =
    selectedCategory !== "ALL" || priceRange[0] !== 0 || priceRange[1] !== 1000;

  return (
    <div className="w-full bg-[#F1F3F6] min-h-screen font-sans pb-16">
      {/* ============ BREADCRUMB ============ */}
      <div className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3">
          <div className="flex items-center gap-2 text-[12px] text-gray-500">
            <span
              onClick={() => navigate("/")}
              className="hover:text-[#2874F0] cursor-pointer font-medium"
            >
              Home
            </span>
            <FiChevronRight size={12} />
            <span
              onClick={() => navigate("/products")}
              className="hover:text-[#2874F0] cursor-pointer font-medium"
            >
              Collections
            </span>
            <FiChevronRight size={12} />
            <span className="text-[#2874F0] font-bold uppercase">
              All Products
            </span>
          </div>
        </div>
      </div>

      {/* ============ HERO BANNER ============ */}
      <div className="bg-gradient-to-r from-[#2874F0] via-[#1e5bc7] to-[#0d47a1] text-white relative overflow-hidden">
        <div
          className="absolute inset-0 opacity-[0.08]"
          style={{
            backgroundImage:
              "radial-gradient(circle, white 1px, transparent 1px)",
            backgroundSize: "20px 20px",
          }}
        />
        <div className="absolute -top-20 -right-20 w-80 h-80 rounded-full bg-[#FFE500]/10 blur-3xl" />

        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-12 relative">
          <div className="grid md:grid-cols-2 gap-6 md:gap-8 items-center">
            <div>
              <div className="inline-flex items-center gap-2 bg-white/15 border border-white/20 px-3 py-1 rounded-full mb-3">
                <FiZap size={11} className="text-[#FFE500]" />
                <span className="text-[10px] font-bold tracking-widest uppercase">
                  Premium Collection
                </span>
              </div>
              <h1 className="text-3xl sm:text-4xl md:text-5xl font-bold leading-tight mb-3">
                Explore Our
                <br />
                <span className="text-[#FFE500]">Premium Linens</span>
              </h1>
              <p className="text-[13px] md:text-[14px] text-white/80 mb-5 max-w-lg leading-relaxed">
                Complete collection of 5-star hotel-grade linens, towels, and
                bedding essentials — trusted by 10,000+ partners.
              </p>

              {/* Stats row */}
              <div className="grid grid-cols-3 gap-4 pt-4 border-t border-white/15 max-w-md">
                {[
                  { value: `${sortedProducts.length}+`, label: "Products" },
                  { value: "4.9★", label: "Rating" },
                  { value: "48hr", label: "Delivery" },
                ].map((stat, i) => (
                  <div key={i}>
                    <p className="text-[20px] font-bold text-[#FFE500] leading-none">
                      {stat.value}
                    </p>
                    <p className="text-[10px] text-white/70 uppercase tracking-wider font-bold mt-1">
                      {stat.label}
                    </p>
                  </div>
                ))}
              </div>
            </div>

            {/* Hero mini images */}
            <div className="hidden md:flex justify-end gap-3">
              {TRENDING_IMAGES.slice(0, 3).map((item, i) => (
                <div
                  key={item.id}
                  className={`rounded-2xl overflow-hidden shadow-2xl border-4 border-white/20 ${
                    i === 1 ? "w-32 h-40 mt-6" : "w-28 h-36"
                  } ${i === 0 ? "-rotate-6" : i === 2 ? "rotate-6" : ""}`}
                >
                  <img
                    src={item.image}
                    alt={item.name}
                    className="w-full h-full object-cover"
                  />
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* ============ TRENDING NOW STRIP ============ */}
      <div className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3">
          <div className="flex items-center gap-3 overflow-x-auto scrollbar-hide">
            <div className="flex items-center gap-1.5 shrink-0">
              <FiTrendingUp size={14} className="text-[#FB641B]" />
              <span className="text-[11px] font-bold uppercase tracking-wider text-gray-700">
                Trending:
              </span>
            </div>
            {TRENDING_IMAGES.map((item) => (
              <button
                key={item.id}
                onClick={() =>
                  setSelectedCategory(item.name.split(" ")[0].toUpperCase())
                }
                className="flex items-center gap-2 bg-[#F1F3F6] hover:bg-[#EAF2FF] border border-gray-200 hover:border-[#2874F0] px-3 py-1.5 rounded-full transition-all shrink-0 group"
              >
                <div className="w-6 h-6 rounded-full overflow-hidden shrink-0">
                  <img
                    src={item.image}
                    alt={item.name}
                    className="w-full h-full object-cover"
                  />
                </div>
                <span className="text-[11px] font-bold text-gray-700 group-hover:text-[#2874F0] transition-colors whitespace-nowrap">
                  {item.name}
                </span>
                <span className="text-[9px] font-bold bg-[#FB641B] text-white px-1.5 py-0.5 rounded-full uppercase tracking-wider">
                  {item.tag}
                </span>
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* ============ STICKY TOOLBAR ============ */}
      <div className="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3">
          <div className="flex items-center justify-between gap-3 flex-wrap">
            <div className="flex items-center gap-3 flex-wrap">
              <button
                onClick={() => setShowMobileFilter(true)}
                className="lg:hidden inline-flex items-center gap-2 px-3.5 py-2 bg-[#EAF2FF] text-[#2874F0] rounded text-[11px] font-bold uppercase tracking-wider"
              >
                <FiFilter size={13} /> Filter
              </button>
              <p className="text-[12px] text-gray-600 font-medium">
                <span className="font-bold text-gray-800">
                  {sortedProducts.length}
                </span>{" "}
                {sortedProducts.length === 1 ? "result" : "results"}
              </p>

              {/* Active filter chips */}
              {hasActiveFilters && (
                <div className="flex items-center gap-2 flex-wrap">
                  {selectedCategory !== "ALL" && (
                    <span className="inline-flex items-center gap-1.5 bg-[#EAF2FF] text-[#2874F0] text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full">
                      {selectedCategory}
                      <button
                        onClick={() => setSelectedCategory("ALL")}
                        className="hover:bg-[#2874F0] hover:text-white rounded-full p-0.5 transition-colors"
                      >
                        <FiX size={10} />
                      </button>
                    </span>
                  )}
                  {(priceRange[0] !== 0 || priceRange[1] !== 1000) && (
                    <span className="inline-flex items-center gap-1.5 bg-[#EAF2FF] text-[#2874F0] text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full">
                      ${priceRange[0]} - ${priceRange[1]}
                      <button
                        onClick={() => setPriceRange([0, 1000])}
                        className="hover:bg-[#2874F0] hover:text-white rounded-full p-0.5 transition-colors"
                      >
                        <FiX size={10} />
                      </button>
                    </span>
                  )}
                  <button
                    onClick={clearFilters}
                    className="text-[10px] text-red-600 hover:text-red-700 font-bold uppercase tracking-wider underline"
                  >
                    Clear All
                  </button>
                </div>
              )}
            </div>

            <div className="flex items-center gap-2">
              <div className="relative">
                <button
                  onClick={() => setShowSortMenu(!showSortMenu)}
                  className="inline-flex items-center gap-2 px-3.5 py-2 bg-white border border-gray-300 hover:border-[#2874F0] rounded text-[11px] font-bold uppercase tracking-wider text-gray-700 transition-colors"
                >
                  <FiSliders size={13} />
                  {sortBy === "popularity"
                    ? "Popular"
                    : sortBy === "price-low"
                    ? "Price ↑"
                    : sortBy === "price-high"
                    ? "Price ↓"
                    : "Name"}
                  <FiChevronDown
                    size={12}
                    className={`transition-transform ${
                      showSortMenu ? "rotate-180" : ""
                    }`}
                  />
                </button>
                {showSortMenu && (
                  <div className="absolute right-0 top-full mt-1 bg-white border border-gray-200 rounded shadow-lg min-w-[180px] z-40 overflow-hidden">
                    {[
                      { val: "popularity", label: "Popularity" },
                      { val: "price-low", label: "Price: Low to High" },
                      { val: "price-high", label: "Price: High to Low" },
                      { val: "name", label: "Name: A to Z" },
                    ].map((opt) => (
                      <button
                        key={opt.val}
                        onClick={() => {
                          setSortBy(opt.val);
                          setShowSortMenu(false);
                        }}
                        className={`w-full text-left px-4 py-2.5 text-[12px] hover:bg-[#F1F3F6] transition-colors ${
                          sortBy === opt.val
                            ? "bg-[#EAF2FF] text-[#2874F0] font-bold"
                            : "text-gray-700"
                        }`}
                      >
                        {opt.label}
                      </button>
                    ))}
                  </div>
                )}
              </div>

              <div className="hidden sm:flex items-center border border-gray-300 rounded overflow-hidden">
                <button
                  onClick={() => setViewMode("grid")}
                  className={`p-2 transition-colors ${
                    viewMode === "grid"
                      ? "bg-[#2874F0] text-white"
                      : "bg-white text-gray-600 hover:bg-gray-50"
                  }`}
                >
                  <FiGrid size={14} />
                </button>
                <button
                  onClick={() => setViewMode("list")}
                  className={`p-2 transition-colors ${
                    viewMode === "list"
                      ? "bg-[#2874F0] text-white"
                      : "bg-white text-gray-600 hover:bg-gray-50"
                  }`}
                >
                  <FiList size={14} />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ============ MAIN LAYOUT ============ */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-6">
        <div className="grid grid-cols-1 lg:grid-cols-[250px_1fr] gap-6">
          {/* ===== FILTER SIDEBAR ===== */}
          <aside className="hidden lg:block">
            <div className="bg-white rounded-lg border border-gray-200 p-5 sticky top-24 max-h-[calc(100vh-120px)] overflow-y-auto scrollbar-hide">
              <div className="flex items-center justify-between mb-5 pb-3 border-b border-gray-100">
                <div className="flex items-center gap-2">
                  <FiFilter size={14} className="text-[#2874F0]" />
                  <h3 className="text-[13px] font-bold text-gray-800 uppercase tracking-wider">
                    Filters
                  </h3>
                </div>
                {hasActiveFilters && (
                  <button
                    onClick={clearFilters}
                    className="text-[10px] font-bold text-red-600 hover:text-red-700 uppercase tracking-wider"
                  >
                    Clear
                  </button>
                )}
              </div>

              {/* Categories */}
              <div className="mb-6 pb-6 border-b border-gray-100">
                <h4 className="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">
                  Category
                </h4>
                <div className="space-y-2">
                  {categories.map((cat) => (
                    <label
                      key={cat}
                      className="flex items-center gap-2.5 cursor-pointer group"
                    >
                      <input
                        type="radio"
                        name="category"
                        checked={selectedCategory === cat}
                        onChange={() => setSelectedCategory(cat)}
                        className="w-3.5 h-3.5 accent-[#2874F0] cursor-pointer"
                      />
                      <span
                        className={`text-[12px] transition-colors flex-1 ${
                          selectedCategory === cat
                            ? "text-[#2874F0] font-bold"
                            : "text-gray-600 group-hover:text-[#2874F0]"
                        }`}
                      >
                        {cat === "ALL" ? "All Products" : cat}
                      </span>
                    </label>
                  ))}
                </div>
              </div>

              {/* Price */}
              <div className="mb-6 pb-6 border-b border-gray-100">
                <h4 className="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">
                  Price Range
                </h4>
                <div className="space-y-2.5">
                  {[
                    { label: "Under $25", range: [0, 25] },
                    { label: "$25 - $50", range: [25, 50] },
                    { label: "$50 - $100", range: [50, 100] },
                    { label: "Above $100", range: [100, 1000] },
                  ].map((r, i) => (
                    <label
                      key={i}
                      className="flex items-center gap-2.5 cursor-pointer group"
                    >
                      <input
                        type="radio"
                        name="price"
                        checked={
                          priceRange[0] === r.range[0] &&
                          priceRange[1] === r.range[1]
                        }
                        onChange={() => setPriceRange(r.range)}
                        className="w-3.5 h-3.5 accent-[#2874F0] cursor-pointer"
                      />
                      <span className="text-[12px] text-gray-600 group-hover:text-[#2874F0] transition-colors">
                        {r.label}
                      </span>
                    </label>
                  ))}
                </div>
              </div>

              {/* Trust badges */}
              <div className="mb-5 space-y-2.5">
                {[
                  { icon: FiTruck, text: "Free shipping over $350" },
                  { icon: FiShield, text: "100% Secure payments" },
                  { icon: FiAward, text: "Premium quality guaranteed" },
                ].map((b, i) => (
                  <div key={i} className="flex items-start gap-2 text-[11px]">
                    <b.icon
                      size={13}
                      className="text-[#2874F0] mt-0.5 shrink-0"
                    />
                    <span className="text-gray-600">{b.text}</span>
                  </div>
                ))}
              </div>

              {/* Support card */}
              <div className="bg-gradient-to-br from-[#2874F0] to-[#0d47a1] rounded-lg p-4 text-white">
                <div className="w-10 h-10 bg-[#FFE500] text-[#031D44] rounded-full flex items-center justify-center mb-2">
                  <FiPackage size={16} />
                </div>
                <p className="text-[12px] font-bold mb-1">Bulk Wholesale?</p>
                <p className="text-[10px] text-white/80 mb-3">
                  Get special pricing on 50+ units
                </p>
                <button
                  onClick={() => navigate("/contact")}
                  className="w-full py-2 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[10px] font-bold uppercase tracking-wider rounded transition-colors"
                >
                  Get Quote
                </button>
              </div>
            </div>
          </aside>

          {/* ===== PRODUCTS ===== */}
          <main>
            {loading ? (
              <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                {[...Array(6)].map((_, i) => (
                  <div
                    key={i}
                    className="bg-white rounded-lg border border-gray-200 overflow-hidden animate-pulse"
                  >
                    <div className="aspect-square bg-gray-200" />
                    <div className="p-3">
                      <div className="h-3 bg-gray-200 rounded w-1/3 mb-2" />
                      <div className="h-3 bg-gray-200 rounded w-3/4 mb-2" />
                      <div className="h-3 bg-gray-200 rounded w-1/2" />
                    </div>
                  </div>
                ))}
              </div>
            ) : sortedProducts.length > 0 ? (
              <div
                className={
                  viewMode === "grid"
                    ? "grid grid-cols-2 md:grid-cols-3 gap-4"
                    : "flex flex-col gap-4"
                }
              >
                {sortedProducts.map((item) => {
                  const productId = item.productId;
                  const priceVal = item.basePrice || 0;
                  const originalPrice = priceVal * 1.35;
                  const discount = Math.round(
                    ((originalPrice - priceVal) / originalPrice) * 100
                  );
                  const { rating, reviews } = getMockRating(productId);
                  const isWished = wishlist[productId];

                  return (
                    <div
                      key={productId}
                      onClick={() => navigate(`/product/${productId}`)}
                      className={`group bg-white rounded-xl border border-gray-200 hover:border-[#2874F0] hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer overflow-hidden ${
                        viewMode === "list" ? "flex gap-4 p-3" : ""
                      }`}
                    >
                      {/* Image */}
                      <div
                        className={`relative bg-[#F1F3F6] overflow-hidden shrink-0 ${
                          viewMode === "list"
                            ? "w-40 h-40 rounded-lg"
                            : "aspect-square"
                        }`}
                      >
                        <img
                          src={item.resolvedImage}
                          onError={(e) => {
                            e.target.onerror = null;
                            e.target.src =
                              "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=500";
                          }}
                          alt={item.name}
                          className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                        />

                        {/* Discount badge */}
                        {discount > 5 && (
                          <span className="absolute top-2 left-2 bg-[#FB641B] text-white text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded shadow-md">
                            {discount}% OFF
                          </span>
                        )}

                        {/* Best Seller badge */}
                        {item.isBestSeller && (
                          <span className="absolute top-2 right-2 bg-[#FFE500] text-[#031D44] text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full shadow-md">
                            Best Seller
                          </span>
                        )}

                        {/* Wishlist */}
                        <button
                          onClick={(e) => toggleWishlist(productId, e)}
                          className="absolute bottom-2 right-2 w-9 h-9 bg-white/95 backdrop-blur rounded-full flex items-center justify-center shadow-md hover:scale-110 transition-transform"
                        >
                          <FiHeart
                            size={15}
                            className={
                              isWished
                                ? "fill-red-500 text-red-500"
                                : "text-gray-500"
                            }
                          />
                        </button>

                        {/* Hover overlay — Quick View */}
                        <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                          <button
                            onClick={(e) => {
                              e.stopPropagation();
                              setQuickView(item);
                            }}
                            className="bg-white hover:bg-[#2874F0] hover:text-white text-gray-800 text-[10px] font-bold uppercase tracking-wider px-3 py-2 rounded shadow-lg flex items-center gap-1.5 transition-colors"
                          >
                            <FiEye size={12} /> Quick View
                          </button>
                        </div>
                      </div>

                      {/* Info */}
                      <div className={`p-3.5 ${viewMode === "list" ? "flex-1 py-2" : ""}`}>
                        <div className="flex items-center justify-between mb-1.5">
                          <span className="text-[10px] text-[#FB641B] font-bold uppercase tracking-wider">
                            {item.categoryName || "LINEN"}
                          </span>
                          <div className="flex items-center gap-1">
                            <div className="flex items-center gap-0.5 bg-[#10B981] text-white text-[10px] font-bold px-1.5 py-0.5 rounded">
                              {rating.toFixed(1)}
                              <FiStar size={9} fill="white" />
                            </div>
                            <span className="text-[10px] text-gray-500">
                              ({reviews})
                            </span>
                          </div>
                        </div>

                        <h3 className="text-[13px] font-semibold text-gray-800 mb-2 line-clamp-2 min-h-[34px] leading-tight group-hover:text-[#2874F0] transition-colors">
                          {item.name}
                        </h3>

                        <div className="flex items-baseline gap-1.5 mb-2">
                          <span className="text-[16px] font-bold text-gray-800">
                            ${Number(priceVal).toFixed(2)}
                          </span>
                          <span className="text-[11px] text-gray-400 line-through">
                            ${originalPrice.toFixed(2)}
                          </span>
                        </div>

                        <div className="flex items-center justify-between mb-2.5">
                          <span className="text-[10px] font-bold text-green-600 uppercase tracking-wider flex items-center gap-1">
                            <span className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse" />
                            In Stock
                          </span>
                          <span className="text-[9px] text-gray-500 font-medium">
                            Free shipping
                          </span>
                        </div>

                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            addToCart(
                              {
                                ...item,
                                id: productId,
                                name: item.name,
                                image: item.resolvedImage,
                              },
                              1,
                              "Standard",
                              priceVal
                            );
                            alert(`Added ${item.name} to cart!`);
                          }}
                          className="w-full py-2.5 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[11px] font-bold uppercase tracking-wider rounded-lg transition-all flex items-center justify-center gap-1.5 shadow-sm hover:shadow-md"
                        >
                          <FiShoppingCart size={13} /> Add to Cart
                        </button>
                      </div>
                    </div>
                  );
                })}
              </div>
            ) : (
              <div className="py-16 text-center bg-white rounded-lg border-2 border-dashed border-gray-300">
                <FiPackage size={48} className="mx-auto text-gray-300 mb-3" />
                <p className="text-[14px] font-bold text-gray-700 mb-1">
                  No products found
                </p>
                <p className="text-[12px] text-gray-500 mb-5">
                  Try adjusting filters or explore other categories.
                </p>
                <button
                  onClick={clearFilters}
                  className="px-5 py-2.5 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[11px] font-bold uppercase tracking-wider rounded transition-colors"
                >
                  Clear Filters
                </button>
              </div>
            )}
          </main>
        </div>

        {/* ============ BOTTOM PROMO ============ */}
        {sortedProducts.length > 0 && (
          <div className="mt-8 bg-gradient-to-r from-[#2874F0] to-[#0d47a1] rounded-xl overflow-hidden shadow-md flex flex-col md:flex-row items-stretch">
            <div className="flex-1 p-6 flex items-center gap-4">
              <div className="w-14 h-14 bg-[#FFE500] text-[#031D44] rounded-2xl flex items-center justify-center shrink-0">
                <FiPercent size={22} />
              </div>
              <div>
                <p className="text-[15px] font-bold text-white mb-0.5">
                  Looking for bulk wholesale pricing?
                </p>
                <p className="text-[12px] text-white/80">
                  Get exclusive B2B rates on orders of 50+ units
                </p>
              </div>
            </div>
            <button
              onClick={() => navigate("/contact")}
              className="bg-[#FB641B] hover:bg-[#e55a15] text-white px-8 py-4 text-[12px] font-bold uppercase tracking-wider transition-colors flex items-center justify-center gap-2 whitespace-nowrap group"
            >
              Request Quote
              <FiArrowRight
                size={14}
                className="group-hover:translate-x-1 transition-transform"
              />
            </button>
          </div>
        )}
      </div>

      {/* ============ QUICK VIEW MODAL ============ */}
      {quickView && (
        <div
          className="fixed inset-0 bg-black/70 backdrop-blur-md z-[300] flex items-center justify-center p-4"
          onClick={() => setQuickView(null)}
        >
          <div
            className="bg-white rounded-xl max-w-3xl w-full shadow-2xl overflow-hidden grid md:grid-cols-2 relative"
            onClick={(e) => e.stopPropagation()}
          >
            <button
              onClick={() => setQuickView(null)}
              className="absolute top-3 right-3 z-10 w-9 h-9 bg-white/90 backdrop-blur rounded-full flex items-center justify-center shadow-md hover:bg-gray-100"
            >
              <FiX size={16} />
            </button>

            {/* Image */}
            <div className="relative bg-[#F1F3F6] aspect-square md:aspect-auto">
              <img
                src={quickView.resolvedImage}
                alt={quickView.name}
                className="w-full h-full object-cover"
              />
              <span className="absolute top-4 left-4 bg-[#FB641B] text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded shadow-lg">
                35% OFF
              </span>
            </div>

            {/* Info */}
            <div className="p-6 flex flex-col justify-center">
              <span className="text-[10px] font-bold text-[#FB641B] uppercase tracking-wider mb-2">
                {quickView.categoryName || "LINEN"}
              </span>
              <h2 className="text-[22px] font-bold text-gray-800 leading-tight mb-3">
                {quickView.name}
              </h2>

              <div className="flex items-center gap-2 mb-4">
                <div className="flex items-center gap-0.5 bg-[#10B981] text-white text-[11px] font-bold px-2 py-0.5 rounded">
                  {getMockRating(quickView.productId).rating.toFixed(1)}
                  <FiStar size={10} fill="white" />
                </div>
                <span className="text-[11px] text-gray-500">
                  ({getMockRating(quickView.productId).reviews} reviews)
                </span>
                <span className="text-[11px] font-bold text-green-600 uppercase tracking-wider ml-auto">
                  ✓ In Stock
                </span>
              </div>

              <div className="flex items-baseline gap-3 mb-5 pb-5 border-b border-gray-100">
                <span className="text-[28px] font-bold text-gray-800">
                  ${Number(quickView.basePrice || 0).toFixed(2)}
                </span>
                <span className="text-[15px] text-gray-400 line-through">
                  ${(Number(quickView.basePrice || 0) * 1.35).toFixed(2)}
                </span>
              </div>

              <p className="text-[12px] text-gray-600 leading-relaxed mb-5">
                Premium hotel-grade quality linen crafted for durability and
                comfort. Ideal for hotels, spas, restaurants, and healthcare
                facilities.
              </p>

              <div className="space-y-2 mb-5">
                {[
                  { icon: FiTruck, text: "Free shipping on orders above $350" },
                  { icon: FiShield, text: "100% secure payment · SSL encrypted" },
                  { icon: FiAward, text: "30-day easy returns policy" },
                ].map((b, i) => (
                  <div key={i} className="flex items-center gap-2 text-[11px]">
                    <b.icon size={13} className="text-[#2874F0]" />
                    <span className="text-gray-600">{b.text}</span>
                  </div>
                ))}
              </div>

              <div className="flex gap-3">
                <button
                  onClick={() => {
                    addToCart(
                      {
                        ...quickView,
                        id: quickView.productId,
                        name: quickView.name,
                        image: quickView.resolvedImage,
                      },
                      1,
                      "Standard",
                      quickView.basePrice
                    );
                    setQuickView(null);
                    alert("Added to cart!");
                  }}
                  className="flex-1 py-3.5 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[12px] font-bold uppercase tracking-wider rounded-lg transition-colors shadow-md flex items-center justify-center gap-2"
                >
                  <FiShoppingCart size={14} /> Add to Cart
                </button>
                <button
                  onClick={() => navigate(`/product/${quickView.productId}`)}
                  className="px-5 py-3.5 bg-white border-2 border-[#2874F0] text-[#2874F0] hover:bg-[#2874F0] hover:text-white text-[12px] font-bold uppercase tracking-wider rounded-lg transition-colors"
                >
                  Details
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ============ MOBILE FILTER DRAWER ============ */}
      {showMobileFilter && (
        <div className="fixed inset-0 z-[200] flex lg:hidden">
          <div
            className="absolute inset-0 bg-black/60 backdrop-blur-sm"
            onClick={() => setShowMobileFilter(false)}
          />
          <div className="relative ml-auto w-[85%] max-w-sm bg-white h-full shadow-2xl flex flex-col animate-in slide-in-from-right duration-300">
            <div className="flex justify-between items-center p-4 bg-[#2874F0] text-white">
              <div className="flex items-center gap-2">
                <FiFilter size={16} />
                <h3 className="text-[14px] font-bold uppercase tracking-wider">
                  Filters
                </h3>
              </div>
              <button
                onClick={() => setShowMobileFilter(false)}
                className="p-1.5 rounded hover:bg-white/10 transition-colors"
              >
                <FiX size={18} />
              </button>
            </div>

            <div className="flex-1 overflow-y-auto p-5">
              <div className="mb-6">
                <h4 className="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">
                  Category
                </h4>
                <div className="space-y-2.5">
                  {categories.map((cat) => (
                    <label
                      key={cat}
                      className="flex items-center gap-3 cursor-pointer"
                    >
                      <input
                        type="radio"
                        name="m-cat"
                        checked={selectedCategory === cat}
                        onChange={() => setSelectedCategory(cat)}
                        className="w-4 h-4 accent-[#2874F0]"
                      />
                      <span className="text-[13px] text-gray-700">
                        {cat === "ALL" ? "All Products" : cat}
                      </span>
                    </label>
                  ))}
                </div>
              </div>

              <div>
                <h4 className="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">
                  Price Range
                </h4>
                <div className="space-y-2.5">
                  {[
                    { label: "Under $25", range: [0, 25] },
                    { label: "$25 - $50", range: [25, 50] },
                    { label: "$50 - $100", range: [50, 100] },
                    { label: "Above $100", range: [100, 1000] },
                  ].map((r, i) => (
                    <label
                      key={i}
                      className="flex items-center gap-3 cursor-pointer"
                    >
                      <input
                        type="radio"
                        name="m-price"
                        checked={
                          priceRange[0] === r.range[0] &&
                          priceRange[1] === r.range[1]
                        }
                        onChange={() => setPriceRange(r.range)}
                        className="w-4 h-4 accent-[#2874F0]"
                      />
                      <span className="text-[13px] text-gray-700">
                        {r.label}
                      </span>
                    </label>
                  ))}
                </div>
              </div>
            </div>

            <div className="p-4 border-t border-gray-200 flex gap-3">
              <button
                onClick={clearFilters}
                className="flex-1 py-3 bg-white border border-gray-300 text-gray-700 text-[11px] font-bold uppercase tracking-wider rounded transition-colors"
              >
                Clear
              </button>
              <button
                onClick={() => setShowMobileFilter(false)}
                className="flex-1 py-3 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[11px] font-bold uppercase tracking-wider rounded transition-colors"
              >
                Apply ({sortedProducts.length})
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

export default ProductsPage;