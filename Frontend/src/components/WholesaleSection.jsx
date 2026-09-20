import { FiArrowRight, FiShield, FiClock, FiTruck } from "react-icons/fi";

const WholesaleSection = () => {
  const features = [
    {
      icon: <FiShield size={24} className="text-[#B58E58]" />,
      title: "Premium Quality",
      desc: "Hotel-ready products",
    },
    {
      icon: <FiClock size={24} className="text-[#B58E58]" />,
      title: "10+ Years",
      desc: "Trusted supply",
    },
    {
      icon: <FiTruck size={24} className="text-[#B58E58]" />,
      title: "Fast Shipping",
      desc: "Across Canada",
    },
  ];

  return (
    <div className="w-full bg-white flex flex-col">
      {/* WHOLESALE BANNER - Navy Blue Background */}
      <div className="w-full bg-[#031D44] py-12 md:py-16">
        <div className="max-w-[1536px] mx-auto px-4 md:px-10 flex flex-col md:flex-row justify-between items-center gap-6">
          <div className="text-center md:text-left">
            <h2 className="text-white text-3xl md:text-4xl font-serif font-bold mb-2">
              Wholesale Inquiries
            </h2>
            <p className="text-gray-300 text-[14px] md:text-[15px] font-light">
              Get the best pricing for bulk orders and long-term partnerships.
            </p>
          </div>
          <button className="bg-[#B58E58] hover:bg-[#9a7745] text-white text-[13.5px] font-medium py-3.5 px-8 rounded-[3px] transition-colors flex items-center gap-2 shadow-lg cursor-pointer whitespace-nowrap">
            Request a Quote <FiArrowRight size={16} />
          </button>
        </div>
      </div>

      {/* FEATURES GRID - White Background */}
      <div className="w-full py-12">
        <div className="max-w-[1536px] mx-auto px-4 md:px-10">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {features.map((feature, index) => (
              <div
                key={index}
                className="flex items-center gap-4 p-6 border border-gray-200 rounded-lg bg-white hover:border-[#B58E58] transition-colors duration-300"
              >
                <div className="flex-shrink-0">{feature.icon}</div>
                <div>
                  <h4 className="text-[#031D44] text-[15px] font-bold">
                    {feature.title}
                  </h4>
                  <p className="text-gray-500 text-[13px] mt-0.5">
                    {feature.desc}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default WholesaleSection;
