import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { FiShoppingCart, FiFilter } from "react-icons/fi";
import { useCart } from "../context/CartContext";

const ProductsPage = () => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedCategory, setSelectedCategory] = useState("ALL");
  const navigate = useNavigate();
  const { addToCart } = useCart();

  useEffect(() => {
    const fetchAllProducts = async () => {
      try {
        const response = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/products/api.php",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
            },
          },
        );
        const result = await response.json();
        if (result.success && result.data && result.data.items) {
          // Add resolvedImage to each product so it renders perfectly
          const formattedProducts = result.data.items.map((item) => {
            let rawImg =
              item.imageUrl || item.ImageUrl || item.image || item.Image || "";
            if (!rawImg && item.images && item.images.length > 0) {
              rawImg = item.images[0].imageUrl || item.images[0].ImageUrl || "";
            }

            let finalImg =
              "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";

            if (rawImg) {
              if (rawImg.startsWith("http")) {
                finalImg = rawImg;
              } else if (rawImg.startsWith("/")) {
                finalImg = `http://localhost${rawImg}`;
              } else {
                const cleanPath = rawImg.replace(/^\/+/, "");
                if (cleanPath.includes("Gateway-Linen")) {
                  finalImg = `http://localhost/${cleanPath}`;
                } else {
                  finalImg = `http://localhost/Gateway-Linen/GatewayLinenAdmin-main/${cleanPath}`;
                }
              }
            }

            return {
              ...item,
              resolvedImage: finalImg,
            };
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

  const filteredProducts =
    selectedCategory === "ALL"
      ? products
      : products.filter(
          (item) =>
            item.categoryName?.toUpperCase() === selectedCategory.toUpperCase(),
        );

  return (
    <div className="w-full bg-[#F0EAE1] min-h-screen py-8 md:py-12 px-3 md:px-10 font-sans">
      <div className="max-w-[1536px] mx-auto">
        <div className="mb-6 md:mb-8">
          <h1 className="text-2xl md:text-4xl font-serif font-bold text-[#031D44]">
            Products Catalog
          </h1>
          <p className="text-xs md:text-sm text-gray-600 font-light mt-1">
            Explore our complete collection of 5-star hotel grade linens and
            supplies.
          </p>
        </div>

        {/* Category Filters */}
        <div className="mb-8">
          <div className="block sm:hidden relative">
            <div className="flex items-center gap-2 mb-2">
              <FiFilter size={14} className="text-[#B58E58]" />
              <span className="text-[11px] font-bold text-[#031D44] uppercase tracking-wider">
                Filter Category:
              </span>
            </div>
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="w-full bg-[#F7F2EB] border border-[#E5DCD0] text-[#031D44] text-xs font-bold uppercase py-3 px-4 rounded-xl shadow-sm focus:outline-none focus:border-[#B58E58]"
            >
              {categories.map((cat, idx) => (
                <option key={idx} value={cat}>
                  {cat === "ALL" ? "All Categories" : cat}
                </option>
              ))}
            </select>
          </div>

          <div className="hidden sm:flex items-center gap-2.5 overflow-x-auto pb-2 scrollbar-none">
            <div className="flex items-center gap-1.5 text-xs font-bold text-[#031D44] uppercase tracking-wider mr-2 flex-shrink-0">
              <FiFilter size={13} className="text-[#B58E58]" /> Filter:
            </div>
            {categories.map((cat, idx) => (
              <button
                key={idx}
                onClick={() => setSelectedCategory(cat)}
                className={`px-4 py-2 rounded-full text-xs font-bold tracking-wider uppercase transition-all flex-shrink-0 cursor-pointer shadow-2xs ${
                  selectedCategory === cat
                    ? "bg-[#031D44] text-white shadow-md"
                    : "bg-[#F7F2EB] text-[#031D44] border border-[#E5DCD0] hover:border-[#B58E58]"
                }`}
              >
                {cat}
              </button>
            ))}
          </div>
        </div>

        {loading ? (
          <div className="text-center py-20 text-xs font-bold text-gray-500 uppercase tracking-widest">
            Loading Catalog...
          </div>
        ) : filteredProducts.length > 0 ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 gap-3.5 md:gap-6">
            {filteredProducts.map((item) => {
              const priceVal = item.basePrice || 0;
              const formattedPrice = `CAD $${Number(priceVal).toFixed(2)}`;

              return (
                <div
                  key={item.productId}
                  onClick={() => navigate(`/product/${item.productId}`)}
                  className="group flex flex-col bg-[#F7F2EB] rounded-2xl border border-[#E5DCD0] hover:border-[#B58E58] overflow-hidden shadow-sm hover:shadow-md transition-all cursor-pointer p-3"
                >
                  <div className="relative h-36 sm:h-44 md:h-52 bg-[#FAF7F2] rounded-xl overflow-hidden mb-3 border border-gray-100">
                    <span className="absolute top-2 left-2 z-10 bg-[#031D44] text-white text-[8px] md:text-[9px] font-bold tracking-wider px-2 py-0.5 rounded-md shadow-md">
                      {item.isBestSeller ? "BEST SELLER" : "POPULAR"}
                    </span>
                    <img
                      src={item.resolvedImage}
                      onError={(e) => {
                        e.target.src =
                          "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
                      }}
                      alt={item.name}
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    />
                  </div>

                  <div className="flex flex-col flex-grow justify-between">
                    <div>
                      <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-widest uppercase">
                        {item.categoryName || "LINEN"}
                      </span>
                      <h3 className="text-xs font-serif font-bold text-[#031D44] mt-1 line-clamp-1">
                        {item.name}
                      </h3>
                      <div className="text-xs font-bold text-gray-900 mt-1">
                        {formattedPrice}
                      </div>
                    </div>

                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        addToCart(
                          {
                            ...item,
                            id: item.productId,
                            name: item.name,
                            image: item.resolvedImage,
                          },
                          1,
                          "Standard",
                          priceVal,
                        );
                        alert(`Added ${item.name} to cart!`);
                      }}
                      className="mt-3 w-full flex items-center justify-center gap-1.5 py-2 md:py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-[10px] md:text-[11px] font-bold text-[#031D44] hover:bg-[#031D44] hover:text-white hover:border-[#031D44] transition-all shadow-2xs cursor-pointer"
                    >
                      <FiShoppingCart size={12} />
                      <span>Add to Cart</span>
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div className="py-20 text-center">
            <p className="text-sm text-gray-500 font-light">
              No products found in this category.
            </p>
          </div>
        )}
      </div>
    </div>
  );
};

export default ProductsPage;
