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
      <section className="relative w-full h-[520px] md:h-[620px] lg:h-[680px] bg-[#031D44] font-sans flex items-center">
        <div className="absolute inset-0 z-0 w-full h-full">
          <img
            src={heroImage}
            alt="Luxury Hospitality Linen"
            className="w-full h-full object-cover object-center"
          />
          <div className="absolute inset-0 bg-black/45"></div>
        </div>

        {/* Content Box - Shifted more to the Left and moved Down */}
        <div className="relative z-10 w-full max-w-[1450px] mx-auto px-6 sm:px-10 md:px-16 flex justify-start pt-12 md:pt-20">
          <div className="bg-transparent p-6 md:p-10 lg:p-12 max-w-[620px] border-l-8 border-[#B58E58] rounded-none">
            <span className="text-[#B58E58] text-[10px] md:text-xs font-bold tracking-[0.2em] uppercase mb-3 block">
              EXCLUSIVELY FOR HOTELS & SPAS
            </span>

            <h1 className="text-3xl md:text-4xl lg:text-[50px] font-serif font-bold text-white leading-[1.15] mb-5 drop-shadow-lg">
              Premium Linens <br />
              for <span className="text-[#B58E58]">Hotels & Healthcare</span>
            </h1>

            <p className="text-xs md:text-sm text-gray-200 font-light mb-8 leading-relaxed drop-shadow-md">
              Equip your establishment with world-class commercial linens,
              durable Egyptian cotton sheets, and ultra-plush towels designed
              for superior comfort and longevity.
            </p>

            <div className="flex flex-col sm:flex-row items-center gap-4">
              <button
                onClick={() => navigate("/products")}
                className="w-full sm:w-auto bg-[#B58E58] hover:bg-white hover:text-[#031D44] text-white px-7 py-3.5 text-xs font-bold tracking-widest uppercase transition-colors flex items-center justify-center gap-2 group cursor-pointer rounded-none shadow-md"
              >
                Shop Collection
                <FiArrowRight
                  className="group-hover:translate-x-1.5 transition-transform"
                  size={15}
                />
              </button>

              <button
                onClick={() => setShowQuoteModal(true)}
                className="w-full sm:w-auto bg-transparent border-2 border-white text-white hover:bg-white hover:text-[#031D44] px-7 py-3.5 text-xs font-bold tracking-widest uppercase transition-colors cursor-pointer rounded-none text-center shadow-md"
              >
                Request a Quote
              </button>
            </div>
          </div>
        </div>
      </section>

      {/* QUOTE MODAL (Fully Intact) */}
      {showQuoteModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-[250] flex items-center justify-center p-3">
          <div className="bg-[#FAF7F2] rounded-none max-w-lg w-full p-6 md:p-8 shadow-2xl relative border border-[#E5DCD0] max-h-[90vh] overflow-y-auto">
            <button
              onClick={() => setShowQuoteModal(false)}
              className="absolute top-4 right-4 md:top-6 md:right-6 text-gray-400 hover:text-[#031D44] bg-white p-2 rounded-none transition-colors cursor-pointer shadow-sm border border-gray-100"
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
                      className="w-full bg-white text-xs px-3 py-2.5 rounded-none border border-gray-200 focus:outline-none focus:border-[#031D44] shadow-sm"
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
                      className="w-full bg-white text-xs px-3 py-2.5 rounded-none border border-gray-200 focus:outline-none focus:border-[#031D44] shadow-sm"
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
                    className="w-full bg-white text-xs px-3 py-2.5 rounded-none border border-gray-200 focus:outline-none focus:border-[#031D44] shadow-sm"
                  />
                </div>

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
                    className="w-full bg-white text-xs px-3 py-2.5 rounded-none border border-gray-200 focus:outline-none focus:border-[#031D44] shadow-sm mb-2"
                  />

                  <div className="flex flex-wrap gap-1">
                    {availableCategories.map((cat, idx) => (
                      <button
                        type="button"
                        key={idx}
                        onClick={() => handleCategoryClick(cat)}
                        className="text-[9px] font-medium bg-white hover:bg-[#031D44] hover:text-white text-[#031D44] border border-gray-200 px-2 py-1 rounded-none transition-colors shadow-sm cursor-pointer"
                      >
                        + {cat}
                      </button>
                    ))}
                  </div>
                </div>

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
                        className={`px-2.5 py-2 text-center rounded-none text-[10px] md:text-[11px] font-medium cursor-pointer transition-all border ${
                          formData.quantity === range
                            ? "bg-[#031D44] text-white border-[#031D44]"
                            : "bg-white text-gray-700 border-gray-200 hover:border-[#031D44]"
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
                    className="w-full bg-white text-xs px-3 py-2.5 rounded-none border border-gray-200 focus:outline-none focus:border-[#031D44] shadow-sm resize-none"
                  ></textarea>
                </div>

                <button
                  type="submit"
                  className="w-full py-3.5 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-none shadow-md transition-all cursor-pointer"
                >
                  Submit Quote Request
                </button>
              </form>
            )}
          </div>
        </div>
      )}
    </>
  );
};

export default Hero;
