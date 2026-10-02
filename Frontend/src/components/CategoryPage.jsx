import { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import {
  FiShoppingCart,
  FiHeart,
  FiArrowLeft,
  FiX,
  FiUser,
  FiChevronRight,
} from "react-icons/fi";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

const CategoryPage = () => {
  const { categoryName } = useParams();
  const navigate = useNavigate();
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showLoginModal, setShowLoginModal] = useState(false);

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
          },
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
            "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=500&auto=format&fit=crop";

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

  // Filter products matching category slug
  const filteredProducts = products.filter((item) => {
    const catName =
      item.categoryName || item.CategoryName || item.category || "";
    const catSlug = catName.toLowerCase().replace(/\s+/g, "-");
    return catSlug === categoryName?.toLowerCase() || categoryName === "all";
  });

  return (
    <div className="w-full bg-white min-h-screen py-8 md:py-14 px-4 sm:px-6 md:px-12 font-sans">
      <div className="max-w-[1400px] mx-auto">
        {/* Breadcrumb Header */}
        <div className="flex items-center gap-2 text-xs text-gray-500 mb-6">
          <span
            onClick={() => navigate("/")}
            className="hover:text-[#031D44] cursor-pointer"
          >
            Home
          </span>
          <FiChevronRight size={12} />
          <span
            onClick={() => navigate("/products")}
            className="hover:text-[#031D44] cursor-pointer"
          >
            Collections
          </span>
          <FiChevronRight size={12} />
          <span className="text-[#031D44] font-bold uppercase">
            {formattedCategory}
          </span>
        </div>

        {/* Header Section */}
        <div className="mb-8 md:mb-10 border-b border-[#E5DCD0] pb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <div>
            <button
              onClick={() => navigate("/")}
              className="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 border border-[#E5DCD0] text-xs font-bold uppercase tracking-wider rounded-xl text-[#031D44] hover:border-[#031D44] transition-all cursor-pointer mb-4 shadow-2xs"
            >
              <FiArrowLeft size={14} /> Back to Home
            </button>
            <span className="text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase block mb-1">
              EXCLUSIVE WHOLESALE CATALOG
            </span>
            <h1 className="text-2xl sm:text-3xl md:text-4xl font-serif font-bold text-[#031D44] uppercase tracking-tight">
              {formattedCategory}
            </h1>
            <p className="text-xs sm:text-sm text-gray-600 font-light mt-2 max-w-2xl leading-relaxed">
              Explore our premium hospitality collection for wholesale and
              professional supplies tailored for luxury comfort.
            </p>
          </div>
          <div className="bg-[#FAF7F2] px-4 py-2 rounded-xl border border-[#E5DCD0] text-xs font-bold text-[#031D44] shadow-2xs shrink-0">
            {filteredProducts.length} Products Available
          </div>
        </div>

        {loading ? (
          <div className="text-center py-20 text-xs font-bold text-gray-500 uppercase tracking-widest">
            Loading Category Products...
          </div>
        ) : filteredProducts.length > 0 ? (
          <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-5">
            {filteredProducts.map((item) => {
              const productId = item.productId || item.ProductId || item.id;
              const productName = item.name || item.Name || "Product Name";
              const priceVal =
                item.basePrice || item.price || item.Price || "29.99";
              const formattedPrice = `$ ${Number(priceVal).toFixed(2)}`;
              const unitVal = item.unit || item.Unit || "EACH";
              const catLabel =
                item.categoryName || item.CategoryName || "LINEN";

              return (
                <div
                  key={productId}
                  onClick={() => navigate(`/product/${productId}`)}
                  className="group flex flex-col bg-white rounded-2xl border border-[#E5DCD0] hover:border-[#B58E58] overflow-hidden shadow-2xs hover:shadow-md transition-all cursor-pointer p-3 sm:p-4 justify-between relative"
                >
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
                        }),
                      );
                    }}
                    className="absolute top-3 right-3 sm:top-4 sm:right-4 z-20 w-7 h-7 sm:w-8 sm:h-8 bg-white/90 rounded-full flex items-center justify-center text-gray-600 hover:text-red-500 shadow-xs transition-transform hover:scale-110"
                    title="Wishlist"
                  >
                    <FiHeart
                      size={14}
                      className={
                        isInWishlist(productId)
                          ? "fill-red-500 text-red-500"
                          : "text-gray-400 hover:text-red-500"
                      }
                    />
                  </button>

                  <div>
                    <div className="w-full aspect-[4/3] bg-gray-50 rounded-xl overflow-hidden mb-3 relative border border-gray-100">
                      <img
                        src={item.image}
                        alt={productName}
                        onError={(e) => {
                          e.target.onerror = null;
                          e.target.src =
                            "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=500&auto=format&fit=crop";
                        }}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                      />
                    </div>

                    <div className="flex items-center gap-2 mb-1.5">
                      <span className="text-[9px] text-[#B58E58] font-bold uppercase tracking-widest">
                        {catLabel}
                      </span>
                    </div>

                    <h3 className="text-xs sm:text-sm font-medium text-[#031D44] mb-3 line-clamp-2 min-h-[34px] leading-tight">
                      {productName}
                    </h3>
                  </div>

                  <div>
                    <div className="pt-2.5 border-t border-[#E5DCD0] flex flex-col sm:flex-row items-baseline justify-between mb-2.5 gap-0.5">
                      <span className="text-[10px] text-gray-400">
                        Starting at:
                      </span>
                      <span className="text-xs sm:text-sm font-bold text-[#B58E58]">
                        {formattedPrice} / {unitVal}
                      </span>
                    </div>

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
                      className="w-full py-2.5 bg-[#031D44] hover:bg-[#B58E58] text-white text-[11px] sm:text-xs font-bold rounded-xl transition-colors flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
                    >
                      <FiShoppingCart size={14} />
                      <span>Add to Cart</span>
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div className="py-20 text-center bg-[#FAF7F2] rounded-2xl border border-dashed border-[#E5DCD0]">
            <p className="text-sm text-gray-500 font-light">
              No products found in this category.
            </p>
          </div>
        )}
      </div>

      {showLoginModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-[#FAF7F2] border border-[#E5DCD0] p-6 sm:p-8 rounded-[24px] shadow-2xl w-full max-w-sm text-center relative">
            <button
              onClick={() => setShowLoginModal(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-800 bg-white p-1.5 rounded-full transition-colors cursor-pointer border border-[#E5DCD0]"
            >
              <FiX size={18} />
            </button>
            <div className="w-14 h-14 bg-[#031D44] text-[#B58E58] rounded-xl flex items-center justify-center mx-auto mb-4 shadow-md">
              <FiUser size={26} />
            </div>
            <h3 className="text-lg font-serif font-bold text-[#031D44] mb-2">
              Login Required
            </h3>
            <p className="text-xs text-gray-500 mb-6 font-light leading-relaxed">
              Please login first to add items to your cart, wishlist, or proceed
              to checkout.
            </p>
            <button
              onClick={() => navigate("/login")}
              className="w-full py-3 bg-[#031D44] text-white rounded-xl text-xs font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all cursor-pointer"
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
