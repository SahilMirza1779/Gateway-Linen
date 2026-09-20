import { useState } from "react";
import { Link } from "react-router-dom";
import { FiMail, FiLock, FiArrowLeft, FiEye, FiEyeOff } from "react-icons/fi";
import { BsMoonStars, BsStars } from "react-icons/bs";
import { GiFeather } from "react-icons/gi";
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
    "http://localhost/GatewayLinen/GatewayLinenadmin-main/users/api.php";
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
    <div className="h-screen w-full bg-gradient-to-tr from-[#F4F6F9] via-[#FAF9F6] to-[#F0EFEA] flex items-center justify-center font-sans relative overflow-hidden">
      {/* Background Decor Elements matching Login */}
      <div className="absolute top-[10%] left-[15%] w-72 h-72 bg-[#B58E58]/10 rounded-full blur-[80px] pointer-events-none"></div>
      <div className="absolute bottom-[15%] right-[15%] w-96 h-96 bg-[#031D44]/5 rounded-full blur-[100px] pointer-events-none"></div>

      <div className="absolute top-12 left-[18%] text-[#B58E58]/40 animate-pulse">
        <BsMoonStars size={22} />
      </div>
      <div className="absolute top-24 right-[20%] text-[#031D44]/30">
        <BsStars size={24} />
      </div>
      <div className="absolute bottom-20 left-[24%] text-[#B58E58]/35">
        <GiFeather size={26} className="rotate-45" />
      </div>
      <div className="absolute bottom-28 right-[22%] text-[#031D44]/25">
        <BsStars size={18} />
      </div>

      <Link
        to="/"
        className="absolute top-6 left-6 flex items-center gap-2 text-[13px] font-semibold text-gray-700 hover:text-[#031D44] transition-all bg-white/80 px-4 py-2.5 rounded-full shadow-sm border border-gray-200 backdrop-blur-md z-50 hover:shadow-md hover:-translate-x-1"
      >
        <FiArrowLeft size={16} />
        <span>Return to Home</span>
      </Link>

      {/* Success Popup Modal */}
      {showPopup && (
        <div className="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-white p-8 rounded-3xl shadow-2xl max-w-sm w-full text-center border border-gray-100 transform transition-all scale-100">
            <div className="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-green-100">
              <svg
                className="w-8 h-8 text-green-500"
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
            <p className="text-gray-500 text-xs mb-6">
              Your new password has been changed successfully. A confirmation
              email has been sent.
            </p>
            <button
              onClick={() => (window.location.href = "/login")}
              className="w-full bg-[#B58E58] hover:bg-[#9E7A4A] text-white py-3 rounded-xl font-semibold text-xs transition-colors shadow-md shadow-[#B58E58]/20"
            >
              Back to Login
            </button>
          </div>
        </div>
      )}

      <div className="w-full max-w-[420px] z-10 px-4">
        <div className="bg-white py-8 px-6 shadow-[0_20px_50px_rgb(0,0,0,0.08)] rounded-3xl sm:px-10 border border-gray-100">
          {/* Header Logo Box matching Login */}
          <div className="flex justify-center items-center gap-3 mb-6 pb-6 border-b border-gray-100">
            <Link to="/" className="shrink-0">
              <div className="w-[55px] h-[55px] bg-white rounded-full shadow-sm flex items-center justify-center p-1 overflow-hidden hover:border-[#B58E58] border border-gray-100 transition-colors cursor-pointer">
                <img
                  src={logo}
                  alt="Gateway Linen"
                  className="w-full h-full object-contain rounded-full"
                />
              </div>
            </Link>
            <div className="flex flex-col justify-center border-l-2 border-gray-200 pl-3">
              <h2 className="text-[16px] font-serif font-bold text-[#031D44] tracking-wide leading-tight">
                GATEWAY LINEN
              </h2>
              <p className="text-[8.5px] text-gray-500 uppercase tracking-[0.2em] mt-0.5 font-medium">
                Hospitality Supply
              </p>
            </div>
          </div>

          <div className="mb-6 text-center">
            <span className="text-[#B58E58] text-[10px] font-bold tracking-[0.2em] uppercase">
              Password Help
            </span>
            <h2 className="text-[24px] font-serif font-bold text-[#031D44] mt-1 leading-tight">
              {step === 1 && "Forgot your password?"}
              {step === 2 && "Enter Verification OTP"}
              {step === 3 && "Create New Password"}
            </h2>
            <p className="text-gray-500 text-xs mt-1">
              {step === 1 &&
                "Enter your account email to receive a 6-digit OTP."}
              {step === 2 && `Enter the 6-digit code sent to ${email}`}
              {step === 3 && "Enter your new strong password below."}
            </p>
          </div>

          {/* Messages Alert */}
          {message.text && (
            <div
              className={`mb-4 text-center text-xs font-bold p-2.5 rounded-lg ${message.type === "error" ? "bg-red-50 text-red-600 border border-red-100" : "bg-green-50 text-green-600 border border-green-100"}`}
            >
              {message.text}
            </div>
          )}

          {/* STEP 1: EMAIL FORM */}
          {step === 1 && (
            <form className="space-y-4" onSubmit={handleSendOTP}>
              <div>
                <label className="block text-[10.5px] font-bold text-gray-700 uppercase tracking-widest mb-1.5">
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
                    className="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl text-[13.5px] text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#B58E58] focus:border-[#B58E58] transition-all bg-white shadow-sm"
                    placeholder="you@company.com"
                  />
                </div>
              </div>

              <div className="pt-3">
                <button
                  disabled={loading}
                  type="submit"
                  className={`w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-md shadow-[#B58E58]/20 text-[13.5px] font-semibold text-white ${loading ? "bg-gray-400" : "bg-[#B58E58] hover:bg-[#9E7A4A]"} focus:outline-none transition-all`}
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
                <label className="block text-[10.5px] font-bold text-gray-700 uppercase tracking-widest mb-1.5">
                  6-Digit OTP Code
                </label>
                <div className="relative">
                  <input
                    type="text"
                    required
                    maxLength="6"
                    value={otp}
                    onChange={(e) => setOtp(e.target.value.replace(/\D/g, ""))}
                    className="block w-full py-2.5 border border-gray-200 rounded-xl text-center text-xl tracking-[0.5em] text-gray-800 placeholder-gray-300 focus:outline-none focus:ring-1 focus:ring-[#B58E58] focus:border-[#B58E58] transition-all bg-white shadow-sm font-bold"
                    placeholder="123456"
                  />
                </div>
              </div>

              <div className="pt-3 flex flex-col gap-2">
                <button
                  disabled={loading}
                  type="submit"
                  className={`w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-md shadow-[#B58E58]/20 text-[13.5px] font-semibold text-white ${loading ? "bg-gray-400" : "bg-[#B58E58] hover:bg-[#9E7A4A]"} focus:outline-none transition-all`}
                >
                  {loading ? "Verifying..." : "Verify OTP"}
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setStep(1);
                    setOtp("");
                  }}
                  className="w-full text-xs text-gray-500 hover:text-[#031D44] transition-colors py-1"
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
                <label className="block text-[10.5px] font-bold text-gray-700 uppercase tracking-widest mb-1.5">
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
                    className="block w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-xl text-[13.5px] text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#B58E58] focus:border-[#B58E58] transition-all bg-white shadow-sm"
                    placeholder="Enter new password"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-[#B58E58] transition-colors"
                  >
                    {showPassword ? (
                      <FiEyeOff size={15} />
                    ) : (
                      <FiEye size={15} />
                    )}
                  </button>
                </div>
              </div>

              <div className="pt-3">
                <button
                  disabled={loading}
                  type="submit"
                  className={`w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-md shadow-[#B58E58]/20 text-[13.5px] font-semibold text-white ${loading ? "bg-gray-400" : "bg-[#B58E58] hover:bg-[#9E7A4A]"} focus:outline-none transition-all`}
                >
                  {loading ? "Updating..." : "Confirm Password"}
                </button>
              </div>
            </form>
          )}

          {step === 1 && (
            <div className="mt-6 text-center">
              <Link
                to="/login"
                className="text-xs font-medium text-gray-600 hover:text-[#031D44] transition-colors"
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
