import { useState } from "react";
import {
  FiArrowRight,
  FiShield,
  FiClock,
  FiTruck,
  FiX,
  FiCheckCircle,
} from "react-icons/fi";

const WholesaleSection = () => {
  const [showQuoteModal, setShowQuoteModal] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const [formData, setFormData] = useState({
    fullName: "",
    email: "",
    company: "",
    productInterest: "",
    quantity: "100 - 500 Units",
    message: "",
  });

  const availableCategories = [
    "Luxury Bath Towels",
    "Egyptian Cotton Bed Sheets",
    "Hospitality Mattress Pads",
    "Hotel Pillows & Protectors",
    "Thermal & Fleece Blankets",
    "Bath Mat Sets",
    "Shower Curtains",
  ];

  const quantityRanges = [
    "50 - 100 Units",
    "100 - 500 Units",
    "500 - 1000 Units",
    "1000+ Units",
  ];

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

  const handleCategoryClick = (cat) => {
    if (formData.productInterest) {
      if (!formData.productInterest.includes(cat)) {
        setFormData({
          ...formData,
          productInterest: `${formData.productInterest}, ${cat}`,
        });
      }
    } else {
      setFormData({ ...formData, productInterest: cat });
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setSubmitted(true);
    setTimeout(() => {
      setSubmitted(false);
      setShowQuoteModal(false);
      setFormData({
        fullName: "",
        email: "",
        company: "",
        productInterest: "",
        quantity: "100 - 500 Units",
        message: "",
      });
      alert(
        "Quotation request submitted successfully! Our team will contact you soon.",
      );
    }, 1500);
  };

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
              onClick={() => setShowQuoteModal(true)}
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

      {/* Mobile Optimized Quote Request Modal */}
      {showQuoteModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-3">
          <div className="bg-[#FAF7F2] rounded-[24px] md:rounded-3xl max-w-lg w-full p-4 sm:p-6 md:p-8 shadow-2xl relative border border-[#E5DCD0] max-h-[90vh] overflow-y-auto">
            <button
              onClick={() => setShowQuoteModal(false)}
              className="absolute top-4 right-4 md:top-6 md:right-6 text-gray-400 hover:text-[#031D44] bg-white p-2 rounded-full transition-colors cursor-pointer shadow-sm border border-gray-100"
            >
              <FiX size={16} />
            </button>

            <div className="mb-4 pr-6">
              <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-[0.2em] uppercase">
                Corporate B2B Desk
              </span>
              <h3 className="text-lg md:text-2xl font-serif font-bold text-[#031D44] mt-0.5">
                Request Wholesale Quote
              </h3>
              <p className="text-[11px] md:text-xs text-gray-500 mt-0.5">
                Fill out your details below and specify the products you need.
              </p>
            </div>

            {submitted ? (
              <div className="py-8 flex flex-col items-center justify-center text-center">
                <FiCheckCircle
                  size={42}
                  className="text-[#B58E58] mb-3 animate-bounce"
                />
                <h4 className="text-base md:text-lg font-serif font-bold text-[#031D44]">
                  Request Submitted!
                </h4>
                <p className="text-xs text-gray-500 mt-1">
                  Thank you for your bulk inquiry. We will reach out shortly.
                </p>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="space-y-3.5">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1">
                      Full Name
                    </label>
                    <input
                      type="text"
                      required
                      value={formData.fullName}
                      onChange={(e) =>
                        setFormData({ ...formData, fullName: e.target.value })
                      }
                      placeholder="Enter your full name"
                      className="w-full bg-white text-xs px-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-2xs"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1">
                      Email Address
                    </label>
                    <input
                      type="email"
                      required
                      value={formData.email}
                      onChange={(e) =>
                        setFormData({ ...formData, email: e.target.value })
                      }
                      placeholder="name@company.ca"
                      className="w-full bg-white text-xs px-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-2xs"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1">
                    Hotel / Company Name
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.company}
                    onChange={(e) =>
                      setFormData({ ...formData, company: e.target.value })
                    }
                    placeholder="Enter your hotel or business name"
                    className="w-full bg-white text-xs px-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-2xs"
                  />
                </div>

                {/* Product Interest Input with Quick-Click Suggestion Badges */}
                <div>
                  <div className="flex justify-between items-center mb-1">
                    <label className="block text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-[#031D44]">
                      Product / Category Interest
                    </label>
                    <span className="text-[9px] text-[#B58E58] font-medium">
                      Click tags below to add
                    </span>
                  </div>
                  <input
                    type="text"
                    required
                    value={formData.productInterest}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        productInterest: e.target.value,
                      })
                    }
                    placeholder="Type or click categories below..."
                    className="w-full bg-white text-xs px-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-2xs mb-2"
                  />

                  {/* Clickable Quick Category Pills */}
                  <div className="flex flex-wrap gap-1">
                    {availableCategories.map((cat, idx) => (
                      <button
                        type="button"
                        key={idx}
                        onClick={() => handleCategoryClick(cat)}
                        className="text-[9px] font-medium bg-white hover:bg-[#031D44] hover:text-white text-[#031D44] border border-[#E5DCD0] px-2 py-1 rounded-lg transition-colors shadow-2xs cursor-pointer"
                      >
                        + {cat}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Estimated Quantity Range */}
                <div>
                  <label className="block text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1.5">
                    Estimated Quantity Range
                  </label>
                  <div className="grid grid-cols-2 gap-2">
                    {quantityRanges.map((range, idx) => (
                      <div
                        key={idx}
                        onClick={() =>
                          setFormData({ ...formData, quantity: range })
                        }
                        className={`px-2.5 py-2 text-center rounded-xl text-[10px] md:text-[11px] font-medium cursor-pointer transition-all border ${
                          formData.quantity === range
                            ? "bg-[#B58E58] text-white border-[#B58E58] shadow-md"
                            : "bg-white text-gray-700 border-gray-200 hover:border-[#B58E58]"
                        }`}
                      >
                        {range}
                      </div>
                    ))}
                  </div>
                </div>

                <div>
                  <label className="block text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1">
                    Additional Requirements (Optional)
                  </label>
                  <textarea
                    rows="2"
                    value={formData.message}
                    onChange={(e) =>
                      setFormData({ ...formData, message: e.target.value })
                    }
                    placeholder="Mention custom embroidery, delivery dates..."
                    className="w-full bg-white text-xs px-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-2xs resize-none"
                  ></textarea>
                </div>

                <button
                  type="submit"
                  className="w-full py-3.5 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-lg transition-all cursor-pointer"
                >
                  Submit Quote Request
                </button>
              </form>
            )}
          </div>
        </div>
      )}
    </section>
  );
};

export default WholesaleSection;
