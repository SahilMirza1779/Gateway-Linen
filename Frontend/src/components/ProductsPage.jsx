import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { FiShoppingCart, FiFilter } from "react-icons/fi";

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

const ProductsPage = () => {
  const [selectedCategory, setSelectedCategory] = useState("ALL");
  const navigate = useNavigate();

  const categories = [
    "ALL",
    "TOWELS",
    "BED SHEETS",
    "MATTRESS PADS",
    "PILLOWS",
    "BLANKETS",
    "OTHERS",
  ];

  const filteredProducts =
    selectedCategory === "ALL"
      ? allProducts
      : allProducts.filter((item) => item.category === selectedCategory);

  return (
    <div className="w-full bg-[#F0EAE1] min-h-screen py-12 px-3 md:px-10 font-sans">
      <div className="max-w-[1536px] mx-auto">
        {/* Page Header */}
        <div className="mb-8">
          <h1 className="text-2xl md:text-4xl font-serif font-bold text-[#031D44]">
            Products Catalog
          </h1>
          <p className="text-xs md:text-sm text-gray-600 font-light mt-1.5">
            Explore our complete collection of 5-star hotel grade linens and
            supplies.
          </p>
        </div>

        {/* Category Filters */}
        <div className="flex items-center gap-2 md:gap-3 overflow-x-auto pb-4 mb-8 scrollbar-none">
          <div className="flex items-center gap-1.5 text-xs font-bold text-[#031D44] uppercase tracking-wider mr-1 flex-shrink-0">
            <FiFilter size={13} className="text-[#B58E58]" /> Filter:
          </div>
          {categories.map((cat, idx) => (
            <button
              key={idx}
              onClick={() => setSelectedCategory(cat)}
              className={`px-4 py-2 rounded-full text-[11px] md:text-xs font-bold tracking-wider uppercase transition-all flex-shrink-0 cursor-pointer shadow-2xs ${
                selectedCategory === cat
                  ? "bg-[#031D44] text-white shadow-md"
                  : "bg-[#F7F2EB] text-[#031D44] border border-[#E5DCD0] hover:border-[#B58E58]"
              }`}
            >
              {cat}
            </button>
          ))}
        </div>

        {/* Products Grid - Mobile par 2 columns, Laptop par 5 columns */}
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 gap-3.5 md:gap-6">
          {filteredProducts.map((item) => (
            <div
              key={item.id}
              onClick={() => navigate(`/product/${item.id}`)}
              className="group flex flex-col bg-[#F7F2EB] rounded-2xl border border-[#E5DCD0] hover:border-[#B58E58] overflow-hidden shadow-sm hover:shadow-md transition-all cursor-pointer p-3"
            >
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
                    alert(`Added ${item.name} to cart!`);
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
      </div>
    </div>
  );
};

export default ProductsPage;
