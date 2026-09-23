import { useEffect } from "react";
import {
  BrowserRouter as Router,
  Routes,
  Route,
  useLocation,
  useNavigate,
} from "react-router-dom";
import Navbar from "./components/Navbar";
import Hero from "./components/Hero";
import CategoryGrid from "./components/CategoryGrid";
import FeaturedProducts from "./components/FeaturedProducts";
import WholesaleSection from "./components/WholesaleSection";
import Footer from "./components/Footer";
import Login from "./components/Login";
import Register from "./components/Register";
import ForgotPassword from "./components/ForgotPassword";
import CategoryPage from "./components/CategoryPage";
import ContactPage from "./components/ContactPage";
import Dashboard from "./components/Dashboard";
import ProductDetail from "./components/ProductDetail";
import Checkout from "./components/Checkout";
import { CartProvider } from "./context/CartContext";
import CartDrawer from "./components/CartDrawer";
import { WishlistProvider } from "./context/WishlistContext";
import WishlistDrawer from "./components/WishlistDrawer";
import ProductsPage from "./components/ProductsPage";
import {
  FiArrowRight,
  FiTruck,
  FiPercent,
  FiShield,
  FiClock,
  FiAward,
  FiHeadphones,
  FiStar,
  FiCheckCircle,
  FiHelpCircle,
  FiSend,
} from "react-icons/fi";

// --- Scroll To Top Helper ---
const ScrollToTop = () => {
  const { pathname } = useLocation();
  useEffect(() => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  }, [pathname]);
  return null;
};

