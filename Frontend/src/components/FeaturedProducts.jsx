import { useState, useEffect } from "react";
import { useNavigate, Link } from "react-router-dom";
import { FiChevronLeft, FiChevronRight, FiHeart } from "react-icons/fi";

// Helper function to format image paths correctly
const resolveImageUrl = (rawImg) => {
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
  return finalImg;
};

const FeaturedProducts = () => {
  const navigate = useNavigate();
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("Featured Products");

  const [currentImageIndices, setCurrentImageIndices] = useState({});

  const tabs = [
    "Featured Products",
    "Bedding",
    "Bathroom",
    "Amenities",
    "Table Linen",
    "Healthcare",
    "New Products",
  ];

  const fallbackProducts = [
    {
      productId: 6,
      name: "Mattress Pad",
      basePrice: "24.78",
      unit: "EACH",
      resolvedImages: [
        "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=500&auto=format&fit=crop",
        "https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=500&auto=format&fit=crop",
      ],
      categoryName: "Bedding",
    },
  ];

  useEffect(() => {
    const fetchFeaturedProducts = async () => {
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

        let rawItems = [];
        if (result.success && result.data && result.data.items) {
          rawItems = result.data.items;
        } else if (result.success && Array.isArray(result.data)) {
          rawItems = result.data;
        } else if (Array.isArray(result)) {
          rawItems = result;
        }

        if (rawItems.length > 0) {
          const formattedProducts = rawItems.map((item) => {
            let imageList = [];

            if (
              item.images &&
              Array.isArray(item.images) &&
              item.images.length > 0
            ) {
              imageList = item.images.map((imgObj) => {
                const raw = imgObj.imageUrl || imgObj.ImageUrl || "";
                return resolveImageUrl(raw);
              });
            }

            if (imageList.length === 0) {
              let rawImg =
                item.imageUrl ||
                item.ImageUrl ||
                item.image ||
                item.Image ||
                "";
              if (rawImg) {
                imageList.push(resolveImageUrl(rawImg));
              }
            }

            if (imageList.length === 0) {
              imageList.push(
                "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600",
              );
            }

            return {
              ...item,
              resolvedImages: imageList,
            };
          });

          setProducts(formattedProducts);
        } else {
          setProducts(fallbackProducts);
        }
      } catch (error) {
        console.error("Products API Fetch Error:", error);
        setProducts(fallbackProducts);
      } finally {
        setLoading(false);
      }
    };

    fetchFeaturedProducts();
  }, []);

  useEffect(() => {
    if (products.length === 0) return;

    const interval = setInterval(() => {
      setCurrentImageIndices((prevIndices) => {
        const updated = { ...prevIndices };
        products.forEach((prod) => {
          const id = prod.productId || prod.ProductId || prod.id || 6;
          const totalImages = prod.resolvedImages
            ? prod.resolvedImages.length
            : 1;
          if (totalImages > 1) {
            const currentIndex = prevIndices[id] || 0;
            updated[id] = (currentIndex + 1) % totalImages;
          }
        });
        return updated;
      });
    }, 6000);

    return () => clearInterval(interval);
  }, [products]);

  const scrollLeft = () => {
    document
      .getElementById("featured-product-slider")
      .scrollBy({ left: -300, behavior: "smooth" });
  };

  const scrollRight = () => {
    document
      .getElementById("featured-product-slider")
      .scrollBy({ left: 300, behavior: "smooth" });
  };

  const displayProducts = products.length > 0 ? products : fallbackProducts;
  const filteredProducts = displayProducts.filter((prod) => {
    if (activeTab === "Featured Products") return true;
    const catName =
      prod.categoryName || prod.CategoryName || prod.category || "";
    return catName.toLowerCase().includes(activeTab.toLowerCase());
  });

  const finalProductsToRender =
    filteredProducts.length > 0 ? filteredProducts : displayProducts;

  return (
    <section className="py-12 md:py-20 px-4 md:px-10 bg-white font-sans border-t border-gray-100 overflow-hidden">
      <div className="max-w-[1400px] mx-auto relative">
        {/* Header Title & All Products Link */}
        <div className="flex flex-col sm:flex-row justify-between items-center mb-6 md:mb-8 gap-4">
          <div className="text-center sm:text-left">
            <span className="text-[10px] md:text-xs font-bold text-[#B58E58] tracking-[0.25em] uppercase block mb-1">
              Top Picks For You
            </span>
            <h2 className="text-2xl md:text-[36px] font-bold text-[#4A5568] tracking-tight">
              Featured Products
            </h2>
          </div>

          <Link
            to="/products"
            className="hidden md:block text-xs md:text-sm font-bold text-[#A03434] hover:underline uppercase tracking-wider cursor-pointer"
          >
            ALL PRODUCTS &rarr;
          </Link>
        </div>

        {/* Category Tabs Header */}
        <div className="flex items-center justify-start md:justify-center gap-5 md:gap-10 overflow-x-auto scrollbar-hide border-b border-gray-200 pb-3 mb-8 md:mb-10">
          {tabs.map((tab) => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={`text-xs md:text-sm font-medium whitespace-nowrap pb-2 transition-colors cursor-pointer relative ${
                activeTab === tab
                  ? "text-[#A03434] font-semibold"
                  : "text-gray-500 hover:text-[#031D44]"
              }`}
            >
              {tab}
              {activeTab === tab && (
                <span className="absolute bottom-[-13px] left-0 w-full h-[2px] bg-[#A03434]"></span>
              )}
            </button>
          ))}
        </div>

        {loading && (
          <div className="text-center py-6 text-xs text-gray-500 uppercase tracking-widest font-bold">
            Loading products from database...
          </div>
        )}

        {/* ======================================================== */}
        {/* 📱 MOBILE VIEW: 2x2 Grid for first 4 products */}
        {/* ======================================================== */}
        <div className="grid grid-cols-2 gap-3 md:hidden">
          {finalProductsToRender.slice(0, 4).map((prod) => {
            const prodId = prod.productId || prod.ProductId || prod.id || 6;
            const prodName =
              prod.name || prod.Name || prod.productName || "Product Name";
            const prodPrice =
              prod.basePrice || prod.price || prod.Price || "24.78";
            const prodUnit = prod.unit || prod.Unit || "EACH";

            const imageList = prod.resolvedImages || [
              "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=400&auto=format&fit=crop",
            ];
            const activeIndex = currentImageIndices[prodId] || 0;
            const currentImage = imageList[activeIndex] || imageList[0];

            return (
              <div
                key={`mobile-${prodId}`}
                onClick={() => navigate(`/product/${prodId}`)}
                className="w-full bg-white border border-gray-200 rounded-xl p-2.5 flex flex-col justify-between group cursor-pointer shadow-2xs hover:shadow-md transition-all"
              >
                <div>
                  <div className="w-full aspect-square bg-gray-50 rounded-lg overflow-hidden mb-2.5 relative">
                    <img
                      key={currentImage}
                      src={currentImage}
                      alt={prodName}
                      className="w-full h-full object-cover transition-opacity duration-700 group-hover:scale-105"
                      onError={(e) => {
                        e.target.onerror = null;
                        e.target.src =
                          "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=400&auto=format&fit=crop";
                      }}
                    />
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                      }}
                      className="absolute top-2 right-2 w-6 h-6 bg-white/80 rounded-full flex items-center justify-center text-gray-600 hover:text-red-500 shadow-xs cursor-pointer"
                    >
                      <FiHeart size={12} />
                    </button>
                  </div>
                  <div className="flex items-center gap-1 mb-1.5">
                    <span className="text-[9px] text-gray-400">Colors:</span>
                    <span className="w-2.5 h-2.5 rounded-full border border-gray-300 inline-block bg-white shadow-2xs"></span>
                  </div>
                  <h3 className="text-[11px] font-medium text-[#4A5568] mb-2 line-clamp-2 min-h-[32px] leading-tight">
                    {prodName}
                  </h3>
                </div>
                <div className="pt-2 border-t border-gray-100 flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-0.5">
                  <span className="text-[9px] text-gray-400">Starting at:</span>
                  <span className="text-[11px] font-bold text-[#A03434]">
                    $ {Number(prodPrice).toFixed(2)} / {prodUnit}
                  </span>
                </div>
              </div>
            );
          })}
        </div>

        {/* Mobile View All Products Button */}
        <div className="mt-6 md:hidden">
          <button
            onClick={() => navigate("/products")}
            className="w-full bg-transparent border border-[#A03434] text-[#A03434] hover:bg-[#A03434] hover:text-white px-6 py-3.5 text-[11px] font-bold uppercase tracking-widest transition-colors cursor-pointer rounded-none shadow-sm"
          >
            View All Products
          </button>
        </div>

        {/* ======================================================== */}
        {/* 💻 DESKTOP/LAPTOP VIEW: Horizontal Scrollable Slider */}
        {/* ======================================================== */}
        <div className="relative mt-4 hidden md:block">
          <button
            onClick={scrollLeft}
            className="absolute left-0 top-[45%] -translate-y-1/2 -ml-5 z-10 bg-white shadow-md p-2.5 rounded-full border border-gray-200 text-gray-600 hover:text-black transition-colors cursor-pointer flex items-center justify-center"
          >
            <FiChevronLeft size={20} />
          </button>

          <button
            onClick={scrollRight}
            className="absolute right-0 top-[45%] -translate-y-1/2 -mr-5 z-10 bg-white shadow-md p-2.5 rounded-full border border-gray-200 text-gray-600 hover:text-black transition-colors cursor-pointer flex items-center justify-center"
          >
            <FiChevronRight size={20} />
          </button>

          {/* Products Horizontal Slider */}
          <div
            id="featured-product-slider"
            className="flex gap-5 overflow-x-auto scrollbar-hide pb-6 snap-x snap-mandatory px-2"
            style={{ scrollbarWidth: "none", msOverflowStyle: "none" }}
          >
            {finalProductsToRender.map((prod) => {
              const prodId = prod.productId || prod.ProductId || prod.id || 6;
              const prodName =
                prod.name || prod.Name || prod.productName || "Product Name";
              const prodPrice =
                prod.basePrice || prod.price || prod.Price || "24.78";
              const prodUnit = prod.unit || prod.Unit || "EACH";

              const imageList = prod.resolvedImages || [
                "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=400&auto=format&fit=crop",
              ];
              const activeIndex = currentImageIndices[prodId] || 0;
              const currentImage = imageList[activeIndex] || imageList[0];

              return (
                <div
                  key={`desktop-${prodId}`}
                  onClick={() => navigate(`/product/${prodId}`)}
                  className="min-w-[220px] max-w-[240px] bg-white border border-gray-200 rounded-xl p-4 flex flex-col justify-between snap-start group cursor-pointer shadow-2xs hover:shadow-md transition-all"
                >
                  <div>
                    {/* Image Box with 6-second Auto Slider */}
                    <div className="w-full aspect-square bg-gray-50 rounded-lg overflow-hidden mb-3 relative">
                      <img
                        key={currentImage}
                        src={currentImage}
                        alt={prodName}
                        className="w-full h-full object-cover transition-opacity duration-700 group-hover:scale-105"
                        onError={(e) => {
                          e.target.onerror = null;
                          e.target.src =
                            "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=400&auto=format&fit=crop";
                        }}
                      />

                      {imageList.length > 1 && (
                        <div className="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1 z-10 bg-black/20 px-2 py-0.5 rounded-full backdrop-blur-xs">
                          {imageList.map((_, dotIdx) => (
                            <span
                              key={dotIdx}
                              className={`w-1.5 h-1.5 rounded-full transition-all ${
                                dotIdx === activeIndex
                                  ? "bg-white w-3"
                                  : "bg-white/60"
                              }`}
                            ></span>
                          ))}
                        </div>
                      )}

                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                        }}
                        className="absolute top-2.5 right-2.5 w-7 h-7 bg-white/80 rounded-full flex items-center justify-center text-gray-600 hover:text-red-500 shadow-xs cursor-pointer"
                      >
                        <FiHeart size={14} />
                      </button>
                    </div>

                    {/* Available Color Option */}
                    <div className="flex items-center gap-1.5 mb-2">
                      <span className="text-[10px] text-gray-400">
                        Available Color:
                      </span>
                      <span className="w-3 h-3 rounded-full border border-gray-300 inline-block bg-white shadow-2xs"></span>
                    </div>

                    {/* Product Title */}
                    <h3 className="text-sm font-medium text-[#4A5568] mb-3 line-clamp-2 min-h-[36px]">
                      {prodName}
                    </h3>
                  </div>

                  {/* Price & Unit */}
                  <div className="pt-3 border-t border-gray-100 flex items-baseline justify-between">
                    <span className="text-[11px] text-gray-400">
                      Starting at:
                    </span>
                    <span className="text-sm font-bold text-[#A03434]">
                      $ {Number(prodPrice).toFixed(2)} / {prodUnit}
                    </span>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
};

export default FeaturedProducts;
