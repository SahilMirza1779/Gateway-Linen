import { useState } from "react";
import { Link } from "react-router-dom";
import {
  FiMail,
  FiLock,
  FiArrowLeft,
  FiEye,
  FiEyeOff,
  FiShield,
  FiBox,
  FiShoppingBag,
  FiHeadphones,
} from "react-icons/fi";
import logo from "../assets/GatewayLinen-logo.png";

const ForgotPassword = () => {
  const [step, setStep] = useState(1); // 1: Email, 2: OTP, 3: New Password
  const [email, setEmail] = useState("");
  const [otp, setOtp] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [resetToken, setResetToken] = useState("");
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: "", text: "" });
  const [showPopup, setShowPopup] = useState(false);

  const API_URL =
    "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/users/api.php";
  const API_KEY = "GatewayLinen@2026";

  const handleSendOTP = async (e) => {
    e.preventDefault();
    setMessage({ type: "", text: "" });
    setLoading(true);
    try {
      const response = await fetch(API_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-API-KEY": API_KEY },
        body: JSON.stringify({ entity: "user", action: "send_otp", email }),
      });
      const result = await response.json();
      if (result.success) {
        setResetToken(result.data.token);
        setStep(2);
        setMessage({
          type: "success",
          text: "6-digit OTP sent to your email.",
        });
      } else {
        setMessage({
          type: "error",
          text: result.message || "Failed to send OTP.",
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

  const handleVerifyOTP = async (e) => {
    e.preventDefault();
    setMessage({ type: "", text: "" });
    setLoading(true);
    try {
      const response = await fetch(API_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-API-KEY": API_KEY },
        body: JSON.stringify({
          entity: "user",
          action: "verify_otp",
          email,
          otp,
          token: resetToken,
        }),
      });
      const result = await response.json();
      if (result.success) {
        setStep(3);
        setMessage({ type: "success", text: "OTP verified successfully." });
      } else {
        setMessage({
          type: "error",
          text: result.message || "Invalid or expired OTP.",
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

  const handleResetPassword = async (e) => {
    e.preventDefault();
    setMessage({ type: "", text: "" });
    setLoading(true);
    try {
      const response = await fetch(API_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-API-KEY": API_KEY },
        body: JSON.stringify({
          entity: "user",
          action: "reset_password",
          email,
          otp,
          newPassword,
          token: resetToken,
        }),
      });
      const result = await response.json();
      if (result.success) {
        setShowPopup(true);
      } else {
        setMessage({
          type: "error",
          text: result.message || "Failed to reset password.",
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
    <div className="min-h-screen w-full flex flex-col lg:flex-row font-sans bg-[#F0EAE1] relative overflow-hidden">
      {/* Global Light Background Glow */}
      <div className="absolute top-1/2 left-1/4 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] lg:w-[900px] lg:h-[900px] bg-[#B58E58]/10 rounded-full blur-[120px] pointer-events-none z-0"></div>

      {/* LEFT COLUMN - BRANDING (Matches Login/Register Theme) */}
      <div className="w-full lg:w-[55%] flex flex-col justify-center px-6 pt-10 pb-4 lg:p-16 xl:px-24 z-10">
        {/* Logo */}
        <Link
          to="/"
          className="inline-block w-[60px] h-[60px] lg:w-[80px] lg:h-[80px] bg-white rounded-2xl shadow-sm flex items-center justify-center p-1.5 overflow-hidden border border-[#E5DCD0] mb-6 lg:mb-8 hover:border-[#B58E58] hover:shadow-md transition-all"
        >
          <img
            src={logo}
            alt="Gateway Linen"
            className="w-full h-full object-contain rounded-xl"
          />
        </Link>

        {/* Brand Name & Slogan */}
        <h1 className="text-3xl sm:text-4xl lg:text-6xl font-serif font-bold text-[#031D44] mb-2 tracking-tight">
          Gateway<span className="text-[#B58E58]">Linen</span>
        </h1>

        <div className="flex items-center gap-2 mb-4 lg:mb-6">
          <div className="w-1.5 h-1.5 bg-[#B58E58] rounded-full"></div>
          <p className="text-[#B58E58] text-[10px] lg:text-xs font-bold tracking-[0.25em] uppercase">
            Premium Linen Solutions
          </p>
        </div>

        {/* Desktop Only Extra Text & Grid */}
        <div className="hidden lg:block mt-2">
          <p className="text-gray-600 text-sm leading-relaxed max-w-md mb-10 font-light">
            Secure password recovery portal. Follow the simple steps to verify
            your email via OTP and secure your account with a new password.
          </p>

          <div className="grid grid-cols-2 gap-4 max-w-lg">
            <div className="flex items-center gap-3 bg-white border border-[#E5DCD0] p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow hover:border-[#B58E58]/40">
              <FiShield className="text-[#B58E58]" size={18} />
              <span className="text-[#031D44] text-xs font-bold tracking-wide">
                Secure Access
              </span>
            </div>
            <div className="flex items-center gap-3 bg-white border border-[#E5DCD0] p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow hover:border-[#B58E58]/40">
              <FiShoppingBag className="text-[#B58E58]" size={18} />
              <span className="text-[#031D44] text-xs font-bold tracking-wide">
                Order Management
              </span>
            </div>
            <div className="flex items-center gap-3 bg-white border border-[#E5DCD0] p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow hover:border-[#B58E58]/40">
              <FiBox className="text-[#B58E58]" size={18} />
              <span className="text-[#031D44] text-xs font-bold tracking-wide">
                Wholesale Catalog
              </span>
            </div>
            <div className="flex items-center gap-3 bg-white border border-[#E5DCD0] p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow hover:border-[#B58E58]/40">
              <FiHeadphones className="text-[#B58E58]" size={18} />
              <span className="text-[#031D44] text-xs font-bold tracking-wide">
                Priority Support
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* RIGHT COLUMN - FORM PANEL */}
      <div className="w-full lg:w-[45%] flex flex-col items-center lg:items-end justify-center px-4 pb-10 pt-4 lg:p-12 z-10 relative">
        {/* Return to Home Button */}
        <div className="w-full max-w-[420px] mb-4 flex justify-end lg:justify-start lg:mb-6">
          <Link
            to="/"
            className="inline-flex items-center gap-2 text-[10px] lg:text-[11px] font-bold uppercase tracking-widest text-[#031D44] hover:text-white transition-all bg-white hover:bg-[#031D44] border border-[#E5DCD0] hover:border-[#031D44] px-4 py-2.5 rounded-full shadow-sm hover:shadow-md"
          >
            <FiArrowLeft size={14} />
            <span>Return to Home</span>
          </Link>
        </div>

        {/* Success Popup Modal */}
        {showPopup && (
          <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-white p-8 rounded-[28px] shadow-2xl max-w-sm w-full text-center border border-[#E5DCD0] relative">
              <div className="w-16 h-16 bg-[#031D44] text-[#B58E58] rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-md">
                <svg
                  className="w-8 h-8 text-[#B58E58]"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="2.5"
                    d="M5 13l4 4L19 7"
                  ></path>
                </svg>
              </div>
              <h3 className="text-xl font-serif font-bold text-[#031D44] mb-2">
                Password Updated!
              </h3>
              <p className="text-gray-500 text-xs mb-6 font-light">
                Your new password has been changed successfully. A confirmation
                email has been sent.
              </p>
              <button
                onClick={() => (window.location.href = "/login")}
                className="w-full bg-[#031D44] hover:bg-[#B58E58] text-white py-3.5 rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-md cursor-pointer"
              >
                Back to Login
              </button>
            </div>
          </div>
        )}

        {/* Forgot Password Card */}
        <div className="w-full max-w-[420px] bg-white py-8 px-6 sm:py-10 sm:px-10 shadow-[0_20px_50px_rgba(0,0,0,0.06)] rounded-[28px] lg:rounded-[32px] border border-[#E5DCD0] relative z-20">
          <div className="mb-6">
            <div className="inline-flex items-center gap-2 bg-[#B58E58]/10 px-3 py-1 rounded-full mb-3 border border-[#B58E58]/20">
              <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
              <span className="text-[#B58E58] text-[9px] font-bold tracking-[0.2em] uppercase">
                Password Help
              </span>
            </div>
            <h2 className="text-2xl sm:text-3xl font-serif font-bold text-[#031D44] mb-1.5 leading-tight">
              {step === 1 && "Forgot your password?"}
              {step === 2 && "Enter Verification OTP"}
              {step === 3 && "Create New Password"}
            </h2>
            <p className="text-xs text-gray-500 font-light">
              {step === 1 &&
                "Enter your account email to receive a 6-digit OTP."}
              {step === 2 && `Enter the 6-digit code sent to ${email}`}
              {step === 3 && "Enter your new strong password below."}
            </p>
          </div>

          {/* Messages Alert */}
          {message.text && (
            <div
              className={`mb-4 text-center text-xs font-bold p-3 rounded-xl ${message.type === "error" ? "bg-red-50 text-red-600 border border-red-200" : "bg-green-50 text-green-700 border border-green-200"}`}
            >
              {message.text}
            </div>
          )}

          {/* STEP 1: EMAIL FORM */}
          {step === 1 && (
            <form className="space-y-4" onSubmit={handleSendOTP}>
              <div>
                <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                  Email address
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <FiMail className="text-gray-400" size={15} />
                  </div>
                  <input
                    type="email"
                    required
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className="block w-full pl-10 pr-3 py-3 border border-[#E5DCD0] rounded-xl text-xs sm:text-[13px] text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#B58E58] focus:ring-1 focus:ring-[#B58E58] transition-all bg-[#FAF7F2] shadow-sm"
                    placeholder="you@company.com"
                  />
                </div>
              </div>

              <div className="pt-2">
                <button
                  disabled={loading}
                  type="submit"
                  className={`w-full flex justify-center py-3.5 px-4 rounded-xl shadow-md text-xs font-bold tracking-widest uppercase text-white ${loading ? "bg-gray-400" : "bg-[#031D44] hover:bg-[#B58E58]"} transition-all cursor-pointer`}
                >
                  {loading ? "Sending OTP..." : "Send OTP"}
                </button>
              </div>
            </form>
          )}

          {/* STEP 2: OTP FORM */}
          {step === 2 && (
            <form className="space-y-4" onSubmit={handleVerifyOTP}>
              <div>
                <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                  6-Digit OTP Code
                </label>
                <div className="relative">
                  <input
                    type="text"
                    required
                    maxLength="6"
                    value={otp}
                    onChange={(e) => setOtp(e.target.value.replace(/\D/g, ""))}
                    className="block w-full py-3 border border-[#E5DCD0] rounded-xl text-center text-xl tracking-[0.5em] text-gray-800 placeholder-gray-300 focus:outline-none focus:border-[#B58E58] focus:ring-1 focus:ring-[#B58E58] transition-all bg-[#FAF7F2] shadow-sm font-bold"
                    placeholder="123456"
                  />
                </div>
              </div>

              <div className="pt-2 flex flex-col gap-2.5">
                <button
                  disabled={loading}
                  type="submit"
                  className={`w-full flex justify-center py-3.5 px-4 rounded-xl shadow-md text-xs font-bold tracking-widest uppercase text-white ${loading ? "bg-gray-400" : "bg-[#031D44] hover:bg-[#B58E58]"} transition-all cursor-pointer`}
                >
                  {loading ? "Verifying..." : "Verify OTP"}
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setStep(1);
                    setOtp("");
                  }}
                  className="w-full text-xs font-bold text-[#B58E58] hover:text-[#031D44] transition-colors py-1 cursor-pointer"
                >
                  ← Change email address
                </button>
              </div>
            </form>
          )}

          {/* STEP 3: NEW PASSWORD FORM */}
          {step === 3 && (
            <form className="space-y-4" onSubmit={handleResetPassword}>
              <div>
                <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                  New Password
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <FiLock className="text-gray-400" size={15} />
                  </div>
                  <input
                    type={showPassword ? "text" : "password"}
                    required
                    minLength="6"
                    value={newPassword}
                    onChange={(e) => setNewPassword(e.target.value)}
                    className="block w-full pl-10 pr-10 py-3 border border-[#E5DCD0] rounded-xl text-xs sm:text-[13px] text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#B58E58] focus:ring-1 focus:ring-[#B58E58] transition-all bg-[#FAF7F2] shadow-sm"
                    placeholder="Enter new password"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-[#B58E58] transition-colors cursor-pointer"
                  >
                    {showPassword ? (
                      <FiEyeOff size={15} />
                    ) : (
                      <FiEye size={15} />
                    )}
                  </button>
                </div>
              </div>

              <div className="pt-2">
                <button
                  disabled={loading}
                  type="submit"
                  className={`w-full flex justify-center py-3.5 px-4 rounded-xl shadow-md text-xs font-bold tracking-widest uppercase text-white ${loading ? "bg-gray-400" : "bg-[#031D44] hover:bg-[#B58E58]"} transition-all cursor-pointer`}
                >
                  {loading ? "Updating..." : "Confirm Password"}
                </button>
              </div>
            </form>
          )}

          {step === 1 && (
            <div className="mt-6 text-center border-t border-[#E5DCD0] pt-6">
              <Link
                to="/login"
                className="text-xs font-bold text-[#031D44] hover:text-[#B58E58] transition-colors uppercase tracking-wider"
              >
                ← Back to sign in
              </Link>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default ForgotPassword;
