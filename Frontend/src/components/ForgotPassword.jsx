import { useState } from "react";
import { Link } from "react-router-dom";
import {
  FiMail,
  FiLock,
  FiArrowLeft,
  FiEye,
  FiEyeOff,
  FiShield,
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
    // 'lg:h-screen lg:overflow-hidden' prevents scrolling on desktop
    <div className="min-h-screen lg:h-screen w-full bg-white font-sans flex flex-col justify-center relative overflow-y-auto lg:overflow-hidden">
      {/* Return to Home Button - Top Left */}
      <div className="absolute top-4 left-4 lg:top-6 lg:left-8 z-20">
        <Link
          to="/"
          className="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-[#031D44] hover:text-white transition-all bg-white hover:bg-[#031D44] border border-[#E5DCD0] px-3.5 py-2 rounded-full shadow-sm"
        >
          <FiArrowLeft size={12} />
          <span>Return to Home</span>
        </Link>
      </div>

      <div className="w-full max-w-[1050px] mx-auto px-6 py-12 lg:py-0 mt-10 lg:mt-0">
        {/* Compact Header Section */}
        <div className="flex items-center gap-4 mb-6 border-b border-[#E5DCD0] pb-4">
          <div className="w-12 h-12 bg-white rounded-xl shadow-sm border border-[#E5DCD0] flex items-center justify-center p-1.5 overflow-hidden">
            <img
              src={logo}
              alt="Gateway Linen"
              className="w-full h-full object-contain"
            />
          </div>
          <h1 className="text-2xl lg:text-[28px] font-serif font-bold text-[#031D44]">
            Password Recovery
          </h1>
        </div>

        {/* Main 2-Column Grid Layout */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-14 items-stretch">
          {/* ================= LEFT SIDE: BRANDING, SECURITY & STEPS ================= */}
          <div className="bg-[#FAF7F2] p-6 lg:p-8 rounded-[20px] border border-[#E5DCD0] flex flex-col justify-between shadow-inner h-full">
            <div>
              <div className="inline-flex items-center gap-2 bg-[#B58E58]/10 px-3 py-1 rounded-full mb-4 border border-[#B58E58]/20">
                <FiShield className="text-[#B58E58]" size={14} />
                <span className="text-[#B58E58] text-[9px] font-bold tracking-[0.2em] uppercase">
                  Data Protection
                </span>
              </div>

              <h2 className="text-lg font-bold text-[#031D44] mb-3 pb-3 border-b border-[#E5DCD0]">
                Strictly Secure Recovery
              </h2>

              <p className="text-[12px] text-gray-600 mb-6 font-light leading-relaxed">
                Your privacy and account security are our highest priority.
                Gateway Linen uses advanced encrypted multi-step verification to
                ensure that only authorized users can regain access to wholesale
                accounts.
              </p>

              {/* Dynamic Steps Info */}
              <div className="space-y-4 mb-8">
                <div
                  className={`flex items-start gap-3 transition-opacity ${step === 1 ? "opacity-100" : "opacity-50"}`}
                >
                  <div
                    className={`w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-sm transition-colors ${step >= 1 ? "bg-[#031D44] text-white border-transparent" : "bg-white border border-[#E5DCD0] text-[#B58E58]"}`}
                  >
                    {step > 1 ? <FiCheck size={12} /> : 1}
                  </div>
                  <div>
                    <h4
                      className={`text-[12px] font-bold ${step === 1 ? "text-[#031D44]" : "text-gray-500"}`}
                    >
                      Email Verification
                    </h4>
                    <p className="text-[11px] text-gray-500 font-light mt-0.5">
                      Enter your registered B2B email to receive a secure link.
                    </p>
                  </div>
                </div>

                <div
                  className={`flex items-start gap-3 transition-opacity ${step === 2 ? "opacity-100" : step > 2 ? "opacity-50" : "opacity-40"}`}
                >
                  <div
                    className={`w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-sm transition-colors ${step >= 2 ? "bg-[#031D44] text-white border-transparent" : "bg-white border border-[#E5DCD0] text-[#B58E58]"}`}
                  >
                    {step > 2 ? <FiCheck size={12} /> : 2}
                  </div>
                  <div>
                    <h4
                      className={`text-[12px] font-bold ${step === 2 ? "text-[#031D44]" : "text-gray-500"}`}
                    >
                      OTP Validation
                    </h4>
                    <p className="text-[11px] text-gray-500 font-light mt-0.5">
                      Enter the 6-digit one-time passcode sent to your inbox.
                    </p>
                  </div>
                </div>

                <div
                  className={`flex items-start gap-3 transition-opacity ${step === 3 ? "opacity-100" : "opacity-40"}`}
                >
                  <div
                    className={`w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-sm transition-colors ${step === 3 ? "bg-[#031D44] text-white border-transparent" : "bg-white border border-[#E5DCD0] text-[#B58E58]"}`}
                  >
                    3
                  </div>
                  <div>
                    <h4
                      className={`text-[12px] font-bold ${step === 3 ? "text-[#031D44]" : "text-gray-500"}`}
                    >
                      Secure Reset
                    </h4>
                    <p className="text-[11px] text-gray-500 font-light mt-0.5">
                      Create and confirm a new strong password to regain access.
                    </p>
                  </div>
                </div>
              </div>

              <div className="text-[11px] text-gray-700 font-light leading-relaxed bg-white p-3.5 rounded-xl border border-[#E5DCD0] shadow-2xs">
                Need manual assistance? Contact our support team at: <br />
                <strong className="font-bold text-[#031D44] text-[12px] mt-1 inline-block">
                  1-800-661-7239
                </strong>{" "}
                <br />
                or email:{" "}
                <a
                  href="mailto:gatewaylinen@gmail.com"
                  className="font-bold text-[#B58E58] hover:text-[#031D44] transition-colors mt-0.5 inline-block"
                >
                  gatewaylinen@gmail.com
                </a>
              </div>
            </div>
          </div>

          {/* ================= RIGHT SIDE: RECOVERY FORM ================= */}
          <div className="flex flex-col justify-center pt-2">
            <h2 className="text-sm font-bold text-[#031D44] mb-1">
              {step === 1 && "Forgot your password?"}
              {step === 2 && "Enter Verification OTP"}
              {step === 3 && "Create New Password"}
            </h2>
            <p className="text-[12px] text-gray-500 mb-5 font-light">
              {step === 1 &&
                "Enter your account email to receive a 6-digit OTP."}
              {step === 2 && `Enter the 6-digit code sent to ${email}`}
              {step === 3 && "Enter your new strong password below."}
            </p>

            {message.text && (
              <div
                className={`mb-4 text-[11px] p-2.5 rounded-lg border ${message.type === "error" ? "bg-red-50 text-red-600 border-red-200" : "bg-green-50 text-green-700 border-green-200"}`}
              >
                {message.text}
              </div>
            )}

            {/* STEP 1: EMAIL FORM */}
            {step === 1 && (
              <form className="space-y-4" onSubmit={handleSendOTP}>
                <div>
                  <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-wider mb-1.5">
                    Email address
                  </label>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                      <FiMail className="text-gray-400" size={14} />
                    </div>
                    <input
                      type="email"
                      required
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      className="w-full pl-9 pr-4 py-2.5 border border-[#E5DCD0] rounded-xl text-[12px] focus:outline-none focus:border-[#B58E58] transition-colors bg-gray-50/50"
                      placeholder="you@company.com"
                    />
                  </div>
                </div>

                <div className="pt-2">
                  <button
                    disabled={loading}
                    type="submit"
                    className={`w-full flex justify-center py-3 px-4 rounded-xl shadow-md text-[11px] font-bold tracking-widest uppercase text-white ${loading ? "bg-gray-400" : "bg-[#031D44] hover:bg-[#B58E58]"} transition-all cursor-pointer`}
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
                  <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-wider mb-1.5">
                    6-Digit OTP Code
                  </label>
                  <div className="relative">
                    <input
                      type="text"
                      required
                      maxLength="6"
                      value={otp}
                      onChange={(e) =>
                        setOtp(e.target.value.replace(/\D/g, ""))
                      }
                      className="block w-full py-3 border border-[#E5DCD0] rounded-xl text-center text-xl tracking-[0.5em] text-gray-800 placeholder-gray-300 focus:outline-none focus:border-[#B58E58] transition-all bg-gray-50/50 font-bold"
                      placeholder="123456"
                    />
                  </div>
                </div>

                <div className="pt-2 flex flex-col gap-3">
                  <button
                    disabled={loading}
                    type="submit"
                    className={`w-full flex justify-center py-3 px-4 rounded-xl shadow-md text-[11px] font-bold tracking-widest uppercase text-white ${loading ? "bg-gray-400" : "bg-[#031D44] hover:bg-[#B58E58]"} transition-all cursor-pointer`}
                  >
                    {loading ? "Verifying..." : "Verify OTP"}
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      setStep(1);
                      setOtp("");
                    }}
                    className="w-full text-[11px] font-bold text-[#B58E58] hover:text-[#031D44] transition-colors py-1 cursor-pointer"
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
                  <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-wider mb-1.5">
                    New Password
                  </label>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                      <FiLock className="text-gray-400" size={14} />
                    </div>
                    <input
                      type={showPassword ? "text" : "password"}
                      required
                      minLength="6"
                      value={newPassword}
                      onChange={(e) => setNewPassword(e.target.value)}
                      className="w-full pl-9 pr-10 py-2.5 border border-[#E5DCD0] rounded-xl text-[12px] focus:outline-none focus:border-[#B58E58] transition-colors bg-gray-50/50"
                      placeholder="Enter new password"
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-[#B58E58] cursor-pointer"
                    >
                      {showPassword ? (
                        <FiEyeOff size={14} />
                      ) : (
                        <FiEye size={14} />
                      )}
                    </button>
                  </div>
                </div>

                <div className="pt-2">
                  <button
                    disabled={loading}
                    type="submit"
                    className={`w-full flex justify-center py-3 px-4 rounded-xl shadow-md text-[11px] font-bold tracking-widest uppercase text-white ${loading ? "bg-gray-400" : "bg-[#031D44] hover:bg-[#B58E58]"} transition-all cursor-pointer`}
                  >
                    {loading ? "Updating..." : "Confirm Password"}
                  </button>
                </div>
              </form>
            )}

            {step === 1 && (
              <div className="mt-8 text-center border-t border-[#E5DCD0] pt-6">
                <Link
                  to="/login"
                  className="text-[11px] font-bold text-[#031D44] hover:text-[#B58E58] transition-colors uppercase tracking-widest"
                >
                  ← Back to sign in
                </Link>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Success Popup Modal */}
      {showPopup && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-white p-8 rounded-[24px] shadow-2xl max-w-sm w-full text-center border border-[#E5DCD0] relative">
            <div className="w-14 h-14 bg-[#031D44] text-[#B58E58] rounded-xl flex items-center justify-center mx-auto mb-4 shadow-md">
              <svg
                className="w-7 h-7 text-[#B58E58]"
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
            <p className="text-gray-500 text-[12px] mb-6 font-light leading-relaxed">
              Your new password has been changed successfully. A confirmation
              email has been sent to your inbox.
            </p>
            <button
              onClick={() => (window.location.href = "/login")}
              className="w-full bg-[#031D44] hover:bg-[#B58E58] text-white py-3 rounded-xl font-bold text-[11px] uppercase tracking-widest transition-all shadow-md cursor-pointer"
            >
              Back to Login
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

// Simple FiCheck icon component used in the steps
const FiCheck = ({ size }) => (
  <svg
    width={size}
    height={size}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="3"
    strokeLinecap="round"
    strokeLinejoin="round"
  >
    <polyline points="20 6 9 17 4 12"></polyline>
  </svg>
);

export default ForgotPassword;
