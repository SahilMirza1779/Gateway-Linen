import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { FiArrowRight, FiX, FiCheckCircle } from "react-icons/fi";

const Hero = () => {
  const navigate = useNavigate();
  const [showQuoteModal, setShowQuoteModal] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  const [heroImage, setHeroImage] = useState(
    "https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=1920&auto=format&fit=crop",
  );

  useEffect(() => {
    const fetchHeroImage = async () => {
      try {
        const response = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/settings/api.php?action=get_hero_image",
        );
        const result = await response.json();
        if (result.success && result.imageUrl) {
          setHeroImage(result.imageUrl);
        }
      } catch (error) {
        console.error("Error fetching dynamic hero image:", error);
      }
    };

    fetchHeroImage();
  }, []);

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
    <>
      {/* EDEN TEXTILE CLONE HERO SECTION */}
      <section className="relative w-full min-h-[85vh] md:min-h-[680px] bg-[#031D44] font-sans flex items-center">
        <div className="absolute inset-0 z-0 w-full h-full">
          <img
            src={heroImage}
            alt="Luxury Hospitality Linen"
            className="w-full h-full object-cover object-center animate-in zoom-in duration-1000"
          />
          {/* Enhanced gradient for better text readability on mobile */}
          <div className="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-transparent"></div>
        </div>

        {/* Content Box */}
        <div className="relative z-10 w-full max-w-[1450px] mx-auto px-6 sm:px-10 md:px-16 flex justify-start pt-16 md:pt-20">
          <div className="bg-transparent p-4 sm:p-8 md:p-10 lg:p-12 max-w-[620px] border-l-[3px] md:border-l-8 border-[#B58E58] rounded-none animate-in fade-in slide-in-from-left-8 duration-700">
            <span className="text-[#B58E58] text-[9px] md:text-xs font-bold tracking-[0.2em] uppercase mb-3 sm:mb-4 block">
              EXCLUSIVELY FOR HOTELS & SPAS
            </span>

            <h1 className="text-4xl sm:text-5xl lg:text-[50px] font-serif font-bold text-white leading-[1.15] mb-4 sm:mb-5 drop-shadow-lg">
              Premium Linens <br />
              for <span className="text-[#B58E58]">Hotels & Healthcare</span>
            </h1>

            <p className="text-xs sm:text-sm md:text-base text-gray-200 font-light mb-8 sm:mb-10 leading-relaxed drop-shadow-md max-w-md">
              Equip your establishment with world-class commercial linens,
              durable Egyptian cotton sheets, and ultra-plush towels designed
              for superior comfort and longevity.
            </p>

            {/* Responsive Buttons (Stacked on Mobile, Row on Desktop) */}
            <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5 sm:gap-4">
              <button
                onClick={() => navigate("/products")}
                className="w-full sm:w-auto bg-[#B58E58] hover:bg-white hover:text-[#031D44] text-white px-7 py-3.5 sm:py-4 text-[11px] sm:text-xs font-bold tracking-widest uppercase transition-colors flex items-center justify-center gap-2 group cursor-pointer rounded-none shadow-xl border border-[#B58E58] hover:border-white"
              >
                Shop Collection
                <FiArrowRight
                  className="group-hover:translate-x-1.5 transition-transform"
                  size={15}
                />
              </button>

              <button
                onClick={() => setShowQuoteModal(true)}
                className="w-full sm:w-auto bg-transparent border border-white/50 hover:border-white text-white hover:bg-white/10 px-7 py-3.5 sm:py-4 text-[11px] sm:text-xs font-bold tracking-widest uppercase transition-colors cursor-pointer rounded-none text-center shadow-lg backdrop-blur-sm"
              >
                Request a Quote
              </button>
            </div>
          </div>
        </div>
      </section>

      {/* QUOTE MODAL (Mobile Optimized) */}
      {showQuoteModal && (
        <div className="fixed inset-0 bg-black/70 backdrop-blur-sm z-[250] flex items-center justify-center p-4 sm:p-6">
          <div className="bg-[#FAF7F2] rounded-[24px] max-w-lg w-full p-6 md:p-8 shadow-2xl relative border border-[#E5DCD0] max-h-[90vh] overflow-y-auto custom-scrollbar">
            <button
              onClick={() => setShowQuoteModal(false)}
              className="absolute top-4 right-4 md:top-6 md:right-6 text-gray-400 hover:text-[#031D44] bg-white p-2 rounded-full transition-colors cursor-pointer shadow-sm border border-gray-200"
            >
              <FiX size={18} />
            </button>

            <div className="mb-5 md:mb-6 pr-8">
              <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-[0.2em] uppercase block mb-1">
                Corporate B2B Desk
              </span>
              <h3 className="text-xl md:text-2xl font-serif font-bold text-[#031D44]">
                Request Wholesale Quote
              </h3>
              <p className="text-[11px] md:text-xs text-gray-500 mt-1.5 font-light">
                Fill out your details below and specify the products you need.
              </p>
            </div>

            {submitted ? (
              <div className="py-10 flex flex-col items-center justify-center text-center">
                <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4 shadow-inner">
                  <FiCheckCircle
                    size={32}
                    className="text-green-600 animate-bounce"
                  />
                </div>
                <h4 className="text-lg md:text-xl font-serif font-bold text-[#031D44]">
                  Request Submitted!
                </h4>
                <p className="text-xs text-gray-500 mt-2 font-light">
                  Thank you for your bulk inquiry. We will reach out shortly.
                </p>
              </div>
            ) : (
              <form onSubmit={handleQuoteSubmit} className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1.5">
                      Full Name <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      required
                      value={formData.fullName}
                      onChange={(e) =>
                        setFormData({ ...formData, fullName: e.target.value })
                      }
                      placeholder="Enter your full name"
                      className="w-full bg-white text-xs px-4 py-3 rounded-xl border border-[#E5DCD0] focus:outline-none focus:border-[#B58E58] shadow-2xs transition-colors"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1.5">
                      Email Address <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="email"
                      required
                      value={formData.email}
                      onChange={(e) =>
                        setFormData({ ...formData, email: e.target.value })
                      }
                      placeholder="name@company.ca"
                      className="w-full bg-white text-xs px-4 py-3 rounded-xl border border-[#E5DCD0] focus:outline-none focus:border-[#B58E58] shadow-2xs transition-colors"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1.5">
                    Hotel / Company Name <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.company}
                    onChange={(e) =>
                      setFormData({ ...formData, company: e.target.value })
                    }
                    placeholder="Enter your hotel or business name"
                    className="w-full bg-white text-xs px-4 py-3 rounded-xl border border-[#E5DCD0] focus:outline-none focus:border-[#B58E58] shadow-2xs transition-colors"
                  />
                </div>

                <div>
                  <div className="flex justify-between items-center mb-1.5">
                    <label className="block text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-[#031D44]">
                      Product / Category Interest{" "}
                      <span className="text-red-500">*</span>
                    </label>
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
                    className="w-full bg-white text-xs px-4 py-3 rounded-xl border border-[#E5DCD0] focus:outline-none focus:border-[#B58E58] shadow-2xs mb-2.5 transition-colors"
                  />

                  <div className="flex flex-wrap gap-1.5">
                    {availableCategories.map((cat, idx) => (
                      <button
                        type="button"
                        key={idx}
                        onClick={() => handleCategoryClick(cat)}
                        className="text-[10px] font-bold bg-white hover:bg-[#031D44] hover:text-white text-[#031D44] border border-[#E5DCD0] px-2.5 py-1.5 rounded-lg transition-colors shadow-sm cursor-pointer"
                      >
                        + {cat}
                      </button>
                    ))}
                  </div>
                </div>

                <div>
                  <label className="block text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-2">
                    Estimated Quantity Range
                  </label>
                  <div className="grid grid-cols-2 gap-2.5">
                    {quantityRanges.map((range, idx) => (
                      <div
                        key={idx}
                        onClick={() =>
                          setFormData({ ...formData, quantity: range })
                        }
                        className={`px-3 py-2.5 text-center rounded-xl text-[10px] md:text-[11px] font-bold cursor-pointer transition-all border shadow-sm ${
                          formData.quantity === range
                            ? "bg-[#031D44] text-white border-[#031D44]"
                            : "bg-white text-gray-700 border-[#E5DCD0] hover:border-[#B58E58]"
                        }`}
                      >
                        {range}
                      </div>
                    ))}
                  </div>
                </div>

                <div>
                  <label className="block text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1.5">
                    Additional Requirements{" "}
                    <span className="text-gray-400 normal-case">
                      (Optional)
                    </span>
                  </label>
                  <textarea
                    rows="2"
                    value={formData.message}
                    onChange={(e) =>
                      setFormData({ ...formData, message: e.target.value })
                    }
                    placeholder="Mention custom embroidery, delivery dates..."
                    className="w-full bg-white text-xs px-4 py-3 rounded-xl border border-[#E5DCD0] focus:outline-none focus:border-[#B58E58] shadow-2xs resize-none transition-colors"
                  ></textarea>
                </div>

                <div className="pt-2">
                  <button
                    type="submit"
                    className="w-full py-4 bg-[#031D44] hover:bg-[#B58E58] text-white text-[11px] md:text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
                  >
                    Submit Quote Request
                  </button>
                </div>
              </form>
            )}
          </div>
        </div>
      )}
    </>
  );
};

export default Hero;
