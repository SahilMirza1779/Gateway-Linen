import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { FiArrowRight, FiGrid, FiX } from "react-icons/fi";

// Fallback images
import duvetImg from "../assets/newImages/egyptianCottonKingSheet.jpg";
import mattressImg from "../assets/newImages/waterproofHospitalityMattressPad.jpg";
import pillowsImg from "../assets/newImages/firmSupportGussetedPillow.jpg";
import bathroomImg from "../assets/newImages/luxuryBathMatSet.jpg";
import blanketsImg from "../assets/newImages/thermalWaffleWeaveBlanket.jpg";

const fallbackImages = [
  duvetImg,
  mattressImg,
  pillowsImg,
  bathroomImg,
  blanketsImg,
];

export default function CategoryGrid() {
  const navigate = useNavigate();
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showAllModal, setShowAllModal] = useState(false);

  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/categories/api.php",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-API-KEY": "GatewayLinen@2026",
            },
          },
        );
        const result = await response.json();
        if (result.success && result.data) {
          const formattedCategories = result.data.map((cat, index) => {
            const rawImg = cat.ImageUrl || cat.image || cat.CategoryImage || "";
            let finalImg = fallbackImages[index % fallbackImages.length];

            if (rawImg.startsWith("http")) {
              finalImg = rawImg;
            } else if (rawImg !== "") {
              const cleanPath = rawImg.replace(/^\/+/, "");
              finalImg = `http://localhost/Gateway-Linen/GatewayLinenAdmin-main/${cleanPath}`;
            }

            return {
              id: cat.CategoryId || cat.categoryId || index + 1,
              name: cat.Name || cat.name,
              path: `/category/${(cat.Name || cat.name || "").toLowerCase().replace(/\s+/g, "-")}`,
              image: finalImg,
              count: cat.ItemCount ? `${cat.ItemCount}+ ITEMS` : "10+ ITEMS",
            };
          });
          setCategories(formattedCategories);
        }
      } catch (error) {
        console.error("Error:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchCategories();
  }, []);

  return (
    <section className="w-full bg-[#F0EAE1] py-10 md:py-14 px-3 md:px-10 font-sans">
      <div className="max-w-[1536px] mx-auto">
        {/* Section Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-6 md:mb-10 border-b border-[#031D44]/10 pb-5">
          <div>
            <div className="inline-flex items-center gap-2 bg-[#B58E58]/15 px-3 py-1 rounded-full mb-2.5 border border-[#B58E58]/30">
              <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
              <span className="text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
                Browse Collections
              </span>
            </div>
            <h2 className="text-2xl md:text-4xl font-serif font-bold text-[#031D44]">
              Shop By Categories
            </h2>
          </div>

          <div className="flex items-center justify-between mt-3 md:mt-0 gap-4">
            <p className="text-xs md:text-sm text-gray-600 max-w-sm font-light leading-relaxed">
              Explore premium hotel-grade linen categories crafted exclusively
              for high-end hospitality.
            </p>
            <button
              onClick={() => setShowAllModal(true)}
              className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-[#E5DCD0] text-[11px] font-bold uppercase tracking-wider text-[#031D44] hover:bg-[#031D44] hover:text-white rounded-xl shadow-2xs transition-all cursor-pointer shrink-0"
            >
              <FiGrid size={14} /> View All ({categories.length})
            </button>
          </div>
        </div>

        {/* Categories Grid - Exactly 5 items in a single line */}
        {loading ? (
          <div className="text-center py-12 text-xs font-bold text-gray-500 uppercase tracking-widest">
            Loading Categories...
          </div>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 md:gap-6">
            {categories.slice(0, 5).map((cat) => (
              <div
                key={cat.id}
                onClick={() => navigate(cat.path)}
                className="group relative bg-[#F7F2EB] rounded-[20px] md:rounded-[26px] p-4 md:p-5 border border-[#E5DCD0] shadow-sm hover:shadow-xl hover:shadow-[#B58E58]/15 hover:-translate-y-1.5 transition-all duration-500 cursor-pointer flex flex-col items-center text-center overflow-hidden"
              >
                <div className="absolute inset-x-0 top-0 h-1.5 bg-[#B58E58] opacity-0 group-hover:opacity-100 transition-opacity"></div>

                <div className="relative w-20 h-20 sm:w-24 sm:h-24 md:w-32 md:h-32 rounded-full overflow-hidden mb-3 shadow-inner border-2 md:border-4 border-[#EAE2D8] group-hover:border-[#B58E58] transition-colors bg-white">
                  <img
                    src={cat.image}
                    alt={cat.name}
                    onError={(e) => {
                      e.target.src = duvetImg;
                    }}
                    className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                  />
                  <div className="absolute inset-0 bg-[#031D44]/10 group-hover:bg-transparent transition-colors"></div>
                </div>

                <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-widest uppercase mb-1">
                  {cat.count}
                </span>

                <h3 className="text-xs md:text-sm font-serif font-bold text-[#031D44] mb-3 group-hover:text-[#B58E58] transition-colors line-clamp-1 leading-snug">
                  {cat.name}
                </h3>

                <div className="mt-auto w-full py-2 px-2 md:px-3 rounded-xl bg-white/70 group-hover:bg-[#031D44] text-[#031D44] group-hover:text-white text-[10px] md:text-xs font-bold tracking-wider uppercase transition-all flex items-center justify-center gap-1.5 shadow-sm">
                  <span>Explore</span>
                  <FiArrowRight
                    size={12}
                    className="group-hover:translate-x-1.5 transition-transform"
                  />
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* VIEW ALL CATEGORIES POPUP MODAL */}
      {showAllModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-in fade-in duration-300">
          <div className="bg-[#F7F2EB] border border-[#E5DCD0] rounded-[28px] max-w-4xl w-full p-6 sm:p-8 shadow-2xl relative max-h-[85vh] overflow-y-auto">
            <button
              onClick={() => setShowAllModal(false)}
              className="absolute top-5 right-5 text-gray-400 hover:text-gray-800 bg-white p-2 rounded-full transition-colors cursor-pointer border border-gray-200"
            >
              <FiX size={18} />
            </button>

            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-1">
              All Categories ({categories.length})
            </h3>
            <p className="text-xs text-gray-500 mb-6 font-light">
              Select any category below to browse our complete collection.
            </p>

            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
              {categories.map((cat) => (
                <div
                  key={cat.id}
                  onClick={() => {
                    setShowAllModal(false);
                    navigate(cat.path);
                  }}
                  className="bg-white p-4 rounded-2xl border border-[#E5DCD0] hover:border-[#B58E58] hover:shadow-md transition-all cursor-pointer flex flex-col items-center text-center group"
                >
                  <div className="w-16 h-16 rounded-full overflow-hidden mb-2.5 border border-gray-100 bg-[#FAF7F2]">
                    <img
                      src={cat.image}
                      alt={cat.name}
                      onError={(e) => {
                        e.target.src = duvetImg;
                      }}
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform"
                    />
                  </div>
                  <h4 className="text-xs font-serif font-bold text-[#031D44] group-hover:text-[#B58E58] transition-colors line-clamp-1">
                    {cat.name}
                  </h4>
                  <span className="text-[9px] text-[#B58E58] font-bold uppercase mt-0.5">
                    {cat.count}
                  </span>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}
    </section>
  );
}
