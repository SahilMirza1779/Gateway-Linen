import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import {
  FiChevronLeft,
  FiChevronRight,
  FiArrowRight,
  FiPackage,
  FiTrendingUp,
  FiStar,
} from "react-icons/fi";

const CategoryGrid = () => {
  const navigate = useNavigate();
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const getSmartCategoryImg = (name) => {
    const lower = (name || "").toLowerCase();
    if (lower.includes("bed sheet") || lower.includes("sheet")) {
      return "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=500";
    } else if (lower.includes("towel") || lower.includes("bath")) {
      return "https://images.pexels.com/photos/5591664/pexels-photo-5591664.jpeg?auto=compress&cs=tinysrgb&w=500";
    } else if (lower.includes("mattress") || lower.includes("pad")) {
      return "https://images.pexels.com/photos/6585757/pexels-photo-6585757.jpeg?auto=compress&cs=tinysrgb&w=500";
    } else if (lower.includes("pillow") || lower.includes("cover")) {
      return "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=500&auto=format&fit=crop";
    } else if (lower.includes("duvet") || lower.includes("blanket")) {
      return "https://images.unsplash.com/photo-1616046229478-9901c5536a45?q=80&w=500&auto=format&fit=crop";
    }
    return "https://images.unsplash.com/photo-1540555700478-4be289fbecef?q=80&w=500&auto=format&fit=crop";
  };

  // Mock counts + starting prices (aap backend se laa sakte ho)
  const getCategoryMeta = (name, index) => {
    const lower = (name || "").toLowerCase();
    const mockData = {
      "bed sheet": { count: 128, startPrice: 39.99, badge: "Best Seller" },
      bedsheet: { count: 128, startPrice: 39.99, badge: "Best Seller" },
      towel: { count: 96, startPrice: 12.99, badge: "Popular" },
      bath: { count: 74, startPrice: 24.99, badge: "Top Rated" },
      blanket: { count: 42, startPrice: 59.99, badge: "New" },
      pillow: { count: 68, startPrice: 19.99, badge: null },
      duvet: { count: 34, startPrice: 89.99, badge: "Deal" },
      mattress: { count: 28, startPrice: 49.99, badge: null },
    };

    for (const key in mockData) {
      if (lower.includes(key)) return mockData[key];
    }

    return {
      count: 40 + (index % 5) * 12,
      startPrice: 19.99 + index * 5,
      badge: index === 0 ? "Best Seller" : index === 2 ? "New" : null,
    };
  };

  useEffect(() => {
    fetch(
      "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/categories/api.php",
      {
        method: "GET",
        headers: {
          "X-API-KEY": "GatewayLinen@2026",
          "Content-Type": "application/json",
        },
      }
    )
      .then((res) => {
        if (!res.ok) {
          throw new Error("Failed to fetch categories from API");
        }
        return res.json();
      })
      .then((data) => {
        if (data.success && Array.isArray(data.data)) {
          setCategories(data.data);
        } else if (Array.isArray(data)) {
          setCategories(data);
        } else {
          setCategories([]);
        }
        setLoading(false);
      })
      .catch((err) => {
        console.error("API Fetch Error:", err);
        setError(err.message);
        setLoading(false);
      });
  }, []);

  const scrollLeft = () => {
    document
      .getElementById("category-slider")
      ?.scrollBy({ left: -350, behavior: "smooth" });
  };

  const scrollRight = () => {
    document
      .getElementById("category-slider")
      ?.scrollBy({ left: 350, behavior: "smooth" });
  };

  const getBadgeStyle = (badge) => {
    switch (badge) {
      case "Best Seller":
        return "bg-[#FB641B] text-white";
      case "New":
        return "bg-[#10B981] text-white";
      case "Deal":
        return "bg-[#FF9F00] text-white";
      case "Top Rated":
        return "bg-[#2874F0] text-white";
      case "Popular":
        return "bg-[#FFE500] text-[#031D44]";
      default:
        return null;
    }
  };

  return (
    <section className="bg-white py-10 md:py-14 w-full font-sans border-t border-gray-100">
      <div className="max-w-7xl mx-auto px-4 md:px-8">
        {/* ============ HEADER ============ */}
        <div className="flex flex-col md:flex-row md:justify-between md:items-end gap-4 mb-8">
          <div>
            <div className="inline-flex items-center gap-2 bg-[#EAF2FF] px-3 py-1 rounded-full mb-3">
              <span className="w-1.5 h-1.5 rounded-full bg-[#2874F0]"></span>
              <span className="text-[10px] font-bold text-[#2874F0] tracking-[0.2em] uppercase">
                Shop by Category
              </span>
            </div>
            <h2 className="text-2xl md:text-3xl lg:text-4xl font-bold text-gray-800 leading-tight">
              Premium Linen Collections
            </h2>
            <p className="text-[12px] md:text-[13px] text-gray-500 mt-2 max-w-lg">
              Browse our curated range of hospitality-grade linens — trusted by
              hotels, restaurants & homes.
            </p>
          </div>

          {/* Desktop: arrows + view all */}
          <div className="hidden md:flex items-center gap-3">
            <button
              onClick={scrollLeft}
              className="w-10 h-10 bg-white border-2 border-gray-200 hover:border-[#2874F0] hover:bg-[#EAF2FF] text-gray-600 hover:text-[#2874F0] rounded-full flex items-center justify-center transition-all cursor-pointer"
              title="Scroll Left"
            >
              <FiChevronLeft size={18} />
            </button>
            <button
              onClick={scrollRight}
              className="w-10 h-10 bg-white border-2 border-gray-200 hover:border-[#2874F0] hover:bg-[#EAF2FF] text-gray-600 hover:text-[#2874F0] rounded-full flex items-center justify-center transition-all cursor-pointer"
              title="Scroll Right"
            >
              <FiChevronRight size={18} />
            </button>
            <button
              onClick={() => navigate("/categories")}
              className="ml-2 inline-flex items-center gap-2 px-5 py-2.5 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[11px] font-bold uppercase tracking-wider rounded transition-all cursor-pointer shadow-sm"
            >
              View All ({categories.length})
              <FiArrowRight size={13} />
            </button>
          </div>
        </div>

        {/* ============ LOADING SKELETON ============ */}
        {loading ? (
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="animate-pulse">
                <div className="aspect-[4/5] bg-gray-200 rounded-lg mb-3" />
                <div className="h-3 bg-gray-200 rounded w-3/4 mb-2" />
                <div className="h-2 bg-gray-200 rounded w-1/2" />
              </div>
            ))}
          </div>
        ) : error ? (
          <div className="text-center py-16 bg-[#F1F3F6] rounded-lg border-2 border-dashed border-gray-300">
            <FiPackage size={40} className="mx-auto text-gray-300 mb-3" />
            <p className="text-[13px] font-bold text-gray-700 mb-1">
              Failed to load categories
            </p>
            <p className="text-[11px] text-gray-500">{error}</p>
          </div>
        ) : categories.length === 0 ? (
          <div className="text-center py-16 bg-[#F1F3F6] rounded-lg border-2 border-dashed border-gray-300">
            <FiPackage size={40} className="mx-auto text-gray-300 mb-3" />
            <p className="text-[13px] font-bold text-gray-700 mb-1">
              No categories available
            </p>
            <p className="text-[11px] text-gray-500">
              Categories will appear here once added.
            </p>
          </div>
        ) : (
          <>
            {/* ============ MOBILE: 2-Column Grid ============ */}
            <div className="grid grid-cols-2 gap-3 md:hidden">
              {categories.slice(0, 6).map((cat, index) => {
                const catId = cat.categoryId || cat.CategoryId || index;
                const catName = cat.name || cat.Name || "Collection";
                const catSlug =
                  cat.slug ||
                  cat.Slug ||
                  catName.toLowerCase().replace(/\s+/g, "-");
                const catImage = getSmartCategoryImg(catName);
                const meta = getCategoryMeta(catName, index);
                const badgeStyle = getBadgeStyle(meta.badge);

                return (
                  <div
                    key={`mobile-${catId}`}
                    onClick={() => navigate(`/category/${catSlug}`)}
                    className="group cursor-pointer bg-white rounded-lg border border-gray-200 overflow-hidden hover:border-[#2874F0] hover:shadow-md transition-all"
                  >
                    {/* Image */}
                    <div className="relative aspect-square bg-[#F1F3F6] overflow-hidden">
                      <img
                        src={catImage}
                        alt={catName}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                      />
                      {meta.badge && (
                        <span
                          className={`absolute top-2 left-2 text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded ${badgeStyle}`}
                        >
                          {meta.badge}
                        </span>
                      )}
                    </div>

                    {/* Info */}
                    <div className="p-2.5">
                      <h3 className="text-[12px] font-bold text-gray-800 truncate mb-1">
                        {catName}
                      </h3>
                      <p className="text-[10px] text-gray-500 mb-1">
                        {meta.count} products
                      </p>
                      <p className="text-[11px] font-bold text-[#2874F0]">
                        From ${meta.startPrice}
                      </p>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Mobile: View All */}
            <div className="mt-6 md:hidden">
              <button
                onClick={() => navigate("/categories")}
                className="w-full bg-[#FB641B] hover:bg-[#e55a15] text-white px-6 py-3.5 text-[12px] font-bold uppercase tracking-wider transition-colors cursor-pointer rounded shadow-sm flex items-center justify-center gap-2"
              >
                View All Categories <FiArrowRight size={14} />
              </button>
            </div>

            {/* ============ DESKTOP: Horizontal Slider ============ */}
            <div
              id="category-slider"
              className="hidden md:flex gap-5 overflow-x-auto scrollbar-hide pb-4 pt-2 snap-x snap-mandatory"
              style={{ scrollbarWidth: "none", msOverflowStyle: "none" }}
            >
              {categories.map((cat, index) => {
                const catId = cat.categoryId || cat.CategoryId || index;
                const catName = cat.name || cat.Name || "Collection";
                const catSlug =
                  cat.slug ||
                  cat.Slug ||
                  catName.toLowerCase().replace(/\s+/g, "-");
                const catImage = getSmartCategoryImg(catName);
                const meta = getCategoryMeta(catName, index);
                const badgeStyle = getBadgeStyle(meta.badge);

                return (
                  <div
                    key={`desktop-${catId}`}
                    onClick={() => navigate(`/category/${catSlug}`)}
                    className="group cursor-pointer flex-shrink-0 w-[240px] snap-start bg-white rounded-lg border border-gray-200 overflow-hidden hover:border-[#2874F0] hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
                  >
                    {/* Image Container */}
                    <div className="relative aspect-[4/5] bg-[#F1F3F6] overflow-hidden">
                      <img
                        src={catImage}
                        alt={catName}
                        className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                      />

                      {/* Badge */}
                      {meta.badge && (
                        <span
                          className={`absolute top-3 left-3 text-[9px] font-bold uppercase tracking-wider px-2 py-1 rounded ${badgeStyle}`}
                        >
                          {meta.badge}
                        </span>
                      )}

                      {/* Hover Overlay */}
                      <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-5">
                        <span className="bg-[#FB641B] text-white text-[10px] font-bold uppercase tracking-wider px-4 py-2 rounded flex items-center gap-1.5">
                          Shop Now <FiArrowRight size={11} />
                        </span>
                      </div>
                    </div>

                    {/* Info */}
                    <div className="p-3.5">
                      <h3 className="text-[13px] font-bold text-gray-800 truncate mb-1.5 group-hover:text-[#2874F0] transition-colors">
                        {catName}
                      </h3>
                      <div className="flex items-center justify-between">
                        <span className="text-[11px] text-gray-500">
                          {meta.count} products
                        </span>
                        <span className="text-[12px] font-bold text-[#2874F0]">
                          From ${meta.startPrice}
                        </span>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </>
        )}

        {/* ============ BOTTOM PROMO STRIP (Desktop) ============ */}
        {!loading && categories.length > 0 && (
          <div className="hidden md:flex mt-8 bg-gradient-to-r from-[#2874F0] to-[#0d47a1] rounded-lg overflow-hidden shadow-md">
            <div className="flex-1 p-6 flex items-center gap-4">
              <div className="w-12 h-12 bg-[#FFE500] text-[#031D44] rounded-xl flex items-center justify-center shrink-0">
                <FiTrendingUp size={20} />
              </div>
              <div className="flex-1">
                <p className="text-[14px] font-bold text-white mb-0.5">
                  Looking for bulk wholesale?
                </p>
                <p className="text-[12px] text-white/80">
                  Get exclusive B2B pricing on orders of 50+ units
                </p>
              </div>
            </div>
            <button
              onClick={() => navigate("/contact")}
              className="bg-[#FB641B] hover:bg-[#e55a15] text-white px-6 text-[11px] font-bold uppercase tracking-wider transition-colors cursor-pointer flex items-center gap-2 whitespace-nowrap"
            >
              Get Quote <FiArrowRight size={13} />
            </button>
          </div>
        )}
      </div>

      {/* CSS for hiding scrollbar */}
      <style>{`
        .scrollbar-hide::-webkit-scrollbar {
          display: none;
        }
      `}</style>
    </section>
  );
};

export default CategoryGrid;