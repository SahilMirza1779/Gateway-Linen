import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { FiChevronLeft, FiChevronRight } from "react-icons/fi";

const CategoryGrid = () => {
  const navigate = useNavigate();
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const pastelColors = [
    "bg-[#EAEBED]",
    "bg-[#E6DFD3]",
    "bg-[#E8DDD6]",
    "bg-[#DDE5DF]",
    "bg-[#DFE4EC]",
    "bg-[#EFEAD8]",
  ];

  const getSmartCategoryImg = (name) => {
    const lower = (name || "").toLowerCase();
    if (lower.includes("bed sheet") || lower.includes("sheet")) {
      return "https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=500&auto=format&fit=crop";
    } else if (lower.includes("towel") || lower.includes("bath")) {
      return "https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?q=80&w=500&auto=format&fit=crop";
    } else if (lower.includes("mattress") || lower.includes("pad")) {
      return "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=500&auto=format&fit=crop";
    } else if (lower.includes("pillow") || lower.includes("cover")) {
      return "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=500&auto=format&fit=crop";
    } else if (lower.includes("duvet") || lower.includes("blanket")) {
      return "https://images.unsplash.com/photo-1616046229478-9901c5536a45?q=80&w=500&auto=format&fit=crop";
    }
    return "https://images.unsplash.com/photo-1540555700478-4be289fbecef?q=80&w=500&auto=format&fit=crop";
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
      },
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
      .scrollBy({ left: -300, behavior: "smooth" });
  };

  const scrollRight = () => {
    document
      .getElementById("category-slider")
      .scrollBy({ left: 300, behavior: "smooth" });
  };

  return (
    <section className="bg-white py-12 md:py-16 w-full font-sans border-t border-gray-100 relative">
      <div className="max-w-[1350px] mx-auto px-4 md:px-8">
        {/* Header with Navigation arrows (Arrows hidden on mobile) */}
        <div className="flex justify-between items-center mb-6 md:mb-8">
          <h2 className="text-xl md:text-[32px] font-serif font-bold text-[#031D44] tracking-wide uppercase">
            OUR PREMIUM LINEN COLLECTIONS
          </h2>
          {/* Only visible on Laptop/Desktop */}
          <div className="hidden md:flex items-center gap-3">
            <button
              onClick={scrollLeft}
              className="p-2 border border-gray-300 rounded-full hover:bg-[#031D44] hover:text-white transition-colors cursor-pointer"
              title="Scroll Left"
            >
              <FiChevronLeft size={18} />
            </button>
            <button
              onClick={scrollRight}
              className="p-2 border border-gray-300 rounded-full hover:bg-[#031D44] hover:text-white transition-colors cursor-pointer"
              title="Scroll Right"
            >
              <FiChevronRight size={18} />
            </button>
            <button
              onClick={() => navigate("/categories")}
              className="text-xs font-bold text-[#B58E58] hover:text-[#031D44] uppercase tracking-widest underline transition-colors ml-4 cursor-pointer"
            >
              View All ({categories.length})
            </button>
          </div>
        </div>

        {loading ? (
          <div className="text-center py-12 text-xs text-gray-500 uppercase tracking-widest font-bold">
            Fetching collections from database API...
          </div>
        ) : error ? (
          <div className="text-center py-12 text-xs text-red-500 font-semibold">
            Error loading categories: {error}
          </div>
        ) : categories.length === 0 ? (
          <div className="text-center py-12 text-xs text-gray-400 font-medium">
            No categories found in the database.
          </div>
        ) : (
          <>
            {/* ======================================================== */}
            {/* 📱 MOBILE VIEW: 2x2 Grid for first 4 categories */}
            {/* ======================================================== */}
            <div className="grid grid-cols-2 gap-4 md:hidden">
              {categories.slice(0, 4).map((cat, index) => {
                const catId = cat.categoryId || cat.CategoryId || index;
                const catName = cat.name || cat.Name || "Collection";
                const catSlug =
                  cat.slug ||
                  cat.Slug ||
                  catName.toLowerCase().replace(/\s+/g, "-");
                const catImage = getSmartCategoryImg(catName);
                const bgClass = pastelColors[index % pastelColors.length];

                return (
                  <div
                    key={`mobile-${catId}`}
                    onClick={() => navigate(`/category/${catSlug}`)}
                    className="group cursor-pointer flex flex-col items-center w-full"
                  >
                    <div
                      className={`w-full aspect-[4/5] ${bgClass} rounded-none overflow-hidden mb-2.5 relative flex items-center justify-center p-3 transition-transform duration-300 border border-gray-200/50`}
                    >
                      <img
                        src={catImage}
                        alt={catName}
                        className="w-full h-full object-cover mix-blend-multiply"
                      />
                    </div>
                    <h3 className="text-[10px] font-bold text-[#031D44] uppercase tracking-wider text-center truncate w-full px-1">
                      {catName}
                    </h3>
                  </div>
                );
              })}
            </div>

            {/* Mobile View All Button */}
            <div className="mt-8 md:hidden">
              <button
                onClick={() => navigate("/categories")}
                className="w-full bg-transparent border border-[#031D44] text-[#031D44] hover:bg-[#031D44] hover:text-white px-6 py-3.5 text-[11px] font-bold uppercase tracking-widest transition-colors cursor-pointer rounded-none shadow-sm"
              >
                View All Categories
              </button>
            </div>

            {/* ======================================================== */}
            {/* 💻 DESKTOP/LAPTOP VIEW: Horizontal Scrollable Slider */}
            {/* ======================================================== */}
            <div
              id="category-slider"
              className="hidden md:flex gap-6 overflow-x-auto scrollbar-hide pb-4 pt-2 snap-x snap-mandatory"
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
                const bgClass = pastelColors[index % pastelColors.length];

                return (
                  <div
                    key={`desktop-${catId}`}
                    onClick={() => navigate(`/category/${catSlug}`)}
                    className="group cursor-pointer flex flex-col items-center flex-shrink-0 w-[230px] snap-start"
                  >
                    <div
                      className={`w-full aspect-[4/5] ${bgClass} rounded-none overflow-hidden mb-3 relative flex items-center justify-center p-4 transition-transform duration-300 group-hover:shadow-xl border border-gray-200/50`}
                    >
                      <img
                        src={catImage}
                        alt={catName}
                        className="w-full h-full object-cover mix-blend-multiply transition-transform duration-700 group-hover:scale-105"
                      />
                    </div>
                    <h3 className="text-sm font-bold text-[#031D44] uppercase tracking-wider group-hover:text-[#B58E58] transition-colors duration-300 text-center truncate w-full px-1">
                      {catName}
                    </h3>
                  </div>
                );
              })}
            </div>
          </>
        )}
      </div>
    </section>
  );
};

export default CategoryGrid;
