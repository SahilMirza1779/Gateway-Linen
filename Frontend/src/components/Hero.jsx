import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { FiArrowRight, FiTruck, FiAward, FiX } from "react-icons/fi";

const Hero = () => {
  const navigate = useNavigate();
  const [showQuoteModal, setShowQuoteModal] = useState(false);
  const [quoteForm, setQuoteForm] = useState({
    name: "",
    email: "",
    requirements: "",
  });
  const [submitted, setSubmitted] = useState(false);

  const handleQuoteSubmit = (e) => {
    e.preventDefault();
    setSubmitted(true);
    setTimeout(() => {
      setSubmitted(false);
      setShowQuoteModal(false);
      setQuoteForm({ name: "", email: "", requirements: "" });
      alert("Quote request submitted successfully! We will contact you soon.");
    }, 1500);
  };

  return (
    <section className="w-full bg-[#F0EAE1] py-8 px-4 md:px-10 font-sans">
      <div className="max-w-[1536px] mx-auto">
        {/* Main Hero Wrapper */}
        <div className="relative w-full h-[580px] md:h-[620px] rounded-[32px] overflow-hidden shadow-xl flex items-center">
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
          <div className="relative z-10 px-8 md:px-16 lg:px-20 max-w-2xl text-white">
            <div className="inline-flex items-center gap-2 bg-[#B58E58]/20 border border-[#B58E58]/40 px-3.5 py-1.5 rounded-full mb-6 backdrop-blur-md">
              <span className="w-2 h-2 rounded-full bg-[#B58E58] animate-ping"></span>
              <span className="text-[#B58E58] text-[10px] md:text-xs font-bold tracking-[0.2em] uppercase">
                Exclusively For Hotels & Spas
              </span>
            </div>

            <h1 className="text-4xl md:text-6xl font-serif font-bold leading-tight mb-6">
              Elevate Your <br />
              <span className="text-[#B58E58]">Hospitality</span> Experience
            </h1>

            <p className="text-[#F0EAE1]/80 text-sm md:text-base font-light mb-8 leading-relaxed max-w-lg">
              Supply your establishment with world-class commercial linens,
              premium Egyptian cotton sheets, and ultra-plush hotel towels
              crafted for ultimate guest comfort.
            </p>

            <div className="flex flex-wrap items-center gap-4">
              <button
                onClick={() => navigate("/products")}
                className="bg-[#B58E58] hover:bg-[#9c7949] text-white px-8 py-4 rounded-2xl text-xs md:text-sm font-bold tracking-widest uppercase transition-all flex items-center gap-3 group shadow-xl shadow-[#B58E58]/30 cursor-pointer"
              >
                Explore Collection
                <FiArrowRight
                  className="group-hover:translate-x-1.5 transition-transform"
                  size={16}
                />
              </button>

              {/* Towel button replaced with Request a Quote */}
              <button
                onClick={() => setShowQuoteModal(true)}
                className="bg-white/10 hover:bg-white/20 text-white border border-white/20 px-8 py-4 rounded-2xl text-xs md:text-sm font-bold tracking-widest uppercase backdrop-blur-md transition-all cursor-pointer"
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

      {/* Quote Request Modal */}
      {showQuoteModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-lg w-full p-6 md:p-8 shadow-2xl relative animate-in fade-in zoom-in duration-200">
            <button
              onClick={() => setShowQuoteModal(false)}
              className="absolute top-6 right-6 text-gray-400 hover:text-gray-600 p-1 rounded-full transition-colors"
            >
              <FiX size={20} />
            </button>

            <h3 className="text-2xl font-serif font-bold text-[#031D44] mb-2">
              Request a Wholesale Quote
            </h3>
            <p className="text-xs text-gray-500 mb-6">
              Fill in your details and commercial requirements. Our team will
              get back to you with custom pricing.
            </p>

            <form onSubmit={handleQuoteSubmit} className="space-y-4">
              <div>
                <label className="block text-[11px] font-bold text-gray-700 uppercase tracking-widest mb-1">
                  Full Name / Hotel Name
                </label>
                <input
                  type="text"
                  required
                  value={quoteForm.name}
                  onChange={(e) =>
                    setQuoteForm({ ...quoteForm, name: e.target.value })
                  }
                  placeholder="e.g. Grand Vista Hotel"
                  className="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#B58E58]"
                />
              </div>
              <div>
                <label className="block text-[11px] font-bold text-gray-700 uppercase tracking-widest mb-1">
                  Email Address
                </label>
                <input
                  type="email"
                  required
                  value={quoteForm.email}
                  onChange={(e) =>
                    setQuoteForm({ ...quoteForm, email: e.target.value })
                  }
                  placeholder="e.g. procurement@hotel.com"
                  className="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#B58E58]"
                />
              </div>
              <div>
                <label className="block text-[11px] font-bold text-gray-700 uppercase tracking-widest mb-1">
                  Requirements & Quantities
                </label>
                <textarea
                  rows="3"
                  required
                  value={quoteForm.requirements}
                  onChange={(e) =>
                    setQuoteForm({ ...quoteForm, requirements: e.target.value })
                  }
                  placeholder="Mention items, sizes, and quantities needed..."
                  className="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#B58E58]"
                ></textarea>
              </div>

              <button
                type="submit"
                disabled={submitted}
                className="w-full py-4 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer"
              >
                {submitted ? "Submitting Quote..." : "Submit Quote Request"}
              </button>
            </form>
          </div>
        </div>
      )}
    </section>
  );
};

export default Hero;
