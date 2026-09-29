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
      <div className="bg-[#FAF7F2] py-12 md:py-16 text-center border-b border-gray-200">
        <h2 className="text-2xl md:text-4xl font-serif font-bold text-[#031D44] mb-3">
          Need Help?
        </h2>
        <p className="text-sm text-gray-500 font-light mb-8">
          Find quick answers to common questions in our FAQ.
        </p>
        <Link
          to="/contact"
          className="inline-block px-8 py-3 bg-[#4A5D4E] hover:bg-[#031D44] text-white text-sm font-bold rounded-full transition-colors cursor-pointer"
        >
          Visit FAQs
        </Link>
      </div>

      {/* Main Footer Content */}
      <div className="bg-white py-12 md:py-20 px-6 md:px-12 max-w-[1400px] mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 lg:gap-6">
        {/* Column 1: Let's Keep In Touch (Client Details Updated) */}
        <div className="lg:col-span-2">
          <h4 className="text-[#031D44] font-bold text-base mb-6">
            Let's Keep In Touch
          </h4>

          <div className="space-y-4 text-sm text-gray-500 font-light mb-8">
            <a
              href="mailto:gatewaylinen@gmail.com"
              className="flex items-center gap-3 hover:text-[#B58E58] transition-colors"
            >
              <FiMail size={16} className="text-gray-400" />{" "}
              tapu_parikh@yahoo.com / gatewaylinen@gmail.com
            </a>
            <a
              href="tel:+12049794044"
              className="flex items-center gap-3 hover:text-[#B58E58] transition-colors"
            >
              <FiPhone size={16} className="text-gray-400" /> +1 (204) 979-4044
            </a>
            <div className="flex items-start gap-3">
              <FiMapPin size={16} className="text-gray-400 mt-1 shrink-0" />
              <p>
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
              className="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center hover:bg-[#031D44] hover:text-white transition-colors"
            >
              <FiFacebook size={18} />
            </a>
            <a
              href="#"
              className="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center hover:bg-[#031D44] hover:text-white transition-colors"
            >
              <FiInstagram size={18} />
            </a>
            <a
              href="#"
              className="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center hover:bg-[#031D44] hover:text-white transition-colors"
            >
              <FiLinkedin size={18} />
            </a>
          </div>
        </div>

        {/* Column 2: General */}
        <div>
          <h4 className="text-[#031D44] font-bold text-base mb-6">General</h4>
          <ul className="space-y-3 text-sm text-gray-500 font-light">
            <li>
              <Link
                to="/about"
                className="hover:text-[#B58E58] transition-colors"
              >
                About Us
              </Link>
            </li>
            <li>
              <Link
                to="/contact"
                className="hover:text-[#B58E58] transition-colors"
              >
                Contact Us
              </Link>
            </li>
            <li>
              <Link
                to="/login"
                className="hover:text-[#B58E58] transition-colors"
              >
                My Account
              </Link>
            </li>
            <li>
              <Link
                to="/blog"
                className="hover:text-[#B58E58] transition-colors"
              >
                Blog & News
              </Link>
            </li>
            <li>
              <Link
                to="/sustainability"
                className="hover:text-[#B58E58] transition-colors"
              >
                Sustainability
              </Link>
            </li>
            <li>
              <Link
                to="/products"
                className="hover:text-[#B58E58] transition-colors"
              >
                Shop All Products
              </Link>
            </li>
          </ul>
        </div>

        {/* Column 3: Support */}
        <div>
          <h4 className="text-[#031D44] font-bold text-base mb-6">Support</h4>
          <ul className="space-y-3 text-sm text-gray-500 font-light">
            <li>
              <Link
                to="/faq"
                className="hover:text-[#B58E58] transition-colors"
              >
                FAQ's
              </Link>
            </li>
            <li>
              <Link
                to="/care"
                className="hover:text-[#B58E58] transition-colors"
              >
                Product Care Instructions
              </Link>
            </li>
            <li>
              <Link
                to="/returns"
                className="hover:text-[#B58E58] transition-colors"
              >
                Request a Return
              </Link>
            </li>
            <li>
              <Link
                to="/privacy"
                className="hover:text-[#B58E58] transition-colors"
              >
                Privacy Policy
              </Link>
            </li>
            <li>
              <Link
                to="/code-of-conduct"
                className="hover:text-[#B58E58] transition-colors"
              >
                Supplier Code of Conduct
              </Link>
            </li>
            <li>
              <Link
                to="/terms"
                className="hover:text-[#B58E58] transition-colors"
              >
                Terms of Use
              </Link>
            </li>
          </ul>
        </div>

        {/* Column 4: Newsletter & Shop For Home */}
        <div className="flex flex-col justify-between">
          <div>
            <h4 className="text-[#031D44] font-bold text-base mb-4">
              Shop For Your Home at
            </h4>
            <h3 className="text-3xl font-serif text-gray-400 mb-8 tracking-wider">
              Gateway<span className="text-xl">+</span>Home{" "}
              <span className="text-[10px] uppercase align-top">Linen Co.</span>
            </h3>
          </div>

          <div>
            <h4 className="text-[#031D44] font-bold text-sm mb-4">
              Subscribe to Our Newsletter
            </h4>
            <form className="flex w-full" onSubmit={(e) => e.preventDefault()}>
              <input
                type="email"
                placeholder="Email address"
                className="flex-grow bg-white border border-gray-300 rounded-l-full px-4 py-2.5 text-sm focus:outline-none focus:border-[#4A5D4E]"
                required
              />
              <button
                type="submit"
                className="bg-[#4A5D4E] hover:bg-[#031D44] text-white px-6 py-2.5 rounded-r-full text-sm font-bold transition-colors cursor-pointer"
              >
                Submit
              </button>
            </form>
          </div>
        </div>
      </div>

      {/* Bottom Bar */}
      <div className="bg-gray-400 text-white py-4 px-6 md:px-12 flex flex-col md:flex-row justify-between items-center text-xs font-light relative">
        <p>Copyright © 2026 Gateway Linen. All rights reserved.</p>

        <div className="flex items-center gap-2 mt-4 md:mt-0">
          <div className="bg-[#031D44] text-white px-2 py-1 text-[10px] font-bold rounded">
            VISA
          </div>
          <div className="bg-[#0070BA] text-white px-2 py-1 text-[10px] font-bold rounded flex items-center gap-1">
            <i>PayPal</i>
          </div>
          <div className="bg-orange-500 text-white px-2 py-1 text-[10px] font-bold rounded flex items-center gap-1">
            <div className="w-2 h-2 bg-red-600 rounded-full"></div>
            <div className="w-2 h-2 bg-yellow-400 rounded-full -ml-1"></div>
          </div>
        </div>

        <button
          onClick={scrollToTop}
          className="absolute right-6 -top-16 w-14 h-14 bg-green-50 text-green-800 rounded-full flex flex-col items-center justify-center shadow-lg hover:bg-green-100 transition-colors cursor-pointer border border-green-200"
        >
          <FiArrowUp size={16} />
          <span className="text-[10px] font-bold mt-0.5">Top</span>
        </button>
      </div>
    </footer>
  );
};

export default Footer;
