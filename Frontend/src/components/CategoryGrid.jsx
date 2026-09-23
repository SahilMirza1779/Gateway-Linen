import { useNavigate } from "react-router-dom";
import { FiArrowRight } from "react-icons/fi";

import duvetImg from "../assets/newImages/egyptianCottonKingSheet.jpg";
import mattressImg from "../assets/newImages/waterproofHospitalityMattressPad.jpg";
import pillowsImg from "../assets/newImages/firmSupportGussetedPillow.jpg";
import bathroomImg from "../assets/newImages/luxuryBathMatSet.jpg";
import blanketsImg from "../assets/newImages/thermalWaffleWeaveBlanket.jpg";

const categories = [
  {
    id: 1,
    name: "Duvet & Duvet Covers",
    path: "/category/bed-sheets",
    image: duvetImg,
    count: "12+ ITEMS",
  },
  {
    id: 2,
    name: "Mattress Protectors",
    path: "/category/mattress-pads",
    image: mattressImg,
    count: "8+ ITEMS",
  },
  {
    id: 3,
    name: "Pillows & Pillow Covers",
    path: "/category/pillows",
    image: pillowsImg,
    count: "10+ ITEMS",
  },
  {
    id: 4,
    name: "Bathroom Accessories",
    path: "/category/others",
    image: bathroomImg,
    count: "15+ ITEMS",
  },
  {
    id: 5,
    name: "Blankets",
    path: "/category/blankets",
    image: blanketsImg,
    count: "6+ ITEMS",
  },
];

export default function CategoryGrid() {
  const navigate = useNavigate();

<<<<<<< HEAD
  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenadmin-main/categories/api.php",
          {
            method: "GET",
            headers: {
              "X-API-KEY": "GatewayLinen@2026",
              "Content-Type": "application/json",
            },
          },
        );

        const result = await response.json();
        if (result.success && result.data) {
          setCategories(result.data);
        }
      } catch (error) {
        console.error("Error fetching categories:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchCategories();
  }, []);

  useEffect(() => {
    const interval = setInterval(() => {
      if (scrollRef.current) {
        const { scrollLeft, scrollWidth, clientWidth } = scrollRef.current;
        if (scrollLeft + clientWidth >= scrollWidth - 10) {
          scrollRef.current.scrollTo({ left: 0, behavior: "smooth" });
        } else {
          scrollRef.current.scrollBy({ left: 280, behavior: "smooth" });
        }
      }
    }, 6000);

    return () => clearInterval(interval);
  }, [categories]);

  const scrollLeft = () => {
    if (scrollRef.current) {
      scrollRef.current.scrollBy({ left: -300, behavior: "smooth" });
    }
  };

  const scrollRight = () => {
    if (scrollRef.current) {
      scrollRef.current.scrollBy({ left: 300, behavior: "smooth" });
    }
  };

  const handleCategoryClick = (categoryName) => {
    const slug = categoryName.toLowerCase().replace(/\s+/g, "-");
    navigate(`/category/${slug}`);
  };

=======
>>>>>>> 76f841f381353ab4294a66c957f4089f48f67a21
  return (
    <section className="w-full bg-[#F0EAE1] py-12 md:py-16 px-3 md:px-10 font-sans">
      <div className="max-w-[1536px] mx-auto">
        {/* Section Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-8 md:mb-12 border-b border-[#031D44]/10 pb-6">
          <div>
            <div className="inline-flex items-center gap-2 bg-[#B58E58]/15 px-3 py-1 rounded-full mb-3 border border-[#B58E58]/30">
              <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
              <span className="text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
                Browse Collections
              </span>
            </div>
            <h2 className="text-2xl md:text-5xl font-serif font-bold text-[#031D44]">
              Shop By Categories
            </h2>
          </div>
          <p className="text-xs md:text-sm text-gray-600 mt-3 md:mt-0 max-w-md font-light leading-relaxed">
            Explore premium hotel-grade linen categories crafted exclusively for
            high-end hospitality and unmatched guest comfort.
          </p>
        </div>

        {/* Categories Grid - Mobile par 2 columns, Laptop par 5 columns */}
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 gap-3.5 md:gap-6">
          {categories.map((cat) => (
            <div
              key={cat.id}
              onClick={() => navigate(cat.path)}
              className="group relative bg-[#F7F2EB] rounded-[20px] md:rounded-[28px] p-4 md:p-6 border border-[#E5DCD0] shadow-sm hover:shadow-2xl hover:shadow-[#B58E58]/15 hover:-translate-y-2 transition-all duration-500 cursor-pointer flex flex-col items-center text-center overflow-hidden"
            >
              {/* Top Gold Accent Line on Hover */}
              <div className="absolute inset-x-0 top-0 h-1.5 bg-[#B58E58] opacity-0 group-hover:opacity-100 transition-opacity"></div>

              {/* Circular Image Container */}
              <div className="relative w-20 h-20 sm:w-28 sm:h-28 md:w-36 md:h-36 rounded-full overflow-hidden mb-3 md:mb-5 shadow-inner border-2 md:border-4 border-[#EAE2D8] group-hover:border-[#B58E58] transition-colors">
                <img
                  src={cat.image}
                  alt={cat.name}
                  className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                />
                <div className="absolute inset-0 bg-[#031D44]/10 group-hover:bg-transparent transition-colors"></div>
              </div>

              {/* Category Counter */}
              <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-widest uppercase mb-1">
                {cat.count}
              </span>

              {/* Category Title */}
              <h3 className="text-xs md:text-base font-serif font-bold text-[#031D44] mb-3 md:mb-5 group-hover:text-[#B58E58] transition-colors line-clamp-2 leading-snug">
                {cat.name}
              </h3>

              {/* Pill Button Action */}
              <div className="mt-auto w-full py-2 md:py-2.5 px-2 md:px-4 rounded-xl bg-white/70 group-hover:bg-[#031D44] text-[#031D44] group-hover:text-white text-[10px] md:text-xs font-bold tracking-wider uppercase transition-all flex items-center justify-center gap-1.5 md:gap-2 shadow-sm">
                <span>Explore</span>
                <FiArrowRight
                  size={13}
                  className="group-hover:translate-x-1.5 transition-transform"
                />
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
