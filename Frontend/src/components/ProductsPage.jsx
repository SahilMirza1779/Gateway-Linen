import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { FiShoppingCart, FiFilter } from "react-icons/fi";

// Imported images (Aapke project ke assets ke hisaab se)
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

export default function ProductsPage() {
  const navigate = useNavigate();
  const [selectedCategory, setSelectedCategory] = useState("ALL");

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
    <div className="min-h-screen bg-[#F0EAE1] py-10 px-4 md:px-10 font-sans">
      <div className="max-w-[1536px] mx-auto">
        {/* Page Header */}
        <div className="mb-8 text-center md:text-left">
          <span className="text-[11px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
            Commercial Catalog
          </span>
          <h1 className="text-3xl md:text-4xl font-serif font-bold text-[#031D44] mt-1">
            All Hospitality Products
          </h1>
          <p className="text-sm text-gray-600 mt-1">
            Explore our complete collection of 5-star hotel grade linens and
            supplies.
          </p>
        </div>

        {/* Category Filter Tabs */}
        <div className="flex items-center gap-2 overflow-x-auto pb-4 mb-8 scrollbar-none">
          <span className="text-[#031D44] font-bold text-xs uppercase flex items-center gap-1.5 mr-2">
            <FiFilter size={14} /> Filter:
          </span>
          {categories.map((cat) => (
            <button
              key={cat}
              onClick={() => setSelectedCategory(cat)}
              className={`px-5 py-2.5 rounded-xl text-xs font-bold tracking-wider uppercase transition-all whitespace-nowrap cursor-pointer ${
                selectedCategory === cat
                  ? "bg-[#031D44] text-white shadow-md"
                  : "bg-white text-[#031D44] border border-gray-200 hover:border-[#B58E58]"
              }`}
            >
              {cat}
            </button>
          ))}
        </div>

        {/* Products Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
          {filteredProducts.map((item) => (
            <div
              key={item.id}
              onClick={() => navigate(`/product/${item.id}`)}
              className="group flex flex-col bg-white rounded-2xl border border-white/50 overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer"
            >
              <div className="relative h-56 bg-white overflow-hidden p-2">
                <span className="absolute top-4 left-4 z-10 bg-[#031D44] text-white text-[9.5px] font-bold tracking-wider px-2 py-1 rounded-md">
                  {item.tag}
                </span>
                <img
                  src={item.image}
                  alt={item.name}
                  className="w-full h-full object-cover rounded-xl group-hover:scale-105 transition-transform duration-500"
                />
              </div>

              <div className="p-4 flex flex-col flex-grow justify-between bg-white">
                <div>
                  <span className="text-[10px] font-bold text-[#B58E58] tracking-widest uppercase">
                    {item.category}
                  </span>
                  <h3 className="text-sm font-serif font-bold text-[#031D44] mt-1 line-clamp-2 leading-snug">
                    {item.name}
                  </h3>
                  <div className="text-sm font-bold text-gray-900 mt-2">
                    {item.price}
                  </div>
                </div>

                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    alert(`Added ${item.name} to cart!`);
                  }}
                  className="mt-4 w-full flex items-center justify-center gap-1.5 py-2.5 border border-[#031D44]/20 rounded-xl text-xs font-bold text-[#031D44] hover:bg-[#031D44] hover:text-white transition-all cursor-pointer"
                >
                  <FiShoppingCart size={14} />
                  <span>Add to Cart</span>
                </button>
              </div>
            </div>
          ))}
        </div>

        {filteredProducts.length === 0 && (
          <div className="text-center py-20 text-gray-500 text-sm">
            No products found in this category.
          </div>
        )}
      </div>
    </div>
  );
}
