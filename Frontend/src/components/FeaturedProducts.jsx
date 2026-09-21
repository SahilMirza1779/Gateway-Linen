import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { FiShoppingCart, FiHeart, FiX, FiUser, FiCheck } from "react-icons/fi";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

// Saare images import kar liye without spaces
import luxuryhotelbathtowel from "../assets/newImages/luxuryhotelbathtowel.jpg";
import premiumspapooltowel from "../assets/newImages/premiumspapooltowel.jpg";
import ultraPlushHandTowel from "../assets/newImages/ultra-plushhandtowel.jpg";
import egyptianCottonKingSheet from "../assets/newImages/egyptianCottonKingSheet.jpg";
import commercialGradeWhiteFittedSheet from "../assets/newImages/commercialGradeWhiteFittedSheet.jpg";
import waterproofHospitalityMattressPad from "../assets/newImages/waterproofHospitalityMattressPad.jpg";
import plushPillowTopMattressProtector from "../assets/newImages/plushPillowTopMattressProtector.jpg";
import downAlternativeHotelPillow from "../assets/newImages/downAlternativeHotelPillow.jpg";
import firmSupportGussetedPillow from "../assets/newImages/firmSupportGussetedPillow.jpg";
import thermalWaffleWeaveBlanket from "../assets/newImages/thermalWaffleWeaveBlanket.jpg";
import plushFleeceHospitalityBlanket from "../assets/newImages/plushFleeceHospitalityBlanket.jpg";
import luxuryBathMatSet from "../assets/newImages/luxuryBathMatSet.jpg";
import waterproofShowerCurtain from "../assets/newImages/waterproofShowerCurtain.jpg";

const allFeaturedItems = [
  {
    id: 1,
    category: "TOWELS",
    name: "Luxury Hotel Bath Towel",
    price: "CAD 24.99",
    tag: "BEST SELLER",
    image: luxuryhotelbathtowel,
  },
  {
    id: 2,
    category: "TOWELS",
    name: "Premium Spa Pool Towel",
    price: "CAD 29.99",
    tag: "POPULAR",
    image: premiumspapooltowel,
  },
  {
    id: 3,
    category: "TOWELS",
    name: "Ultra-Plush Hand Towel",
    price: "CAD 12.99",
    tag: "TOP RATED",
    image: ultraPlushHandTowel,
  },
  {
    id: 4,
    category: "BED SHEETS",
    name: "Egyptian Cotton King Sheet Set",
    price: "CAD 89.99",
    tag: "NEW ARRIVAL",
    image: egyptianCottonKingSheet,
  },
  {
    id: 5,
    category: "BED SHEETS",
    name: "Commercial Grade White Fitted Sheet",
    price: "CAD 45.00",
    tag: "FEATURED",
    image: commercialGradeWhiteFittedSheet,
  },
  {
    id: 6,
    category: "MATTRESS PADS",
    name: "Waterproof Hospitality Mattress Pad",
    price: "CAD 54.99",
    tag: "POPULAR",
    image: waterproofHospitalityMattressPad,
  },
  {
    id: 7,
    category: "MATTRESS PADS",
    name: "Plush Pillow-Top Mattress Protector",
    price: "CAD 69.99",
    tag: "PREMIUM",
    image: plushPillowTopMattressProtector,
  },
  {
    id: 8,
    category: "PILLOWS",
    name: "Down-Alternative Hotel Pillow",
    price: "CAD 34.99",
    tag: "TOP RATED",
    image: downAlternativeHotelPillow,
  },
  {
    id: 9,
    category: "PILLOWS",
    name: "Firm Support Gusseted Pillow",
    price: "CAD 39.99",
    tag: "BEST SELLER",
    image: firmSupportGussetedPillow,
  },
  {
    id: 10,
    category: "BLANKETS",
    name: "Thermal Waffle Weave Blanket",
    price: "CAD 49.99",
    tag: "TRENDING",
    image: thermalWaffleWeaveBlanket,
  },
  {
    id: 11,
    category: "BLANKETS",
    name: "Plush Fleece Hospitality Blanket",
    price: "CAD 59.99",
    tag: "HOT DEAL",
    image: plushFleeceHospitalityBlanket,
  },
  {
    id: 12,
    category: "OTHERS",
    name: "Luxury Bath Mat Set",
    price: "CAD 19.99",
    tag: "POPULAR",
    image: luxuryBathMatSet,
  },
  {
    id: 13,
    category: "OTHERS",
    name: "Waterproof Shower Curtain",
    price: "CAD 22.99",
    tag: "NEW",
    image: waterproofShowerCurtain,
  },
];

