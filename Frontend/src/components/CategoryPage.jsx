import { useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { FiShoppingCart, FiArrowLeft } from "react-icons/fi";

// Aapke original local imported images
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

const allProducts = [
  {
    id: 1,
    category: "towels",
    name: "Luxury Hotel Bath Towel",
    price: "CAD 24.99",
    tag: "BEST SELLER",
    image: luxuryhotelbathtowel,
  },
  {
    id: 2,
    category: "towels",
    name: "Premium Spa Pool Towel",
    price: "CAD 29.99",
    tag: "POPULAR",
    image: premiumspapooltowel,
  },
  {
    id: 3,
    category: "towels",
    name: "Ultra-Plush Hand Towel",
    price: "CAD 12.99",
    tag: "TOP RATED",
    image: ultraPlushHandTowel,
  },
  {
    id: 4,
    category: "bed-sheets",
    name: "Egyptian Cotton King Sheet Set",
    price: "CAD 89.99",
    tag: "NEW ARRIVAL",
    image: egyptianCottonKingSheet,
  },
  {
    id: 5,
    category: "bed-sheets",
    name: "Commercial Grade White Fitted Sheet",
    price: "CAD 45.00",
    tag: "FEATURED",
    image: commercialGradeWhiteFittedSheet,
  },
  {
    id: 6,
    category: "mattress-pads",
    name: "Waterproof Hospitality Mattress Pad",
    price: "CAD 54.99",
    tag: "POPULAR",
    image: waterproofHospitalityMattressPad,
  },
  {
    id: 7,
    category: "mattress-pads",
    name: "Plush Pillow-Top Mattress Protector",
    price: "CAD 69.99",
    tag: "PREMIUM",
    image: plushPillowTopMattressProtector,
  },
  {
    id: 8,
    category: "pillows",
    name: "Down-Alternative Hotel Pillow",
    price: "CAD 34.99",
    tag: "TOP RATED",
    image: downAlternativeHotelPillow,
  },
  {
    id: 9,
    category: "pillows",
    name: "Firm Support Gusseted Pillow",
    price: "CAD 39.99",
    tag: "BEST SELLER",
    image: firmSupportGussetedPillow,
  },
  {
    id: 10,
    category: "blankets",
    name: "Thermal Waffle Weave Blanket",
    price: "CAD 49.99",
    tag: "TRENDING",
    image: thermalWaffleWeaveBlanket,
  },
  {
    id: 11,
    category: "blankets",
    name: "Plush Fleece Hospitality Blanket",
    price: "CAD 59.99",
    tag: "HOT DEAL",
    image: plushFleeceHospitalityBlanket,
  },
  {
    id: 12,
    category: "others",
    name: "Luxury Bath Mat Set",
    price: "CAD 19.99",
    tag: "POPULAR",
    image: luxuryBathMatSet,
  },
  {
    id: 13,
    category: "others",
    name: "Waterproof Shower Curtain",
    price: "CAD 22.99",
    tag: "NEW",
    image: waterproofShowerCurtain,
  },
];

const CategoryPage = () => {
  const { categoryName } = useParams();
  const navigate = useNavigate();

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [categoryName]);

  const formattedCategory = categoryName ? categoryName.replace("-", " ") : "";

  const filteredProducts = allProducts.filter(
    (item) => item.category.toLowerCase() === categoryName?.toLowerCase(),
  );

  return (
    <div className="w-full bg-[#F0EAE1] min-h-screen py-12 px-4 md:px-10 font-sans">
      <div className="max-w-[1536px] mx-auto">
        {/* Back Link & Header */}
        <div className="mb-10">
          <button
            onClick={() => navigate("/")}
            className="inline-flex items-center gap-2 text-xs font-bold text-[#031D44] hover:text-[#B58E58] transition-colors mb-4 cursor-pointer"
          >
            <FiArrowLeft size={14} /> Back to Home
          </button>
          <h1 className="text-3xl md:text-4xl font-serif font-bold text-[#031D44] uppercase tracking-wide">
            {formattedCategory}
          </h1>
          <p className="text-xs md:text-sm text-gray-600 font-light mt-2">
            Explore our premium hospitality collection for wholesale and
            professional supplies.
          </p>
        </div>

        {/* Products Grid - 5 Cards per row */}
        {filteredProducts.length > 0 ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
            {filteredProducts.map((item) => (
              <div
                key={item.id}
                onClick={() => navigate(`/product/${item.id}`)}
                className="group flex flex-col bg-[#F7F2EB] rounded-2xl border border-[#E5DCD0] hover:border-[#B58E58] overflow-hidden shadow-sm hover:shadow-md transition-all cursor-pointer p-3.5"
              >
                {/* Image Container */}
                <div className="relative h-52 bg-[#FAF7F2] rounded-xl overflow-hidden mb-3 border border-gray-100">
                  <span className="absolute top-2.5 left-2.5 z-10 bg-[#031D44] text-white text-[9px] font-bold tracking-wider px-2 py-0.5 rounded-md shadow-md">
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
                    <span className="text-[10px] font-bold text-[#B58E58] tracking-widest uppercase">
                      {item.category}
                    </span>
                    <h3 className="text-xs font-serif font-bold text-[#031D44] mt-1 line-clamp-1">
                      {item.name}
                    </h3>
                    <div className="text-xs font-bold text-gray-900 mt-1.5">
                      {item.price}
                    </div>
                  </div>

                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      alert(`Added ${item.name} to cart!`);
                    }}
                    className="mt-4 w-full flex items-center justify-center gap-1.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-[11px] font-bold text-[#031D44] hover:bg-[#031D44] hover:text-white hover:border-[#031D44] transition-all shadow-2xs cursor-pointer"
                  >
                    <FiShoppingCart size={13} />
                    <span>Add to Cart</span>
                  </button>
                </div>
              </div>
            ))}
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

export default CategoryPage;
