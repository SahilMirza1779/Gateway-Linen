import { FiArrowRight, FiShield, FiClock, FiTruck } from "react-icons/fi";
import { useNavigate } from "react-router-dom"; // Hook import kiya

const WholesaleSection = () => {
  const navigate = useNavigate(); // Navigation ke liye

  const features = [
    {
      icon: <FiShield size={20} className="text-[#B58E58]" />,
      title: "Premium Quality",
      desc: "Hotel-ready commercial grade products",
    },
    {
      icon: <FiClock size={20} className="text-[#B58E58]" />,
      title: "10+ Years Trust",
      desc: "Reliable hospitality supply chain",
    },
    {
      icon: <FiTruck size={20} className="text-[#B58E58]" />,
      title: "Fast Shipping",
      desc: "Priority delivery across Canada",
    },
  ];

  return (
    <section className="w-full py-10 md:py-16 px-3 md:px-10 bg-[#F0EAE1] font-sans">
      <div className="max-w-[1536px] mx-auto">
        {/* Main Luxury Wholesale Banner - Mobile Optimized */}
        <div className="relative bg-[#031D44] rounded-[24px] md:rounded-[32px] p-6 sm:p-8 md:p-14 overflow-hidden shadow-2xl border border-[#B58E58]/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 md:gap-8 mb-6 md:mb-8">
          <div className="absolute -right-20 -bottom-20 w-96 h-96 bg-[#B58E58]/15 rounded-full blur-3xl pointer-events-none"></div>

          <div className="relative z-10 max-w-2xl">
            <div className="inline-flex items-center gap-1.5 bg-[#B58E58]/20 px-3 py-0.5 rounded-full mb-2.5 md:mb-4 border border-[#B58E58]/40">
              <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
              <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
                B2B Commercial Partner
              </span>
            </div>

            <h2 className="text-2xl sm:text-3xl md:text-5xl font-serif font-bold text-white tracking-tight leading-tight mb-2 md:mb-3">
              Wholesale Inquiries
            </h2>

            <p className="text-xs sm:text-sm md:text-base text-gray-300 font-light leading-relaxed">
              Get the best pricing for bulk orders, custom hotel branding, and
              long-term supply partnerships tailored for luxury hospitality.
            </p>
          </div>

          <div className="relative z-10 flex-shrink-0 w-full md:w-auto">
            <button
              onClick={() => navigate("/quote-builder")} // Seedha naye QuoteBuilder route pe jayega
              className="w-full md:w-auto px-6 py-3.5 md:px-8 md:py-4 bg-[#B58E58] hover:bg-white text-white hover:text-[#031D44] text-[11px] md:text-xs font-bold tracking-widest uppercase rounded-xl md:rounded-2xl shadow-xl transition-all duration-300 flex items-center justify-center gap-2 md:gap-3 cursor-pointer group"
            >
              <span>Request a Quote</span>
              <FiArrowRight
                size={15}
                className="group-hover:translate-x-1.5 transition-transform"
              />
            </button>
          </div>
        </div>

        {/* Feature Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
          {features.map((feature, index) => (
            <div
              key={index}
              className="flex items-center gap-4 p-4 md:p-7 border border-[#E5DCD0] rounded-[20px] md:rounded-[24px] bg-[#F7F2EB] shadow-sm hover:shadow-xl hover:shadow-[#B58E58]/15 hover:border-[#B58E58] transition-all duration-300 group"
            >
              <div className="w-12 h-12 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-[#031D44] flex items-center justify-center flex-shrink-0 shadow-md group-hover:scale-110 transition-transform">
                {feature.icon}
              </div>
              <div>
                <h4 className="text-[#031D44] text-xs md:text-base font-serif font-bold mb-0.5 md:mb-1">
                  {feature.title}
                </h4>
                <p className="text-gray-600 text-[11px] md:text-xs font-light leading-relaxed">
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
