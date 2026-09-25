import { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import {
  FiShoppingCart,
  FiHeart,
  FiArrowLeft,
  FiX,
  FiUser,
} from "react-icons/fi";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

const CategoryPage = () => {
  const { categoryName } = useParams();
  const navigate = useNavigate();
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showLoginModal, setShowLoginModal] = useState(false);

  const { toggleWishlistItem, isInWishlist } = useWishlist();
  const { addToCart } = useCart();

  useEffect(() => {
    window.scrollTo(0, 0);
    const fetchCategoryProducts = async () => {
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
          // Format product images properly with base path
          const formattedItems = result.data.items.map((item) => {
            const rawImg = item.ImageUrl || item.image || item.imageUrl || "";
            let finalProductImg =
              "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";

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
        }
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

  const formattedCategory = categoryName ? categoryName.replace("-", " ") : "";

  // Filter products matching category slug
  const filteredProducts = products.filter((item) => {
    const catSlug = (item.categoryName || "")
      .toLowerCase()
      .replace(/\s+/g, "-");
    return catSlug === categoryName?.toLowerCase();
  });

  return (
    <div className="w-full bg-[#F0EAE1] min-h-screen py-12 px-3 md:px-10 font-sans">
      <div className="max-w-[1536px] mx-auto">
        <div className="mb-10">
          <button
            onClick={() => navigate("/")}
            className="inline-flex items-center gap-2 px-4 py-2 bg-[#F7F2EB] border border-[#E5DCD0] text-xs font-bold uppercase tracking-wider rounded-xl text-[#031D44] hover:border-[#B58E58] transition-all cursor-pointer shadow-2xs mb-4"
          >
            <FiArrowLeft size={14} /> Back to Home
          </button>
          <h1 className="text-2xl md:text-4xl font-serif font-bold text-[#031D44] uppercase tracking-wide">
            {formattedCategory}
          </h1>
          <p className="text-xs md:text-sm text-gray-600 font-light mt-2">
            Explore our premium hospitality collection for wholesale and
            professional supplies.
          </p>
        </div>

        {loading ? (
          <div className="text-center py-20 text-xs font-bold text-gray-500 uppercase tracking-widest">
            Loading Category Products...
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
                  className="group flex flex-col bg-[#F7F2EB] rounded-2xl border border-[#E5DCD0] hover:border-[#B58E58] overflow-hidden shadow-sm hover:shadow-md transition-all cursor-pointer p-3 relative"
                >
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleAuthAction(() =>
                        toggleWishlistItem({
                          ...item,
                          id: item.productId,
                          price: formattedPrice,
                          name: item.name,
                          category: item.categoryName,
                        }),
                      );
                    }}
                    className="absolute top-4 right-4 z-20 p-2 bg-white/95 backdrop-blur-xs rounded-full shadow-md hover:scale-110 transition-transform cursor-pointer border border-gray-100"
                    title="Wishlist"
                  >
                    <FiHeart
                      size={14}
                      className={
                        isInWishlist(item.productId)
                          ? "fill-red-500 text-red-500"
                          : "text-gray-400 hover:text-gray-600"
                      }
                    />
                  </button>

                  <div className="relative h-36 sm:h-44 md:h-52 bg-[#FAF7F2] rounded-xl overflow-hidden mb-3 border border-gray-100">
                    <span className="absolute top-2 left-2 z-10 bg-[#031D44] text-white text-[8px] md:text-[9px] font-bold tracking-wider px-2 py-0.5 rounded-md shadow-md">
                      {item.isBestSeller ? "BEST SELLER" : "POPULAR"}
                    </span>
                    <img
                      src={item.image}
                      alt={item.name}
                      onError={(e) => {
                        e.target.src =
                          "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
                      }}
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
                        handleAuthAction(() => {
                          addToCart(
                            { ...item, id: item.productId, name: item.name },
                            1,
                            "Standard",
                            priceVal,
                          );
                          alert(`Added ${item.name} to cart!`);
                        });
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

      {showLoginModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity p-4">
          <div className="bg-[#F7F2EB] border border-[#E5DCD0] p-8 rounded-[28px] shadow-2xl w-full max-w-sm text-center relative">
            <button
              onClick={() => setShowLoginModal(false)}
              className="absolute top-5 right-5 text-gray-400 hover:text-gray-800 bg-white p-2 rounded-full transition-colors cursor-pointer border border-gray-200"
            >
              <FiX size={18} />
            </button>
            <div className="w-16 h-16 bg-[#031D44] text-[#B58E58] rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-md">
              <FiUser size={28} />
            </div>
            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-2">
              Login Required
            </h3>
            <p className="text-xs text-gray-600 mb-8 font-light leading-relaxed px-2">
              Please login first to add items to your cart, wishlist, or proceed
              to checkout.
            </p>
            <button
              onClick={() => navigate("/login")}
              className="w-full py-3.5 bg-[#031D44] text-white rounded-xl text-xs font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all cursor-pointer"
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
