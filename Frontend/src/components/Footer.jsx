import logo from "../assets/GatewayLinen-logo.png";
import { FiPhone, FiMail, FiMapPin } from "react-icons/fi";

const Footer = () => {
  return (
    <footer className="w-full bg-[#031D44] text-white pt-16 pb-6 border-t-[4px] border-[#B58E58]">
      <div className="max-w-[1536px] mx-auto px-4 md:px-10">
        {/* Top Section - 4 Columns */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 md:gap-12 mb-16">
          {/* Col 1: Brand Info */}
          <div className="flex flex-col items-start">
            {/* LOGO FIX: Changed from circle to a sleek rounded rectangle for wide logos */}
            <div className="bg-white px-5 py-3 rounded-xl inline-flex items-center justify-center mb-6 shadow-lg">
              <img
                src={logo}
                alt="Gateway Linen"
                className="h-10 sm:h-12 w-auto object-contain"
              />
            </div>
            <p className="text-gray-300 text-[13px] leading-relaxed font-light">
              Premium linen solutions for hotels, resorts, healthcare, and
              hospitality facilities across North America. Quality you can
              trust.
            </p>
          </div>

          {/* Col 2: Quick Links */}
          <div>
            <h4 className="text-white text-[15px] font-bold mb-5 tracking-wide">
              Quick Links
            </h4>
            <ul className="flex flex-col space-y-3 text-[13px] text-gray-400 font-light">
              <li>
                <a href="#" className="hover:text-[#B58E58] transition-colors">
                  Home
                </a>
              </li>
              <li>
                <a href="#" className="hover:text-[#B58E58] transition-colors">
                  All Products
                </a>
              </li>
              <li>
                <a href="#" className="hover:text-[#B58E58] transition-colors">
                  Towels
                </a>
              </li>
              <li>
                <a href="#" className="hover:text-[#B58E58] transition-colors">
                  Bed Sheets
                </a>
              </li>
              <li>
                <a href="#" className="hover:text-[#B58E58] transition-colors">
                  Mattress Pads
                </a>
              </li>
            </ul>
          </div>

          {/* Col 3: Company */}
          <div>
            <h4 className="text-white text-[15px] font-bold mb-5 tracking-wide">
              Company
            </h4>
            <ul className="flex flex-col space-y-3 text-[13px] text-gray-400 font-light">
              <li>
                <a href="#" className="hover:text-[#B58E58] transition-colors">
                  About Us
                </a>
              </li>
              <li>
                <a href="#" className="hover:text-[#B58E58] transition-colors">
                  Contact Us
                </a>
              </li>
              <li>
                <a href="#" className="hover:text-[#B58E58] transition-colors">
                  FAQ / Support
                </a>
              </li>
              <li>
                <a href="#" className="hover:text-[#B58E58] transition-colors">
                  Request a Quote
                </a>
              </li>
            </ul>
          </div>

          {/* Col 4: Contact Info (UPDATED WITH REAL DETAILS) */}
          <div>
            <h4 className="text-white text-[15px] font-bold mb-5 tracking-wide">
              Contact Us
            </h4>
            <ul className="flex flex-col space-y-4 text-[13px] text-gray-300 font-light">
              {/* Address */}
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

              {/* Phone */}
              <li className="flex items-center gap-3">
                <FiPhone size={18} className="text-[#B58E58] shrink-0" />
                <a
                  href="tel:+12049794044"
                  className="hover:text-white transition-colors"
                >
                  +1 (204) 979-4044
                </a>
              </li>

              {/* Emails */}
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

        {/* Bottom Section - Copyright */}
        <div className="border-t border-gray-700/50 pt-6 flex flex-col md:flex-row justify-between items-center gap-4 text-[12px] text-gray-500 font-light">
          <p>
            © {new Date().getFullYear()} Gateway Linen. All rights reserved.
          </p>
          <div className="flex gap-4">
            <a href="#" className="hover:text-white transition-colors">
              Privacy Policy
            </a>
            <span>|</span>
            <a href="#" className="hover:text-white transition-colors">
              Terms & Conditions
            </a>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