// --- Promotional Banners Component (Mobile Optimized) ---
const PromotionalBanners = () => {
  const navigate = useNavigate();

  return (
    <section className="py-8 md:py-12 px-4 md:px-10 bg-[#F0EAE1] font-sans">
      <div className="max-w-[1300px] mx-auto">
        <div className="text-center mb-6 md:mb-10">
          <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
            Special Curated Offers
          </span>
          <h2 className="text-xl md:text-3xl font-serif font-bold text-[#031D44] mt-1">
            Exclusive Linen & Hospitality Deals
          </h2>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
          <div className="lg:col-span-2 bg-[#F7F2EB] border border-[#E5DCD0] rounded-[24px] md:rounded-[32px] p-6 md:p-12 relative overflow-hidden flex flex-col justify-between shadow-xl group">
            <div className="absolute -right-10 -bottom-10 w-64 h-64 bg-[#B58E58]/10 rounded-full blur-2xl group-hover:scale-110 transition-transform"></div>

            <div className="relative z-10 max-w-lg">
              <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-[#031D44] text-white text-[9px] md:text-[10px] font-bold uppercase tracking-wider rounded-full mb-3 md:mb-5 shadow-2xs">
                <FiPercent size={12} /> Commercial Partner Program
              </span>
              <h3 className="text-xl md:text-4xl font-serif font-bold text-[#031D44] mb-2 md:mb-3 leading-tight">
                Hospitality Grade Bulk Supply & Custom Linens
              </h3>
              <p className="text-xs md:text-sm text-gray-600 font-light mb-6 md:mb-8 leading-relaxed">
                Equipping hotels, spas, and healthcare facilities across Canada
                with premium, durable, commercial-grade linen solutions at
                wholesale pricing.
              </p>
              <button
                onClick={() => navigate("/products")}
                className="inline-flex items-center gap-2 px-5 py-3 md:px-6 md:py-3.5 bg-[#031D44] hover:bg-[#B58E58] text-white text-[11px] md:text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
              >
                Explore Commercial <FiArrowRight size={16} />
              </button>
            </div>
          </div>

          <div className="flex flex-col gap-4 md:gap-6">
            <div className="bg-[#F7F2EB] border border-[#E5DCD0] rounded-[22px] md:rounded-[28px] p-5 md:p-6 relative overflow-hidden shadow-md flex flex-col justify-between group">
              <div className="relative z-10">
                <span className="text-[9px] font-bold text-[#B58E58] tracking-widest uppercase">
                  New Season
                </span>
                <h4 className="text-base md:text-lg font-serif font-bold text-[#031D44] mt-1 mb-1.5 md:mb-2">
                  Organic Cotton Bed Sheets
                </h4>
                <p className="text-xs text-gray-600 font-light mb-3 md:mb-4">
                  Experience unmatched breathability and softness for superior
                  sleep.
                </p>
                <button
                  onClick={() => navigate("/products")}
                  className="inline-flex items-center gap-1.5 text-xs font-bold text-[#031D44] hover:text-[#B58E58] transition-colors cursor-pointer group-hover:translate-x-1 duration-300"
                >
                  Shop Collection <FiArrowRight size={14} />
                </button>
              </div>
            </div>

            <div className="bg-[#031D44] text-white rounded-[22px] md:rounded-[28px] p-5 md:p-6 relative overflow-hidden shadow-md flex items-center gap-4">
              <div className="w-10 h-10 md:w-12 md:h-12 bg-[#B58E58]/20 text-[#B58E58] rounded-xl md:rounded-2xl flex items-center justify-center shrink-0">
                <FiTruck size={22} />
              </div>
              <div>
                <h4 className="text-xs md:text-sm font-serif font-bold mb-0.5 md:mb-1">
                  Free Pan-Canada Delivery
                </h4>
                <p className="text-[10px] md:text-[11px] text-gray-300 font-light leading-relaxed">
                  Complimentary shipping on all orders above CAD $100.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

// --- Flash Sale & Trust Badges ---
const FlashSaleAndTrust = () => {
  const navigate = useNavigate();

  return (
    <section className="py-8 md:py-10 px-4 md:px-10 bg-[#F0EAE1] font-sans border-t border-[#E5DCD0]">
      <div className="max-w-[1300px] mx-auto">
        <div className="bg-gradient-to-r from-[#031D44] to-[#14336B] rounded-[24px] md:rounded-[32px] p-6 md:p-10 text-white shadow-xl mb-8 md:mb-12 flex flex-col lg:flex-row items-center justify-between gap-6 md:gap-8 relative overflow-hidden">
          <div className="absolute right-0 top-0 w-96 h-96 bg-[#B58E58]/10 rounded-full blur-3xl"></div>

          <div className="relative z-10 text-center lg:text-left">
            <div className="inline-flex items-center gap-2 px-3 py-1 bg-[#B58E58] text-white text-[9px] md:text-[10px] font-bold uppercase tracking-wider rounded-full mb-2 md:mb-3">
              <FiClock size={12} /> Limited Time Wholesale Event
            </div>
            <h3 className="text-xl md:text-3xl font-serif font-bold mb-2">
              Grand Hotel & Spa Clearance Sale
            </h3>
            <p className="text-xs text-gray-300 font-light max-w-xl">
              Get up to 40% off on commercial-grade pool towels, duvet covers,
              and luxury mattress pads. Valid for registered B2B partners and
              retail shoppers.
            </p>
          </div>

          <button
            onClick={() => navigate("/products")}
            className="relative z-10 px-6 py-3.5 md:px-8 md:py-4 bg-[#B58E58] hover:bg-white hover:text-[#031D44] text-white text-xs font-bold tracking-widest uppercase rounded-xl md:rounded-2xl shadow-lg transition-all cursor-pointer whitespace-nowrap"
          >
            Grab Deals Now
          </button>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
          <div className="bg-[#F7F2EB] p-5 md:p-6 rounded-[20px] md:rounded-[24px] border border-[#E5DCD0] flex items-center gap-4 shadow-sm">
            <div className="w-10 h-10 md:w-12 md:h-12 bg-[#031D44]/10 text-[#031D44] rounded-xl md:rounded-2xl flex items-center justify-center shrink-0">
              <FiShield size={20} className="text-[#B58E58]" />
            </div>
            <div>
              <h4 className="text-xs font-serif font-bold text-[#031D44] mb-0.5">
                100% Secure Checkout
              </h4>
              <p className="text-[10px] md:text-[11px] text-gray-500 font-light">
                Encrypted 256-bit payment gateway
              </p>
            </div>
          </div>

          <div className="bg-[#F7F2EB] p-5 md:p-6 rounded-[20px] md:rounded-[24px] border border-[#E5DCD0] flex items-center gap-4 shadow-sm">
            <div className="w-10 h-10 md:w-12 md:h-12 bg-[#031D44]/10 text-[#031D44] rounded-xl md:rounded-2xl flex items-center justify-center shrink-0">
              <FiAward size={20} className="text-[#B58E58]" />
            </div>
            <div>
              <h4 className="text-xs font-serif font-bold text-[#031D44] mb-0.5">
                Hospitality Grade
              </h4>
              <p className="text-[10px] md:text-[11px] text-gray-500 font-light">
                Built for heavy commercial laundry
              </p>
            </div>
          </div>

          <div className="bg-[#F7F2EB] p-5 md:p-6 rounded-[20px] md:rounded-[24px] border border-[#E5DCD0] flex items-center gap-4 shadow-sm">
            <div className="w-10 h-10 md:w-12 md:h-12 bg-[#031D44]/10 text-[#031D44] rounded-xl md:rounded-2xl flex items-center justify-center shrink-0">
              <FiTruck size={20} className="text-[#B58E58]" />
            </div>
            <div>
              <h4 className="text-xs font-serif font-bold text-[#031D44] mb-0.5">
                Express Shipping
              </h4>
              <p className="text-[10px] md:text-[11px] text-gray-500 font-light">
                Fast dispatch across North America
              </p>
            </div>
          </div>

          <div className="bg-[#F7F2EB] p-5 md:p-6 rounded-[20px] md:rounded-[24px] border border-[#E5DCD0] flex items-center gap-4 shadow-sm">
            <div className="w-10 h-10 md:w-12 md:h-12 bg-[#031D44]/10 text-[#031D44] rounded-xl md:rounded-2xl flex items-center justify-center shrink-0">
              <FiHeadphones size={20} className="text-[#B58E58]" />
            </div>
            <div>
              <h4 className="text-xs font-serif font-bold text-[#031D44] mb-0.5">
                Dedicated Support
              </h4>
              <p className="text-[10px] md:text-[11px] text-gray-500 font-light">
                B2B wholesale customer assistance
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

// --- Testimonials & Partners Component ---
const TestimonialsAndPartners = () => {
  return (
    <section className="py-10 md:py-14 px-4 md:px-10 bg-[#F0EAE1] font-sans border-t border-[#E5DCD0]">
      <div className="max-w-[1300px] mx-auto">
        <div className="text-center mb-8 md:mb-10">
          <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
            Trusted Worldwide
          </span>
          <h2 className="text-xl md:text-3xl font-serif font-bold text-[#031D44] mt-1">
            What Hotel & Spa Partners Say
          </h2>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-8 md:mb-14">
          <div className="bg-[#F7F2EB] p-6 md:p-8 rounded-[22px] md:rounded-[28px] border border-[#E5DCD0] shadow-sm flex flex-col justify-between">
            <div>
              <div className="flex text-amber-500 gap-1 mb-3 md:mb-4">
                {[...Array(5)].map((_, i) => (
                  <FiStar key={i} size={14} fill="currentColor" />
                ))}
              </div>
              <p className="text-xs text-gray-700 font-light leading-relaxed mb-4 md:mb-6">
                "Gateway Linen has been our primary supplier for over 2 years.
                Their pool towels and bed sheets withstand heavy commercial
                washing without losing softness."
              </p>
            </div>
            <div className="flex items-center gap-3 pt-3 md:pt-4 border-t border-[#E5DCD0]">
              <div className="w-9 h-9 md:w-10 md:h-10 bg-[#031D44] text-[#B58E58] font-bold rounded-full flex items-center justify-center text-xs">
                MR
              </div>
              <div>
                <h4 className="text-xs font-serif font-bold text-[#031D44]">
                  Marcus Reynolds
                </h4>
                <p className="text-[10px] text-gray-500">
                  Operations Director, Grand Plaza Hotel
                </p>
              </div>
            </div>
          </div>

          <div className="bg-[#F7F2EB] p-6 md:p-8 rounded-[22px] md:rounded-[28px] border border-[#E5DCD0] shadow-sm flex flex-col justify-between">
            <div>
              <div className="flex text-amber-500 gap-1 mb-3 md:mb-4">
                {[...Array(5)].map((_, i) => (
                  <FiStar key={i} size={14} fill="currentColor" />
                ))}
              </div>
              <p className="text-xs text-gray-700 font-light leading-relaxed mb-4 md:mb-6">
                "Exceptional quality and reliable wholesale pricing. Their
                delivery across Canada is remarkably fast, and customer support
                is always responsive."
              </p>
            </div>
            <div className="flex items-center gap-3 pt-3 md:pt-4 border-t border-[#E5DCD0]">
              <div className="w-9 h-9 md:w-10 md:h-10 bg-[#031D44] text-[#B58E58] font-bold rounded-full flex items-center justify-center text-xs">
                SL
              </div>
              <div>
                <h4 className="text-xs font-serif font-bold text-[#031D44]">
                  Sophia Laurent
                </h4>
                <p className="text-[10px] text-gray-500">
                  Manager, Serenity Luxury Spa & Resort
                </p>
              </div>
            </div>
          </div>

          <div className="bg-[#F7F2EB] p-6 md:p-8 rounded-[22px] md:rounded-[28px] border border-[#E5DCD0] shadow-sm flex flex-col justify-between">
            <div>
              <div className="flex text-amber-500 gap-1 mb-3 md:mb-4">
                {[...Array(5)].map((_, i) => (
                  <FiStar key={i} size={14} fill="currentColor" />
                ))}
              </div>
              <p className="text-xs text-gray-700 font-light leading-relaxed mb-4 md:mb-6">
                "The custom branding and bulk order discounts helped us furnish
                50+ rooms effortlessly. Highly recommend Gateway Linen for
                hospitality needs!"
              </p>
            </div>
            <div className="flex items-center gap-3 pt-3 md:pt-4 border-t border-[#E5DCD0]">
              <div className="w-9 h-9 md:w-10 md:h-10 bg-[#031D44] text-[#B58E58] font-bold rounded-full flex items-center justify-center text-xs">
                DK
              </div>
              <div>
                <h4 className="text-xs font-serif font-bold text-[#031D44]">
                  David Kennedy
                </h4>
                <p className="text-[10px] text-gray-500">
                  Procurement Head, Metro Suites
                </p>
              </div>
            </div>
          </div>
        </div>

        <div className="bg-[#FFFDF9] border border-[#E5DCD0] rounded-[20px] md:rounded-[24px] p-5 md:p-6 flex flex-col md:flex-row items-center justify-between gap-4 md:gap-6 shadow-xs">
          <div className="flex items-center gap-3 md:gap-4">
            <div className="w-10 h-10 md:w-12 md:h-12 bg-green-100 text-green-700 rounded-xl md:rounded-2xl flex items-center justify-center shrink-0">
              <FiCheckCircle size={22} />
            </div>
            <div>
              <h4 className="text-xs md:text-sm font-serif font-bold text-[#031D44]">
                Verified B2B Global Supplier & Manufacturer
              </h4>
              <p className="text-[11px] md:text-xs text-gray-500 font-light">
                Audited facilities, strict quality control, and direct factory
                wholesale rates.
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2 md:gap-3 text-[11px] md:text-xs font-bold text-[#031D44] whitespace-nowrap">
            <span className="px-3 py-1.5 md:px-4 md:py-2 bg-[#F7F2EB] rounded-xl border border-[#E5DCD0]">
              ISO 9001 Certified
            </span>
            <span className="px-3 py-1.5 md:px-4 md:py-2 bg-[#F7F2EB] rounded-xl border border-[#E5DCD0]">
              OEKO-TEX Standard
            </span>
          </div>
        </div>
      </div>
    </section>
  );
};

// --- B2B FAQ & Quick Quote Component ---
const B2BFaqAndNewsletter = () => {
  const navigate = useNavigate();

  return (
    <section className="py-10 md:py-14 px-4 md:px-10 bg-[#F0EAE1] font-sans border-t border-[#E5DCD0]">
      <div className="max-w-[1300px] mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-10 items-start">
        <div>
          <div className="mb-4 md:mb-6">
            <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase flex items-center gap-1.5">
              <FiHelpCircle size={14} /> Got Questions?
            </span>
            <h2 className="text-xl md:text-2xl font-serif font-bold text-[#031D44] mt-1">
              Frequently Asked Questions
            </h2>
          </div>

          <div className="space-y-3 md:space-y-4">
            <div className="bg-[#F7F2EB] p-4 md:p-5 rounded-2xl border border-[#E5DCD0]">
              <h4 className="text-xs font-serif font-bold text-[#031D44] mb-1">
                What is the minimum order quantity for wholesale pricing?
              </h4>
              <p className="text-[11px] text-gray-600 font-light">
                For wholesale and commercial partners, our minimum order value
                starts at CAD $250 with tiered discounts for bulk hotel
                supplies.
              </p>
            </div>

            <div className="bg-[#F7F2EB] p-4 md:p-5 rounded-2xl border border-[#E5DCD0]">
              <h4 className="text-xs font-serif font-bold text-[#031D44] mb-1">
                Do you offer custom logo embroidery for hotels and spas?
              </h4>
              <p className="text-[11px] text-gray-600 font-light">
                Yes! We provide custom embroidery and branding on all our
                premium pool towels, bathrobes, and bed linens.
              </p>
            </div>

            <div className="bg-[#F7F2EB] p-4 md:p-5 rounded-2xl border border-[#E5DCD0]">
              <h4 className="text-xs font-serif font-bold text-[#031D44] mb-1">
                What is the standard delivery time across Canada?
              </h4>
              <p className="text-[11px] text-gray-600 font-light">
                Standard commercial deliveries across major Canadian provinces
                take between 3 to 5 business days.
              </p>
            </div>
          </div>
        </div>

        <div className="bg-[#031D44] text-white p-6 md:p-10 rounded-[24px] md:rounded-[32px] shadow-xl relative overflow-hidden flex flex-col justify-between">
          <div className="absolute right-0 bottom-0 w-64 h-64 bg-[#B58E58]/20 rounded-full blur-3xl"></div>

          <div className="relative z-10">
            <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
              B2B Quick Inquiry
            </span>
            <h3 className="text-lg md:text-2xl font-serif font-bold mt-1 mb-2 md:mb-3">
              Request a Custom Wholesale Quote
            </h3>
            <p className="text-xs text-gray-300 font-light mb-5 md:mb-6 leading-relaxed">
              Are you managing a hotel, hospital, or spa? Get personalized
              pricing and samples shipped directly to your facility.
            </p>

            <form
              onSubmit={(e) => {
                e.preventDefault();
                alert(
                  "Thank you! Our wholesale team will contact you shortly.",
                );
              }}
              className="space-y-3"
            >
              <input
                required
                type="email"
                placeholder="Enter your business email"
                className="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-xs text-white placeholder-gray-400 focus:outline-none focus:border-[#B58E58]"
              />
              <button
                type="submit"
                className="w-full py-3.5 bg-[#B58E58] hover:bg-white hover:text-[#031D44] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer flex items-center justify-center gap-2"
              >
                <FiSend size={14} /> Submit Quote Request
              </button>
            </form>
          </div>

          <div className="mt-6 md:mt-8 pt-4 md:pt-6 border-t border-white/10 relative z-10 flex items-center justify-between text-[11px] text-gray-300">
            <span>Need immediate assistance?</span>
            <button
              onClick={() => navigate("/contact")}
              className="font-bold text-[#B58E58] hover:underline cursor-pointer"
            >
              Contact Sales Team &rarr;
            </button>
          </div>
        </div>
      </div>
    </section>
  );
};

const Home = () => {
  return (
    <>
      <Hero />
      <CategoryGrid />
      <PromotionalBanners />
      <FeaturedProducts />
      <FlashSaleAndTrust />
      <TestimonialsAndPartners />
      <B2BFaqAndNewsletter />
      <WholesaleSection />
    </>
  );
};

const AppLayout = () => {
  const location = useLocation();
  const isAuthPage =
    location.pathname === "/login" ||
    location.pathname === "/register" ||
    location.pathname === "/forgot-password";

  return (
    <WishlistProvider>
      <CartProvider>
        <div className="min-h-screen bg-white font-sans flex flex-col relative">
          <ScrollToTop />
          {!isAuthPage && <Navbar />}
          <CartDrawer />
          <WishlistDrawer />
          <div className="flex-grow">
            <Routes>
              <Route path="/" element={<Home />} />
              <Route path="/login" element={<Login />} />
              <Route path="/register" element={<Register />} />
              <Route path="/forgot-password" element={<ForgotPassword />} />
              <Route
                path="/category/:categoryName"
                element={<CategoryPage />}
              />
              <Route path="/contact" element={<ContactPage />} />
              <Route path="/dashboard" element={<Dashboard />} />
              <Route path="/product/:id" element={<ProductDetail />} />
              <Route path="/checkout" element={<Checkout />} />
              <Route path="/products" element={<ProductsPage />} />
            </Routes>
          </div>
          {!isAuthPage && <Footer />}
        </div>
      </CartProvider>
    </WishlistProvider>
  );
};

export default function App() {
  return (
    <Router>
      <AppLayout />
    </Router>
  );
}
