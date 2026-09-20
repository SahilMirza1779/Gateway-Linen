import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import {
  FiUser,
  FiMail,
  FiPhone,
  FiLock,
  FiArrowLeft,
  FiEye,
  FiEyeOff,
} from "react-icons/fi";
import { BsMoonStars, BsStars } from "react-icons/bs";
import { GiFeather } from "react-icons/gi";
import logo from "../assets/GatewayLinen-logo.png";

const Register = () => {
  const navigate = useNavigate();
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: "", text: "" });

  // Form states (Removed companyName)
  const [formData, setFormData] = useState({
    fullName: "",
    email: "",
    phone: "",
    password: "",
    confirmPassword: "",
  });

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleRegister = async (e) => {
    e.preventDefault();
    setMessage({ type: "", text: "" });

    if (formData.password !== formData.confirmPassword) {
      setMessage({ type: "error", text: "Passwords do not match!" });
      return;
    }

    setLoading(true);

    try {
      const payload = {
        entity: "user",
        action: "insert",
        fullName: formData.fullName,
        email: formData.email,
        phone: formData.phone,
        password: formData.password,
      };

      const response = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/users/api.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-API-KEY": "GatewayLinen@2026",
          },
          body: JSON.stringify(payload),
        },
      );

      const result = await response.json();

      if (result.success) {
        setMessage({
          type: "success",
          text: "Account created successfully! Redirecting to login...",
        });

        setTimeout(() => {
          navigate("/login");
        }, 2000);
      } else {
        setMessage({
          type: "error",
          text: result.message || "Registration failed.",
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

      <div className="w-full max-w-[600px] z-10 px-4">
        <div className="bg-white py-6 px-6 shadow-[0_20px_50px_rgb(0,0,0,0.08)] rounded-3xl sm:px-10 border border-gray-100">
          <div className="flex justify-center items-center gap-3 mb-4 pb-4 border-b border-gray-100">
            <Link to="/" className="shrink-0">
              <div className="w-[50px] h-[50px] bg-white rounded-full shadow-sm flex items-center justify-center p-1 overflow-hidden hover:border-[#B58E58] border border-gray-100 transition-colors cursor-pointer">
                <img
                  src={logo}
                  alt="Gateway Linen"
                  className="w-full h-full object-contain rounded-full"
                />
              </div>
            </Link>
            <div className="flex flex-col justify-center border-l-2 border-gray-200 pl-3">
              <h2 className="text-[15px] font-serif font-bold text-[#031D44] tracking-wide leading-tight">
                GATEWAY LINEN
              </h2>
              <p className="text-[8px] text-gray-500 uppercase tracking-[0.2em] mt-0.5 font-medium">
                Hospitality Supply
              </p>
            </div>
          </div>

          <div className="mb-5 text-center">
            <span className="text-[#B58E58] text-[9.5px] font-bold tracking-[0.2em] uppercase">
              Create Account
            </span>
            <h2 className="text-[22px] font-serif font-bold text-[#031D44] mt-1 leading-tight">
              Join Gateway Linen
            </h2>
          </div>

          {message.text && (
            <div
              className={`mb-4 text-center text-xs font-bold p-2.5 rounded-lg ${message.type === "error" ? "bg-red-50 text-red-600 border border-red-100" : "bg-green-50 text-green-600 border border-green-100"}`}
            >
              {message.text}
            </div>
          )}

          <form className="space-y-4" onSubmit={handleRegister}>
            {/* Full Name - Full Width */}
            <div>
              <label className="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1.5">
                Full name
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <FiUser className="text-gray-400" size={14} />
                </div>
                <input
                  type="text"
                  name="fullName"
                  value={formData.fullName}
                  onChange={handleChange}
                  className="block w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-[13px] focus:outline-none focus:ring-1 focus:ring-[#B58E58] focus:border-[#B58E58] transition-all bg-white shadow-sm"
                  placeholder="John Smith"
                  required
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {/* Email */}
              <div>
                <label className="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1.5">
                  Email address
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <FiMail className="text-gray-400" size={14} />
                  </div>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    className="block w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-[13px] focus:outline-none focus:ring-1 focus:ring-[#B58E58] focus:border-[#B58E58] transition-all bg-white shadow-sm"
                    placeholder="you@company.com"
                    required
                  />
                </div>
              </div>

              {/* Phone */}
              <div>
                <label className="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1.5">
                  Phone
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <FiPhone className="text-gray-400" size={14} />
                  </div>
                  <input
                    type="tel"
                    name="phone"
                    value={formData.phone}
                    onChange={handleChange}
                    className="block w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-[13px] focus:outline-none focus:ring-1 focus:ring-[#B58E58] focus:border-[#B58E58] transition-all bg-white shadow-sm"
                    placeholder="+1 416 555 0123"
                  />
                </div>
              </div>

              {/* Password */}
              <div>
                <label className="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1.5">
                  Password
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <FiLock className="text-gray-400" size={14} />
                  </div>
                  <input
                    type={showPassword ? "text" : "password"}
                    name="password"
                    value={formData.password}
                    onChange={handleChange}
                    className="block w-full pl-9 pr-10 py-2 border border-gray-200 rounded-xl text-[13px] focus:outline-none focus:ring-1 focus:ring-[#B58E58] focus:border-[#B58E58] transition-all bg-white shadow-sm"
                    placeholder="Minimum 6 chars"
                    required
                    minLength="6"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-[#B58E58] transition-colors"
                  >
                    {showPassword ? (
                      <FiEyeOff size={14} />
                    ) : (
                      <FiEye size={14} />
                    )}
                  </button>
                </div>
              </div>

              {/* Confirm Password */}
              <div>
                <label className="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1.5">
                  Confirm password
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <FiLock className="text-gray-400" size={14} />
                  </div>
                  <input
                    type={showConfirmPassword ? "text" : "password"}
                    name="confirmPassword"
                    value={formData.confirmPassword}
                    onChange={handleChange}
                    className="block w-full pl-9 pr-10 py-2 border border-gray-200 rounded-xl text-[13px] focus:outline-none focus:ring-1 focus:ring-[#B58E58] focus:border-[#B58E58] transition-all bg-white shadow-sm"
                    placeholder="Repeat password"
                    required
                    minLength="6"
                  />
                  <button
                    type="button"
                    onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                    className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-[#B58E58] transition-colors"
                  >
                    {showConfirmPassword ? (
                      <FiEyeOff size={14} />
                    ) : (
                      <FiEye size={14} />
                    )}
                  </button>
                </div>
              </div>
            </div>

            <div className="pt-3">
              <button
                disabled={loading}
                type="submit"
                className={`w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-md shadow-[#B58E58]/20 text-[13px] font-semibold text-white ${loading ? "bg-gray-400" : "bg-[#B58E58] hover:bg-[#9E7A4A]"} focus:outline-none transition-all`}
              >
                {loading ? "Processing..." : "Create Account"}
              </button>
            </div>
          </form>

          <div className="mt-5 text-center text-[12.5px] text-gray-600">
            Already have an account?{" "}
            <Link
              to="/login"
              className="font-semibold text-[#031D44] hover:text-[#B58E58] transition-colors ml-1"
            >
              Sign in
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Register;
