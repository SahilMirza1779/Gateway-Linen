import { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import {
  FiShoppingCart,
  FiTruck,
  FiShield,
  FiX,
  FiUser,
  FiHeart,
  FiArrowLeft,
} from "react-icons/fi";
import { useCart } from "../context/CartContext";
import { useWishlist } from "../context/WishlistContext";

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

const productsData = {
  1: {
    name: "Luxury Hotel Bath Towel",
    category: "TOWELS",
    basePrice: 24.99,
    image: luxuryhotelbathtowel,
    gallery: [
      luxuryhotelbathtowel,
      premiumspapooltowel,
      ultraPlushHandTowel,
      luxuryBathMatSet,
      waterproofShowerCurtain,
      egyptianCottonKingSheet,
    ],
  },
  2: {
    name: "Premium Spa Pool Towel",
    category: "TOWELS",
    basePrice: 29.99,
    image: premiumspapooltowel,
    gallery: [
      premiumspapooltowel,
      luxuryhotelbathtowel,
      ultraPlushHandTowel,
      waterproofShowerCurtain,
      luxuryBathMatSet,
      thermalWaffleWeaveBlanket,
    ],
  },
  3: {
    name: "Ultra-Plush Hand Towel",
    category: "TOWELS",
    basePrice: 12.99,
    image: ultraPlushHandTowel,
    gallery: [
      ultraPlushHandTowel,
      luxuryhotelbathtowel,
      premiumspapooltowel,
      luxuryBathMatSet,
      waterproofShowerCurtain,
      firmSupportGussetedPillow,
    ],
  },
  4: {
    name: "Egyptian Cotton King Sheet Set",
    category: "BED SHEETS",
    basePrice: 89.99,
    image: egyptianCottonKingSheet,
    gallery: [
      egyptianCottonKingSheet,
      commercialGradeWhiteFittedSheet,
      waterproofHospitalityMattressPad,
      plushPillowTopMattressProtector,
      thermalWaffleWeaveBlanket,
      plushFleeceHospitalityBlanket,
    ],
  },
  5: {
    name: "Commercial Grade White Fitted Sheet",
    category: "BED SHEETS",
    basePrice: 45.0,
    image: commercialGradeWhiteFittedSheet,
    gallery: [
      commercialGradeWhiteFittedSheet,
      egyptianCottonKingSheet,
      waterproofHospitalityMattressPad,
      plushPillowTopMattressProtector,
      thermalWaffleWeaveBlanket,
      downAlternativeHotelPillow,
    ],
  },
  6: {
    name: "Waterproof Hospitality Mattress Pad",
    category: "MATTRESS PADS",
    basePrice: 54.99,
    image: waterproofHospitalityMattressPad,
    gallery: [
      waterproofHospitalityMattressPad,
      plushPillowTopMattressProtector,
      commercialGradeWhiteFittedSheet,
      egyptianCottonKingSheet,
      downAlternativeHotelPillow,
      firmSupportGussetedPillow,
    ],
  },
  7: {
    name: "Plush Pillow-Top Mattress Protector",
    category: "MATTRESS PADS",
    basePrice: 69.99,
    image: plushPillowTopMattressProtector,
    gallery: [
      plushPillowTopMattressProtector,
      waterproofHospitalityMattressPad,
      egyptianCottonKingSheet,
      commercialGradeWhiteFittedSheet,
      firmSupportGussetedPillow,
      downAlternativeHotelPillow,
    ],
  },
  8: {
    name: "Down-Alternative Hotel Pillow",
    category: "PILLOWS",
    basePrice: 34.99,
    image: downAlternativeHotelPillow,
    gallery: [
      downAlternativeHotelPillow,
      firmSupportGussetedPillow,
      plushPillowTopMattressProtector,
      waterproofHospitalityMattressPad,
      thermalWaffleWeaveBlanket,
      plushFleeceHospitalityBlanket,
    ],
  },
  9: {
    name: "Firm Support Gusseted Pillow",
    category: "PILLOWS",
    basePrice: 39.99,
    image: firmSupportGussetedPillow,
    gallery: [
      firmSupportGussetedPillow,
      downAlternativeHotelPillow,
      plushPillowTopMattressProtector,
      waterproofHospitalityMattressPad,
      plushFleeceHospitalityBlanket,
      thermalWaffleWeaveBlanket,
    ],
  },
  10: {
    name: "Thermal Waffle Weave Blanket",
    category: "BLANKETS",
    basePrice: 49.99,
    image: thermalWaffleWeaveBlanket,
    gallery: [
      thermalWaffleWeaveBlanket,
      plushFleeceHospitalityBlanket,
      egyptianCottonKingSheet,
      commercialGradeWhiteFittedSheet,
      luxuryhotelbathtowel,
      premiumspapooltowel,
    ],
  },
  11: {
    name: "Plush Fleece Hospitality Blanket",
    category: "BLANKETS",
    basePrice: 59.99,
    image: plushFleeceHospitalityBlanket,
    gallery: [
      plushFleeceHospitalityBlanket,
      thermalWaffleWeaveBlanket,
      egyptianCottonKingSheet,
      commercialGradeWhiteFittedSheet,
      premiumspapooltowel,
      luxuryhotelbathtowel,
    ],
  },
  12: {
    name: "Luxury Bath Mat Set",
    category: "OTHERS",
    basePrice: 19.99,
    image: luxuryBathMatSet,
    gallery: [
      luxuryBathMatSet,
      waterproofShowerCurtain,
      luxuryhotelbathtowel,
      premiumspapooltowel,
      ultraPlushHandTowel,
      egyptianCottonKingSheet,
    ],
  },
  13: {
    name: "Waterproof Shower Curtain",
    category: "OTHERS",
    basePrice: 22.99,
    image: waterproofShowerCurtain,
    gallery: [
      waterproofShowerCurtain,
      luxuryBathMatSet,
      luxuryhotelbathtowel,
      premiumspapooltowel,
      ultraPlushHandTowel,
      egyptianCottonKingSheet,
    ],
  },
};

export default function ProductDetail() {
  const { id } = useParams();
  const navigate = useNavigate();

  const { addToCart } = useCart();
  const { toggleWishlistItem, isInWishlist } = useWishlist();

  const productId = Number(id) || 1;
  const product = productsData[productId] || productsData[1];

  const [selectedSize, setSelectedSize] = useState("Standard");
  const [quantity, setQuantity] = useState(1);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [showLoginModal, setShowLoginModal] = useState(false);

  const sizePricing = {
    Standard: product.basePrice,
    "Queen Size": Number((product.basePrice * 1.15).toFixed(2)),
    "King Size": Number((product.basePrice * 1.3).toFixed(2)),
  };

  const unitPrice = sizePricing[selectedSize];
  const totalPrice = Number((unitPrice * quantity).toFixed(2));
  const sizes = ["Standard", "Queen Size", "King Size"];

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [productId]);

  useEffect(() => {
    const interval = setInterval(() => {
      setCurrentIndex((prevIndex) => (prevIndex + 1) % product.gallery.length);
    }, 10000);
    return () => clearInterval(interval);
  }, [product.gallery]);

  const handleAuthAction = (actionCallback) => {
    const loggedInUser = localStorage.getItem("user");
    if (!loggedInUser) {
      setShowLoginModal(true);
    } else {
      actionCallback();
    }
  };

  const handleAddToCart = () => {
    handleAuthAction(() => {
      addToCart(
        { ...product, id: productId },
        quantity,
        selectedSize,
        unitPrice,
      );
    });
  };

  const handleBuyNow = () => {
    handleAuthAction(() => {
      navigate("/checkout", {
        state: {
          product: { ...product, id: productId },
          quantity,
          selectedSize,
          currentPrice: totalPrice,
        },
      });
    });
  };

  return (
    <div className="w-full min-h-screen bg-[#F0EAE1] py-6 px-4 md:px-8 font-sans text-gray-800 relative">
      <div className="max-w-[1300px] mx-auto">
        <button
          onClick={() => navigate(-1)}
          className="mb-4 inline-flex items-center gap-2 px-4 py-2 bg-[#F7F2EB] border border-[#E5DCD0] text-[11px] font-bold uppercase tracking-wider rounded-xl text-[#031D44] hover:border-[#B58E58] transition-all cursor-pointer shadow-2xs"
        >
          <FiArrowLeft size={13} /> Back to Products
        </button>

        {/* Compact & Screen-Fit Card */}
        <div className="bg-[#F7F2EB] p-6 md:p-8 rounded-[28px] border border-[#E5DCD0] shadow-xl grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          {/* Left Gallery Section (Span 5) */}
          <div className="lg:col-span-5 flex flex-col gap-3.5">
            <div className="relative w-full h-[320px] md:h-[360px] bg-[#FAF7F2] rounded-2xl overflow-hidden border border-[#E5DCD0] shadow-sm flex items-center justify-center">
              <button
                onClick={() =>
                  handleAuthAction(() =>
                    toggleWishlistItem({ ...product, id: productId }),
                  )
                }
                className="absolute top-3 right-3 z-10 p-2.5 bg-white rounded-full shadow-md hover:scale-110 transition-transform cursor-pointer border border-gray-100"
                title="Add to Wishlist"
              >
                <FiHeart
                  size={18}
                  className={
                    isInWishlist(productId)
                      ? "fill-red-500 text-red-500"
                      : "text-gray-400"
                  }
                />
              </button>

              <img
                src={product.gallery[currentIndex]}
                alt={product.name}
                className="w-full h-full object-cover transition-all duration-500"
              />
            </div>

            {/* Thumbnails */}
            <div className="flex gap-2.5 overflow-x-auto pb-1 scrollbar-none">
              {product.gallery.map((img, index) => (
                <div
                  key={index}
                  onClick={() => setCurrentIndex(index)}
                  className={`min-w-[65px] h-14 bg-[#FAF7F2] rounded-xl overflow-hidden border-2 cursor-pointer transition-all flex-shrink-0 shadow-2xs ${currentIndex === index ? "border-[#B58E58] scale-105" : "border-[#E5DCD0] hover:border-[#B58E58]"}`}
                >
                  <img
                    src={img}
                    alt={`Thumb ${index}`}
                    className="w-full h-full object-cover"
                  />
                </div>
              ))}
            </div>
          </div>

          {/* Right Product Info Section (Span 7) */}
          <div className="lg:col-span-7 flex flex-col justify-between">
            <div>
              <div className="inline-flex items-center gap-1.5 bg-[#B58E58]/15 px-3 py-0.5 rounded-full mb-2 border border-[#B58E58]/30">
                <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
                <span className="text-[9.5px] font-bold text-[#B58E58] tracking-[0.2em] uppercase">
                  {product.category}
                </span>
              </div>

              <h1 className="text-2xl md:text-3xl font-serif font-bold text-[#031D44] tracking-tight mb-2">
                {product.name}
              </h1>

              <div className="text-xl md:text-2xl font-bold text-[#031D44] mb-3 flex items-baseline gap-3">
                <span>CAD ${totalPrice}</span>
                <span className="text-[11px] font-normal text-gray-500">
                  ({quantity} item{quantity > 1 ? "s" : ""} &bull;{" "}
                  {selectedSize})
                </span>
              </div>

              <p className="text-[11.5px] md:text-xs text-gray-600 leading-relaxed mb-4 border-b border-[#E5DCD0] pb-4 font-light">
                Premium hospitality linen crafted for superior comfort,
                durability, and luxury feel. Designed specifically for elite
                hotels and resorts.
              </p>

              {/* Size Selector */}
              <div className="mb-4">
                <label className="block text-[10.5px] font-bold text-[#031D44] uppercase tracking-wider mb-2">
                  Select Size Option
                </label>
                <div className="flex gap-2 flex-wrap">
                  {sizes.map((size) => (
                    <button
                      key={size}
                      onClick={() => setSelectedSize(size)}
                      className={`px-4 py-2 rounded-xl text-[11px] font-bold transition-all cursor-pointer shadow-2xs ${selectedSize === size ? "bg-[#031D44] text-white shadow-md border border-[#031D44]" : "bg-white text-gray-700 border border-[#E5DCD0] hover:border-[#B58E58]"}`}
                    >
                      {size}
                    </button>
                  ))}
                </div>
              </div>

              {/* Quantity Selector */}
              <div className="mb-5">
                <label className="block text-[10.5px] font-bold text-[#031D44] uppercase tracking-wider mb-2">
                  Quantity
                </label>
                <div className="flex items-center gap-3">
                  <div className="flex items-center border border-[#E5DCD0] rounded-xl overflow-hidden bg-white shadow-2xs">
                    <button
                      onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                      className="w-9 h-9 flex items-center justify-center text-gray-700 hover:bg-gray-100 font-bold transition-all cursor-pointer text-xs"
                    >
                      -
                    </button>
                    <span className="w-10 text-center text-xs font-bold text-[#031D44]">
                      {quantity}
                    </span>
                    <button
                      onClick={() => setQuantity((q) => q + 1)}
                      className="w-9 h-9 flex items-center justify-center text-gray-700 hover:bg-gray-100 font-bold transition-all cursor-pointer text-xs"
                    >
                      +
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div>
              {/* Action Buttons */}
              <div className="flex flex-col sm:flex-row gap-3 mb-5">
                <button
                  onClick={handleAddToCart}
                  className="flex-1 flex items-center justify-center gap-2 py-3 bg-white border border-[#031D44] rounded-xl text-[11px] font-bold tracking-widest uppercase text-[#031D44] hover:bg-[#031D44] hover:text-white transition-all shadow-2xs cursor-pointer"
                >
                  <FiShoppingCart size={15} />
                  <span>Add to Cart</span>
                </button>
                <button
                  onClick={handleBuyNow}
                  className="flex-1 py-3 bg-[#031D44] text-white rounded-xl text-[11px] font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all cursor-pointer"
                >
                  Buy Now
                </button>
              </div>

              {/* Trust Perks */}
              <div className="grid grid-cols-2 gap-3 pt-4 border-t border-[#E5DCD0] text-[11px] text-gray-600 font-light">
                <div className="flex items-center gap-2 bg-white/50 p-2 rounded-lg border border-gray-200/30">
                  <FiTruck className="text-[#B58E58]" size={16} />
                  <span>Fast Shipping Across Canada</span>
                </div>
                <div className="flex items-center gap-2 bg-white/50 p-2 rounded-lg border border-gray-200/30">
                  <FiShield className="text-[#B58E58]" size={16} />
                  <span>Hospital Grade Quality</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Login Modal */}
      {showLoginModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity p-4">
          <div className="bg-[#F7F2EB] border border-[#E5DCD0] p-6 rounded-2xl shadow-2xl w-full max-w-sm text-center relative">
            <button
              onClick={() => setShowLoginModal(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-800 bg-white p-2 rounded-full transition-colors cursor-pointer border border-gray-200"
            >
              <FiX size={16} />
            </button>

            <div className="w-14 h-14 bg-[#031D44] text-[#B58E58] rounded-xl flex items-center justify-center mx-auto mb-4 shadow-md">
              <FiUser size={24} />
            </div>

            <h3 className="text-lg font-serif font-bold text-[#031D44] mb-1">
              Login Required
            </h3>
            <p className="text-[11px] text-gray-600 mb-6 font-light leading-relaxed px-2">
              Please login first to add items to your cart, wishlist, or proceed
              to checkout.
            </p>

            <button
              onClick={() => navigate("/login")}
              className="w-full py-3 bg-[#031D44] text-white rounded-xl text-[11px] font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all cursor-pointer"
            >
              Login Now
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
