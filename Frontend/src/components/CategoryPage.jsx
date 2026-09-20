import { useParams, Link } from "react-router-dom";
import { FiArrowLeft, FiShoppingCart, FiStar } from "react-icons/fi";

// Exact filename imports from newImages folder
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

// All products with direct image links
const productsData = {
  towels: [
    {
      id: 1,
      name: "Luxury Hotel Bath Towel",
      price: "24.99 CAD",
      rating: 4.9,
      image: luxuryhotelbathtowel,
    },
    {
      id: 2,
      name: "Premium Spa Pool Towel",
      price: "29.99 CAD",
      rating: 4.8,
      image: premiumspapooltowel,
    },
    {
      id: 3,
      name: "Ultra-Plush Hand Towel",
      price: "12.99 CAD",
      rating: 4.7,
      image: ultraPlushHandTowel,
    },
  ],
  "bed-sheets": [
    {
      id: 4,
      name: "Egyptian Cotton King Sheet Set",
      price: "89.99 CAD",
      rating: 5.0,
      image: egyptianCottonKingSheet,
    },
    {
      id: 5,
      name: "Commercial Grade White Fitted Sheet",
      price: "45.00 CAD",
      rating: 4.8,
      image: commercialGradeWhiteFittedSheet,
    },
  ],
  "mattress-pads": [
    {
      id: 6,
      name: "Waterproof Hospitality Mattress Pad",
      price: "54.99 CAD",
      rating: 4.9,
      image: waterproofHospitalityMattressPad,
    },
    {
      id: 7,
      name: "Plush Pillow-Top Mattress Protector",
      price: "69.99 CAD",
      rating: 4.8,
      image: plushPillowTopMattressProtector,
    },
  ],
  pillows: [
    {
      id: 8,
      name: "Down-Alternative Hotel Pillow",
      price: "34.99 CAD",
      rating: 4.9,
      image: downAlternativeHotelPillow,
    },
    {
      id: 9,
      name: "Firm Support Gusseted Pillow",
      price: "39.99 CAD",
      rating: 4.7,
      image: firmSupportGussetedPillow,
    },
  ],
  blankets: [
    {
      id: 10,
      name: "Thermal Waffle Weave Blanket",
      price: "49.99 CAD",
      rating: 4.8,
      image: thermalWaffleWeaveBlanket,
    },
    {
      id: 11,
      name: "Plush Fleece Hospitality Blanket",
      price: "59.99 CAD",
      rating: 4.9,
      image: plushFleeceHospitalityBlanket,
    },
  ],
  others: [
    {
      id: 12,
      name: "Luxury Bath Mat Set",
      price: "19.99 CAD",
      rating: 4.7,
      image: luxuryBathMatSet,
    },
    {
      id: 13,
      name: "Waterproof Shower Curtain",
      price: "22.99 CAD",
      rating: 4.6,
      image: waterproofShowerCurtain,
    },
  ],
};

const CategoryPage = () => {
  const { categoryName } = useParams();

  let key = categoryName ? categoryName.toLowerCase() : "all";

  // NAYA: Smart Mapping taaki database ya URL ke alag naam bhi sahi products se match ho sakein
  if (key === "bedding" || key === "duvet" || key === "bed-sheets") {
    key = "bed-sheets";
  } else if (key === "bath-towels" || key === "bath") {
    key = "towels";
  }

  const products =
    key === "all" || key === "new-arrivals" || key === "best-sellers"
      ? Object.values(productsData).flat()
      : productsData[key] || productsData["towels"];

  return (
    <div className="min-h-screen bg-[#FAFAFA] py-12 px-4 sm:px-6 lg:px-8 font-sans">
      <div className="max-w-7xl mx-auto">
        {/* Top Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4 border-b border-gray-200 pb-6">
          <div>
            <Link
              to="/"
              className="inline-flex items-center gap-2 text-[13px] font-semibold text-gray-500 hover:text-[#031D44] transition-colors mb-3"
            >
              <FiArrowLeft size={16} />
              <span>Back to Home</span>
            </Link>
            <h1 className="text-3xl font-serif font-bold text-[#031D44] capitalize">
              {categoryName ? categoryName.replace("-", " ") : "Products"}
            </h1>
            <p className="text-sm text-gray-500 mt-1">
              Explore our premium hospitality collection for wholesale and
              professional supplies.
            </p>
          </div>
        </div>

        {/* Product Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {products.map((product) => (
            <div
              key={product.id}
              className="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group"
            >
              <div>
                <div className="h-60 bg-[#F4F4F5] rounded-xl overflow-hidden flex items-center justify-center mb-4 border border-gray-100">
                  <img
                    src={product.image}
                    alt={product.name}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                </div>
                <div className="flex items-center justify-between mb-2">
                  <span className="text-[11px] font-bold text-[#B58E58] tracking-widest uppercase">
                    Gateway Linen
                  </span>
                  <div className="flex items-center gap-1 text-xs text-amber-500 font-semibold">
                    <FiStar fill="currentColor" size={13} />
                    <span>{product.rating}</span>
                  </div>
                </div>
                <h3 className="text-base font-serif font-bold text-[#031D44] mb-2">
                  {product.name}
                </h3>
              </div>

              <div className="pt-4 border-t border-gray-100 flex items-center justify-between mt-4">
                <span className="text-lg font-bold text-gray-900">
                  {product.price}
                </span>
                <button
                  onClick={() => alert(`Added ${product.name} to cart!`)}
                  className="flex items-center gap-1.5 bg-[#031D44] text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-[#B58E58] transition-colors shadow-sm"
                >
                  <FiShoppingCart size={14} />
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

export default CategoryPage;
