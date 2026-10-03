import { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import {
  FiShoppingCart,
  FiHeart,
  FiArrowLeft,
  FiX,
  FiUser,
  FiChevronRight,
  FiStar,
  FiFilter,
  FiGrid,
  FiList,
  FiChevronDown,
  FiSliders,
  FiPackage,
  FiTrendingUp,
} from "react-icons/fi";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

const CategoryPage = () => {
  const { categoryName } = useParams();
  const navigate = useNavigate();
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showLoginModal, setShowLoginModal] = useState(false);
  const [showMobileFilter, setShowMobileFilter] = useState(false);
  const [viewMode, setViewMode] = useState("grid");
  const [sortBy, setSortBy] = useState("popularity");
  const [showSortMenu, setShowSortMenu] = useState(false);
  const [priceRange, setPriceRange] = useState([0, 500]);
  const [minRating, setMinRating] = useState(0);

  const { isInWishlist, toggleWishlistItem } = useWishlist();
  const { addToCart } = useCart();

  useEffect(() => {
    window.scrollTo(0, 0);
    const fetchCategoryProducts = async () => {
      setLoading(true);
      try {
        const response = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/products/api.php?action=get_products",
          {
            method: "GET",
            headers: {
              "X-API-KEY": "GatewayLinen@2026",
              "Content-Type": "application/json",
            },
          }
        );
        const result = await response.json();

        let fetchedItems = [];
        if (result.success && Array.isArray(result.data)) {
          fetchedItems = result.data;
        } else if (
          result.success &&
          result.data &&
          Array.isArray(result.data.items)
        ) {
          fetchedItems = result.data.items;
        } else if (Array.isArray(result)) {
          fetchedItems = result;
        }

        const formattedItems = fetchedItems.map((item) => {
          const rawImg = item.ImageUrl || item.imageUrl || item.image || "";
          let finalProductImg =
            "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=500";

          if (rawImg.startsWith("http")) {
            finalProductImg = rawImg;
          } else if (rawImg !== "") {
            const cleanPath = rawImg.replace(/^\/+/, "");
            finalProductImg = `http://localhost/Gateway-Linen/GatewayLinenAdmin-main/${cleanPath}`;
          }

          return {
            ...item,
            image: finalProductImg,
          };
        });

        setProducts(formattedItems);
      } catch (error) {
        console.error("Error fetching category products:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchCategoryProducts();
  }, [categoryName]);

  const handleAuthAction = (actionCallback) => {
    const loggedInUser = localStorage.getItem("user");
    if (!loggedInUser) {
      setShowLoginModal(true);
    } else {
      actionCallback();
    }
  };

  const formattedCategory = categoryName
    ? categoryName.replace(/-/g, " ").toUpperCase()
    : "COLLECTION";

  // Filter products
  const filteredProducts = products
    .filter((item) => {
      const catName =
        item.categoryName || item.CategoryName || item.category || "";
      const catSlug = catName.toLowerCase().replace(/\s+/g, "-");
      return catSlug === categoryName?.toLowerCase() || categoryName === "all";
    })
    .filter((item) => {
      const price = Number(item.basePrice || item.price || item.Price || 0);
      return price >= priceRange[0] && price <= priceRange[1];
    });

  // Sort products
  const sortedProducts = [...filteredProducts].sort((a, b) => {
    const priceA = Number(a.basePrice || a.price || a.Price || 0);
    const priceB = Number(b.basePrice || b.price || b.Price || 0);
    switch (sortBy) {
      case "price-low":
        return priceA - priceB;
      case "price-high":
        return priceB - priceA;
      case "name":
        return (a.name || a.Name || "").localeCompare(b.name || b.Name || "");
      default:
        return 0;
    }
  });

  // Mock rating helper (stable per product)
  const getMockRating = (id) => {
    const seed = Number(String(id).slice(-2)) || 50;
    const rating = 3.5 + (seed % 15) / 10;
    const reviews = 20 + ((seed * 7) % 250);
    return { rating: Math.min(rating, 5), reviews };
  };

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
              {formattedCategory}
            </span>
          </div>
        </div>
      </div>

      {/* ============ HEADER BANNER ============ */}
      <div className="bg-gradient-to-r from-[#2874F0] via-[#1e5bc7] to-[#0d47a1] text-white relative overflow-hidden">
        <div
          className="absolute inset-0 opacity-[0.08]"
          style={{
            backgroundImage:
              "radial-gradient(circle, white 1px, transparent 1px)",
            backgroundSize: "20px 20px",
          }}
        />
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-10 relative">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
              <button
                onClick={() => navigate("/")}
                className="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-white/80 hover:text-white transition-colors mb-3"
              >
                <FiArrowLeft size={13} /> Back to Home
              </button>
              <div className="inline-flex items-center gap-2 bg-white/15 border border-white/20 px-3 py-1 rounded-full mb-2">
                <span className="w-1.5 h-1.5 rounded-full bg-[#FFE500]"></span>
                <span className="text-[10px] font-bold tracking-widest uppercase">
                  {formattedCategory} Collection
                </span>
              </div>
              <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold uppercase tracking-tight">
                {formattedCategory}
              </h1>
              <p className="text-[12px] md:text-[13px] text-white/80 mt-2 max-w-2xl">
                Premium hospitality linens crafted for luxury hotels,
                restaurants & homes.
              </p>
            </div>

            <div className="bg-white/15 backdrop-blur border border-white/20 px-4 py-3 rounded-lg text-center shrink-0">
              <p className="text-[10px] uppercase tracking-widest text-white/70 font-bold">
                Products
              </p>
              <p className="text-[20px] font-bold">{sortedProducts.length}</p>
            </div>
          </div>
        </div>
      </div>

      {/* ============ TOOLBAR ============ */}
      <div className="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3">
          <div className="flex items-center justify-between gap-3 flex-wrap">
            {/* Left: Filter button (mobile) + Result count */}
            <div className="flex items-center gap-3">
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
            </div>

            {/* Right: Sort + View */}
            <div className="flex items-center gap-2">
              {/* Sort */}
              <div className="relative">
                <button
                  onClick={() => setShowSortMenu(!showSortMenu)}
                  className="inline-flex items-center gap-2 px-3.5 py-2 bg-white border border-gray-300 hover:border-[#2874F0] rounded text-[11px] font-bold uppercase tracking-wider text-gray-700 transition-colors"
                >
                  <FiSliders size={13} />
                  Sort:{" "}
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

              {/* View toggle */}
              <div className="hidden sm:flex items-center border border-gray-300 rounded overflow-hidden">
                <button
                  onClick={() => setViewMode("grid")}
                  className={`p-2 transition-colors ${
                    viewMode === "grid"
                      ? "bg-[#2874F0] text-white"
                      : "bg-white text-gray-600 hover:bg-gray-50"
                  }`}
                  title="Grid View"
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
                  title="List View"
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
        <div className="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6">
          {/* ===== FILTER SIDEBAR (Desktop) ===== */}
          <aside className="hidden lg:block">
            <div className="bg-white rounded-lg border border-gray-200 p-5 sticky top-20">
              <div className="flex items-center gap-2 mb-5 pb-3 border-b border-gray-100">
                <FiFilter size={14} className="text-[#2874F0]" />
                <h3 className="text-[13px] font-bold text-gray-800 uppercase tracking-wider">
                  Filters
                </h3>
              </div>

              {/* Price */}
              <div className="mb-6">
                <h4 className="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">
                  Price Range
                </h4>
                <div className="space-y-2.5">
                  {[
                    { label: "Under $25", range: [0, 25] },
                    { label: "$25 - $50", range: [25, 50] },
                    { label: "$50 - $100", range: [50, 100] },
                    { label: "Above $100", range: [100, 500] },
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
                  <button
                    onClick={() => setPriceRange([0, 500])}
                    className="text-[10px] text-[#2874F0] font-bold uppercase tracking-wider hover:underline mt-1"
                  >
                    Clear
                  </button>
                </div>
              </div>

              {/* Rating */}
              <div className="mb-6 pb-6 border-b border-gray-100">
                <h4 className="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">
                  Customer Rating
                </h4>
                <div className="space-y-2.5">
                  {[4, 3, 2].map((r) => (
                    <label
                      key={r}
                      className="flex items-center gap-2.5 cursor-pointer group"
                    >
                      <input
                        type="radio"
                        name="rating"
                        checked={minRating === r}
                        onChange={() => setMinRating(r)}
                        className="w-3.5 h-3.5 accent-[#2874F0] cursor-pointer"
                      />
                      <div className="flex items-center gap-1">
                        {[...Array(5)].map((_, i) => (
                          <FiStar
                            key={i}
                            size={11}
                            fill={i < r ? "#FFE500" : "transparent"}
                            className={
                              i < r ? "text-[#FFE500]" : "text-gray-300"
                            }
                          />
                        ))}
                        <span className="text-[11px] text-gray-600 ml-1">
                          & above
                        </span>
                      </div>
                    </label>
                  ))}
                </div>
              </div>

              {/* Support card */}
              <div className="bg-gradient-to-br from-[#2874F0] to-[#0d47a1] rounded-lg p-4 text-white">
                <div className="w-10 h-10 bg-[#FFE500] text-[#031D44] rounded-full flex items-center justify-center mb-2">
                  <FiPackage size={16} />
                </div>
                <p className="text-[12px] font-bold mb-1">
                  Bulk Wholesale?
                </p>
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
              // Loading skeletons
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
                  const productId =
                    item.productId || item.ProductId || item.id;
                  const productName =
                    item.name || item.Name || "Product Name";
                  const priceVal =
                    item.basePrice || item.price || item.Price || "29.99";
                  const numericPrice = Number(priceVal);
                  const formattedPrice = `$${numericPrice.toFixed(2)}`;
                  const originalPrice = (numericPrice * 1.35).toFixed(2);
                  const discount = Math.round(
                    ((originalPrice - numericPrice) / originalPrice) * 100
                  );
                  const unitVal = item.unit || item.Unit || "EACH";
                  const catLabel =
                    item.categoryName || item.CategoryName || "LINEN";
                  const { rating, reviews } = getMockRating(productId);
                  const inWishlist = isInWishlist(productId);

                  return (
                    <div
                      key={productId}
                      onClick={() => navigate(`/product/${productId}`)}
                      className={`group bg-white rounded-lg border border-gray-200 hover:border-[#2874F0] hover:shadow-lg transition-all cursor-pointer overflow-hidden ${
                        viewMode === "list" ? "flex gap-4 p-3" : ""
                      }`}
                    >
                      {/* Image */}
                      <div
                        className={`relative bg-[#F1F3F6] overflow-hidden shrink-0 ${
                          viewMode === "list"
                            ? "w-40 h-40 rounded"
                            : "aspect-square"
                        }`}
                      >
                        <img
                          src={item.image}
                          alt={productName}
                          onError={(e) => {
                            e.target.onerror = null;
                            e.target.src =
                              "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=500";
                          }}
                          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />

                        {/* Discount badge */}
                        {discount > 5 && (
                          <span className="absolute top-2 left-2 bg-[#FB641B] text-white text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded">
                            {discount}% off
                          </span>
                        )}

                        {/* Wishlist */}
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            handleAuthAction(() =>
                              toggleWishlistItem({
                                ...item,
                                id: productId,
                                price: formattedPrice,
                                name: productName,
                                category: catLabel,
                              })
                            );
                          }}
                          className="absolute top-2 right-2 w-8 h-8 bg-white/95 backdrop-blur rounded-full flex items-center justify-center shadow-sm hover:scale-110 transition-transform"
                          title="Add to Wishlist"
                        >
                          <FiHeart
                            size={14}
                            className={
                              inWishlist
                                ? "fill-red-500 text-red-500"
                                : "text-gray-500"
                            }
                          />
                        </button>
                      </div>

                      {/* Info */}
                      <div
                        className={`p-3 ${
                          viewMode === "list" ? "flex-1 py-2" : ""
                        }`}
                      >
                        {/* Category + Rating */}
                        <div className="flex items-center justify-between mb-1.5">
                          <span className="text-[9px] text-[#FB641B] font-bold uppercase tracking-wider">
                            {catLabel}
                          </span>
                          <div className="flex items-center gap-1">
                            <div className="flex items-center gap-0.5 bg-[#10B981] text-white text-[9px] font-bold px-1.5 py-0.5 rounded">
                              {rating.toFixed(1)}
                              <FiStar size={8} fill="white" />
                            </div>
                            <span className="text-[9px] text-gray-500">
                              ({reviews})
                            </span>
                          </div>
                        </div>

                        {/* Name */}
                        <h3 className="text-[12px] font-medium text-gray-800 mb-2 line-clamp-2 min-h-[32px] leading-tight group-hover:text-[#2874F0] transition-colors">
                          {productName}
                        </h3>

                        {/* Price */}
                        <div className="flex items-baseline gap-1.5 mb-2">
                          <span className="text-[15px] font-bold text-gray-800">
                            {formattedPrice}
                          </span>
                          <span className="text-[11px] text-gray-400 line-through">
                            ${originalPrice}
                          </span>
                          <span className="text-[10px] text-gray-500">
                            /{unitVal}
                          </span>
                        </div>

                        {/* Action Button */}
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            handleAuthAction(() => {
                              addToCart({
                                ...item,
                                id: productId,
                                name: productName,
                                price: priceVal,
                              });
                            });
                          }}
                          className="w-full py-2.5 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[11px] font-bold uppercase tracking-wider rounded transition-colors flex items-center justify-center gap-1.5 shadow-sm"
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
                  onClick={() => navigate("/products")}
                  className="px-5 py-2.5 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[11px] font-bold uppercase tracking-wider rounded transition-colors"
                >
                  Browse All Products
                </button>
              </div>
            )}
          </main>
        </div>

        {/* ============ BOTTOM PROMO STRIP ============ */}
        {sortedProducts.length > 0 && (
          <div className="mt-8 bg-gradient-to-r from-[#2874F0] to-[#0d47a1] rounded-lg overflow-hidden shadow-md flex flex-col md:flex-row items-stretch">
            <div className="flex-1 p-6 flex items-center gap-4">
              <div className="w-12 h-12 bg-[#FFE500] text-[#031D44] rounded-xl flex items-center justify-center shrink-0">
                <FiTrendingUp size={20} />
              </div>
              <div>
                <p className="text-[14px] font-bold text-white mb-0.5">
                  Looking for bulk wholesale pricing?
                </p>
                <p className="text-[12px] text-white/80">
                  Get exclusive B2B rates on orders of 50+ units
                </p>
              </div>
            </div>
            <button
              onClick={() => navigate("/contact")}
              className="bg-[#FB641B] hover:bg-[#e55a15] text-white px-6 py-4 text-[11px] font-bold uppercase tracking-wider transition-colors flex items-center justify-center gap-2 whitespace-nowrap"
            >
              Request Quote <FiChevronRight size={13} />
            </button>
          </div>
        )}
      </div>

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
                  Price Range
                </h4>
                <div className="space-y-3">
                  {[
                    { label: "Under $25", range: [0, 25] },
                    { label: "$25 - $50", range: [25, 50] },
                    { label: "$50 - $100", range: [50, 100] },
                    { label: "Above $100", range: [100, 500] },
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
                  <button
                    onClick={() => setPriceRange([0, 500])}
                    className="text-[11px] text-[#2874F0] font-bold uppercase tracking-wider"
                  >
                    Clear
                  </button>
                </div>
              </div>
            </div>

            <div className="p-4 border-t border-gray-200 flex gap-3">
              <button
                onClick={() => {
                  setPriceRange([0, 500]);
                  setMinRating(0);
                }}
                className="flex-1 py-3 bg-white border border-gray-300 text-gray-700 text-[11px] font-bold uppercase tracking-wider rounded transition-colors"
              >
                Clear All
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

      {/* ============ LOGIN MODAL ============ */}
      {showLoginModal && (
        <div className="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-white p-7 rounded-lg shadow-2xl w-full max-w-sm text-center relative">
            <button
              onClick={() => setShowLoginModal(false)}
              className="absolute top-3 right-3 text-gray-400 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 p-1.5 rounded-full transition-colors"
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
              Please login to add items to your cart, wishlist, or proceed to
              checkout.
            </p>
            <button
              onClick={() => {
                setShowLoginModal(false);
                navigate("/login");
              }}
              className="w-full py-3.5 bg-[#FB641B] text-white rounded text-[12px] font-bold uppercase tracking-wider hover:bg-[#e55a15] transition-all shadow-sm"
            >
              Login Now
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default CategoryPage;