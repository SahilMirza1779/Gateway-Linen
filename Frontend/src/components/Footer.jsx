import { Link } from "react-router-dom";
import {
  FiMail,
  FiPhone,
  FiMapPin,
  FiFacebook,
  FiInstagram,
  FiLinkedin,
  FiTwitter,
  FiYoutube,
  FiArrowUp,
  FiShield,
  FiTruck,
  FiRefreshCw,
  FiHeadphones,
  FiCreditCard,
  FiSend,
} from "react-icons/fi";
import { FaCcVisa, FaCcMastercard, FaCcPaypal, FaCcAmex } from "react-icons/fa";

const Footer = () => {
  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const currentYear = new Date().getFullYear();

  return (
    <footer className="font-sans">
      {/* ============ TRUST BADGES STRIP (Above Footer) ============ */}
      <div className="bg-white border-t border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-6 md:py-8">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
            {[
              {
                icon: FiShield,
                title: "100% Secure Payments",
                subtitle: "SSL Encrypted Checkout",
                color: "#2874F0",
                bg: "#EAF2FF",
              },
              {
                icon: FiTruck,
                title: "Fast Free Shipping",
                subtitle: "On orders above $350",
                color: "#FB641B",
                bg: "#FFF1E8",
              },
              {
                icon: FiRefreshCw,
                title: "Easy Returns",
                subtitle: "Within 30 days",
                color: "#10B981",
                bg: "#ECFDF5",
              },
              {
                icon: FiHeadphones,
                title: "24×7 Customer Support",
                subtitle: "Dedicated assistance",
                color: "#FF9F00",
                bg: "#FFF8E6",
              },
            ].map((item, i) => (
              <div
                key={i}
                className="flex items-center gap-3 group cursor-pointer"
              >
                <div
                  className="w-11 h-11 md:w-12 md:h-12 rounded-full flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                  style={{ backgroundColor: item.bg }}
                >
                  <item.icon size={20} style={{ color: item.color }} />
                </div>
                <div className="min-w-0">
                  <p className="text-[11px] md:text-[12px] font-bold text-gray-800 leading-tight">
                    {item.title}
                  </p>
                  <p className="text-[10px] md:text-[11px] text-gray-500 mt-0.5">
                    {item.subtitle}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* ============ MAIN FOOTER (Flipkart Dark Navy) ============ */}
      <div className="bg-[#172337] text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-10 md:py-14">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-6">
            {/* ===== Column 1: Company Info ===== */}
            <div>
              <div className="flex items-center gap-2.5 mb-4">
                <div className="w-10 h-10 bg-white rounded-lg flex items-center justify-center p-1.5 shadow-md">
                  <img
                    src="/src/assets/GatewayLinen-logo.png"
                    alt="Gateway Linen"
                    className="w-full h-full object-contain"
                  />
                </div>
                <div className="leading-none">
                  <p className="text-[15px] font-bold text-white italic">
                    Gateway<span className="text-[#FFE500]">Linen</span>
                  </p>
                  <p className="text-[9px] uppercase tracking-[0.15em] text-white/70 italic mt-0.5">
                    Explore <span className="text-[#FFE500]">Plus</span> ✦
                  </p>
                </div>
              </div>

              <p className="text-[12px] text-white/70 leading-relaxed mb-5">
                Premium hospitality linen supplier for hotels, restaurants &
                homes across North America since 2005.
              </p>

              {/* Contact Info */}
              <div className="space-y-2.5 text-[12px]">
                <a
                  href="mailto:gatewaylinen@gmail.com"
                  className="flex items-start gap-2.5 text-white/80 hover:text-[#FFE500] transition-colors"
                >
                  <FiMail
                    size={14}
                    className="text-[#FFE500] mt-0.5 shrink-0"
                  />
                  <span>gatewaylinen@gmail.com</span>
                </a>
                <a
                  href="tel:+12049794044"
                  className="flex items-start gap-2.5 text-white/80 hover:text-[#FFE500] transition-colors"
                >
                  <FiPhone
                    size={14}
                    className="text-[#FFE500] mt-0.5 shrink-0"
                  />
                  <span>+1 (204) 979-4044</span>
                </a>
                <div className="flex items-start gap-2.5 text-white/80">
                  <FiMapPin
                    size={14}
                    className="text-[#FFE500] mt-0.5 shrink-0"
                  />
                  <span className="leading-relaxed">
                    9 Mapleridge Crescent,
                    <br />
                    Brandon R7A 6P8, Manitoba, Canada
                  </span>
                </div>
              </div>

              {/* Social Icons */}
              <div className="flex items-center gap-2 mt-5">
                {[
                  {
                    icon: FiFacebook,
                    href: "#",
                    hover: "hover:bg-[#1877F2]",
                    label: "Facebook",
                  },
                  {
                    icon: FiInstagram,
                    href: "#",
                    hover: "hover:bg-[#E1306C]",
                    label: "Instagram",
                  },
                  {
                    icon: FiLinkedin,
                    href: "#",
                    hover: "hover:bg-[#0A66C2]",
                    label: "LinkedIn",
                  },
                  {
                    icon: FiTwitter,
                    href: "#",
                    hover: "hover:bg-[#1DA1F2]",
                    label: "Twitter",
                  },
                  {
                    icon: FiYoutube,
                    href: "#",
                    hover: "hover:bg-[#FF0000]",
                    label: "YouTube",
                  },
                ].map((social, i) => (
                  <a
                    key={i}
                    href={social.href}
                    aria-label={social.label}
                    className={`w-9 h-9 rounded-full bg-white/10 border border-white/15 flex items-center justify-center text-white/80 transition-all ${social.hover} hover:text-white hover:border-transparent hover:scale-110`}
                  >
                    <social.icon size={14} />
                  </a>
                ))}
              </div>
            </div>

            {/* ===== Column 2: Shop / General ===== */}
            <div>
              <h4 className="text-[11px] font-bold text-[#FFE500] uppercase tracking-[0.15em] mb-4">
                Shop
              </h4>
              <ul className="space-y-2.5 text-[12px]">
                {[
                  { to: "/products", label: "All Products" },
                  { to: "/category/bed-sheets", label: "Bed Sheets" },
                  { to: "/category/towels", label: "Bath Towels" },
                  { to: "/category/bath-mats", label: "Bath Mats" },
                  { to: "/category/blankets", label: "Blankets" },
                  { to: "/category/pillows", label: "Pillows & Covers" },
                  { to: "/category/bathroom-accessories", label: "Bathroom Accessories" },
                  { to: "/products", label: "New Arrivals" },
                ].map((item, i) => (
                  <li key={i}>
                    <Link
                      to={item.to}
                      className="text-white/75 hover:text-[#FFE500] transition-all inline-flex items-center gap-1 group"
                    >
                      <span className="w-0 group-hover:w-3 h-px bg-[#FFE500] transition-all duration-300"></span>
                      {item.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>

            {/* ===== Column 3: Support ===== */}
            <div>
              <h4 className="text-[11px] font-bold text-[#FFE500] uppercase tracking-[0.15em] mb-4">
                Customer Care
              </h4>
              <ul className="space-y-2.5 text-[12px]">
                {[
                  { to: "/about", label: "About Us" },
                  { to: "/contact", label: "Contact Us" },
                  { to: "/faq", label: "FAQ's" },
                  { to: "/returns", label: "Request a Return" },
                  { to: "/care", label: "Product Care" },
                  { to: "/sustainability", label: "Sustainability" },
                  { to: "/blog", label: "Blog & News" },
                  { to: "/dashboard", label: "My Account" },
                ].map((item, i) => (
                  <li key={i}>
                    <Link
                      to={item.to}
                      className="text-white/75 hover:text-[#FFE500] transition-all inline-flex items-center gap-1 group"
                    >
                      <span className="w-0 group-hover:w-3 h-px bg-[#FFE500] transition-all duration-300"></span>
                      {item.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>

            {/* ===== Column 4: Newsletter + Policies ===== */}
            <div>
              <h4 className="text-[11px] font-bold text-[#FFE500] uppercase tracking-[0.15em] mb-4">
                Stay Connected
              </h4>
              <p className="text-[12px] text-white/70 leading-relaxed mb-4">
                Subscribe to get exclusive deals, new arrivals & wholesale
                offers directly in your inbox.
              </p>

              <form
                className="flex w-full shadow-sm mb-6"
                onSubmit={(e) => e.preventDefault()}
              >
                <input
                  type="email"
                  placeholder="Enter your email"
                  className="flex-grow bg-white/10 border border-white/20 rounded-l-md px-3 py-2.5 text-[12px] text-white placeholder-white/50 focus:outline-none focus:border-[#FFE500] focus:bg-white/15 transition-colors"
                  required
                />
                <button
                  type="submit"
                  className="bg-[#FB641B] hover:bg-[#e55a15] text-white px-3.5 rounded-r-md text-[11px] font-bold transition-colors cursor-pointer flex items-center justify-center"
                  aria-label="Subscribe"
                >
                  <FiSend size={14} />
                </button>
              </form>

              <h4 className="text-[11px] font-bold text-[#FFE500] uppercase tracking-[0.15em] mb-4">
                Policies
              </h4>
              <ul className="space-y-2.5 text-[12px]">
                {[
                  { to: "/privacy", label: "Privacy Policy" },
                  { to: "/terms", label: "Terms of Use" },
                  { to: "/code-of-conduct", label: "Supplier Code" },
                  { to: "/shipping", label: "Shipping Policy" },
                ].map((item, i) => (
                  <li key={i}>
                    <Link
                      to={item.to}
                      className="text-white/75 hover:text-[#FFE500] transition-all inline-flex items-center gap-1 group"
                    >
                      <span className="w-0 group-hover:w-3 h-px bg-[#FFE500] transition-all duration-300"></span>
                      {item.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>

        {/* ============ BOTTOM BAR ============ */}
        <div className="border-t border-white/10">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 py-5">
            <div className="flex flex-col md:flex-row items-center justify-between gap-4">
              {/* Copyright */}
              <div className="flex items-center gap-3 order-3 md:order-1">
                <div className="w-7 h-7 bg-white/10 rounded-full flex items-center justify-center border border-white/20">
                  <FiShield size={12} className="text-[#FFE500]" />
                </div>
                <p className="text-[11px] text-white/60 text-center md:text-left">
                  © {currentYear}{" "}
                  <span className="text-white/90 font-semibold">
                    Gateway Linen
                  </span>
                  . All rights reserved.
                </p>
              </div>

              {/* Payment Methods */}
              <div className="flex items-center gap-3 order-2">
                <span className="text-[10px] uppercase tracking-wider text-white/50 hidden sm:inline">
                  We Accept
                </span>
                <div className="flex items-center gap-2">
                  <div
                    className="w-9 h-6 bg-white rounded flex items-center justify-center shadow-sm hover:scale-110 transition-transform cursor-pointer"
                    title="Visa"
                  >
                    <FaCcVisa size={22} className="text-[#1A1F71]" />
                  </div>
                  <div
                    className="w-9 h-6 bg-white rounded flex items-center justify-center shadow-sm hover:scale-110 transition-transform cursor-pointer"
                    title="Mastercard"
                  >
                    <FaCcMastercard size={22} className="text-[#EB001B]" />
                  </div>
                  <div
                    className="w-9 h-6 bg-white rounded flex items-center justify-center shadow-sm hover:scale-110 transition-transform cursor-pointer"
                    title="PayPal"
                  >
                    <FaCcPaypal size={22} className="text-[#003087]" />
                  </div>
                  <div
                    className="w-9 h-6 bg-white rounded flex items-center justify-center shadow-sm hover:scale-110 transition-transform cursor-pointer"
                    title="American Express"
                  >
                    <FaCcAmex size={22} className="text-[#006FCF]" />
                  </div>
                </div>
              </div>

              {/* Quick Links */}
              <div className="flex items-center gap-4 order-1 md:order-3">
                <Link
                  to="/contact"
                  className="text-[11px] text-white/70 hover:text-[#FFE500] transition-colors"
                >
                  Help
                </Link>
                <span className="w-px h-3 bg-white/20"></span>
                <Link
                  to="/faq"
                  className="text-[11px] text-white/70 hover:text-[#FFE500] transition-colors"
                >
                  FAQ
                </Link>
                <span className="w-px h-3 bg-white/20"></span>
                <Link
                  to="/returns"
                  className="text-[11px] text-white/70 hover:text-[#FFE500] transition-colors"
                >
                  Returns
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ============ BACK TO TOP BUTTON ============ */}
      <button
        onClick={scrollToTop}
        className="fixed bottom-6 right-6 z-40 w-11 h-11 md:w-12 md:h-12 bg-[#FB641B] hover:bg-[#e55a15] text-white rounded-full flex items-center justify-center shadow-lg transition-all hover:scale-110 cursor-pointer border-2 border-white/20"
        aria-label="Back to top"
      >
        <FiArrowUp size={18} />
      </button>
    </footer>
  );
};

export default Footer;