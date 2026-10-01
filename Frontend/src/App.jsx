import { useState, useEffect } from "react";
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
import QuoteBuilder from "./components/QuoteBuilder";
import BulkOrder from "./components/BulkOrder";
import OrderHistory from "./components/OrderHistory";
import FloatingWidget from "./components/FloatingWidget";
import {
  FiArrowRight,
  FiUserPlus,
  FiLogIn,
  FiStar,
  FiChevronLeft,
  FiChevronRight,
  FiLayout,
  FiShoppingBag,
} from "react-icons/fi";

// --- Scroll To Top Helper ---
const ScrollToTop = () => {
  const { pathname } = useLocation();
  useEffect(() => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  }, [pathname]);
  return null;
};

// --- Eden Style: Create Your Account Banner with Real Mockup ---
const AccountSection = () => {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);

  useEffect(() => {
    const checkUserStatus = () => {
      const storedUser = localStorage.getItem("user");
      const parsedUser = storedUser ? JSON.parse(storedUser) : null;
      setUser((prev) => {
        if (JSON.stringify(prev) !== JSON.stringify(parsedUser)) {
          return parsedUser;
        }
        return prev;
      });
    };

    const timer = setTimeout(() => {
      checkUserStatus();
    }, 0);

    window.addEventListener("storage", checkUserStatus);

    return () => {
      clearTimeout(timer);
      window.removeEventListener("storage", checkUserStatus);
    };
  }, []);

  return (
    <section className="py-12 md:py-16 px-4 md:px-10 bg-white font-sans border-t border-gray-100">
      <div className="max-w-[1300px] mx-auto bg-[#FAF7F2] border border-[#E5DCD0] rounded-[24px] p-6 sm:p-10 md:p-14 relative overflow-hidden shadow-sm">
        <div className="absolute right-0 bottom-0 w-80 h-80 bg-[#B58E58]/10 rounded-full blur-3xl pointer-events-none"></div>

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 md:gap-12 items-center">
          {/* Left Side: Laptop & Mobile Mockup */}
          <div className="lg:col-span-7 relative flex justify-center items-center">
            <div className="relative w-full max-w-[560px] flex items-end justify-center lg:justify-start">
              {/* Laptop Mockup */}
              <div className="w-[90%] sm:w-[85%] bg-[#2D3748] p-2.5 sm:p-3 rounded-t-xl shadow-2xl border border-gray-700">
                <div className="bg-white rounded overflow-hidden aspect-[16/10] relative">
                  <img
                    src="https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=800&auto=format&fit=crop"
                    alt="Laptop Preview"
                    className="w-full h-full object-cover"
                  />
                  <div className="absolute inset-0 bg-black/10 flex items-center justify-center">
                    <span className="bg-white/90 px-3 py-1 text-[10px] font-bold text-[#031D44] uppercase tracking-wider shadow">
                      Gateway Linen B2B
                    </span>
                  </div>
                </div>
                <div className="h-2 bg-[#1A202C] rounded-b-xl -mx-2.5 sm:-mx-3 -mb-2.5 sm:-mb-3 mt-2"></div>
              </div>

              {/* Mobile Mockup overlapping */}
              <div className="w-[32%] bg-[#1A202C] p-1.5 sm:p-2 rounded-2xl shadow-2xl border border-gray-700 absolute right-2 sm:-right-2 -bottom-3 sm:-bottom-4">
                <div className="bg-white rounded-xl overflow-hidden aspect-[9/18] relative">
                  <img
                    src="https://images.unsplash.com/photo-1584132967334-10e028bd69f7?q=80&w=400&auto=format&fit=crop"
                    alt="Mobile Preview"
                    className="w-full h-full object-cover"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Right Side: Dynamic Content */}
          <div className="lg:col-span-5 text-center lg:text-left">
            {user ? (
              <div className="animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div className="inline-flex items-center gap-2 bg-[#B58E58]/10 px-3 py-1 rounded-full mb-4 border border-[#B58E58]/20">
                  <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
                  <p className="text-[#B58E58] text-[9px] font-bold uppercase tracking-[0.2em]">
                    Welcome Back, {user.fullName?.split(" ")[0] || "Partner"}
                  </p>
                </div>

                <h2 className="text-2xl md:text-4xl font-serif font-bold text-[#031D44] mb-3 md:mb-4">
                  Your B2B Portal
                </h2>

                <p className="text-xs md:text-sm text-gray-700 font-light mb-6 md:mb-8 leading-relaxed">
                  Access your personalized wholesale dashboard. Track active
                  shipments, review past invoices, and easily re-order our
                  latest premium collections with your exclusive pricing.
                </p>

                <div className="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3.5">
                  <button
                    onClick={() => navigate("/dashboard")}
                    className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
                  >
                    <FiLayout size={15} /> Account Dashboard
                  </button>
                  <button
                    onClick={() => navigate("/products")}
                    className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-white border border-[#E5DCD0] text-[#031D44] hover:border-[#031D44] hover:bg-gray-50 text-xs font-bold tracking-widest uppercase rounded-xl transition-all cursor-pointer shadow-sm"
                  >
                    <FiShoppingBag size={15} /> Browse Catalog
                  </button>
                </div>
              </div>
            ) : (
              <div className="animate-in fade-in slide-in-from-bottom-4 duration-500">
                <span className="text-[9px] md:text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase block mb-2">
                  CLIENT PORTAL & BENEFITS
                </span>
                <h2 className="text-2xl md:text-4xl font-serif font-bold text-[#031D44] mb-3 md:mb-4">
                  Create Your Account
                </h2>
                <p className="text-xs md:text-sm text-gray-700 font-light mb-6 md:mb-8 leading-relaxed">
                  Sign up to get access to exclusive offers, the opportunity for
                  preferred pricing, detailed account records, order history
                  tracking, and more!
                </p>

                <div className="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3.5">
                  <button
                    onClick={() => navigate("/register")}
                    className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-[#4A5D4E] hover:bg-[#031D44] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
                  >
                    <FiUserPlus size={15} /> SIGN UP NOW
                  </button>

                  <button
                    onClick={() => navigate("/login")}
                    className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-white border border-[#031D44] text-[#031D44] hover:bg-[#031D44] hover:text-white text-xs font-bold tracking-widest uppercase rounded-xl transition-all cursor-pointer shadow-sm"
                  >
                    <FiLogIn size={15} /> SIGN IN
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </section>
  );
};

// --- Blog Section ---
const BlogSection = () => {
  const navigate = useNavigate();

  const blogs = [
    {
      date: "September 22, 2026",
      title: "Building a Smarter Linen Budget...",
      image:
        "https://images.unsplash.com/photo-1621252179027-94459d278660?q=80&w=800&auto=format&fit=crop",
      link: "/blog/linen-budget",
    },
    {
      date: "September 15, 2026",
      title: "Creating a Memorable Fall...",
      image:
        "https://images.unsplash.com/photo-1540555700478-4be289fbecef?q=80&w=800&auto=format&fit=crop",
      link: "/blog/memorable-fall",
    },
    {
      date: "September 8, 2026",
      title: "Table Napkin Selection Guide for Fal...",
      image:
        "https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=800&auto=format&fit=crop",
      link: "/blog/napkin-guide",
    },
  ];

  return (
    <section className="py-12 md:py-24 px-4 md:px-10 bg-white font-sans border-t border-gray-100">
      <div className="max-w-[1300px] mx-auto">
        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 md:mb-12 gap-4">
          <div>
            <span className="text-xs md:text-sm text-gray-500 font-light mb-1.5 block">
              Learn More About Industry News and Insights
            </span>
            <h2 className="text-2xl md:text-[40px] font-bold text-[#4A5568] tracking-tight">
              Latest From the Blog
            </h2>
          </div>
          <button
            onClick={() => navigate("/blog")}
            className="px-6 py-2.5 bg-[#4A5D4E] hover:bg-[#031D44] text-white text-xs font-bold rounded-xl transition-colors cursor-pointer uppercase tracking-wider"
          >
            View All
          </button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-10">
          {blogs.map((blog, index) => (
            <div key={index} className="flex flex-col group">
              <div className="w-full aspect-[4/3] rounded-xl overflow-hidden mb-4 bg-gray-100 shadow-sm border border-gray-100">
                <img
                  src={blog.image}
                  alt={blog.title}
                  className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                />
              </div>
              <p className="text-[11px] md:text-xs text-gray-500 font-light mb-1.5">
                {blog.date}
              </p>
              <h3 className="text-base md:text-xl font-medium text-[#4A5568] mb-4 truncate w-full">
                {blog.title}
              </h3>
              <div>
                <button
                  onClick={() => navigate(blog.link)}
                  className="inline-block px-6 py-2 border border-[#4A5568] text-[#4A5568] hover:bg-[#4A5568] hover:text-white text-xs font-bold rounded-xl transition-colors cursor-pointer"
                >
                  Read more
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

// --- Eden Style "About Us" Section ---
const AboutUsSection = () => {
  const navigate = useNavigate();

  return (
    <section className="py-12 md:py-20 px-4 md:px-10 bg-white font-sans border-t border-gray-100">
      <div className="max-w-[1300px] mx-auto flex flex-col lg:flex-row items-center">
        <div className="w-full lg:w-3/5 h-[300px] md:h-[500px] relative rounded-xl overflow-hidden shadow-sm">
          <img
            src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=1200&auto=format&fit=crop"
            alt="Gateway Linen Facility"
            className="w-full h-full object-cover"
          />
        </div>

        <div className="w-full lg:w-2/5 lg:-ml-16 relative z-10 mt-6 lg:mt-0">
          <div className="bg-[#FAF7F2] p-6 sm:p-8 md:p-12 lg:p-14 border border-[#E5DCD0] shadow-xl rounded-2xl lg:rounded-none">
            <h2 className="text-2xl md:text-[32px] font-serif font-bold text-[#031D44] mb-3 md:mb-4 leading-tight">
              Comforts your guests will love
            </h2>
            <p className="text-xs md:text-sm text-gray-700 font-light leading-relaxed mb-6 md:mb-8">
              Gateway Linen is a trusted leader in textile manufacturing and
              supply in Canada. We specialize in terry, linens, and premium
              amenities for hospitality, healthcare, and beyond. With decades of
              experience, we have dedicated ourselves to providing exceptional
              quality, comfort, and service.
            </p>
            <button
              onClick={() => navigate("/about")}
              className="inline-block px-8 py-3 border border-[#031D44] text-[#031D44] hover:bg-[#031D44] hover:text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-colors cursor-pointer shadow-sm"
            >
              About Us
            </button>
          </div>
        </div>
      </div>
    </section>
  );
};

// --- Shop Products by Industry ---
const ShopByIndustry = () => {
  const navigate = useNavigate();

  const industries = [
    {
      name: "Salons & Spas",
      image:
        "https://images.unsplash.com/photo-1544161515-4ab6ce6db874?q=80&w=800&auto=format&fit=crop",
      link: "/products",
    },
    {
      name: "Food Service",
      image:
        "https://images.unsplash.com/photo-1414235077428-33898dd1c739?q=80&w=800&auto=format&fit=crop",
      link: "/products",
    },
    {
      name: "Healthcare",
      image:
        "https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?q=80&w=800&auto=format&fit=crop",
      link: "/products",
    },
    {
      name: "Hospitality",
      image:
        "https://images.unsplash.com/photo-1566665797739-1674de7a421a?q=80&w=800&auto=format&fit=crop",
      link: "/products",
    },
  ];

  const scrollLeft = () => {
    document
      .getElementById("industry-slider")
      .scrollBy({ left: -350, behavior: "smooth" });
  };

  const scrollRight = () => {
    document
      .getElementById("industry-slider")
      .scrollBy({ left: 350, behavior: "smooth" });
  };

  return (
    <section className="py-12 md:py-24 px-4 md:px-10 bg-white font-sans border-t border-gray-100 overflow-hidden">
      <div className="max-w-[1300px] mx-auto relative">
        <div className="mb-6 md:mb-12">
          <span className="text-xs md:text-sm text-gray-500 font-light mb-1.5 block">
            Explore Our Offerings
          </span>
          <h2 className="text-2xl md:text-[40px] font-bold text-[#4A5568] tracking-tight">
            Shop Products by Industry
          </h2>
        </div>

        <button
          onClick={scrollLeft}
          className="absolute left-0 top-[60%] -translate-y-1/2 -ml-4 z-10 bg-white shadow-lg p-3 rounded-full border border-gray-200 text-gray-600 hover:bg-[#031D44] hover:text-white transition-colors cursor-pointer hidden md:block"
        >
          <FiChevronLeft size={20} />
        </button>
        <button
          onClick={scrollRight}
          className="absolute right-0 top-[60%] -translate-y-1/2 -mr-4 z-10 bg-white shadow-lg p-3 rounded-full border border-gray-200 text-gray-600 hover:bg-[#031D44] hover:text-white transition-colors cursor-pointer hidden md:block"
        >
          <FiChevronRight size={20} />
        </button>

        <div
          id="industry-slider"
          className="flex gap-5 md:gap-8 overflow-x-auto scrollbar-hide pb-4 snap-x snap-mandatory"
          style={{ scrollbarWidth: "none", msOverflowStyle: "none" }}
        >
          {industries.map((ind, i) => (
            <div
              key={i}
              onClick={() => navigate(ind.link)}
              className="min-w-[260px] sm:min-w-[320px] md:min-w-[380px] snap-start group cursor-pointer flex flex-col"
            >
              <div className="w-full aspect-[4/3] mb-4 overflow-hidden rounded-xl bg-gray-100 shadow-sm border border-gray-100">
                <img
                  src={ind.image}
                  alt={ind.name}
                  className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                />
              </div>
              <div className="flex justify-between items-center text-[#4A5568] transition-colors pb-1">
                <h3 className="text-sm md:text-lg font-bold">{ind.name}</h3>
                <FiArrowRight
                  size={18}
                  className="text-gray-400 group-hover:text-[#031D44] transition-colors"
                />
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

// --- The Gateway Difference ---
const TheDifferenceSection = () => {
  const navigate = useNavigate();

  const diffs = [
    {
      title: "Designed For Lasting Impressions",
      desc: "From luxurious linens to premium amenities, we blend comfort and durability, upholding the highest standards in every aspect.",
      image:
        "https://images.unsplash.com/photo-1544161515-4ab6ce6db874?q=80&w=800&auto=format&fit=crop",
    },
    {
      title: "Innovation in Every Thread",
      desc: "We carefully curate, evaluate, and assess comforts to balance elegance and reliability, making a difference where it matters most.",
      image:
        "https://images.unsplash.com/photo-1612423284934-2850a4ea6b0f?q=80&w=800&auto=format&fit=crop",
    },
    {
      title: "Style with Substance",
      desc: "With decades of experience, we have dedicated ourselves to providing exceptional quality, comfort, and outstanding service.",
      image:
        "https://images.unsplash.com/photo-1618221118493-9cfa1a1c00da?q=80&w=800&auto=format&fit=crop",
    },
  ];

  return (
    <section className="py-12 md:py-24 px-4 md:px-10 bg-white font-sans border-t border-gray-100">
      <div className="max-w-[1300px] mx-auto">
        <div className="mb-8 md:mb-14">
          <span className="text-xs md:text-sm text-gray-500 font-light mb-1.5 block">
            Designed to Deliver Excellence
          </span>
          <h2 className="text-2xl md:text-[40px] font-bold text-[#4A5568] tracking-tight">
            The Gateway Difference
          </h2>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-10">
          {diffs.map((item, idx) => (
            <div
              key={idx}
              className="flex flex-col group bg-[#FAF7F2] p-5 rounded-2xl border border-[#E5DCD0] shadow-sm"
            >
              <div className="w-full aspect-[4/3] rounded-xl overflow-hidden mb-4 shadow-xs">
                <img
                  src={item.image}
                  alt={item.title}
                  className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                />
              </div>
              <h3 className="text-base md:text-xl font-serif font-bold text-[#031D44] mb-2">
                {item.title}
              </h3>
              <p className="text-xs md:text-sm text-gray-700 font-light leading-relaxed mb-6 flex-grow">
                {item.desc}
              </p>
              <div>
                <button
                  onClick={() => navigate("/about")}
                  className="inline-block px-6 py-2.5 bg-white border border-[#031D44] text-[#031D44] hover:bg-[#031D44] hover:text-white text-xs font-bold rounded-xl transition-colors cursor-pointer shadow-xs uppercase tracking-wider"
                >
                  Read more
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

// --- Eden Style Testimonials Slider ---
const TestimonialsSlider = () => {
  const testimonials = [
    {
      author: "Social Services Provider in Vancouver",
      company: "Social Services Provider in Vancouver NA",
      text: '"We received our order, and were very pleased with the quality of the items. We also appreciated your customer service. Thank you for informing us about the back ordered towels, and the samples being shipped separately."',
      initials: "",
    },
    {
      author: "Byron Bradley",
      company:
        "The Mustard Seed Senior Director Development and Government Relations",
      text: '"Gateway Linen was an exceptional partner, and we highly recommend their high-quality products and organization to anyone looking for textiles. Thank you for your generosity and for helping make 24 families feel comfortable and cozy in their new homes."',
      logo: true,
    },
    {
      author: "Marcus Reynolds",
      company: "Operations Director, Grand Plaza Hotel",
      text: '"Gateway Linen has been our primary supplier for over 2 years. Their pool towels and bed sheets withstand heavy commercial washing without losing softness. Exceptional wholesale partner."',
      initials: "MR",
    },
  ];

  const scrollLeft = () => {
    document
      .getElementById("testimonial-slider")
      .scrollBy({ left: -350, behavior: "smooth" });
  };

  const scrollRight = () => {
    document
      .getElementById("testimonial-slider")
      .scrollBy({ left: 350, behavior: "smooth" });
  };

  return (
    <section className="py-12 md:py-24 px-4 md:px-10 bg-[#EBF0EC] font-sans border-t border-gray-100 relative overflow-hidden">
      <div className="max-w-[1400px] mx-auto relative">
        <div className="text-center mb-8 md:mb-12">
          <span className="text-xs md:text-sm text-gray-600 font-medium block mb-1.5">
            Trusted by Our Valued Customers
          </span>
          <h2 className="text-2xl md:text-[44px] font-bold text-[#4A5568] tracking-tight">
            Testimonials
          </h2>
        </div>

        <button
          onClick={scrollLeft}
          className="absolute left-0 top-[55%] -translate-y-1/2 -ml-2 md:-ml-4 z-10 bg-white shadow-lg p-3 rounded-full border border-gray-200 text-gray-600 hover:text-[#4A5568] transition-colors cursor-pointer hidden md:flex items-center justify-center"
        >
          <FiChevronLeft size={20} />
        </button>
        <button
          onClick={scrollRight}
          className="absolute right-0 top-[55%] -translate-y-1/2 -mr-2 md:-mr-4 z-10 bg-white shadow-lg p-3 rounded-full border border-gray-200 text-gray-600 hover:text-[#4A5568] transition-colors cursor-pointer hidden md:flex items-center justify-center"
        >
          <FiChevronRight size={20} />
        </button>

        <div
          id="testimonial-slider"
          className="flex gap-5 overflow-x-auto scrollbar-hide pb-4 snap-x snap-mandatory px-2 md:px-8"
          style={{ scrollbarWidth: "none", msOverflowStyle: "none" }}
        >
          {testimonials.map((test, index) => (
            <div
              key={index}
              className="min-w-[280px] sm:min-w-[360px] md:min-w-[450px] lg:min-w-[550px] bg-white rounded-2xl p-6 md:p-10 shadow-sm snap-center flex flex-col justify-between border border-[#DCE4DE]"
            >
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                <div className="flex items-center gap-3.5">
                  {test.logo ? (
                    <div className="w-12 h-12 flex items-center justify-center text-[#B58E58] font-bold text-[9px] leading-tight text-center bg-[#FAF7F2] rounded-xl border border-[#E5DCD0]">
                      MUSTARD
                    </div>
                  ) : test.initials ? (
                    <div className="w-12 h-12 bg-[#031D44] text-[#B58E58] rounded-xl flex items-center justify-center font-bold text-sm shadow-inner">
                      {test.initials}
                    </div>
                  ) : (
                    <div className="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center text-gray-600 font-bold">
                      SV
                    </div>
                  )}

                  <div>
                    <h4 className="text-xs md:text-sm font-bold text-[#031D44]">
                      {test.author}
                    </h4>
                    <p className="text-[10px] md:text-[11px] text-gray-600 font-light mt-0.5 max-w-[200px] sm:max-w-[250px]">
                      {test.company}
                    </p>
                  </div>
                </div>

                <div className="flex text-amber-400 gap-0.5">
                  {[...Array(5)].map((_, i) => (
                    <FiStar key={i} size={14} fill="currentColor" />
                  ))}
                </div>
              </div>

              <p className="text-xs md:text-sm text-gray-700 font-light leading-relaxed flex-grow">
                {test.text}
              </p>
            </div>
          ))}
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
      <BlogSection />
      <AccountSection />
      <FeaturedProducts />
      <AboutUsSection />
      <ShopByIndustry />
      <TheDifferenceSection />
      <TestimonialsSlider />
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

              <Route path="/categories" element={<CategoryGrid />} />
              <Route
                path="/category/:categoryName"
                element={<CategoryPage />}
              />
              <Route path="/bulk-order" element={<BulkOrder />} />
              <Route path="/order-history" element={<OrderHistory />} />

              <Route path="/contact" element={<ContactPage />} />
              <Route path="/dashboard" element={<Dashboard />} />
              <Route path="/product/:id" element={<ProductDetail />} />
              <Route path="/checkout" element={<Checkout />} />
              <Route path="/products" element={<ProductsPage />} />
              <Route path="/quote-builder" element={<QuoteBuilder />} />
            </Routes>
          </div>
          {!isAuthPage && <Footer />}
          {/* Floating WhatsApp and AI Chatbot Widget */}
          <FloatingWidget />
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
