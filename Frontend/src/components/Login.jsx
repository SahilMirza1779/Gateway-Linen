import { useState, useRef, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import {
  FiMail,
  FiArrowLeft,
  FiCheckCircle,
  FiAlertCircle,
  FiShield,
  FiRefreshCw,
  FiTruck,
  FiHeadphones,
  FiStar,
  FiTag,
  FiGift,
  FiPackage,
} from "react-icons/fi";
import logo from "../assets/GatewayLinen-logo.png";

// ✅ Product images — direct online URLs (no download needed)
const linen1 =
  "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?w=600&q=80&auto=format";
const linen2 =
  "https://images.unsplash.com/photo-1600369671236-e74521d4b6ad?w=600&q=80&auto=format";
const linen3 =
  "https://images.unsplash.com/photo-1620912189866-474843ba5c14?w=600&q=80&auto=format";

const Login = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: "", text: "" });

  const [step, setStep] = useState(1);
  const [email, setEmail] = useState("");
  const [otp, setOtp] = useState(["", "", "", "", "", ""]);
  const [otpToken, setOtpToken] = useState("");
  const [resendTimer, setResendTimer] = useState(0);

  const otpRefs = useRef([]);

  useEffect(() => {
    if (resendTimer <= 0) return;
    const t = setTimeout(() => setResendTimer((s) => s - 1), 1000);
    return () => clearTimeout(t);
  }, [resendTimer]);

  useEffect(() => {
    if (step === 2) setTimeout(() => otpRefs.current[0]?.focus(), 100);
  }, [step]);

  const handleRequestOTP = async (e) => {
    e?.preventDefault();
    setMessage({ type: "", text: "" });

    if (!email || !email.includes("@")) {
      setMessage({ type: "error", text: "Please enter a valid email address." });
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
            action: "send_otp",
            email: email,
          }),
        }
      );

      const result = await response.json();

      if (result.success) {
        setOtpToken(result.data.token);
        setStep(2);
        setResendTimer(30);
        setMessage({
          type: "success",
          text: "OTP sent successfully! Check your email inbox.",
        });
      } else {
        setMessage({
          type: "error",
          text: result.message || "Failed to send OTP.",
        });
      }
    } catch (error) {
      console.error("API Error: ", error);
      setMessage({ type: "error", text: "Server error. Please try again." });
    } finally {
      setLoading(false);
    }
  };

  const handleOtpChange = (index, value) => {
    const digit = value.replace(/[^0-9]/g, "").slice(-1);
    const next = [...otp];
    next[index] = digit;
    setOtp(next);
    if (digit && index < 5) otpRefs.current[index + 1]?.focus();
  };

  const handleOtpKeyDown = (index, e) => {
    if (e.key === "Backspace" && !otp[index] && index > 0)
      otpRefs.current[index - 1]?.focus();
    if (e.key === "ArrowLeft" && index > 0) otpRefs.current[index - 1]?.focus();
    if (e.key === "ArrowRight" && index < 5) otpRefs.current[index + 1]?.focus();
  };

  const handleOtpPaste = (e) => {
    e.preventDefault();
    const pasted = e.clipboardData
      .getData("text")
      .replace(/[^0-9]/g, "")
      .slice(0, 6);
    if (!pasted) return;
    const next = ["", "", "", "", "", ""];
    pasted.split("").forEach((d, i) => (next[i] = d));
    setOtp(next);
    otpRefs.current[Math.min(pasted.length, 5)]?.focus();
  };

  const handleVerifyOTP = async (e) => {
    e?.preventDefault();
    setMessage({ type: "", text: "" });

    const otpString = otp.join("");
    if (otpString.length < 6) {
      setMessage({ type: "error", text: "Please enter all 6 digits." });
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
            action: "verify_otp",
            email: email,
            otp: otpString,
            token: otpToken,
          }),
        }
      );

      const result = await response.json();

      if (result.success) {
        setMessage({
          type: "success",
          text: "Login successful! Redirecting...",
        });
        localStorage.setItem("user", JSON.stringify(result.data));
        setTimeout(() => navigate("/"), 1200);
      } else {
        setMessage({
          type: "error",
          text: result.message || "Invalid or expired OTP.",
        });
      }
    } catch (error) {
      console.error("API Error: ", error);
      setMessage({ type: "error", text: "Server error during verification." });
    } finally {
      setLoading(false);
    }
  };

  const resetToEmail = () => {
    setStep(1);
    setOtp(["", "", "", "", "", ""]);
    setMessage({ type: "", text: "" });
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
          <span>NEW USER? Get 10% OFF on your first wholesale order!</span>
          <FiTag size={12} />
        </p>
      </div>

      {/* ============ MAIN ============ */}
      <main className="flex-1 flex items-center justify-center px-4 py-8">
        <div className="w-full max-w-[900px]">
          {/* ==== LOGIN CARD ==== */}
          <div className="bg-white rounded-xl shadow-[0_8px_30px_rgba(0,0,0,0.08)] overflow-hidden grid md:grid-cols-[1.05fr_1fr]">
            {/* ===== LEFT: BLUE PANEL with REAL PRODUCT IMAGES ===== */}
            <div className="relative bg-gradient-to-br from-[#2874F0] via-[#1e5bc7] to-[#0d47a1] text-white p-7 sm:p-8 flex flex-col justify-between min-h-[480px] overflow-hidden">
              {/* dot pattern */}
              <div
                className="absolute inset-0 opacity-[0.08]"
                style={{
                  backgroundImage:
                    "radial-gradient(circle, white 1px, transparent 1px)",
                  backgroundSize: "18px 18px",
                }}
              />
              {/* glow */}
              <div className="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-[#FFE500]/10 blur-3xl" />
              <div className="absolute -bottom-24 -left-24 w-72 h-72 rounded-full bg-[#FFE500]/10 blur-3xl" />

              {/* Top text */}
              <div className="relative z-10">
                <h2 className="text-[24px] font-bold leading-tight mb-1.5">
                  {step === 1 ? "Login" : "Verify OTP"}
                </h2>
                <p className="text-[12px] text-white/80 leading-relaxed font-light">
                  {step === 1
                    ? "Get access to your Orders, Wishlist & Recommendations."
                    : `Code sent to ${email}`}
                </p>
              </div>

              {/* ============ PRODUCT IMAGES (Shopping vibe) ============ */}
              <div className="relative z-10 flex justify-center my-6">
                <div className="relative w-52 h-52">
                  {/* Main image - Bed Sheet */}
                  <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-40 h-40 rounded-2xl overflow-hidden shadow-2xl border-4 border-white/30 rotate-[-4deg]">
                    <img
                      src={linen1}
                      alt="Premium Bed Sheets"
                      className="w-full h-full object-cover"
                    />
                  </div>

                  {/* Top-right small image - Bath Towel */}
                  <div className="absolute top-0 right-0 w-24 h-24 rounded-2xl overflow-hidden shadow-2xl border-4 border-white/40 rotate-[8deg]">
                    <img
                      src={linen2}
                      alt="Bath Towels"
                      className="w-full h-full object-cover"
                    />
                  </div>

                  {/* Bottom-left small image - Bath Mat */}
                  <div className="absolute bottom-0 left-0 w-24 h-24 rounded-2xl overflow-hidden shadow-2xl border-4 border-white/40 rotate-[-10deg]">
                    <img
                      src={linen3}
                      alt="Bath Mats"
                      className="w-full h-full object-cover"
                    />
                  </div>

                  {/* Floating badge - Verified */}
                  <div className="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-[#FFE500] text-[#031D44] px-3 py-1 rounded-full shadow-lg flex items-center gap-1.5 whitespace-nowrap">
                    <FiShield size={11} />
                    <span className="text-[9px] font-bold uppercase tracking-wider">
                      Verified Quality
                    </span>
                  </div>

                  {/* Floating badge - Products count */}
                  <div className="absolute -top-2 -left-2 bg-white text-[#2874F0] px-2.5 py-1 rounded-full shadow-lg flex items-center gap-1 whitespace-nowrap">
                    <FiPackage size={10} />
                    <span className="text-[9px] font-bold uppercase tracking-wider">
                      500+ Products
                    </span>
                  </div>
                </div>
              </div>

              {/* Bottom: category chips + rating */}
              <div className="relative z-10 pt-4 border-t border-white/20 space-y-3">
                {/* Category chips */}
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

                {/* Rating */}
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
              {/* Alert */}
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

              {step === 1 ? (
                <form onSubmit={handleRequestOTP} className="space-y-5">
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-600 mb-1.5">
                      Email Address
                    </label>
                    <div className="relative group">
                      <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <FiMail
                          className="text-gray-400 group-focus-within:text-[#2874F0] transition-colors"
                          size={15}
                        />
                      </div>
                      <input
                        type="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        className="w-full pl-10 pr-4 py-3 border-b-2 border-gray-300 rounded-none text-[14px] focus:outline-none focus:border-[#2874F0] transition-all bg-transparent text-gray-800 placeholder:text-gray-400"
                        placeholder="Enter your registered email"
                        required
                      />
                    </div>
                    <p className="text-[10px] text-gray-400 mt-2">
                      We'll send a secure OTP to this email address.
                    </p>
                  </div>

                  <button
                    disabled={loading}
                    type="submit"
                    className={`w-full py-3.5 rounded-md text-[13px] font-bold text-white transition-all cursor-pointer tracking-wide uppercase shadow-md ${
                      loading
                        ? "bg-gray-400 cursor-not-allowed"
                        : "bg-[#FB641B] hover:bg-[#e55a15] hover:shadow-lg active:scale-[0.98]"
                    }`}
                  >
                    {loading ? "Sending..." : "Request OTP"}
                  </button>

                  <p className="text-[10px] text-center text-gray-400 leading-relaxed pt-1">
                    By continuing, you agree to Gateway Linen's{" "}
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
                    </a>
                  </p>
                </form>
              ) : (
                <form onSubmit={handleVerifyOTP} className="space-y-5">
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-600 mb-3 text-center">
                      Enter 6-digit OTP sent to
                      <br />
                      <span className="text-[#2874F0] font-bold text-[12px]">
                        {email}
                      </span>
                    </label>
                    <div
                      className="flex items-center justify-center gap-1.5 sm:gap-2"
                      onPaste={handleOtpPaste}
                    >
                      {otp.map((digit, i) => (
                        <input
                          key={i}
                          ref={(el) => (otpRefs.current[i] = el)}
                          type="text"
                          inputMode="numeric"
                          maxLength="1"
                          value={digit}
                          onChange={(e) => handleOtpChange(i, e.target.value)}
                          onKeyDown={(e) => handleOtpKeyDown(i, e)}
                          className="w-10 h-12 sm:w-11 sm:h-13 text-center text-lg font-bold rounded-md border-2 border-gray-200 focus:outline-none focus:border-[#2874F0] focus:ring-2 focus:ring-[#2874F0]/15 transition-all bg-white text-[#2874F0]"
                        />
                      ))}
                    </div>
                  </div>

                  <button
                    disabled={loading}
                    type="submit"
                    className="w-full py-3.5 rounded-md text-[13px] font-bold text-white transition-all cursor-pointer tracking-wide uppercase bg-[#FB641B] hover:bg-[#e55a15] hover:shadow-lg active:scale-[0.98] disabled:bg-gray-400 shadow-md"
                  >
                    {loading ? "Verifying..." : "Verify & Login"}
                  </button>

                  <div className="flex items-center justify-between pt-1">
                    <button
                      type="button"
                      onClick={resetToEmail}
                      className="text-[11px] font-semibold text-[#2874F0] hover:text-[#031D44] transition-colors"
                    >
                      ← Change Email
                    </button>
                    <button
                      type="button"
                      disabled={resendTimer > 0 || loading}
                      onClick={handleRequestOTP}
                      className={`text-[11px] font-semibold transition-colors inline-flex items-center gap-1 ${
                        resendTimer > 0 || loading
                          ? "text-gray-300 cursor-not-allowed"
                          : "text-[#FB641B] hover:text-[#031D44]"
                      }`}
                    >
                      <FiRefreshCw size={10} />
                      {resendTimer > 0
                        ? `Resend in ${resendTimer}s`
                        : "Resend OTP"}
                    </button>
                  </div>
                </form>
              )}

              {/* Divider */}
              <div className="flex items-center gap-3 my-6">
                <div className="h-px flex-1 bg-gray-200" />
                <span className="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                  New here?
                </span>
                <div className="h-px flex-1 bg-gray-200" />
              </div>

              <button
                onClick={() => navigate("/register")}
                className="w-full py-3.5 rounded-md text-[12px] font-bold text-[#2874F0] bg-white border-2 border-[#2874F0] hover:bg-[#2874F0] hover:text-white transition-all cursor-pointer uppercase tracking-wide shadow-sm active:scale-[0.98]"
              >
                Create New Account
              </button>
            </div>
          </div>

          {/* ==== TRUST BADGES ==== */}
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

          {/* Help line */}
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

      {/* ============ FOOTER ============ */}
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

export default Login;