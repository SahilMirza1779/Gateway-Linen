import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { FiShoppingCart, FiHeart, FiX, FiUser } from "react-icons/fi";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

// Saare images import kar liye taaki load more chal sake
import luxuryhotelbathtowel from "../assets/newImages/luxuryhotelbathtowel.jpg";
import premiumspapooltowel from "../assets/newImages/premiumspapooltowel.jpg";
import ultraPlushHandTowel from "../assets/newImages/ultra-plushhandtowel.jpg";
import egyptianCottonKingSheet from "../assets/newImages/egyptianCottonKingSheet.jpg";
import commercialGradeWhiteFittedSheet from "../assets/newImages/CommercialGradeWhiteFittedSheet.jpg";
import waterproofHospitalityMattressPad from "../assets/newImages/Waterproof Hospitality Mattress Pad.jpg";
import plushPillowTopMattressProtector from "../assets/newImages/Plush Pillow-Top Mattress Protector.jpg";
import downAlternativeHotelPillow from "../assets/newImages/Down-Alternative Hotel Pillow.jpg";
import firmSupportGussetedPillow from "../assets/newImages/Firm Support Gusseted Pillow.jpg";
import thermalWaffleWeaveBlanket from "../assets/newImages/Thermal Waffle Weave Blanket.jpg";
import plushFleeceHospitalityBlanket from "../assets/newImages/Plush Fleece Hospitality Blanket.jpg";
import luxuryBathMatSet from "../assets/newImages/Luxury Bath Mat Set.jpg";
import waterproofShowerCurtain from "../assets/newImages/Waterproof Shower Curtain.jpg";

const products = [
  {
    id: 1,
    name: "Luxury Hotel Bath Towel",
    category: "TOWELS",
    price: 24.99,
    image: luxuryhotelbathtowel,
    tag: "BEST SELLER",
  },
  {
    id: 2,
    name: "Premium Spa Pool Towel",
    category: "TOWELS",
    price: 29.99,
    image: premiumspapooltowel,
    tag: "POPULAR",
  },
  {
    id: 3,
    name: "Ultra-Plush Hand Towel",
    category: "TOWELS",
    price: 12.99,
    image: ultraPlushHandTowel,
    tag: "TOP RATED",
  },
  {
    id: 4,
    name: "Egyptian Cotton King Sheet Set",
    category: "BED SHEETS",
    price: 89.99,
    image: egyptianCottonKingSheet,
    tag: "NEW ARRIVAL",
  },
  {
    id: 5,
    name: "Commercial Grade White Fitted Sheet",
    category: "BED SHEETS",
    price: 45.0,
    image: commercialGradeWhiteFittedSheet,
    tag: "HOT SALE",
  },
  {
    id: 6,
    name: "Waterproof Hospitality Mattress Pad",
    category: "MATTRESS PADS",
    price: 54.99,
    image: waterproofHospitalityMattressPad,
    tag: "POPULAR",
  },
  {
    id: 7,
    name: "Plush Pillow-Top Mattress Protector",
    category: "MATTRESS PADS",
    price: 69.99,
    image: plushPillowTopMattressProtector,
    tag: "BEST SELLER",
  },
  {
    id: 8,
    name: "Down-Alternative Hotel Pillow",
    category: "PILLOWS",
    price: 34.99,
    image: downAlternativeHotelPillow,
    tag: "NEW",
  },
  {
    id: 9,
    name: "Firm Support Gusseted Pillow",
    category: "PILLOWS",
    price: 39.99,
    image: firmSupportGussetedPillow,
    tag: "TOP RATED",
  },
  {
    id: 10,
    name: "Thermal Waffle Weave Blanket",
    category: "BLANKETS",
    price: 49.99,
    image: thermalWaffleWeaveBlanket,
    tag: "TRENDING",
  },
  {
    id: 11,
    name: "Plush Fleece Hospitality Blanket",
    category: "BLANKETS",
    price: 59.99,
    image: plushFleeceHospitalityBlanket,
    tag: "HOT SALE",
  },
  {
    id: 12,
    name: "Luxury Bath Mat Set",
    category: "OTHERS",
    price: 19.99,
    image: luxuryBathMatSet,
    tag: "POPULAR",
  },
  {
    id: 13,
    name: "Waterproof Shower Curtain",
    category: "OTHERS",
    price: 22.99,
    image: waterproofShowerCurtain,
    tag: "NEW ARRIVAL",
  },
];

