import { FiArrowRight, FiShield, FiClock, FiTruck } from "react-icons/fi";
import { useNavigate } from "react-router-dom";

const WholesaleSection = () => {
  const navigate = useNavigate();

  const features = [
    {
      icon: <FiShield size={22} className="text-[#4A5D4E]" />,
      title: "Premium Quality",
      desc: "Hotel-ready commercial grade products",
    },
    {
      icon: <FiClock size={22} className="text-[#4A5D4E]" />,
      title: "10+ Years Trust",
      desc: "Reliable hospitality supply chain",
    },
    {
      icon: <FiTruck size={22} className="text-[#4A5D4E]" />,
      title: "Fast Shipping",
      desc: "Priority delivery across Canada",
    },
  ];

  return (
    <section className="w-full py-16 md:py-24 px-4 md:px-10 bg-white font-sans border-t border-gray-100">
      <div className="max-w-[1300px] mx-auto">
        {/* Main Luxury Wholesale Banner - Light Green Theme */}
        <div className="relative bg-[#EBF0EC] border border-[#DCE4DE] rounded-[24px] md:rounded-[32px] p-8 md:p-14 overflow-hidden shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-8 mb-8">
          <div className="relative z-10 max-w-2xl">
            {/* Badge */}
            <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full mb-6 bg-white border border-[#DCE4DE] shadow-xs">
              <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
              <span className="text-[10px] font-bold text-[#B58E58] tracking-widest uppercase">
                B2B Commercial Partner
              </span>
            </div>

            {/* Title & Description */}
            <h2 className="text-3xl md:text-5xl font-serif font-bold text-[#4A5568] tracking-tight leading-tight mb-4">
              Wholesale Inquiries
            </h2>
            <p className="text-sm md:text-base text-gray-600 font-light leading-relaxed max-w-xl">
              Get the best pricing for bulk orders, custom hotel branding, and
              long-term supply partnerships tailored for luxury hospitality.
            </p>
          </div>

          {/* Call to Action Button */}
          <div className="relative z-10 flex-shrink-0 w-full md:w-auto">
            <button
              onClick={() => navigate("/quote-builder")}
              className="w-full md:w-auto px-8 py-4 bg-[#4A5D4E] hover:bg-[#031D44] text-white text-xs font-bold tracking-widest uppercase rounded-full shadow-md transition-all duration-300 flex items-center justify-center gap-3 cursor-pointer group"
            >
              <span>Request a Quote</span>
              <FiArrowRight
                size={16}
                className="group-hover:translate-x-1 transition-transform"
              />
            </button>
          </div>
        </div>

        {/* Feature Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {features.map((feature, index) => (
            <div
              key={index}
              className="flex items-center gap-5 p-6 md:p-8 border border-gray-200 rounded-2xl bg-[#FAF7F2] shadow-sm hover:shadow-md transition-shadow group"
            >
              <div className="w-14 h-14 md:w-16 md:h-16 rounded-xl bg-white border border-gray-100 flex items-center justify-center flex-shrink-0 shadow-sm">
                {feature.icon}
              </div>
              <div>
                <h4 className="text-[#4A5568] text-sm md:text-base font-serif font-bold mb-1">
                  {feature.title}
                </h4>
                <p className="text-gray-500 text-xs md:text-sm font-light">
                  {feature.desc}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default WholesaleSection;
