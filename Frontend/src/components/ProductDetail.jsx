import { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import {
  FiShoppingCart,
  FiTruck,
  FiShield,
  FiX,
  FiUser,
  FiHeart,
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
    <div className="w-full min-h-screen bg-white py-10 px-4 md:px-10 font-sans text-gray-800 relative">
      <div className="max-w-[1300px] mx-auto">
        <button
          onClick={() => navigate(-1)}
          className="mb-8 px-4 py-2 border border-gray-200 text-xs font-semibold uppercase tracking-wider rounded-xl text-[#031D44] hover:bg-gray-50 transition-all"
        >
          &larr; Back to Products
        </button>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
          <div className="flex flex-col gap-4">
            <div className="relative w-full h-[420px] bg-[#F4F4F5] rounded-2xl overflow-hidden border border-gray-100 shadow-sm flex items-center justify-center">
              <button
                onClick={() =>
                  handleAuthAction(() =>
                    toggleWishlistItem({ ...product, id: productId }),
                  )
                }
                className="absolute top-4 right-4 z-10 p-3 bg-white rounded-full shadow-md hover:scale-110 transition-transform"
                title="Add to Wishlist"
              >
                <FiHeart
                  size={22}
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

            <div className="flex gap-3 overflow-x-auto pb-2 scrollbar-thin">
              {product.gallery.map((img, index) => (
                <div
                  key={index}
                  onClick={() => setCurrentIndex(index)}
                  className={`min-w-[80px] h-20 bg-[#F4F4F5] rounded-xl overflow-hidden border-2 cursor-pointer transition-all flex-shrink-0 ${currentIndex === index ? "border-[#B58E58]" : "border-transparent hover:border-gray-300"}`}
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

          <div className="flex flex-col">
            <span className="text-xs font-bold text-[#B58E58] tracking-[0.2em] uppercase">
              {product.category}
            </span>

            <h1 className="text-3xl md:text-4xl font-serif font-bold text-[#031D44] mt-2 mb-3">
              {product.name}
            </h1>

            <div className="text-2xl font-bold text-gray-900 mb-6 flex items-baseline gap-3">
              <span>CAD ${totalPrice}</span>
              <span className="text-xs font-normal text-gray-400">
                ({quantity} x ${unitPrice} - {selectedSize})
              </span>
            </div>

            <p className="text-sm text-gray-600 leading-relaxed mb-6 border-b border-gray-100 pb-6">
              Premium quality hospitality linen crafted for superior comfort,
              durability, and luxury feel. Designed specifically for hotels,
              resorts, and high-end residential use.
            </p>

            <div className="mb-6">
              <label className="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                Select Size
              </label>
              <div className="flex gap-3">
                {sizes.map((size) => (
                  <button
                    key={size}
                    onClick={() => setSelectedSize(size)}
                    className={`px-5 py-2.5 rounded-xl text-xs font-semibold border transition-all ${selectedSize === size ? "bg-[#031D44] text-white border-[#031D44]" : "bg-white text-gray-700 border-gray-200 hover:border-gray-400"}`}
                  >
                    {size}
                  </button>
                ))}
              </div>
            </div>

            <div className="mb-8">
              <label className="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                Quantity
              </label>
              <div className="flex items-center gap-4">
                <div className="flex items-center border border-gray-200 rounded-xl overflow-hidden bg-gray-50">
                  <button
                    onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                    className="w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-200 font-bold transition-all"
                  >
                    -
                  </button>
                  <span className="w-12 text-center text-sm font-bold text-[#031D44]">
                    {quantity}
                  </span>
                  <button
                    onClick={() => setQuantity((q) => q + 1)}
                    className="w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-200 font-bold transition-all"
                  >
                    +
                  </button>
                </div>
              </div>
            </div>

            <div className="flex flex-col sm:flex-row gap-4 mb-8">
              <button
                onClick={handleAddToCart}
                className="flex-1 flex items-center justify-center gap-2 py-4 border border-[#031D44] rounded-xl text-xs font-bold tracking-widest uppercase text-[#031D44] hover:bg-gray-50 transition-all"
              >
                <FiShoppingCart size={16} />
                <span>Add to Cart</span>
              </button>
              <button
                onClick={handleBuyNow}
                className="flex-1 py-4 bg-[#031D44] text-white rounded-xl text-xs font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all"
              >
                Buy Now
              </button>
            </div>

            <div className="grid grid-cols-2 gap-4 pt-6 border-t border-gray-100 text-xs text-gray-500">
              <div className="flex items-center gap-2">
                <FiTruck className="text-[#B58E58]" size={18} />
                <span>Fast Shipping Across Canada</span>
              </div>
              <div className="flex items-center gap-2">
                <FiShield className="text-[#B58E58]" size={18} />
                <span>Hospital Grade Quality Guarantee</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {showLoginModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm transition-opacity">
          <div className="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-sm text-center relative transform transition-all scale-100">
            <button
              onClick={() => setShowLoginModal(false)}
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
              Please login first to add items to your cart, wishlist, or proceed
              to checkout.
            </p>

            <button
              onClick={() => navigate("/login")}
              className="w-full py-3.5 bg-[#031D44] text-white rounded-xl text-xs font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all"
            >
              Login Now
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
