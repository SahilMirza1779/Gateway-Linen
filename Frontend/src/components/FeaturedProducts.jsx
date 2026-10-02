import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { FiShoppingCart, FiHeart, FiX, FiUser, FiCheck } from "react-icons/fi";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

const FeaturedProducts = () => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [visibleCount, setVisibleCount] = useState(5);
  const [showLoginModal, setShowLoginModal] = useState(false);
  const [toastMessage, setToastMessage] = useState("");
  const navigate = useNavigate();
  const { toggleWishlistItem, isInWishlist } = useWishlist();
  const { addToCart } = useCart();

  useEffect(() => {
    const fetchProducts = async () => {
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
          const formattedItems = result.data.items.map((item) => {
            // Aaftab ke changes ya API update ke baad images array check karna
            let rawImg =
              item.imageUrl || item.ImageUrl || item.image || item.Image;

            // Agar imageUrl direct nahi mili, toh images array se pehli image nikalenge
            if (!rawImg && item.images && item.images.length > 0) {
              rawImg = item.images[0].imageUrl || item.images[0].ImageUrl;
            }

            // Fallback string ko blank set kar rahe hain, Unsplash nahi
            rawImg = rawImg || "";

            let finalImg =
              "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";

            if (rawImg.startsWith("http")) {
              finalImg = rawImg;
            } else if (rawImg !== "") {
              // Path cleaning
              const cleanPath = rawImg.replace(/^\/+/, "");
              finalImg = `http://localhost/Gateway-Linen/GatewayLinenAdmin-main/${cleanPath}`;
            }

            return {
              ...item,
              image: finalImg,
            };
          });

          setProducts(formattedItems);
        }
      } catch (error) {
        console.error("Error fetching featured products:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchProducts();
  }, []);

  const showToast = (msg) => {
    setToastMessage(msg);
    setTimeout(() => {
      setToastMessage("");
    }, 2500);
  };

  const handleAuthAction = (actionCallback) => {
    const loggedInUser = localStorage.getItem("user");
    if (!loggedInUser) {
      setShowLoginModal(true);
    } else {
      actionCallback();
    }
  };

  const handleLoadMore = () => {
    setVisibleCount((prevCount) => Math.min(prevCount + 5, products.length));
  };

  const displayedItems = products.slice(0, visibleCount);

  return (
    <section className="w-full py-12 md:py-16 px-3 md:px-10 bg-[#F0EAE1] font-sans relative">
      <div className="max-w-[1536px] mx-auto">
        <div className="text-center mb-8 md:mb-12">
          <span className="text-[10px] md:text-[11px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
            Top Picks For You
          </span>
          <h2 className="text-2xl md:text-4xl font-serif font-bold text-[#031D44] mt-1">
            Featured Products
          </h2>
          <div className="w-12 h-0.5 bg-[#B58E58] mx-auto mt-2 md:mt-3"></div>
        </div>

        {loading ? (
          <div className="text-center py-16 text-xs font-bold text-gray-500 uppercase tracking-widest">
            Loading Featured Products...
          </div>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 gap-3.5 md:gap-6">
            {displayedItems.map((item) => {
              const priceVal = item.basePrice || item.BasePrice || 0;
              const formattedPrice = `CAD $${Number(priceVal).toFixed(2)}`;

              return (
                <div
                  key={item.productId || item.ProductId}
                  onClick={() =>
                    navigate(`/product/${item.productId || item.ProductId}`)
                  }
                  className="group flex flex-col bg-[#F7F2EB] rounded-2xl border border-[#E5DCD0] hover:border-[#B58E58] overflow-hidden shadow-sm hover:shadow-md transition-all cursor-pointer p-3 relative"
                >
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleAuthAction(() =>
                        toggleWishlistItem({
                          ...item,
                          id: item.productId || item.ProductId,
                          price: formattedPrice,
                          name: item.name || item.Name,
                          category: item.categoryName || item.CategoryName,
                        }),
                      );
                    }}
                    className="absolute top-4 right-4 z-20 p-2 bg-white/95 backdrop-blur-xs rounded-full shadow-md hover:scale-110 transition-transform cursor-pointer border border-gray-100"
                    title="Wishlist"
                  >
                    <FiHeart
                      size={14}
                      className={
                        isInWishlist(item.productId || item.ProductId)
                          ? "fill-red-500 text-red-500"
                          : "text-gray-400 hover:text-gray-600"
                      }
                    />
                  </button>

                  <div className="relative h-36 sm:h-44 md:h-52 bg-[#FAF7F2] rounded-xl overflow-hidden mb-3 border border-gray-100">
                    <span className="absolute top-2 left-2 z-10 bg-[#031D44] text-white text-[8px] md:text-[9px] font-bold tracking-wider px-2 py-0.5 rounded-md shadow-md">
                      {item.isBestSeller || item.IsBestSeller
                        ? "BEST SELLER"
                        : item.isNewArrival || item.IsNewArrival
                          ? "NEW"
                          : "POPULAR"}
                    </span>
                    <img
                      src={item.image}
                      alt={item.name || item.Name}
                      onError={(e) => {
                        console.error(
                          "Local Image Load Failed URL -> ",
                          e.target.src,
                        );
                        e.target.src =
                          "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
                      }}
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    />
                  </div>

                  <div className="flex flex-col flex-grow justify-between">
                    <div>
                      <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-widest uppercase">
                        {item.categoryName || item.CategoryName || "LINEN"}
                      </span>
                      <h3 className="text-xs font-serif font-bold text-[#031D44] mt-1 line-clamp-1">
                        {item.name || item.Name}
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
                            {
                              ...item,
                              id: item.productId || item.ProductId,
                              name: item.name || item.Name,
                            },
                            1,
                            "Standard",
                            priceVal,
                          );
                          showToast(`Added ${item.name || item.Name} to cart!`);
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
        )}

        {visibleCount < products.length && (
          <div className="text-center mt-10 md:mt-12">
            <button
              onClick={handleLoadMore}
              className="px-6 py-3 md:px-8 md:py-3.5 bg-[#031D44] text-white text-xs font-semibold tracking-widest uppercase rounded-xl shadow-md hover:bg-[#B58E58] transition-all cursor-pointer"
            >
              Load More Products
            </button>
          </div>
        )}
      </div>

      {toastMessage && (
        <div className="fixed bottom-6 right-6 z-50 bg-[#031D44] text-white px-5 py-3 rounded-2xl shadow-2xl border border-[#B58E58]/40 flex items-center gap-3 animate-in fade-in slide-in-from-bottom-5 duration-300">
          <div className="w-6 h-6 bg-[#B58E58] text-white rounded-full flex items-center justify-center shrink-0">
            <FiCheck size={14} />
          </div>
          <span className="text-xs font-bold tracking-wide">
            {toastMessage}
          </span>
        </div>
      )}

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
    </section>
  );
};

export default FeaturedProducts;