const FeaturedProducts = () => {
  const [visibleCount, setVisibleCount] = useState(5);
  const [showLoginModal, setShowLoginModal] = useState(false);
  const [toastMessage, setToastMessage] = useState("");
  const navigate = useNavigate();
  const { toggleWishlistItem, isInWishlist } = useWishlist();
  const { addToCart } = useCart();

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
    setVisibleCount((prevCount) =>
      Math.min(prevCount + 5, allFeaturedItems.length),
    );
  };

  const displayedItems = allFeaturedItems.slice(0, visibleCount);

  return (
    <section className="w-full py-12 md:py-16 px-3 md:px-10 bg-[#F0EAE1] font-sans relative">
      <div className="max-w-[1536px] mx-auto">
        {/* Header Title */}
        <div className="text-center mb-8 md:mb-12">
          <span className="text-[10px] md:text-[11px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
            Top Picks For You
          </span>
          <h2 className="text-2xl md:text-4xl font-serif font-bold text-[#031D44] mt-1">
            Featured Products
          </h2>
          <div className="w-12 h-0.5 bg-[#B58E58] mx-auto mt-2 md:mt-3"></div>
        </div>

        {/* Product Grid - Mobile par 2 columns, Laptop par 5 columns */}
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 gap-3.5 md:gap-6">
          {displayedItems.map((item) => (
            <div
              key={item.id}
              onClick={() => navigate(`/product/${item.id}`)}
              className="group flex flex-col bg-[#F7F2EB] rounded-2xl border border-[#E5DCD0] hover:border-[#B58E58] overflow-hidden shadow-sm hover:shadow-md transition-all cursor-pointer p-3 relative"
            >
              {/* Wishlist Heart Icon Button */}
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  handleAuthAction(() => toggleWishlistItem(item));
                }}
                className="absolute top-4 right-4 z-20 p-2 bg-white/95 backdrop-blur-xs rounded-full shadow-md hover:scale-110 transition-transform cursor-pointer border border-gray-100"
                title="Wishlist"
              >
                <FiHeart
                  size={14}
                  className={
                    isInWishlist(item.id)
                      ? "fill-red-500 text-red-500"
                      : "text-gray-400 hover:text-gray-600"
                  }
                />
              </button>

              {/* Image Container */}
              <div className="relative h-36 sm:h-44 md:h-52 bg-[#FAF7F2] rounded-xl overflow-hidden mb-3 border border-gray-100">
                <span className="absolute top-2 left-2 z-10 bg-[#031D44] text-white text-[8px] md:text-[9px] font-bold tracking-wider px-2 py-0.5 rounded-md shadow-md">
                  {item.tag}
                </span>
                <img
                  src={item.image}
                  alt={item.name}
                  className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                />
              </div>

              {/* Details */}
              <div className="flex flex-col flex-grow justify-between">
                <div>
                  <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-widest uppercase">
                    {item.category}
                  </span>
                  <h3 className="text-xs font-serif font-bold text-[#031D44] mt-1 line-clamp-1">
                    {item.name}
                  </h3>
                  <div className="text-xs font-bold text-gray-900 mt-1">
                    {item.price}
                  </div>
                </div>

                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    handleAuthAction(() => {
                      const numericPrice = Number(
                        item.price.replace(/[^0-9.]/g, ""),
                      );
                      addToCart(item, 1, "Standard", numericPrice);
                      showToast(`Added ${item.name} to cart!`);
                    });
                  }}
                  className="mt-3 w-full flex items-center justify-center gap-1.5 py-2 md:py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-[10px] md:text-[11px] font-bold text-[#031D44] hover:bg-[#031D44] hover:text-white hover:border-[#031D44] transition-all shadow-2xs cursor-pointer"
                >
                  <FiShoppingCart size={12} />
                  <span>Add to Cart</span>
                </button>
              </div>
            </div>
          ))}
        </div>

        {/* Load More Button */}
        {visibleCount < allFeaturedItems.length && (
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

      {/* Custom Theme-Matched Toast Notification */}
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

      {/* Login Required Modal */}
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
