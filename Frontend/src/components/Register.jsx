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
} from "react-icons/fi";
import logo from "../assets/GatewayLinen-logo.png";

const Register = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: "", text: "" });

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
        },
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
        <div className="flex items-center gap-3.5 mb-4 border-b border-[#E5DCD0] pb-3">
          <div className="w-11 h-11 sm:w-12 sm:h-12 bg-white rounded-xl shadow-sm border border-[#E5DCD0] flex items-center justify-center p-1.5 overflow-hidden shrink-0">
            <img
              src={logo}
              alt="Gateway Linen"
              className="w-full h-full object-contain"
            />
          </div>
          <h1 className="text-xl sm:text-2xl lg:text-[26px] font-serif font-bold text-[#031D44]">
            Customer Registration
          </h1>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10 items-stretch">
          {/* ================= LEFT SIDE: REGISTRATION FORM ================= */}
          <div className="flex flex-col justify-center">
            <h2 className="text-xs sm:text-sm font-bold text-[#031D44] mb-1">
              Create New Account
            </h2>
            <p className="text-[11px] sm:text-[12px] text-gray-500 mb-3 font-light leading-relaxed">
              Enter your details to get exclusive wholesale access. No password
              required.
            </p>

            {message.text && (
              <div
                className={`mb-3 p-3 text-xs rounded-xl flex items-center gap-2 font-bold ${
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

            <form onSubmit={handleRegister} className="space-y-3">
              <div>
                <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-wider mb-1">
                  Full Name <span className="text-red-500">*</span>
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <FiUser className="text-gray-400" size={13} />
                  </div>
                  <input
                    type="text"
                    name="fullName"
                    value={formData.fullName}
                    onChange={handleChange}
                    className="w-full pl-9 pr-3 py-2.5 border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58] transition-colors bg-gray-50/50 shadow-inner text-gray-800"
                    placeholder="John Doe"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-wider mb-1">
                  Email Address <span className="text-red-500">*</span>
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <FiMail className="text-gray-400" size={13} />
                  </div>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    className="w-full pl-9 pr-3 py-2.5 border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58] transition-colors bg-gray-50/50 shadow-inner text-gray-800"
                    placeholder="you@company.com"
                    required
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-wider mb-1">
                    Phone{" "}
                    <span className="text-gray-400 font-normal">
                      (Optional)
                    </span>
                  </label>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <FiPhone className="text-gray-400" size={13} />
                    </div>
                    <input
                      type="tel"
                      name="phone"
                      value={formData.phone}
                      onChange={handleChange}
                      className="w-full pl-9 pr-3 py-2.5 border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58] transition-colors bg-gray-50/50 shadow-inner text-gray-800"
                      placeholder="+1 234 567 890"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-wider mb-1">
                    Company{" "}
                    <span className="text-gray-400 font-normal">
                      (Optional)
                    </span>
                  </label>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <FiBriefcase className="text-gray-400" size={13} />
                    </div>
                    <input
                      type="text"
                      name="companyName"
                      value={formData.companyName}
                      onChange={handleChange}
                      className="w-full pl-9 pr-3 py-2.5 border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58] transition-colors bg-gray-50/50 shadow-inner text-gray-800"
                      placeholder="Your Business Name"
                    />
                  </div>
                </div>
              </div>

              <div className="pt-2">
                <button
                  disabled={loading}
                  type="submit"
                  className={`w-full px-8 py-3.5 rounded-xl text-xs font-bold text-white transition-all cursor-pointer tracking-widest uppercase shadow-md flex items-center justify-center gap-2 ${
                    loading ? "bg-gray-400" : "bg-[#031D44] hover:bg-[#B58E58]"
                  }`}
                >
                  {loading ? "Creating Account..." : "Create Account"}
                </button>
              </div>
            </form>
          </div>

          {/* ================= RIGHT SIDE: BENEFITS INFO ================= */}
          <div className="bg-[#FAF7F2] p-5 sm:p-6 rounded-[20px] border border-[#E5DCD0] flex flex-col justify-between shadow-inner h-full">
            <div>
              <h2 className="text-sm sm:text-base font-bold text-[#031D44] mb-2.5 pb-2 border-b border-[#E5DCD0] flex items-center gap-2">
                Wholesale Partner Benefits
              </h2>
              <p className="text-[11px] sm:text-[12px] text-gray-700 mb-4 font-light leading-relaxed">
                Join Gateway Linen's B2B portal to unlock exclusive commercial
                privileges and seamless digital ordering.
              </p>

              <ul className="space-y-2.5 mb-4 text-[11px] text-gray-700 font-medium">
                <li className="flex items-center gap-2.5">
                  <span className="w-1 h-1 rounded-full bg-[#B58E58] flex-shrink-0"></span>
                  <span>Instant access to wholesale commercial pricing</span>
                </li>
                <li className="flex items-center gap-2.5">
                  <span className="w-1 h-1 rounded-full bg-[#B58E58] flex-shrink-0"></span>
                  <span>Password-less secure Email OTP login flow</span>
                </li>
                <li className="flex items-center gap-2.5">
                  <span className="w-1 h-1 rounded-full bg-[#B58E58] flex-shrink-0"></span>
                  <span>Priority shipping & bulk order quote requests</span>
                </li>
              </ul>
            </div>

            <div className="mt-4 pt-2 border-t border-[#E5DCD0] flex items-center justify-between text-xs">
              <span className="text-gray-500 font-light">
                Already have an account?
              </span>
              <button
                onClick={() => navigate("/login")}
                className="font-bold text-[#031D44] hover:text-[#B58E58] uppercase tracking-wider transition-colors cursor-pointer underline"
              >
                Sign In
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Register;
