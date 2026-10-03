import { useState } from "react";
import { Link } from "react-router-dom";
import {
  FiMapPin,
  FiPhone,
  FiMail,
  FiSend,
  FiCheckCircle,
  FiShield,
  FiClock,
  FiHeadphones,
  FiTruck,
  FiMessageSquare,
  FiArrowRight,
  FiPercent,
  FiPackage,
  FiStar,
  FiUsers,
  FiAward,
} from "react-icons/fi";

// ✅ Product images (reliable URLs)
const HERO_IMAGE =
  "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=800";
const SHOWROOM_IMAGE =
  "https://images.unsplash.com/photo-1631679706909-1844bbd07221?w=600&q=80";
const WAREHOUSE_IMAGE =
  "https://images.unsplash.com/photo-1553413077-190dd305871c?w=600&q=80";
const TEAM_IMAGE =
  "https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=600&q=80";

const ContactPage = () => {
  const [submitted, setSubmitted] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    subject: "",
    message: "",
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    setSubmitted(true);
    setTimeout(() => {
      setSubmitted(false);
      setFormData({
        name: "",
        email: "",
        phone: "",
        subject: "",
        message: "",
      });
      alert(
        "Your message has been sent successfully! Our team will contact you soon."
      );
    }, 1500);
  };

  const contactCards = [
    {
      icon: FiMapPin,
      title: "Visit Our Office",
      lines: ["9 Mapleridge Crescent,", "Brandon R7A 6P8,", "Manitoba, Canada"],
      color: "#2874F0",
      bg: "#EAF2FF",
      action: null,
    },
    {
      icon: FiPhone,
      title: "Call Us Directly",
      lines: ["+1 (204) 979-4044", "Mon - Sat, 9:00 AM - 6:00 PM CST"],
      color: "#FB641B",
      bg: "#FFF1E8",
      action: { href: "tel:+12049794044", label: "Call Now" },
    },
    {
      icon: FiMail,
      title: "Email Inquiries",
      lines: ["gatewaylinen@gmail.com", "Response within 24 hours"],
      color: "#FF9F00",
      bg: "#FFF8E6",
      action: { href: "mailto:gatewaylinen@gmail.com", label: "Send Email" },
    },
  ];

  return (
    <div className="w-full bg-[#F1F3F6] min-h-screen font-sans">
      {/* ============ HERO BANNER WITH IMAGE ============ */}
      <div className="bg-gradient-to-r from-[#2874F0] via-[#1e5bc7] to-[#0d47a1] text-white relative overflow-hidden">
        <div
          className="absolute inset-0 opacity-[0.08]"
          style={{
            backgroundImage:
              "radial-gradient(circle, white 1px, transparent 1px)",
            backgroundSize: "20px 20px",
          }}
        />
        <div className="absolute -top-20 -right-20 w-96 h-96 rounded-full bg-[#FFE500]/10 blur-3xl" />
        <div className="absolute -bottom-20 -left-20 w-96 h-96 rounded-full bg-[#FFE500]/10 blur-3xl" />

        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-10 md:py-16 relative">
          <div className="grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
            {/* LEFT: Text Content */}
            <div>
              <div className="inline-flex items-center gap-2 bg-white/15 border border-white/20 px-3 py-1 rounded-full mb-4">
                <FiHeadphones size={11} className="text-[#FFE500]" />
                <span className="text-[10px] font-bold tracking-[0.2em] uppercase">
                  Corporate B2B Support
                </span>
              </div>

              <h1 className="text-3xl sm:text-4xl md:text-5xl font-bold leading-tight mb-4">
                Connect With Our
                <br />
                <span className="text-[#FFE500]">Linen Experts</span>
              </h1>
              <p className="text-[13px] md:text-[15px] text-white/85 font-light leading-relaxed max-w-xl mb-6">
                Have inquiries about bulk hotel linen supplies, custom
                embroidery, or long-term partnerships? Our Canadian team is
                here to help — 24×7.
              </p>

              {/* Quick action buttons */}
              <div className="flex flex-wrap items-center gap-3 mb-6">
                <a
                  href="tel:+12049794044"
                  className="inline-flex items-center gap-2 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[12px] font-bold uppercase tracking-wider px-5 py-3 rounded transition-all shadow-md"
                >
                  <FiPhone size={14} /> Call Now
                </a>
                <a
                  href="mailto:gatewaylinen@gmail.com"
                  className="inline-flex items-center gap-2 bg-white/15 hover:bg-white/25 border border-white/20 text-white text-[12px] font-bold uppercase tracking-wider px-5 py-3 rounded transition-all"
                >
                  <FiMail size={14} /> Email Us
                </a>
              </div>

              {/* Trust indicators */}
              <div className="flex flex-wrap items-center gap-5 pt-4 border-t border-white/15">
                <div className="flex items-center gap-2">
                  <div className="flex -space-x-2">
                    {[1, 2, 3, 4].map((i) => (
                      <div
                        key={i}
                        className="w-7 h-7 rounded-full border-2 border-[#2874F0] bg-gradient-to-br from-[#FFE500] to-[#FB641B] flex items-center justify-center text-[10px] font-bold text-[#031D44]"
                      >
                        {String.fromCharCode(64 + i)}
                      </div>
                    ))}
                    <div className="w-7 h-7 rounded-full border-2 border-[#2874F0] bg-white/20 flex items-center justify-center text-[9px] font-bold text-white">
                      +9k
                    </div>
                  </div>
                  <span className="text-[11px] text-white/80 font-medium">
                    Trusted by 10,000+ partners
                  </span>
                </div>
                <div className="flex items-center gap-1">
                  {[1, 2, 3, 4, 5].map((s) => (
                    <FiStar
                      key={s}
                      size={12}
                      fill="#FFE500"
                      className="text-[#FFE500]"
                    />
                  ))}
                  <span className="text-[11px] font-bold ml-1">4.9/5</span>
                </div>
              </div>
            </div>

            {/* RIGHT: Image with floating cards */}
            <div className="hidden lg:block relative">
              <div className="relative w-full max-w-md mx-auto">
                {/* Main hero image */}
                <div className="relative rounded-2xl overflow-hidden shadow-2xl border-4 border-white/20 rotate-1">
                  <img
                    src={HERO_IMAGE}
                    alt="Premium Linen"
                    className="w-full h-72 object-cover"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>

                  {/* Badge */}
                  <div className="absolute bottom-4 left-4 right-4">
                    <div className="bg-white/95 backdrop-blur-sm px-4 py-2.5 rounded-lg shadow-lg flex items-center gap-3">
                      <div className="w-9 h-9 bg-[#2874F0] rounded-full flex items-center justify-center shrink-0">
                        <FiPackage size={15} className="text-[#FFE500]" />
                      </div>
                      <div>
                        <p className="text-[10px] font-bold text-[#FB641B] uppercase tracking-wider">
                          Premium Quality
                        </p>
                        <p className="text-[12px] font-bold text-gray-800">
                          500+ Hospitality Products
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Floating small image - top right */}
                <div className="absolute -top-4 -right-6 w-24 h-24 rounded-xl overflow-hidden shadow-2xl border-4 border-white/30 rotate-12">
                  <img
                    src="https://images.pexels.com/photos/5591664/pexels-photo-5591664.jpeg?auto=compress&cs=tinysrgb&w=200"
                    alt="Bath Towels"
                    className="w-full h-full object-cover"
                  />
                </div>

                {/* Floating small image - bottom left */}
                <div className="absolute -bottom-4 -left-6 w-24 h-24 rounded-xl overflow-hidden shadow-2xl border-4 border-white/30 -rotate-12">
                  <img
                    src="https://images.pexels.com/photos/6585757/pexels-photo-6585757.jpeg?auto=compress&cs=tinysrgb&w=200"
                    alt="Bath Mat"
                    className="w-full h-full object-cover"
                  />
                </div>

                {/* Verified badge */}
                <div className="absolute -top-2 -left-4 bg-[#FFE500] text-[#031D44] px-3 py-1.5 rounded-full shadow-xl flex items-center gap-1.5 whitespace-nowrap rotate-[-8deg]">
                  <FiAward size={12} />
                  <span className="text-[9px] font-bold uppercase tracking-wider">
                    Since 2005
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ============ CONTACT INFO CARDS STRIP ============ */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 -mt-6 md:-mt-8 relative z-10">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {contactCards.map((card, i) => (
            <div
              key={i}
              className="bg-white rounded-lg border border-gray-200 shadow-md hover:shadow-lg hover:border-[#2874F0] transition-all p-5 group"
            >
              <div className="flex items-start gap-4">
                <div
                  className="w-12 h-12 rounded-full flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                  style={{ backgroundColor: card.bg }}
                >
                  <card.icon size={22} style={{ color: card.color }} />
                </div>
                <div className="min-w-0 flex-1">
                  <h3 className="text-[13px] font-bold text-gray-800 mb-1.5">
                    {card.title}
                  </h3>
                  {card.lines.map((line, j) => (
                    <p
                      key={j}
                      className="text-[12px] text-gray-500 leading-relaxed break-words"
                    >
                      {line}
                    </p>
                  ))}
                  {card.action && (
                    <a
                      href={card.action.href}
                      className="inline-flex items-center gap-1 mt-2 text-[11px] font-bold uppercase tracking-wider transition-colors"
                      style={{ color: card.color }}
                    >
                      {card.action.label}
                      <FiArrowRight size={11} />
                    </a>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* ============ MAIN CONTENT ============ */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-12">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-5 md:gap-6">
          {/* ===== FORM ===== */}
          <div className="lg:col-span-7 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div className="bg-[#F1F3F6] px-5 md:px-6 py-4 border-b border-gray-200 flex items-center gap-3">
              <div className="w-9 h-9 bg-[#EAF2FF] text-[#2874F0] rounded-full flex items-center justify-center shrink-0">
                <FiMessageSquare size={16} />
              </div>
              <div>
                <h2 className="text-[15px] font-bold text-gray-800">
                  Send Us a Message
                </h2>
                <p className="text-[11px] text-gray-500">
                  Fill the form below — we reply within 24 hours
                </p>
              </div>
            </div>

            <div className="p-5 md:p-6">
              {submitted ? (
                <div className="py-16 md:py-20 flex flex-col items-center justify-center text-center">
                  <div className="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-4">
                    <FiCheckCircle size={32} />
                  </div>
                  <h4 className="text-[18px] font-bold text-gray-800 mb-1">
                    Message Sent Successfully!
                  </h4>
                  <p className="text-[12px] text-gray-500">
                    Thank you for connecting with Gateway Linen. Our team will
                    respond shortly.
                  </p>
                </div>
              ) : (
                <form onSubmit={handleSubmit} className="space-y-4">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                        Full Name <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="text"
                        required
                        value={formData.name}
                        onChange={(e) =>
                          setFormData({ ...formData, name: e.target.value })
                        }
                        placeholder="Enter your name"
                        className="w-full px-3.5 py-2.5 border border-gray-300 rounded text-[13px] text-gray-800 focus:outline-none focus:border-[#2874F0] focus:ring-2 focus:ring-[#2874F0]/10 transition-all"
                      />
                    </div>
                    <div>
                      <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
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
                        className="w-full px-3.5 py-2.5 border border-gray-300 rounded text-[13px] text-gray-800 focus:outline-none focus:border-[#2874F0] focus:ring-2 focus:ring-[#2874F0]/10 transition-all"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                        Phone Number <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="tel"
                        required
                        value={formData.phone}
                        onChange={(e) =>
                          setFormData({ ...formData, phone: e.target.value })
                        }
                        placeholder="+1 (xxx) xxx-xxxx"
                        className="w-full px-3.5 py-2.5 border border-gray-300 rounded text-[13px] text-gray-800 focus:outline-none focus:border-[#2874F0] focus:ring-2 focus:ring-[#2874F0]/10 transition-all"
                      />
                    </div>
                    <div>
                      <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                        Subject <span className="text-red-500">*</span>
                      </label>
                      <select
                        required
                        value={formData.subject}
                        onChange={(e) =>
                          setFormData({ ...formData, subject: e.target.value })
                        }
                        className="w-full px-3.5 py-2.5 border border-gray-300 rounded text-[13px] text-gray-800 focus:outline-none focus:border-[#2874F0] focus:ring-2 focus:ring-[#2874F0]/10 transition-all bg-white cursor-pointer"
                      >
                        <option value="">Select a topic</option>
                        <option value="Bulk Wholesale Inquiry">
                          Bulk Wholesale Inquiry
                        </option>
                        <option value="Custom Embroidery">
                          Custom Embroidery
                        </option>
                        <option value="Order Support">Order Support</option>
                        <option value="Return & Exchange">
                          Return & Exchange
                        </option>
                        <option value="Partnership Request">
                          Partnership Request
                        </option>
                        <option value="Other">Other</option>
                      </select>
                    </div>
                  </div>

                  <div>
                    <label className="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                      Your Message <span className="text-red-500">*</span>
                    </label>
                    <textarea
                      rows="5"
                      required
                      value={formData.message}
                      onChange={(e) =>
                        setFormData({ ...formData, message: e.target.value })
                      }
                      placeholder="Write your requirements here..."
                      className="w-full px-3.5 py-2.5 border border-gray-300 rounded text-[13px] text-gray-800 focus:outline-none focus:border-[#2874F0] focus:ring-2 focus:ring-[#2874F0]/10 resize-none transition-all"
                    ></textarea>
                  </div>

                  <button
                    type="submit"
                    className="w-full py-3.5 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[12px] font-bold tracking-wider uppercase rounded transition-all cursor-pointer shadow-sm flex items-center justify-center gap-2 group"
                  >
                    <FiSend
                      size={14}
                      className="group-hover:translate-x-1 transition-transform"
                    />
                    Send Message
                  </button>

                  <p className="text-[10px] text-gray-400 text-center pt-1">
                    By submitting, you agree to Gateway Linen's{" "}
                    <Link
                      to="/terms"
                      className="text-[#2874F0] font-semibold hover:underline"
                    >
                      Terms
                    </Link>{" "}
                    &{" "}
                    <Link
                      to="/privacy"
                      className="text-[#2874F0] font-semibold hover:underline"
                    >
                      Privacy Policy
                    </Link>
                  </p>
                </form>
              )}
            </div>

            {/* Trust Badges Strip */}
            <div className="bg-[#F1F3F6] border-t border-gray-200 px-4 py-3">
              <div className="grid grid-cols-3 gap-2">
                {[
                  { icon: FiClock, label: "24hr Reply", color: "#2874F0" },
                  { icon: FiShield, label: "100% Secure", color: "#FB641B" },
                  { icon: FiHeadphones, label: "Live Support", color: "#FF9F00" },
                ].map((b, i) => (
                  <div
                    key={i}
                    className="flex items-center justify-center gap-1.5 text-[10px] font-bold text-gray-700 uppercase tracking-wider"
                  >
                    <b.icon size={12} style={{ color: b.color }} />
                    <span>{b.label}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* ===== RIGHT SIDEBAR ===== */}
          <div className="lg:col-span-5 space-y-4">
            {/* Map */}
            <div className="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
              <div className="bg-[#F1F3F6] px-5 py-3 border-b border-gray-200 flex items-center gap-2">
                <FiMapPin size={14} className="text-[#2874F0]" />
                <h3 className="text-[13px] font-bold text-gray-800">
                  Find Us on Map
                </h3>
              </div>
              <div className="h-[240px] w-full">
                <iframe
                  title="Gateway Linen Location"
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d81559.45876313715!2d-99.98816!3d49.8482!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x52c1e65e638b6d85%3A0x44614e758784d531!2sBrandon%2C%20MB%2C%20Canada!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin"
                  width="100%"
                  height="100%"
                  style={{ border: 0 }}
                  allowFullScreen=""
                  loading="lazy"
                  referrerPolicy="no-referrer-when-downgrade"
                ></iframe>
              </div>
              <div className="p-4 bg-[#F1F3F6] flex items-center gap-3">
                <div className="w-10 h-10 bg-[#EAF2FF] rounded-full flex items-center justify-center shrink-0">
                  <FiMapPin size={16} className="text-[#2874F0]" />
                </div>
                <div className="min-w-0">
                  <p className="text-[11px] font-bold text-gray-800">
                    Brandon, Manitoba
                  </p>
                  <p className="text-[10px] text-gray-500">
                    Head Office & Showroom
                  </p>
                </div>
              </div>
            </div>

            {/* Why Partner */}
            <div className="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
              <div className="bg-[#F1F3F6] px-5 py-3 border-b border-gray-200 flex items-center gap-2">
                <FiPackage size={14} className="text-[#FB641B]" />
                <h3 className="text-[13px] font-bold text-gray-800">
                  Why Partner With Us
                </h3>
              </div>
              <div className="p-4 space-y-3">
                {[
                  {
                    icon: FiTruck,
                    title: "Fast Delivery",
                    desc: "Nationwide shipping across Canada & USA",
                    color: "#2874F0",
                    bg: "#EAF2FF",
                  },
                  {
                    icon: FiPercent,
                    title: "Wholesale Pricing",
                    desc: "Exclusive B2B discounts on bulk orders",
                    color: "#FB641B",
                    bg: "#FFF1E8",
                  },
                  {
                    icon: FiShield,
                    title: "Trusted Since 2005",
                    desc: "10,000+ satisfied hospitality partners",
                    color: "#FF9F00",
                    bg: "#FFF8E6",
                  },
                ].map((item, i) => (
                  <div key={i} className="flex items-start gap-3">
                    <div
                      className="w-9 h-9 rounded-full flex items-center justify-center shrink-0"
                      style={{ backgroundColor: item.bg }}
                    >
                      <item.icon size={15} style={{ color: item.color }} />
                    </div>
                    <div className="min-w-0">
                      <p className="text-[12px] font-bold text-gray-800">
                        {item.title}
                      </p>
                      <p className="text-[11px] text-gray-500 mt-0.5 leading-relaxed">
                        {item.desc}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Business Hours */}
            <div className="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
              <div className="bg-[#F1F3F6] px-5 py-3 border-b border-gray-200 flex items-center gap-2">
                <FiClock size={14} className="text-[#FF9F00]" />
                <h3 className="text-[13px] font-bold text-gray-800">
                  Business Hours
                </h3>
              </div>
              <div className="p-4 space-y-2">
                {[
                  { day: "Mon - Fri", hours: "9:00 AM - 6:00 PM CST" },
                  { day: "Saturday", hours: "10:00 AM - 4:00 PM CST" },
                  { day: "Sunday", hours: "Closed" },
                ].map((h, i) => (
                  <div
                    key={i}
                    className="flex justify-between items-center text-[12px] py-1.5 border-b border-gray-100 last:border-0"
                  >
                    <span className="text-gray-600 font-medium">{h.day}</span>
                    <span
                      className={`font-bold ${
                        h.hours === "Closed" ? "text-red-500" : "text-gray-800"
                      }`}
                    >
                      {h.hours}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* ============ OFFICE / SHOWROOM / TEAM GALLERY ============ */}
        <div className="mt-8 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
          <div className="bg-[#F1F3F6] px-5 md:px-6 py-4 border-b border-gray-200 flex items-center gap-3">
            <div className="w-9 h-9 bg-[#FFF8E6] text-[#FF9F00] rounded-full flex items-center justify-center shrink-0">
              <FiUsers size={16} />
            </div>
            <div>
              <h2 className="text-[15px] font-bold text-gray-800">
                Visit Our Facilities
              </h2>
              <p className="text-[11px] text-gray-500">
                Showroom, warehouse & team — get to know us better
              </p>
            </div>
          </div>

          <div className="p-5 md:p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            {[
              {
                img: SHOWROOM_IMAGE,
                title: "Showroom",
                desc: "Explore our premium linen collection in person",
                tag: "Brandon, MB",
              },
              {
                img: WAREHOUSE_IMAGE,
                title: "Warehouse",
                desc: "50,000 sq ft stocked with hospitality linens",
                tag: "Fast Dispatch",
              },
              {
                img: TEAM_IMAGE,
                title: "Our Team",
                desc: "Dedicated experts ready to help your business",
                tag: "24×7 Support",
              },
            ].map((item, i) => (
              <div
                key={i}
                className="group rounded-lg overflow-hidden border border-gray-200 hover:border-[#2874F0] hover:shadow-lg transition-all"
              >
                <div className="relative h-40 bg-[#F1F3F6] overflow-hidden">
                  <img
                    src={item.img}
                    alt={item.title}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                  <span className="absolute top-3 left-3 bg-[#FFE500] text-[#031D44] text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded">
                    {item.tag}
                  </span>
                </div>
                <div className="p-4">
                  <h3 className="text-[13px] font-bold text-gray-800 mb-1">
                    {item.title}
                  </h3>
                  <p className="text-[11px] text-gray-500 leading-relaxed">
                    {item.desc}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* ============ FAQ SHORTCUT ============ */}
        <div className="mt-8 bg-gradient-to-r from-[#2874F0] to-[#0d47a1] rounded-lg shadow-md p-6 md:p-8 flex flex-col md:flex-row items-center justify-between gap-4 text-white relative overflow-hidden">
          <div
            className="absolute inset-0 opacity-[0.08]"
            style={{
              backgroundImage:
                "radial-gradient(circle, white 1px, transparent 1px)",
              backgroundSize: "20px 20px",
            }}
          />
          <div className="flex items-center gap-4 relative">
            <div className="w-14 h-14 bg-[#FFE500] text-[#031D44] rounded-full flex items-center justify-center shrink-0">
              <FiHeadphones size={24} />
            </div>
            <div>
              <h3 className="text-[16px] md:text-[18px] font-bold mb-0.5">
                Looking for quick answers?
              </h3>
              <p className="text-[12px] text-white/80">
                Check our FAQ section for instant answers to common questions.
              </p>
            </div>
          </div>
          <Link
            to="/faq"
            className="inline-flex items-center gap-2 px-5 py-3 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[12px] font-bold uppercase tracking-wider rounded transition-all shadow-sm shrink-0 relative"
          >
            Visit FAQ <FiArrowRight size={13} />
          </Link>
        </div>
      </div>
    </div>
  );
};

export default ContactPage;