import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import {
  FiMail,
  FiArrowLeft,
  FiLock,
  FiCheckCircle,
  FiAlertCircle,
} from "react-icons/fi";
import logo from "../assets/GatewayLinen-logo.png";

const Login = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: "", text: "" });

  const [step, setStep] = useState(1); // 1 = Enter Email, 2 = Enter OTP
  const [email, setEmail] = useState("");
  const [otp, setOtp] = useState("");
  const [otpToken, setOtpToken] = useState("");

  // Step 1: Request OTP from backend
  const handleRequestOTP = async (e) => {
    e.preventDefault();
    setMessage({ type: "", text: "" });

    if (!email || !email.includes("@")) {
      setMessage({
        type: "error",
        text: "Please enter a valid email address.",
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
            action: "send_otp",
            email: email,
          }),
        },
      );

      const result = await response.json();

      if (result.success) {
        setOtpToken(result.data.token);
        setStep(2);
        setMessage({
          type: "success",
          text: "OTP sent! Please check your email for the secure code.",
        });
      } else {
        setMessage({
          type: "error",
          text:
            result.message || "Failed to send OTP. Ensure your account exists.",
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

  // Step 2: Verify OTP and Login
  const handleVerifyOTP = async (e) => {
    e.preventDefault();
    setMessage({ type: "", text: "" });

    if (!otp || otp.length < 6) {
      setMessage({ type: "error", text: "Please enter a valid 6-digit OTP." });
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
            otp: otp,
            token: otpToken,
          }),
        },
      );

      const result = await response.json();

      if (result.success) {
        setMessage({
          type: "success",
          text: "Login successful! Securing your session...",
        });

        localStorage.setItem("user", JSON.stringify(result.data));

        setTimeout(() => {
          navigate("/");
        }, 1500);
      } else {
        setMessage({
          type: "error",
          text: result.message || "Invalid or expired OTP. Please try again.",
        });
      }
    } catch (error) {
      console.error("API Error: ", error);
      setMessage({ type: "error", text: "Server error during verification." });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen lg:h-screen w-full bg-white font-sans flex flex-col justify-center relative overflow-y-auto lg:overflow-hidden">
      {/* Return to Home Button */}
      <div className="absolute top-4 left-4 lg:top-6 lg:left-8 z-20">
        <Link
          to="/"
          className="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-[#031D44] hover:text-white transition-all bg-white hover:bg-[#031D44] border border-[#E5DCD0] px-3.5 py-2 rounded-full shadow-sm"
        >
          <FiArrowLeft size={12} />
          <span>Return to Home</span>
        </Link>
      </div>

      <div className="w-full max-w-[1050px] mx-auto px-4 sm:px-6 py-12 lg:py-0 mt-10 lg:mt-0">
        <div className="flex items-center gap-3.5 mb-5 border-b border-[#E5DCD0] pb-4">
          <div className="w-11 h-11 sm:w-12 sm:h-12 bg-white rounded-xl shadow-sm border border-[#E5DCD0] flex items-center justify-center p-1.5 overflow-hidden shrink-0">
            <img
              src={logo}
              alt="Gateway Linen"
              className="w-full h-full object-contain"
            />
          </div>
          <h1 className="text-xl sm:text-2xl lg:text-[28px] font-serif font-bold text-[#031D44]">
            Customer Secure Login
          </h1>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-14 items-stretch">
          {/* ================= LEFT SIDE: LOGIN FORM ================= */}
          <div className="flex flex-col justify-center pt-1">
            <h2 className="text-xs sm:text-sm font-bold text-[#031D44] mb-1">
              {step === 1 ? "Registered Customers" : "Verify Identity"}
            </h2>
            <p className="text-[11px] sm:text-[12px] text-gray-500 mb-4 font-light leading-relaxed">
              {step === 1
                ? "Enter your email address to receive a secure, password-less login OTP."
                : `We've sent a 6-digit secure OTP to ${email}. Please enter it below.`}
            </p>

            {message.text && (
              <div
                className={`mb-4 p-3 text-xs rounded-xl flex items-center gap-2 font-bold ${
                  message.type === "error"
                    ? "bg-red-50 border border-red-200 text-red-700"
                    : "bg-green-50 border border-green-200 text-green-700"
                }`}
              >
                {message.type === "error" ? (
                  <FiAlertCircle size={16} className="shrink-0" />
                ) : (
                  <FiCheckCircle size={16} className="shrink-0" />
                )}
                <span>{message.text}</span>
              </div>
            )}

            {step === 1 ? (
              <form onSubmit={handleRequestOTP} className="space-y-4">
                <div>
                  <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-wider mb-1.5">
                    Email Address <span className="text-red-500">*</span>
                  </label>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                      <FiMail className="text-gray-400" size={14} />
                    </div>
                    <input
                      type="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      className="w-full pl-9 pr-4 py-3 border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58] transition-colors bg-gray-50/50 shadow-inner text-gray-800"
                      placeholder="Enter your registered email"
                      required
                    />
                  </div>
                </div>

                <div className="pt-1">
                  <button
                    disabled={loading}
                    type="submit"
                    className={`w-full px-8 py-3.5 rounded-xl text-xs font-bold text-white transition-all cursor-pointer tracking-widest uppercase shadow-md flex items-center justify-center gap-2 ${
                      loading
                        ? "bg-gray-400"
                        : "bg-[#031D44] hover:bg-[#B58E58]"
                    }`}
                  >
                    {loading ? "Sending..." : "Get Secure OTP"}
                  </button>
                </div>
              </form>
            ) : (
              <form onSubmit={handleVerifyOTP} className="space-y-4">
                <div>
                  <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-wider mb-1.5">
                    Enter 6-Digit OTP <span className="text-red-500">*</span>
                  </label>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                      <FiLock className="text-gray-400" size={14} />
                    </div>
                    <input
                      type="text"
                      maxLength="6"
                      value={otp}
                      onChange={(e) =>
                        setOtp(e.target.value.replace(/[^0-9]/g, ""))
                      }
                      className="w-full pl-9 pr-4 py-3 border border-[#E5DCD0] rounded-xl text-base sm:text-lg font-bold tracking-[0.3em] focus:outline-none focus:border-[#B58E58] transition-colors bg-gray-50/50 text-center shadow-inner text-gray-800"
                      placeholder="••••••"
                      required
                    />
                  </div>
                </div>

                <div className="pt-1 flex flex-col gap-3">
                  <button
                    disabled={loading}
                    type="submit"
                    className={`w-full px-8 py-3.5 rounded-xl text-xs font-bold text-white transition-all cursor-pointer tracking-widest uppercase shadow-md flex items-center justify-center gap-2 ${
                      loading
                        ? "bg-gray-400"
                        : "bg-[#031D44] hover:bg-[#B58E58]"
                    }`}
                  >
                    {loading ? "Verifying..." : "Verify & Login"}
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      setStep(1);
                      setOtp("");
                      setMessage({ type: "", text: "" });
                    }}
                    className="text-[11px] font-bold text-gray-500 hover:text-[#031D44] underline tracking-wider uppercase transition-colors text-center"
                  >
                    Change Email Address
                  </button>
                </div>
              </form>
            )}
          </div>

          {/* ================= RIGHT SIDE: ACCOUNT INFO ================= */}
          <div className="bg-[#FAF7F2] p-5 sm:p-6 lg:p-7 rounded-[20px] border border-[#E5DCD0] flex flex-col justify-between shadow-inner h-full">
            <div>
              <h2 className="text-sm sm:text-base font-bold text-[#031D44] mb-3 pb-2 border-b border-[#E5DCD0] flex items-center gap-2">
                <FiLock className="text-[#B58E58] shrink-0" size={16} /> 100%
                Secure Authentication
              </h2>
              <p className="text-[11px] sm:text-[12px] text-gray-700 mb-4 font-light leading-relaxed">
                Welcome to Gateway Linen. To ensure the highest level of
                security for our wholesale partners, we have eliminated
                passwords.
              </p>

              <ul className="space-y-2 mb-5 text-[11px] text-gray-700 font-medium">
                <li className="flex items-center gap-2.5">
                  <span className="w-1 h-1 rounded-full bg-[#B58E58] flex-shrink-0"></span>
                  <span>No passwords to remember or get stolen</span>
                </li>
                <li className="flex items-center gap-2.5">
                  <span className="w-1 h-1 rounded-full bg-[#B58E58] flex-shrink-0"></span>
                  <span>Unique one-time passcode sent directly to you</span>
                </li>
                <li className="flex items-center gap-2.5">
                  <span className="w-1 h-1 rounded-full bg-[#B58E58] flex-shrink-0"></span>
                  <span>Protects your wholesale pricing and order history</span>
                </li>
              </ul>

              <div className="text-[11px] text-gray-700 font-light leading-relaxed bg-white p-3 rounded-xl border border-[#E5DCD0] shadow-2xs">
                If you have any questions about the account sign-in process,
                please contact us for assistance at: <br />
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

            <div className="mt-5 pt-2">
              <button
                onClick={() => navigate("/register")}
                className="w-full sm:w-auto px-8 py-2.5 rounded-xl text-[11px] font-bold text-white bg-[#B58E58] hover:bg-[#031D44] transition-colors cursor-pointer shadow-md uppercase tracking-widest"
              >
                Create New Account
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;
