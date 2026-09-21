import { useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  FiArrowRight,
  FiTruck,
  FiAward,
  FiX,
  FiCheckCircle,
} from "react-icons/fi";

const Hero = () => {
  const navigate = useNavigate();
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

  const handleQuoteSubmit = (e) => {
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
      alert("Quote request submitted successfully! We will contact you soon.");
    }, 1500);
  };

  return (
    <section className="w-full bg-[#F0EAE1] py-4 md:py-8 px-3 md:px-10 font-sans">
      <div className="max-w-[1536px] mx-auto">
        {/* Main Hero Wrapper */}
        <div className="relative w-full h-[460px] sm:h-[500px] md:h-[620px] rounded-[24px] md:rounded-[32px] overflow-hidden shadow-xl flex items-center">
          {/* Background Image with Rich Overlay */}
          <div className="absolute inset-0 z-0 bg-[#031D44]">
            <img
              src="https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=1920&auto=format&fit=crop"
              alt="Luxury Hospitality Linen"
              className="w-full h-full object-cover opacity-80 scale-105 transition-transform duration-1000"
            />
            <div className="absolute inset-0 bg-gradient-to-r from-[#031D44]/95 via-[#031D44]/75 to-transparent"></div>
          </div>

          {/* Content Box */}
          <div className="relative z-10 px-5 sm:px-10 md:px-16 lg:px-20 max-w-2xl text-white">
            <div className="inline-flex items-center gap-2 bg-[#B58E58]/20 border border-[#B58E58]/40 px-3 py-1 rounded-full mb-3 md:mb-6 backdrop-blur-md">
              <span className="w-2 h-2 rounded-full bg-[#B58E58] animate-ping"></span>
              <span className="text-[#B58E58] text-[9px] md:text-xs font-bold tracking-[0.2em] uppercase">
                Exclusively For Hotels & Spas
              </span>
            </div>

            <h1 className="text-3xl sm:text-4xl md:text-6xl font-serif font-bold leading-tight mb-3 md:mb-6">
              Elevate Your <br />
              <span className="text-[#B58E58]">Hospitality</span> Experience
            </h1>

            <p className="text-[#F0EAE1]/90 text-xs sm:text-sm md:text-base font-light mb-6 md:mb-8 leading-relaxed max-w-lg">
              Supply your establishment with world-class commercial linens,
              premium Egyptian cotton sheets, and ultra-plush hotel towels
              crafted for ultimate guest comfort.
            </p>

            <div className="flex flex-wrap items-center gap-3 md:gap-4">
              <button
                onClick={() => navigate("/products")}
                className="bg-[#B58E58] hover:bg-[#9c7949] text-white px-5 sm:px-8 py-3.5 md:py-4 rounded-xl md:rounded-2xl text-[11px] md:text-sm font-bold tracking-widest uppercase transition-all flex items-center gap-2 md:gap-3 group shadow-xl shadow-[#B58E58]/30 cursor-pointer"
              >
                Explore Collection
                <FiArrowRight
                  className="group-hover:translate-x-1.5 transition-transform"
                  size={16}
                />
              </button>

              <button
                onClick={() => setShowQuoteModal(true)}
                className="bg-white/10 hover:bg-white/20 text-white border border-white/20 px-5 sm:px-8 py-3.5 md:py-4 rounded-xl md:rounded-2xl text-[11px] md:text-sm font-bold tracking-widest uppercase backdrop-blur-md transition-all cursor-pointer"
              >
                Request a Quote
              </button>
            </div>
          </div>

          {/* Floating Trust Badges */}
          <div className="absolute bottom-6 right-6 z-10 hidden lg:flex items-center gap-4 bg-[#031D44]/80 backdrop-blur-md border border-white/10 p-4 rounded-2xl shadow-2xl">
            <div className="flex items-center gap-3 px-3 border-r border-white/10">
              <FiAward className="text-[#B58E58]" size={24} />
              <div>
                <h4 className="text-xs font-bold text-white">5-Star Quality</h4>
                <p className="text-[10px] text-gray-300">
                  Hotel Grade Durability
                </p>
              </div>
            </div>
            <div className="flex items-center gap-3 px-3">
              <FiTruck className="text-[#B58E58]" size={24} />
              <div>
                <h4 className="text-xs font-bold text-white">Fast Delivery</h4>
                <p className="text-[10px] text-gray-300">Across Canada</p>
              </div>
            </div>
          </div>
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
              <form onSubmit={handleQuoteSubmit} className="space-y-3.5">
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

export default Hero;
