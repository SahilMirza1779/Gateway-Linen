import { FiArrowRight, FiShield, FiClock, FiTruck } from "react-icons/fi";
import { useNavigate } from "react-router-dom";

const WholesaleSection = () => {
  const navigate = useNavigate();

  const features = [
    {
      icon: (
        <FiShield
          size={20}
          className="text-[#4A5D4E] md:w-[22px] md:h-[22px]"
        />
      ),
      title: "Premium Quality",
      desc: "Hotel-ready commercial grade products",
    },
    {
      icon: (
        <FiClock size={20} className="text-[#4A5D4E] md:w-[22px] md:h-[22px]" />
      ),
      title: "10+ Years Trust",
      desc: "Reliable hospitality supply chain",
    },
    {
      icon: (
        <FiTruck size={20} className="text-[#4A5D4E] md:w-[22px] md:h-[22px]" />
      ),
      title: "Fast Shipping",
      desc: "Priority delivery across Canada",
    },
  ];

  return (
    <section className="w-full py-12 md:py-24 px-4 md:px-10 bg-white font-sans border-t border-gray-100">
      <div className="max-w-[1300px] mx-auto">
        {/* Main Luxury Wholesale Banner - Light Green Theme */}
        <div className="relative bg-[#EBF0EC] border border-[#DCE4DE] rounded-[20px] md:rounded-[32px] p-6 md:p-14 overflow-hidden shadow-sm flex flex-col md:flex-row items-center justify-between gap-6 md:gap-8 mb-6 md:mb-8 text-center md:text-left">
          <div className="relative z-10 max-w-2xl flex flex-col items-center md:items-start w-full">
            {/* Badge */}
            <div className="inline-flex items-center gap-2 px-3 py-1.5 md:px-4 md:py-1.5 rounded-full mb-4 md:mb-6 bg-white border border-[#DCE4DE] shadow-xs">
              <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
              <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-[0.2em] uppercase">
                B2B Commercial Partner
              </span>
            </div>

            {/* Title & Description */}
            <h2 className="text-2xl sm:text-3xl md:text-5xl font-serif font-bold text-[#4A5568] tracking-tight leading-tight mb-3 md:mb-4">
              Wholesale Inquiries
            </h2>
            <p className="text-xs md:text-base text-gray-600 font-light leading-relaxed max-w-xl px-2 md:px-0">
              Get the best pricing for bulk orders, custom hotel branding, and
              long-term supply partnerships tailored for luxury hospitality.
            </p>
          </div>

          {/* Call to Action Button */}
          <div className="relative z-10 flex-shrink-0 w-full md:w-auto mt-1 md:mt-0">
            <button
              onClick={() => navigate("/quote-builder")}
              className="w-full md:w-auto px-8 py-3.5 md:py-4 bg-[#4A5D4E] hover:bg-[#031D44] text-white text-[11px] md:text-xs font-bold tracking-widest uppercase rounded-xl md:rounded-full shadow-md transition-all duration-300 flex items-center justify-center gap-3 cursor-pointer group"
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
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-6">
          {features.map((feature, index) => (
            <div
              key={index}
              className="flex items-center gap-4 md:gap-5 p-4 md:p-8 border border-gray-200 rounded-[16px] md:rounded-2xl bg-[#FAF7F2] shadow-xs hover:shadow-md transition-shadow group"
            >
              <div className="w-12 h-12 md:w-16 md:h-16 rounded-xl bg-white border border-gray-100 flex items-center justify-center flex-shrink-0 shadow-sm">
                {feature.icon}
              </div>
              <div>
                <h4 className="text-[#4A5568] text-[13px] md:text-base font-serif font-bold mb-0.5 md:mb-1">
                  {feature.title}
                </h4>
                <p className="text-gray-500 text-[11px] md:text-sm font-light leading-snug">
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