export default function FeaturedProducts() {
  const navigate = useNavigate();
  const { toggleWishlistItem, isInWishlist } = useWishlist();
  const { addToCart } = useCart();

  const [showLoginModal, setShowLoginModal] = useState(false);
  const [visibleCount, setVisibleCount] = useState(6); // Default 6 products dikhenge

  const handleLoadMore = () => {
    setVisibleCount((prevCount) => prevCount + 6); // Load more par 6 aur add honge
  };

  const handleWishlistClick = (e, product) => {
    e.stopPropagation();
    const user = localStorage.getItem("user");
    if (!user) {
      setShowLoginModal(true);
    } else {
      toggleWishlistItem(product);
    }
  };

  const handleAddToCartClick = (e, product) => {
    e.stopPropagation();
    const user = localStorage.getItem("user");
    if (!user) {
      setShowLoginModal(true);
    } else {
      addToCart(product, 1, "Standard", product.price);
    }
  };

  return (
    <section className="py-16 bg-white px-2 md:px-10 w-full overflow-hidden">
      <div className="max-w-[1536px] mx-auto">
        {/* Section Heading */}
        <div className="text-center mb-12">
          <span className="text-[10px] font-bold tracking-[0.2em] text-[#B58E58] uppercase">
            Top Picks For You
          </span>
          <h2 className="text-3xl md:text-4xl font-serif font-bold text-[#031D44] mt-2">
            Featured Products
          </h2>
          <div className="w-16 h-0.5 bg-[#B58E58] mx-auto mt-4"></div>
        </div>

        {/* Product Grid - Updated to lg:grid-cols-6 */}
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 lg:gap-4">
          {products.slice(0, visibleCount).map((product) => (
            <div
              key={product.id}
              onClick={() => navigate(`/product/${product.id}`)}
              className="group cursor-pointer flex flex-col bg-white border border-transparent hover:border-gray-100 hover:shadow-xl transition-all duration-300 rounded-2xl overflow-hidden p-2"
            >
              <div className="relative h-48 sm:h-56 bg-[#F4F4F5] rounded-xl overflow-hidden mb-3">
                <span className="absolute top-2 left-2 z-10 bg-[#031D44] text-white text-[8px] font-bold tracking-wider px-2 py-1 rounded">
                  {product.tag}
                </span>

                <button
                  onClick={(e) => handleWishlistClick(e, product)}
                  className="absolute top-2 right-2 z-10 p-1.5 bg-white rounded-full shadow hover:scale-110 transition-transform"
                  title="Add to Wishlist"
                >
                  <FiHeart
                    size={14}
                    className={
                      isInWishlist(product.id)
                        ? "fill-red-500 text-red-500"
                        : "text-gray-400"
                    }
                  />
                </button>

                <img
                  src={product.image}
                  alt={product.name}
                  className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                />
              </div>

              <div className="flex flex-col flex-1 px-1 pb-1">
                <span className="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">
                  {product.category}
                </span>
                <h3
                  className="font-bold text-xs text-[#031D44] line-clamp-1 mb-1.5 group-hover:text-[#B58E58] transition-colors"
                  title={product.name}
                >
                  {product.name}
                </h3>
                <p className="text-xs font-bold text-gray-900 mb-3">
                  CAD {product.price}
                </p>

                <button
                  onClick={(e) => handleAddToCartClick(e, product)}
                  className="mt-auto w-full py-2 flex items-center justify-center gap-1.5 border border-gray-200 text-gray-600 rounded-lg text-[10px] font-bold hover:bg-[#031D44] hover:text-white hover:border-[#031D44] transition-all"
                >
                  <FiShoppingCart size={12} /> Add to Cart
                </button>
              </div>
            </div>
          ))}
        </div>

        {/* Load More Button Logic */}
        {visibleCount < products.length && (
          <div className="flex justify-center mt-10">
            <button
              onClick={handleLoadMore}
              className="px-8 py-3 bg-[#031D44] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md hover:bg-[#B58E58] transition-all"
            >
              Load More Products
            </button>
          </div>
        )}
      </div>

      {/* Login Modal */}
      {showLoginModal && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm transition-opacity">
          <div className="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-sm text-center relative transform transition-all scale-100">
            <button
              onClick={(e) => {
                e.stopPropagation();
                setShowLoginModal(false);
              }}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors"
            >
              <FiX size={18} />
            </button>

            <div className="w-16 h-16 bg-blue-50 text-[#031D44] rounded-full flex items-center justify-center mx-auto mb-5">
              <FiUser size={30} />
            </div>

            <h3 className="text-xl font-serif font-bold text-[#031D44] mb-2">
              Login Required
            </h3>
            <p className="text-sm text-gray-500 mb-8 px-2">
              Please login first to add items to your cart or wishlist.
            </p>

            <button
              onClick={(e) => {
                e.stopPropagation();
                navigate("/login");
              }}
              className="w-full py-3.5 bg-[#031D44] text-white rounded-xl text-xs font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all"
            >
              Login Now
            </button>
          </div>
        </div>
      )}
    </section>
  );
}
