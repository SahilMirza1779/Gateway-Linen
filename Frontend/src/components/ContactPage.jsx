import { useState } from "react";
import { FiShoppingCart } from "react-icons/fi";
import { useNavigate } from "react-router-dom"; // Navigation ke liye import kiya

// Importing all 13 product images from newImages folder
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
  const [visibleCount, setVisibleCount] = useState(4);
  const navigate = useNavigate(); // Hook initialize kiya

  const handleLoadMore = () => {
    setVisibleCount((prevCount) =>
      Math.min(prevCount + 4, allFeaturedItems.length),
    );
  };

  const displayedItems = allFeaturedItems.slice(0, visibleCount);

  return (
    <section className="w-full py-16 px-4 md:px-10 bg-white font-sans">
      <div className="max-w-[1536px] mx-auto">
        {/* Header Title */}
        <div className="text-center mb-12">
          <span className="text-[11px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
            Top Picks For You
          </span>
          <h2 className="text-3xl md:text-4xl font-serif font-bold text-[#031D44] mt-1">
            Featured Products
          </h2>
          <div className="w-12 h-0.5 bg-[#B58E58] mx-auto mt-3"></div>
        </div>

        {/* Product Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
          {displayedItems.map((item) => (
            <div
              key={item.id}
              onClick={() => navigate(`/product/${item.id}`)} // Pure card par click karne par product detail page khulega
              className="group flex flex-col bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-md transition-all cursor-pointer"
            >
              {/* Image Container */}
              <div className="relative h-72 bg-[#F4F4F5] overflow-hidden">
                <span className="absolute top-3 left-3 z-10 bg-[#031D44] text-white text-[9.5px] font-bold tracking-wider px-2.5 py-1 rounded-md">
                  {item.tag}
                </span>
                <img
                  src={item.image}
                  alt={item.name}
                  className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                />
              </div>

              {/* Details */}
              <div className="p-5 flex flex-col flex-grow justify-between">
                <div>
                  <span className="text-[10.5px] font-bold text-gray-400 tracking-widest uppercase">
                    {item.category}
                  </span>
                  <h3 className="text-sm font-serif font-bold text-[#031D44] mt-1 line-clamp-1">
                    {item.name}
                  </h3>
                  <div className="text-sm font-bold text-gray-900 mt-2">
                    {item.price}
                  </div>
                </div>

                <button
                  onClick={(e) => {
                    e.stopPropagation(); // Card click event trigger hone se rokega taaki sirf cart action ho
                    alert(`Added ${item.name} to cart!`);
                  }}
                  className="mt-5 w-full flex items-center justify-center gap-2 py-2.5 border border-gray-200 rounded-xl text-xs font-semibold text-[#031D44] hover:bg-[#031D44] hover:text-white hover:border-[#031D44] transition-all"
                >
                  <FiShoppingCart size={14} />
                  <span>Add to Cart</span>
                </button>
              </div>
            </div>
          ))}
        </div>

        {/* Load More Button */}
        {visibleCount < allFeaturedItems.length && (
          <div className="text-center mt-12">
            <button
              onClick={handleLoadMore}
              className="px-8 py-3.5 bg-[#031D44] text-white text-xs font-semibold tracking-widest uppercase rounded-xl shadow-md hover:bg-[#B58E58] transition-all"
            >
              Load More Products
            </button>
          </div>
        )}
      </div>
    </section>
  );
};

export default FeaturedProducts;
