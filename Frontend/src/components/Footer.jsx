import { Link } from "react-router-dom";
import { FiPhone, FiMail, FiMapPin } from "react-icons/fi";
import logo from "../assets/GatewayLinen-logo.png";

const Footer = () => {
  return (
    <footer className="w-full bg-[#031D44] text-white pt-16 pb-8 border-t-[4px] border-[#B58E58] font-sans relative overflow-hidden">
      {/* Background Decorative Gold Glow */}
      <div className="absolute right-0 bottom-0 w-96 h-96 bg-[#B58E58]/10 rounded-full blur-3xl pointer-events-none"></div>

      <div className="max-w-[1536px] mx-auto px-4 md:px-10 relative z-10">
        {/* Main Footer Grid - Clean & Balanced 4 Columns */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 md:gap-12 mb-16">
          {/* Col 1: Brand Info */}
          <div className="flex flex-col items-start">
            <div className="bg-white px-5 py-3 rounded-xl inline-flex items-center justify-center mb-6 shadow-md border border-[#B58E58]/30">
              <img
                src={logo}
                alt="Gateway Linen"
                className="h-9 sm:h-10 w-auto object-contain"
              />
            </div>
            <p className="text-gray-300 text-[13px] leading-relaxed font-light mb-6">
              Premium linen solutions for hotels, resorts, healthcare, and
              hospitality facilities across North America. Quality you can
              trust.
            </p>
            <div className="inline-flex items-center gap-2 bg-[#B58E58]/20 px-3.5 py-1.5 rounded-full border border-[#B58E58]/40">
              <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
              <span className="text-[10px] font-bold text-[#B58E58] tracking-widest uppercase">
                B2B Commercial Partner
              </span>
            </div>
          </div>

          {/* Col 2: Quick Links */}
          <div>
            <h4 className="text-white text-[15px] font-serif font-bold mb-6 tracking-wide border-l-2 border-[#B58E58] pl-3">
              Quick Links
            </h4>
            <ul className="flex flex-col space-y-3 text-[13px] text-gray-300 font-light">
              <li>
                <Link
                  to="/"
                  onClick={() =>
                    window.scrollTo({ top: 0, behavior: "smooth" })
                  }
                  className="hover:text-[#B58E58] transition-colors block cursor-pointer"
                >
                  Home
                </Link>
              </li>
              <li>
                <Link
                  to="/products"
                  onClick={() =>
                    window.scrollTo({ top: 0, behavior: "smooth" })
                  }
                  className="hover:text-[#B58E58] transition-colors block cursor-pointer"
                >
                  All Products
                </Link>
              </li>
              <li>
                <Link
                  to="/category/towels"
                  onClick={() =>
                    window.scrollTo({ top: 0, behavior: "smooth" })
                  }
                  className="hover:text-[#B58E58] transition-colors block cursor-pointer"
                >
                  Towels
                </Link>
              </li>
              <li>
                <Link
                  to="/category/bed-sheets"
                  onClick={() =>
                    window.scrollTo({ top: 0, behavior: "smooth" })
                  }
                  className="hover:text-[#B58E58] transition-colors block cursor-pointer"
                >
                  Bed Sheets
                </Link>
              </li>
              <li>
                <Link
                  to="/category/mattress-pads"
                  onClick={() =>
                    window.scrollTo({ top: 0, behavior: "smooth" })
                  }
                  className="hover:text-[#B58E58] transition-colors block cursor-pointer"
                >
                  Mattress Pads
                </Link>
              </li>
            </ul>
          </div>

          {/* Col 3: Company (Fully Functional Links) */}
          <div>
            <h4 className="text-white text-[15px] font-serif font-bold mb-6 tracking-wide border-l-2 border-[#B58E58] pl-3">
              Company
            </h4>
            <ul className="flex flex-col space-y-3 text-[13px] text-gray-300 font-light">
              <li>
                <Link
                  to="/contact"
                  onClick={() =>
                    window.scrollTo({ top: 0, behavior: "smooth" })
                  }
                  className="hover:text-[#B58E58] transition-colors block cursor-pointer"
                >
                  About Us
                </Link>
              </li>
              <li>
                <Link
                  to="/contact"
                  onClick={() =>
                    window.scrollTo({ top: 0, behavior: "smooth" })
                  }
                  className="hover:text-[#B58E58] transition-colors block cursor-pointer"
                >
                  Contact Us
                </Link>
              </li>
              <li>
                <Link
                  to="/contact"
                  onClick={() =>
                    window.scrollTo({ top: 0, behavior: "smooth" })
                  }
                  className="hover:text-[#B58E58] transition-colors block cursor-pointer"
                >
                  FAQ / Support
                </Link>
              </li>
              <li>
                <Link
                  to="/contact"
                  onClick={() =>
                    window.scrollTo({ top: 0, behavior: "smooth" })
                  }
                  className="hover:text-[#B58E58] transition-colors block cursor-pointer"
                >
                  Request a Quote
                </Link>
              </li>
            </ul>
          </div>

          {/* Col 4: Contact Info */}
          <div>
            <h4 className="text-white text-[15px] font-serif font-bold mb-6 tracking-wide border-l-2 border-[#B58E58] pl-3">
              Contact Us
            </h4>
            <ul className="flex flex-col space-y-4 text-[13px] text-gray-300 font-light">
              <li className="flex items-start gap-3">
                <FiMapPin size={18} className="text-[#B58E58] mt-1 shrink-0" />
                <span className="leading-relaxed">
                  9 Mapleridge crescent,
                  <br />
                  Brandon R7A6P8,
                  <br />
                  Manitoba, Canada
                </span>
              </li>

              <li className="flex items-center gap-3">
                <FiPhone size={18} className="text-[#B58E58] shrink-0" />
                <a
                  href="tel:+12049794044"
                  className="hover:text-white transition-colors"
                >
                  +1 (204) 979-4044
                </a>
              </li>

              <li className="flex items-start gap-3">
                <FiMail size={18} className="text-[#B58E58] mt-1 shrink-0" />
                <div className="flex flex-col space-y-1.5">
                  <a
                    href="mailto:tapu_parikh@yahoo.com"
                    className="hover:text-white transition-colors break-all"
                  >
                    tapu_parikh@yahoo.com
                  </a>
                  <a
                    href="mailto:gatewaylinen@gmail.com"
                    className="hover:text-white transition-colors break-all"
                  >
                    gatewaylinen@gmail.com
                  </a>
                </div>
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom Section - Copyright & Legal */}
        <div className="border-t border-white/10 pt-6 flex flex-col md:flex-row justify-between items-center gap-4 text-[12px] text-gray-400 font-light">
          <p>
            © {new Date().getFullYear()} Gateway Linen. All rights reserved.
          </p>
          <div className="flex gap-4 items-center">
            <Link
              to="/contact"
              className="hover:text-[#B58E58] transition-colors"
            >
              Privacy Policy
            </Link>
            <span>•</span>
            <Link
              to="/contact"
              className="hover:text-[#B58E58] transition-colors"
            >
              Terms & Conditions
            </Link>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
