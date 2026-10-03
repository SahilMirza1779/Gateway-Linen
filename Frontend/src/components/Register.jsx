import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import {
  FiUser,
  FiMail,
  FiPhone,
  FiBriefcase,
  FiArrowLeft,
  FiCheckCircle,
  FiAlertCircle,
  FiShield,
  FiTruck,
  FiHeadphones,
  FiStar,
  FiTag,
  FiGift,
  FiPackage,
  FiUsers,
  FiPercent,
  FiAward,
} from "react-icons/fi";
import logo from "../assets/GatewayLinen-logo.png";

// ✅ ONLINE IMAGES — Direct Pexels URLs (No download, no error)
const linen1 =
  "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=800";
const linen2 =
  "https://images.pexels.com/photos/5591664/pexels-photo-5591664.jpeg?auto=compress&cs=tinysrgb&w=800";
const linen3 =
  "https://images.pexels.com/photos/6585757/pexels-photo-6585757.jpeg?auto=compress&cs=tinysrgb&w=800";

const Register = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: "", text: "" });
  const [agreeTerms, setAgreeTerms] = useState(false);

  const [formData, setFormData] = useState({
    fullName: "",
    email: "",
    phone: "",
    companyName: "",
    taxNumber: "",
  });

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleRegister = async (e) => {
    e.preventDefault();
    setMessage({ type: "", text: "" });

    if (!formData.fullName || !formData.email) {
      setMessage({ type: "error", text: "Full name and email are required." });
      return;
    }

    if (!agreeTerms) {
      setMessage({
        type: "error",
        text: "Please agree to the Terms & Privacy Policy to continue.",
      });
      return;
    }

    setLoading(true);
    try {
      const response = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/users/api.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-API-KEY": "GatewayLinen@2026",
          },
          body: JSON.stringify({
            entity: "user",
            action: "insert",
            ...formData,
            isWholesaleApproved: 1,
            isActive: 1,
          }),
        }
      );

      const result = await response.json();

      if (result.success) {
        setMessage({
          type: "success",
          text: "Registration successful! Welcome email sent. Redirecting to login...",
        });

        if (result.data) {
          localStorage.setItem("user", JSON.stringify(result.data));
        }

        setTimeout(() => {
          navigate("/login");
        }, 2000);
      } else {
        setMessage({
          type: "error",
          text:
            result.message || "Registration failed. Email might already exist.",
        });
      }
    } catch (error) {
      console.error("API Error: ", error);
      setMessage({
        type: "error",
        text: "Server error. Please try again later.",
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen w-full bg-[#F1F3F6] font-sans flex flex-col">
      {/* ============ NAVBAR ============ */}
      <header className="w-full bg-[#2874F0] shadow-md sticky top-0 z-30">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">
          <Link to="/" className="flex items-center gap-2">
            <div className="w-9 h-9 bg-white rounded-md flex items-center justify-center p-1 shadow-sm">
              <img
                src={logo}
                alt="Gateway Linen"
                className="w-full h-full object-contain"
              />
            </div>
            <div className="leading-none">
              <p className="text-[15px] font-bold text-white italic">
                Gateway<span className="text-[#FFE500]">Linen</span>
              </p>
              <p className="text-[9px] uppercase tracking-[0.15em] text-white/80 italic mt-0.5">
                Explore <span className="text-[#FFE500]">Plus</span> ✦
              </p>
            </div>
          </Link>

          <Link
            to="/"
            className="inline-flex items-center gap-1.5 text-[11px] font-semibold text-white bg-white/15 hover:bg-white/25 backdrop-blur px-3.5 py-1.5 rounded transition-all uppercase tracking-wider border border-white/20"
          >
            <FiArrowLeft size={12} />
            <span className="hidden sm:inline">Back to Home</span>
            <span className="sm:hidden">Home</span>
          </Link>
        </div>
      </header>

      {/* ============ OFFER STRIP ============ */}
      <div className="w-full bg-gradient-to-r from-[#FF9F00] via-[#FB641B] to-[#FF9F00] text-white text-center py-1.5 px-4">
        <p className="text-[11px] font-semibold flex items-center justify-center gap-2 tracking-wide">
          <FiGift size={12} />
          <span>NEW PARTNER? Get 10% OFF on your first wholesale order!</span>
          <FiTag size={12} />
        </p>
      </div>

      {/* ============ MAIN ============ */}
      <main className="flex-1 flex items-center justify-center px-4 py-8">
        <div className="w-full max-w-[920px]">
          <div className="bg-white rounded-xl shadow-[0_8px_30px_rgba(0,0,0,0.08)] overflow-hidden grid md:grid-cols-[1.05fr_1fr]">
            {/* ===== LEFT PANEL ===== */}
            <div className="relative bg-gradient-to-br from-[#2874F0] via-[#1e5bc7] to-[#0d47a1] text-white p-7 sm:p-8 flex flex-col justify-between min-h-[560px] overflow-hidden">
              <div
                className="absolute inset-0 opacity-[0.08]"
                style={{
                  backgroundImage:
                    "radial-gradient(circle, white 1px, transparent 1px)",
                  backgroundSize: "18px 18px",
                }}
              />
              <div className="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-[#FFE500]/10 blur-3xl" />
              <div className="absolute -bottom-24 -left-24 w-72 h-72 rounded-full bg-[#FFE500]/10 blur-3xl" />

              <div className="relative z-10">
                <h2 className="text-[24px] font-bold leading-tight mb-1.5">
                  Join Us
                </h2>
                <p className="text-[12px] text-white/80 leading-relaxed font-light">
                  Create your account to unlock exclusive wholesale pricing &
                  fast ordering.
                </p>
              </div>

              <div className="relative z-10 flex justify-center my-6">
                <div className="relative w-52 h-52">
                  <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-40 h-40 rounded-2xl overflow-hidden shadow-2xl border-4 border-white/30 rotate-[-4deg]">
                    <img
                      src={linen1}
                      alt="Premium Bed Sheets"
                      className="w-full h-full object-cover"
                      onError={(e) => {
                        e.target.src =
                          "https://via.placeholder.com/400x400/2874F0/FFFFFF?text=Bed+Sheet";
                      }}
                    />
                  </div>

                  <div className="absolute top-0 right-0 w-24 h-24 rounded-2xl overflow-hidden shadow-2xl border-4 border-white/40 rotate-[8deg]">
                    <img
                      src={linen2}
                      alt="Bath Towels"
                      className="w-full h-full object-cover"
                      onError={(e) => {
                        e.target.src =
                          "https://via.placeholder.com/300x300/2874F0/FFFFFF?text=Towels";
                      }}
                    />
                  </div>

                  <div className="absolute bottom-0 left-0 w-24 h-24 rounded-2xl overflow-hidden shadow-2xl border-4 border-white/40 rotate-[-10deg]">
                    <img
                      src={linen3}
                      alt="Bath Mats"
                      className="w-full h-full object-cover"
                      onError={(e) => {
                        e.target.src =
                          "https://via.placeholder.com/300x300/2874F0/FFFFFF?text=Bath+Mat";
                      }}
                    />
                  </div>

                  <div className="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-[#FFE500] text-[#031D44] px-3 py-1 rounded-full shadow-lg flex items-center gap-1.5 whitespace-nowrap">
                    <FiShield size={11} />
                    <span className="text-[9px] font-bold uppercase tracking-wider">
                      Verified Quality
                    </span>
                  </div>

                  <div className="absolute -top-2 -left-2 bg-white text-[#2874F0] px-2.5 py-1 rounded-full shadow-lg flex items-center gap-1 whitespace-nowrap">
                    <FiPackage size={10} />
                    <span className="text-[9px] font-bold uppercase tracking-wider">
                      500+ Products
                    </span>
                  </div>
                </div>
              </div>

              <div className="relative z-10 space-y-2 mb-3">
                {[
                  { icon: FiPercent, text: "Exclusive wholesale pricing" },
                  { icon: FiUsers, text: "Priority bulk order support" },
                  { icon: FiAward, text: "Trusted by 10,000+ partners" },
                ].map((perk, i) => (
                  <div key={i} className="flex items-center gap-2.5 text-[11px]">
                    <div className="w-6 h-6 rounded-full bg-white/15 border border-white/20 flex items-center justify-center shrink-0">
                      <perk.icon size={11} className="text-[#FFE500]" />
                    </div>
                    <span className="text-white/90 font-medium">
                      {perk.text}
                    </span>
                  </div>
                ))}
              </div>

              <div className="relative z-10 pt-4 border-t border-white/20 space-y-3">
                <div className="flex flex-wrap gap-1.5 justify-center">
                  {["Bed Sheets", "Towels", "Bath Mats"].map((cat) => (
                    <span
                      key={cat}
                      className="text-[9px] font-semibold uppercase tracking-wider bg-white/15 border border-white/20 px-2 py-0.5 rounded-full"
                    >
                      {cat}
                    </span>
                  ))}
                </div>

                <div className="flex items-center justify-center gap-1">
                  {[1, 2, 3, 4, 5].map((s) => (
                    <FiStar
                      key={s}
                      size={12}
                      fill="#FFE500"
                      className="text-[#FFE500]"
                    />
                  ))}
                  <span className="text-[11px] font-bold ml-1.5">4.8/5</span>
                  <span className="text-[9px] text-white/70 uppercase tracking-wider ml-2">
                    • 10,000+ wholesalers
                  </span>
                </div>
              </div>
            </div>

            {/* ===== RIGHT: FORM ===== */}
            <div className="p-7 sm:p-9 flex flex-col justify-center">
              <div className="mb-5">
                <h1 className="text-[20px] font-bold text-[#031D44] mb-1">
                  Create Account
                </h1>
                <p className="text-[11px] text-gray-500 font-light leading-relaxed">
                  Fill your details to get exclusive wholesale access.
                </p>
              </div>

              {message.text && (
                <div
                  className={`mb-5 p-3 rounded-md text-[12px] flex items-start gap-2 font-medium animate-[slideDown_0.25s_ease] ${
                    message.type === "error"
                      ? "bg-red-50 border-l-4 border-red-500 text-red-700"
                      : "bg-green-50 border-l-4 border-green-500 text-green-700"
                  }`}
                >
                  {message.type === "error" ? (
                    <FiAlertCircle size={14} className="shrink-0 mt-0.5" />
                  ) : (
                    <FiCheckCircle size={14} className="shrink-0 mt-0.5" />
                  )}
                  <span>{message.text}</span>
                </div>
              )}

              <form onSubmit={handleRegister} className="space-y-3.5">
                <div>
                  <label className="block text-[10px] font-semibold text-gray-600 mb-1">
                    Full Name <span className="text-red-500">*</span>
                  </label>
                  <div className="relative group">
                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                      <FiUser
                        className="text-gray-400 group-focus-within:text-[#2874F0] transition-colors"
                        size={14}
                      />
                    </div>
                    <input
                      type="text"
                      name="fullName"
                      value={formData.fullName}
                      onChange={handleChange}
                      className="w-full pl-10 pr-3 py-2.5 border-b-2 border-gray-300 rounded-none text-[13px] focus:outline-none focus:border-[#2874F0] transition-all bg-transparent text-gray-800 placeholder:text-gray-400"
                      placeholder="John Doe"
                      required
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-[10px] font-semibold text-gray-600 mb-1">
                    Email Address <span className="text-red-500">*</span>
                  </label>
                  <div className="relative group">
                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                      <FiMail
                        className="text-gray-400 group-focus-within:text-[#2874F0] transition-colors"
                        size={14}
                      />
                    </div>
                    <input
                      type="email"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      className="w-full pl-10 pr-3 py-2.5 border-b-2 border-gray-300 rounded-none text-[13px] focus:outline-none focus:border-[#2874F0] transition-all bg-transparent text-gray-800 placeholder:text-gray-400"
                      placeholder="you@company.com"
                      required
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[10px] font-semibold text-gray-600 mb-1">
                      Phone{" "}
                      <span className="text-gray-400 font-normal">
                        (Optional)
                      </span>
                    </label>
                    <div className="relative group">
                      <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <FiPhone
                          className="text-gray-400 group-focus-within:text-[#2874F0] transition-colors"
                          size={14}
                        />
                      </div>
                      <input
                        type="tel"
                        name="phone"
                        value={formData.phone}
                        onChange={handleChange}
                        className="w-full pl-10 pr-3 py-2.5 border-b-2 border-gray-300 rounded-none text-[13px] focus:outline-none focus:border-[#2874F0] transition-all bg-transparent text-gray-800 placeholder:text-gray-400"
                        placeholder="+1 234 567 890"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-[10px] font-semibold text-gray-600 mb-1">
                      Company{" "}
                      <span className="text-gray-400 font-normal">
                        (Optional)
                      </span>
                    </label>
                    <div className="relative group">
                      <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <FiBriefcase
                          className="text-gray-400 group-focus-within:text-[#2874F0] transition-colors"
                          size={14}
                        />
                      </div>
                      <input
                        type="text"
                        name="companyName"
                        value={formData.companyName}
                        onChange={handleChange}
                        className="w-full pl-10 pr-3 py-2.5 border-b-2 border-gray-300 rounded-none text-[13px] focus:outline-none focus:border-[#2874F0] transition-all bg-transparent text-gray-800 placeholder:text-gray-400"
                        placeholder="Business Name"
                      />
                    </div>
                  </div>
                </div>

                <label className="flex items-start gap-2.5 pt-1 cursor-pointer select-none">
                  <input
                    type="checkbox"
                    checked={agreeTerms}
                    onChange={(e) => setAgreeTerms(e.target.checked)}
                    className="mt-0.5 w-3.5 h-3.5 accent-[#2874F0] cursor-pointer"
                  />
                  <span className="text-[10px] text-gray-500 leading-relaxed">
                    I agree to the{" "}
                    <a
                      href="#"
                      className="text-[#2874F0] font-semibold hover:underline"
                    >
                      Terms of Use
                    </a>{" "}
                    &{" "}
                    <a
                      href="#"
                      className="text-[#2874F0] font-semibold hover:underline"
                    >
                      Privacy Policy
                    </a>{" "}
                    of Gateway Linen.
                  </span>
                </label>

                <button
                  disabled={loading}
                  type="submit"
                  className={`w-full py-3.5 rounded-md text-[13px] font-bold text-white transition-all cursor-pointer tracking-wide uppercase shadow-md ${
                    loading
                      ? "bg-gray-400 cursor-not-allowed"
                      : "bg-[#FB641B] hover:bg-[#e55a15] hover:shadow-lg active:scale-[0.98]"
                  }`}
                >
                  {loading ? "Creating Account..." : "Create Account"}
                </button>

                <div className="flex items-center gap-3 my-4">
                  <div className="h-px flex-1 bg-gray-200" />
                  <span className="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                    Already registered?
                  </span>
                  <div className="h-px flex-1 bg-gray-200" />
                </div>

                <button
                  type="button"
                  onClick={() => navigate("/login")}
                  className="w-full py-3 rounded-md text-[12px] font-bold text-[#2874F0] bg-white border-2 border-[#2874F0] hover:bg-[#2874F0] hover:text-white transition-all cursor-pointer uppercase tracking-wide shadow-sm active:scale-[0.98]"
                >
                  Sign In Instead
                </button>
              </form>
            </div>
          </div>

          <div className="mt-5 grid grid-cols-3 gap-3">
            {[
              {
                icon: FiShield,
                label: "100% Secure",
                color: "#2874F0",
                bg: "#EAF2FF",
              },
              {
                icon: FiTruck,
                label: "Fast Delivery",
                color: "#FB641B",
                bg: "#FFF1E8",
              },
              {
                icon: FiHeadphones,
                label: "24×7 Support",
                color: "#FF9F00",
                bg: "#FFF8E6",
              },
            ].map((b, i) => (
              <div
                key={i}
                className="bg-white rounded-lg border border-gray-200 px-3 py-3.5 text-center hover:shadow-md hover:border-gray-300 transition-all flex items-center justify-center gap-2"
              >
                <div
                  className="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                  style={{ backgroundColor: b.bg }}
                >
                  <b.icon size={15} style={{ color: b.color }} />
                </div>
                <p className="text-[10px] font-bold text-gray-700 uppercase tracking-wider text-left leading-tight">
                  {b.label}
                </p>
              </div>
            ))}
          </div>

          <p className="text-[11px] text-gray-500 text-center mt-5 leading-relaxed">
            Need help? Call{" "}
            <span className="font-bold text-[#2874F0]">1-800-661-7239</span> or
            email{" "}
            <a
              href="mailto:gatewaylinen@gmail.com"
              className="font-bold text-[#FB641B] hover:underline"
            >
              gatewaylinen@gmail.com
            </a>
          </p>
        </div>
      </main>

      <footer className="bg-[#172337] text-white mt-auto">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div className="flex items-center gap-2">
            <FiShield size={13} className="text-[#FFE500]" />
            <p className="text-[11px] text-white/70">
              © {new Date().getFullYear()} Gateway Linen. All rights reserved.
            </p>
          </div>
          <div className="flex items-center gap-5">
            {["Privacy", "Terms", "Contact", "Help"].map((item) => (
              <a
                key={item}
                href="#"
                className="text-[11px] text-white/70 hover:text-[#FFE500] transition-colors"
              >
                {item}
              </a>
            ))}
          </div>
        </div>
      </footer>

      <style>{`
        @keyframes slideDown {
          from { opacity: 0; transform: translateY(-6px); }
          to { opacity: 1; transform: translateY(0); }
        }
      `}</style>
    </div>
  );
};

export default Register;