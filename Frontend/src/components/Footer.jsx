import { Link } from "react-router-dom";
import {
  FiMail,
  FiPhone,
  FiMapPin,
  FiFacebook,
  FiInstagram,
  FiLinkedin,
  FiArrowUp,
} from "react-icons/fi";

const Footer = () => {
  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  return (
    <footer className="font-sans border-t border-gray-100">
      {/* Top Section - Need Help? */}
      <div className="bg-[#FAF7F2] py-12 md:py-16 text-center border-b border-gray-200 px-4">
        <h2 className="text-2xl md:text-4xl font-serif font-bold text-[#031D44] mb-3">
          Need Help?
        </h2>
        <p className="text-[13px] md:text-sm text-gray-700 font-medium mb-8">
          Find quick answers to common questions in our FAQ.
        </p>
        <Link
          to="/contact"
          className="inline-block px-8 py-3.5 bg-[#4A5D4E] hover:bg-[#031D44] text-white text-[13px] font-bold rounded-full transition-colors cursor-pointer shadow-md tracking-wider uppercase"
        >
          Visit FAQs
        </Link>
      </div>

      {/* Main Footer Content */}
      <div className="bg-white py-12 md:py-20 px-6 md:px-12 max-w-[1400px] mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-y-10 gap-x-6 lg:gap-6">
        {/* Column 1: Let's Keep In Touch */}
        <div className="lg:col-span-2">
          <h4 className="text-[#031D44] font-bold text-[15px] mb-6 uppercase tracking-wider">
            Let's Keep In Touch
          </h4>

          <div className="space-y-4 text-[13px] md:text-sm text-gray-700 font-medium mb-8">
            <a
              href="mailto:gatewaylinen@gmail.com"
              className="flex items-center gap-3 hover:text-[#B58E58] transition-colors"
            >
              <FiMail size={18} className="text-[#031D44] shrink-0" />{" "}
              tapu_parikh@yahoo.com / gatewaylinen@gmail.com
            </a>
            <a
              href="tel:+12049794044"
              className="flex items-center gap-3 hover:text-[#B58E58] transition-colors"
            >
              <FiPhone size={18} className="text-[#031D44] shrink-0" /> +1 (204)
              979-4044
            </a>
            <div className="flex items-start gap-3">
              <FiMapPin size={18} className="text-[#031D44] mt-0.5 shrink-0" />
              <p className="leading-relaxed">
                9 Mapleridge crescent,
                <br />
                Brandon R7A6P8, Manitoba, Canada
              </p>
            </div>
          </div>

          {/* Social Icons */}
          <div className="flex items-center gap-3">
            <a
              href="#"
              className="w-10 h-10 rounded-full bg-gray-100 text-gray-700 border border-gray-200 flex items-center justify-center hover:bg-[#031D44] hover:text-white transition-all shadow-sm"
            >
              <FiFacebook size={18} />
            </a>
            <a
              href="#"
              className="w-10 h-10 rounded-full bg-gray-100 text-gray-700 border border-gray-200 flex items-center justify-center hover:bg-[#031D44] hover:text-white transition-all shadow-sm"
            >
              <FiInstagram size={18} />
            </a>
            <a
              href="#"
              className="w-10 h-10 rounded-full bg-gray-100 text-gray-700 border border-gray-200 flex items-center justify-center hover:bg-[#031D44] hover:text-white transition-all shadow-sm"
            >
              <FiLinkedin size={18} />
            </a>
          </div>
        </div>

        {/* Column 2: General */}
        <div>
          <h4 className="text-[#031D44] font-bold text-[15px] mb-6 uppercase tracking-wider">
            General
          </h4>
          <ul className="space-y-3.5 text-[13px] md:text-sm text-gray-600 font-medium">
            <li>
              <Link
                to="/about"
                className="hover:text-[#B58E58] hover:translate-x-1 transition-all inline-block"
              >
                About Us
              </Link>
            </li>
            <li>
              <Link
                to="/contact"
                className="hover:text-[#B58E58] hover:translate-x-1 transition-all inline-block"
              >
                Contact Us
              </Link>
            </li>
            <li>
              <Link
                to="/login"
                className="hover:text-[#B58E58] hover:translate-x-1 transition-all inline-block"
              >
                My Account
              </Link>
            </li>
            <li>
              <Link
                to="/blog"
                className="hover:text-[#B58E58] hover:translate-x-1 transition-all inline-block"
              >
                Blog & News
              </Link>
            </li>
            <li>
              <Link
                to="/sustainability"
                className="hover:text-[#B58E58] hover:translate-x-1 transition-all inline-block"
              >
                Sustainability
              </Link>
            </li>
            <li>
              <Link
                to="/products"
                className="hover:text-[#B58E58] hover:translate-x-1 transition-all inline-block text-[#4A5D4E] font-bold"
              >
                Shop All Products
              </Link>
            </li>
          </ul>
        </div>

        {/* Column 3: Support */}
        <div>
          <h4 className="text-[#031D44] font-bold text-[15px] mb-6 uppercase tracking-wider">
            Support
          </h4>
          <ul className="space-y-3.5 text-[13px] md:text-sm text-gray-600 font-medium">
            <li>
              <Link
                to="/faq"
                className="hover:text-[#B58E58] hover:translate-x-1 transition-all inline-block"
              >
                FAQ's
              </Link>
            </li>
            <li>
              <Link
                to="/care"
                className="hover:text-[#B58E58] hover:translate-x-1 transition-all inline-block"
              >
                Product Care Instructions
              </Link>
            </li>
            <li>
              <Link
                to="/returns"
                className="hover:text-[#B58E58] hover:translate-x-1 transition-all inline-block"
              >
                Request a Return
              </Link>
            </li>
            <li>
              <Link
                to="/privacy"
                className="hover:text-[#B58E58] hover:translate-x-1 transition-all inline-block"
              >
                Privacy Policy
              </Link>
            </li>
            <li>
              <Link
                to="/code-of-conduct"
                className="hover:text-[#B58E58] hover:translate-x-1 transition-all inline-block"
              >
                Supplier Code of Conduct
              </Link>
            </li>
            <li>
              <Link
                to="/terms"
                className="hover:text-[#B58E58] hover:translate-x-1 transition-all inline-block"
              >
                Terms of Use
              </Link>
            </li>
          </ul>
        </div>

        {/* Column 4: Newsletter & Shop For Home */}
        <div className="flex flex-col justify-between">
          <div className="mb-8 md:mb-0">
            <h4 className="text-[#031D44] font-bold text-[14px] mb-3 uppercase tracking-wider">
              Shop For Your Home at
            </h4>
            <h3 className="text-3xl font-serif text-gray-500 tracking-wider">
              Gateway<span className="text-[#B58E58] font-sans">+</span>Home{" "}
              <span className="text-[10px] uppercase align-top font-sans text-gray-400">
                Linen Co.
              </span>
            </h3>
          </div>

          <div>
            <h4 className="text-[#031D44] font-bold text-[14px] mb-4 uppercase tracking-wider">
              Subscribe to Our Newsletter
            </h4>
            <form
              className="flex w-full shadow-sm"
              onSubmit={(e) => e.preventDefault()}
            >
              <input
                type="email"
                placeholder="Email address"
                className="flex-grow bg-white border border-gray-300 rounded-l-full px-4 py-3 text-[13px] text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#4A5D4E] transition-colors"
                required
              />
              <button
                type="submit"
                className="bg-[#4A5D4E] hover:bg-[#031D44] text-white px-6 py-3 rounded-r-full text-[13px] font-bold transition-colors cursor-pointer uppercase tracking-wider"
              >
                Submit
              </button>
            </form>
          </div>
        </div>
      </div>

      {/* Bottom Bar */}
      <div className="bg-[#4A5568] text-gray-200 py-5 px-6 md:px-12 flex flex-col md:flex-row justify-between items-center text-xs font-medium relative">
        <p className="text-center md:text-left mb-4 md:mb-0">
          Copyright © 2026 Gateway Linen. All rights reserved.
        </p>

        <div className="flex items-center gap-3">
          <div className="bg-[#031D44] text-white px-2.5 py-1 text-[10px] font-bold rounded shadow-sm border border-[#031D44]">
            VISA
          </div>
          <div className="bg-[#0070BA] text-white px-2.5 py-1 text-[10px] font-bold rounded flex items-center gap-1 shadow-sm border border-[#0070BA]">
            <i>PayPal</i>
          </div>
          <div className="bg-white px-2.5 py-1 text-[10px] font-bold rounded flex items-center gap-1 shadow-sm border border-gray-200">
            <div className="w-2.5 h-2.5 bg-red-600 rounded-full"></div>
            <div className="w-2.5 h-2.5 bg-yellow-400 rounded-full -ml-1.5 opacity-90"></div>
          </div>
        </div>

        <button
          onClick={scrollToTop}
          className="absolute right-6 -top-16 w-12 h-12 md:w-14 md:h-14 bg-white text-[#4A5D4E] rounded-full flex flex-col items-center justify-center shadow-lg hover:bg-[#4A5D4E] hover:text-white transition-all cursor-pointer border border-[#E5DCD0]"
        >
          <FiArrowUp size={16} className="mb-0.5" />
          <span className="text-[9px] font-bold uppercase tracking-widest">
            Top
          </span>
        </button>
      </div>
    </footer>
  );
};

export default Footer;
