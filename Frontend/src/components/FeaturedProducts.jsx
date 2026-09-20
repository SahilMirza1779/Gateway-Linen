import { useNavigate } from "react-router-dom";
import { FiShoppingCart, FiHeart } from "react-icons/fi";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

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
    price: 24.99,
    priceStr: "CAD 24.99",
    tag: "BEST SELLER",
    image: luxuryhotelbathtowel,
  },
  {
    id: 2,
    category: "TOWELS",
    name: "Premium Spa Pool Towel",
    price: 29.99,
    priceStr: "CAD 29.99",
    tag: "POPULAR",
    image: premiumspapooltowel,
  },
  {
    id: 3,
    category: "TOWELS",
    name: "Ultra-Plush Hand Towel",
    price: 12.99,
    priceStr: "CAD 12.99",
    tag: "TOP RATED",
    image: ultraPlushHandTowel,
  },
  {
    id: 4,
    category: "BED SHEETS",
    name: "Egyptian Cotton King Sheet Set",
    price: 89.99,
    priceStr: "CAD 89.99",
    tag: "NEW ARRIVAL",
    image: egyptianCottonKingSheet,
  },
  {
    id: 5,
    category: "BED SHEETS",
    name: "Commercial Grade White Fitted Sheet",
    price: 45.0,
    priceStr: "CAD 45.00",
    tag: "FEATURED",
    image: commercialGradeWhiteFittedSheet,
  },
  {
    id: 6,
    category: "MATTRESS PADS",
    name: "Waterproof Hospitality Mattress Pad",
    price: 54.99,
    priceStr: "CAD 54.99",
    tag: "POPULAR",
    image: waterproofHospitalityMattressPad,
  },
  {
    id: 7,
    category: "MATTRESS PADS",
    name: "Plush Pillow-Top Mattress Protector",
    price: 69.99,
    priceStr: "CAD 69.99",
    tag: "PREMIUM",
    image: plushPillowTopMattressProtector,
  },
  {
    id: 8,
    category: "PILLOWS",
    name: "Down-Alternative Hotel Pillow",
    price: 34.99,
    priceStr: "CAD 34.99",
    tag: "TOP RATED",
    image: downAlternativeHotelPillow,
  },
  {
    id: 9,
    category: "PILLOWS",
    name: "Firm Support Gusseted Pillow",
    price: 39.99,
    priceStr: "CAD 39.99",
    tag: "BEST SELLER",
    image: firmSupportGussetedPillow,
  },
  {
    id: 10,
    category: "BLANKETS",
    name: "Thermal Waffle Weave Blanket",
    price: 49.99,
    priceStr: "CAD 49.99",
    tag: "TRENDING",
    image: thermalWaffleWeaveBlanket,
  },
  {
    id: 11,
    category: "BLANKETS",
    name: "Plush Fleece Hospitality Blanket",
    price: 59.99,
    priceStr: "CAD 59.99",
    tag: "HOT DEAL",
    image: plushFleeceHospitalityBlanket,
  },
  {
    id: 12,
    category: "OTHERS",
    name: "Luxury Bath Mat Set",
    price: 19.99,
    priceStr: "CAD 19.99",
    tag: "POPULAR",
    image: luxuryBathMatSet,
  },
  {
    id: 13,
    category: "OTHERS",
    name: "Waterproof Shower Curtain",
    price: 22.99,
    priceStr: "CAD 22.99",
    tag: "NEW",
    image: waterproofShowerCurtain,
  },
];

const FeaturedProducts = () => {
  const navigate = useNavigate();
  const { wishlistItems, addToWishlist, removeFromWishlist } = useWishlist();
  const { addToCart } = useCart();

  const isItemInWishlist = (id) => {
    return wishlistItems && wishlistItems.some((item) => item.id === id);
  };

  const handleWishlistToggle = (e, product) => {
    e.stopPropagation();
    if (isItemInWishlist(product.id)) {
      removeFromWishlist(product.id);
    } else {
      addToWishlist(product);
    }
  };

  const handleAddToCart = (e, product) => {
    e.stopPropagation();
    addToCart(product, 1, "Standard", product.price);
  };

  return (
    <section className="w-full py-16 px-4 md:px-10 bg-[#F0EAE1] font-sans">
      <div className="max-w-[1536px] mx-auto">
        <div className="text-center mb-12">
          <div className="inline-flex items-center gap-2 bg-[#B58E58]/15 px-3 py-1 rounded-full mb-3 border border-[#B58E58]/30">
            <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
            <span className="text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
              Top Picks For You
            </span>
          </div>
          <h2 className="text-3xl md:text-5xl font-serif font-bold text-[#031D44]">
            Featured Products
          </h2>
          <div className="w-12 h-0.5 bg-[#B58E58] mx-auto mt-3"></div>
        </div>

        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 md:gap-6">
          {allFeaturedItems.map((item) => {
            const inWishlist = isItemInWishlist(item.id);
            return (
              <div
                key={item.id}
                onClick={() => navigate(`/product/${item.id}`)}
                className="group flex flex-col bg-white rounded-2xl border border-white/50 overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer relative"
              >
                <div className="relative h-48 md:h-56 bg-white overflow-hidden p-2">
                  <span className="absolute top-4 left-4 z-10 bg-[#031D44] text-white text-[8px] md:text-[9.5px] font-bold tracking-wider px-2 py-1 rounded-md shadow-sm">
                    {item.tag}
                  </span>

                  {/* Wishlist Icon Button */}
                  <button
                    onClick={(e) => handleWishlistToggle(e, item)}
                    className="absolute top-4 right-4 z-10 w-8 h-8 rounded-full bg-white/90 backdrop-blur-md shadow-md flex items-center justify-center text-[#031D44] hover:text-red-500 transition-colors cursor-pointer"
                    title="Add to Wishlist"
                  >
                    <FiHeart
                      size={15}
                      className={inWishlist ? "fill-red-500 text-red-500" : ""}
                    />
                  </button>

                  <img
                    src={item.image}
                    alt={item.name}
                    className="w-full h-full object-cover rounded-xl group-hover:scale-105 transition-transform duration-500"
                  />
                </div>

                <div className="p-4 flex flex-col flex-grow justify-between bg-white">
                  <div>
                    <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-widest uppercase">
                      {item.category}
                    </span>
                    <h3 className="text-xs md:text-sm font-serif font-bold text-[#031D44] mt-1 line-clamp-2 leading-snug">
                      {item.name}
                    </h3>
                    <div className="text-xs md:text-sm font-bold text-gray-900 mt-2">
                      {item.priceStr}
                    </div>
                  </div>

                  <button
                    onClick={(e) => handleAddToCart(e, item)}
                    className="mt-4 w-full flex items-center justify-center gap-1.5 py-2.5 border border-[#031D44]/20 rounded-xl text-[10px] md:text-xs font-bold text-[#031D44] hover:bg-[#031D44] hover:text-white transition-all cursor-pointer shadow-sm"
                  >
                    <FiShoppingCart size={12} />
                    <span>Add to Cart</span>
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
};

export default FeaturedProducts;
