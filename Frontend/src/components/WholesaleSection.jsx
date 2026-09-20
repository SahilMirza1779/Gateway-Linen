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

  // Available categories list taaki user ko pata chale ki client ke paas kya hai
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
      icon: <FiShield size={22} className="text-[#B58E58]" />,
      title: "Premium Quality",
      desc: "Hotel-ready commercial grade products",
    },
    {
      icon: <FiClock size={22} className="text-[#B58E58]" />,
      title: "10+ Years Trust",
      desc: "Reliable hospitality supply chain",
    },
    {
      icon: <FiTruck size={22} className="text-[#B58E58]" />,
      title: "Fast Shipping",
      desc: "Priority delivery across Canada",
    },
  ];

  const handleCategoryClick = (cat) => {
    if (formData.productInterest) {
      // Agar pehle se kuch likha hai toh comma lagakar add kar do
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
    <section className="w-full py-16 px-4 md:px-10 bg-[#F0EAE1] font-sans">
      <div className="max-w-[1536px] mx-auto">
        {/* Main Luxury Wholesale Banner */}
        <div className="relative bg-[#031D44] rounded-[32px] p-8 md:p-14 overflow-hidden shadow-2xl border border-[#B58E58]/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-8 mb-8">
          <div className="absolute -right-20 -bottom-20 w-96 h-96 bg-[#B58E58]/15 rounded-full blur-3xl pointer-events-none"></div>

          <div className="relative z-10 max-w-2xl">
            <div className="inline-flex items-center gap-2 bg-[#B58E58]/20 px-3.5 py-1 rounded-full mb-4 border border-[#B58E58]/40">
              <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
              <span className="text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
                B2B Commercial Partner
              </span>
            </div>

            <h2 className="text-3xl md:text-5xl font-serif font-bold text-white tracking-tight leading-tight mb-3">
              Wholesale Inquiries
            </h2>

            <p className="text-sm md:text-base text-gray-300 font-light leading-relaxed">
              Get the best pricing for bulk orders, custom hotel branding, and
              long-term supply partnerships tailored for luxury hospitality.
            </p>
          </div>

          <div className="relative z-10 flex-shrink-0">
            <button
              onClick={() => setShowQuoteModal(true)}
              className="px-8 py-4 bg-[#B58E58] hover:bg-white text-white hover:text-[#031D44] text-xs font-bold tracking-widest uppercase rounded-2xl shadow-xl transition-all duration-300 flex items-center gap-3 cursor-pointer group"
            >
              <span>Request a Quote</span>
              <FiArrowRight
                size={16}
                className="group-hover:translate-x-1.5 transition-transform"
              />
            </button>
          </div>
        </div>

        {/* Feature Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {features.map((feature, index) => (
            <div
              key={index}
              className="flex items-center gap-5 p-6 md:p-7 border border-[#E5DCD0] rounded-[24px] bg-[#F7F2EB] shadow-sm hover:shadow-xl hover:shadow-[#B58E58]/15 hover:border-[#B58E58] transition-all duration-300 group"
            >
              <div className="w-14 h-14 rounded-2xl bg-[#031D44] flex items-center justify-center flex-shrink-0 shadow-md group-hover:scale-110 transition-transform">
                {feature.icon}
              </div>
              <div>
                <h4 className="text-[#031D44] text-base font-serif font-bold mb-1">
                  {feature.title}
                </h4>
                <p className="text-gray-600 text-xs font-light leading-relaxed">
                  {feature.desc}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Quote Request Modal Popup */}
      {showQuoteModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-[#FAF7F2] rounded-3xl max-w-xl w-full p-6 md:p-8 shadow-2xl relative border border-[#E5DCD0] max-h-[90vh] overflow-y-auto">
            <button
              onClick={() => setShowQuoteModal(false)}
              className="absolute top-6 right-6 text-gray-400 hover:text-[#031D44] bg-white p-2 rounded-full transition-colors cursor-pointer shadow-sm border border-gray-100"
            >
              <FiX size={18} />
            </button>

            <div className="mb-5">
              <span className="text-[10px] font-bold text-[#B58E58] tracking-[0.2em] uppercase">
                Corporate B2B Desk
              </span>
              <h3 className="text-2xl font-serif font-bold text-[#031D44] mt-0.5">
                Request Wholesale Quote
              </h3>
              <p className="text-xs text-gray-500 mt-1">
                Fill out your details below and specify the products you need.
              </p>
            </div>

            {submitted ? (
              <div className="py-12 flex flex-col items-center justify-center text-center">
                <FiCheckCircle
                  size={54}
                  className="text-[#B58E58] mb-3 animate-bounce"
                />
                <h4 className="text-lg font-serif font-bold text-[#031D44]">
                  Request Submitted!
                </h4>
                <p className="text-xs text-gray-500 mt-1">
                  Thank you for your bulk inquiry. We will reach out shortly.
                </p>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1">
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
                      className="w-full bg-white text-xs px-3.5 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-sm"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1">
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
                      className="w-full bg-white text-xs px-3.5 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-sm"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1">
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
                    className="w-full bg-white text-xs px-3.5 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-sm"
                  />
                </div>

                {/* Product Interest Input with Quick-Click Suggestion Badges */}
                <div>
                  <div className="flex justify-between items-center mb-1">
                    <label className="block text-[11px] font-bold uppercase tracking-wider text-[#031D44]">
                      Product / Category Interest
                    </label>
                    <span className="text-[10px] text-[#B58E58] font-medium">
                      Click tags below to add quickly
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
                    className="w-full bg-white text-xs px-3.5 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-sm mb-2"
                  />

                  {/* Clickable Quick Category Pills */}
                  <div className="flex flex-wrap gap-1.5">
                    {availableCategories.map((cat, idx) => (
                      <button
                        type="button"
                        key={idx}
                        onClick={() => handleCategoryClick(cat)}
                        className="text-[10px] font-medium bg-white hover:bg-[#031D44] hover:text-white text-[#031D44] border border-[#E5DCD0] px-2.5 py-1 rounded-lg transition-colors shadow-2xs cursor-pointer"
                      >
                        + {cat}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Estimated Quantity Range */}
                <div>
                  <label className="block text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-2">
                    Estimated Quantity Range
                  </label>
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    {quantityRanges.map((range, idx) => (
                      <div
                        key={idx}
                        onClick={() =>
                          setFormData({ ...formData, quantity: range })
                        }
                        className={`px-3 py-2 text-center rounded-xl text-[11px] font-medium cursor-pointer transition-all border ${
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
                  <label className="block text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1">
                    Additional Requirements (Optional)
                  </label>
                  <textarea
                    rows="2"
                    value={formData.message}
                    onChange={(e) =>
                      setFormData({ ...formData, message: e.target.value })
                    }
                    placeholder="Mention custom embroidery, specific delivery dates, etc."
                    className="w-full bg-white text-xs px-3.5 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-sm resize-none"
                  ></textarea>
                </div>

                <button
                  type="submit"
                  className="w-full py-4 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-lg transition-all cursor-pointer"
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
