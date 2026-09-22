import { useState, useEffect, useRef } from "react";
import { useNavigate } from "react-router-dom";
import { FiChevronLeft, FiChevronRight } from "react-icons/fi";

import towelImg from "../assets/towel.jpg";
import badsheetImg from "../assets/badsheet.jpg";

const CategoryGrid = () => {
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const scrollRef = useRef(null);
  const navigate = useNavigate();

  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenadmin-main/categories/api.php",
          {
            method: "GET",
            headers: {
              "X-API-KEY": "GatewayLinen@2026",
              "Content-Type": "application/json",
            },
          },
        );

        const result = await response.json();
        if (result.success && result.data) {
          setCategories(result.data);
        }
      } catch (error) {
        console.error("Error fetching categories:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchCategories();
  }, []);

  useEffect(() => {
    const interval = setInterval(() => {
      if (scrollRef.current) {
        const { scrollLeft, scrollWidth, clientWidth } = scrollRef.current;
        if (scrollLeft + clientWidth >= scrollWidth - 10) {
          scrollRef.current.scrollTo({ left: 0, behavior: "smooth" });
        } else {
          scrollRef.current.scrollBy({ left: 280, behavior: "smooth" });
        }
      }
    }, 6000);

    return () => clearInterval(interval);
  }, [categories]);

  const scrollLeft = () => {
    if (scrollRef.current) {
      scrollRef.current.scrollBy({ left: -300, behavior: "smooth" });
    }
  };

  const scrollRight = () => {
    if (scrollRef.current) {
      scrollRef.current.scrollBy({ left: 300, behavior: "smooth" });
    }
  };

  const handleCategoryClick = (categoryName) => {
    const slug = categoryName.toLowerCase().replace(/\s+/g, "-");
    navigate(`/category/${slug}`);
  };

  return (
    <section className="w-full bg-white py-12 md:py-16">
      <div className="max-w-[1536px] mx-auto px-4 md:px-10 relative">
        <div className="flex justify-between items-center mb-8">
          <div>
            <span className="text-[#B58E58] text-[11px] font-bold tracking-[0.2em] uppercase">
              Browse Collections
            </span>
            <h2 className="text-2xl md:text-3xl font-serif font-bold text-[#031D44] mt-1">
              Shop By Categories
            </h2>
          </div>

          <div className="flex gap-2">
            <button
              onClick={scrollLeft}
              className="p-2.5 rounded-full border border-gray-200 text-gray-700 hover:bg-[#031D44] hover:text-white hover:border-[#031D44] transition-colors shadow-sm cursor-pointer"
            >
              <FiChevronLeft size={20} />
            </button>
            <button
              onClick={scrollRight}
              className="p-2.5 rounded-full border border-gray-200 text-gray-700 hover:bg-[#031D44] hover:text-white hover:border-[#031D44] transition-colors shadow-sm cursor-pointer"
            >
              <FiChevronRight size={20} />
            </button>
          </div>
        </div>

        {loading ? (
          <div className="text-center py-12 text-gray-400 text-sm">
            Loading categories from database...
          </div>
        ) : (
          <div
            ref={scrollRef}
            className="flex gap-5 overflow-x-auto scrollbar-none scroll-smooth pb-4 px-1"
            style={{ scrollbarWidth: "none", msOverflowStyle: "none" }}
          >
            {categories.map((category) => {
              let imageUrl = category.imageUrl
                ? `http://localhost/GatewayLinen/GatewayLinenadmin-main/${category.imageUrl}`
                : null;

              if (!imageUrl) {
                imageUrl = category.name.toLowerCase().includes("bed")
                  ? badsheetImg
                  : towelImg;
              }

              return (
                <div
                  key={category.categoryId}
                  onClick={() => handleCategoryClick(category.name)}
                  className="group flex-shrink-0 w-[calc(50%-10px)] md:w-[calc(33.333%-14px)] lg:w-[calc(20%-16px)] bg-white border border-gray-100 rounded-[40px] p-5 cursor-pointer hover:border-gray-300 hover:shadow-xl transition-all duration-300 flex flex-col items-center"
                >
                  <div className="w-[140px] h-[140px] md:w-[155px] md:h-[155px] rounded-full overflow-hidden mb-4 shadow-sm border border-gray-100 relative">
                    <img
                      src={imageUrl}
                      alt={category.name}
                      className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 ease-in-out"
                    />
                  </div>

                  <div className="w-full py-2.5 px-3 bg-gray-50 group-hover:bg-[#031D44] rounded-2xl transition-all duration-300 text-center mt-1">
                    <h3 className="text-[#031D44] group-hover:text-white text-[13.5px] font-semibold tracking-wide transition-colors">
                      {category.name}
                    </h3>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </section>
  );
};

export default CategoryGrid;
