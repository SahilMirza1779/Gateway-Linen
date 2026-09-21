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
import logo from "../assets/GatewayLinen-logo.png";

const Register = () => {
  const navigate = useNavigate();
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: "", text: "" });

  // Form states
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
    <div className="min-h-screen w-full flex flex-col items-center justify-center font-sans relative overflow-x-hidden bg-[#021026] py-6 px-3">
      {/* Background Deep Navy Gradient */}
      <div className="absolute inset-0 bg-gradient-to-tr from-[#021026] via-[#062454] to-[#021026]"></div>

      {/* Card ke pichhe Gold / Amber Glow */}
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] sm:w-[700px] sm:h-[700px] bg-gradient-to-r from-[#F39C12]/45 via-[#E67E22]/50 to-[#D4AF37]/55 rounded-full blur-[90px] pointer-events-none"></div>

      {/* Return to Home Button */}
      <div className="w-full max-w-[440px] mb-3 z-50 flex justify-start">
        <Link
          to="/"
          className="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-[#031D44] hover:text-[#B58E58] transition-all bg-[#F7F2EB] px-3.5 py-2 rounded-xl border border-[#E5DCD0] shadow-md hover:shadow-lg hover:-translate-x-1"
        >
          <FiArrowLeft size={13} />
          <span>Return to Home</span>
        </Link>
      </div>

      <div className="w-full max-w-[440px] z-10">
        <div className="bg-[#F7F2EB] py-6 px-5 sm:py-8 sm:px-8 shadow-[0_30px_70px_rgba(0,0,0,0.6)] rounded-[24px] sm:rounded-[32px] border border-[#E5DCD0] relative backdrop-blur-md">
          {/* Logo & Header */}
          <div className="flex justify-center items-center gap-3 mb-4 pb-4 border-b border-[#E5DCD0]">
            <Link to="/" className="shrink-0">
              <div className="w-12 h-12 sm:w-[50px] sm:h-[50px] bg-[#FAF7F2] rounded-2xl shadow-2xs flex items-center justify-center p-1 overflow-hidden border border-[#E5DCD0] hover:border-[#B58E58] transition-colors cursor-pointer">
                <img
                  src={logo}
                  alt="Gateway Linen"
                  className="w-full h-full object-contain rounded-xl"
                />
              </div>
            </Link>
            <div className="flex flex-col justify-center border-l-2 border-[#E5DCD0] pl-3">
              <h2 className="text-[15px] font-serif font-bold text-[#031D44] tracking-wide leading-tight">
                GATEWAY LINEN
              </h2>
              <p className="text-[8px] text-[#B58E58] uppercase tracking-[0.2em] mt-0.5 font-bold">
                Hospitality Supply
              </p>
            </div>
          </div>

          <div className="mb-4 text-center">
            <span className="text-[#B58E58] text-[9.5px] font-bold tracking-[0.25em] uppercase">
              Create Account
            </span>
            <h2 className="text-xl sm:text-[22px] font-serif font-bold text-[#031D44] mt-0.5 leading-tight">
              Join Gateway Linen
            </h2>
          </div>

          {message.text && (
            <div
              className={`mb-3 text-center text-xs font-bold p-2.5 rounded-xl ${message.type === "error" ? "bg-red-50 text-red-600 border border-red-200" : "bg-green-50 text-green-700 border border-green-200"}`}
            >
              {message.text}
            </div>
          )}

          <form className="space-y-3" onSubmit={handleRegister}>
            {/* Full Name */}
            <div>
              <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                Full name
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <FiUser className="text-gray-400" size={14} />
                </div>
                <input
                  type="text"
                  name="fullName"
                  value={formData.fullName}
                  onChange={handleChange}
                  className="block w-full pl-10 pr-3 py-2.5 border border-[#E5DCD0] rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#B58E58] transition-all bg-white shadow-2xs"
                  placeholder="John Smith"
                  required
                />
              </div>
            </div>

            {/* Email */}
            <div>
              <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                Email address
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <FiMail className="text-gray-400" size={14} />
                </div>
                <input
                  type="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  className="block w-full pl-10 pr-3 py-2.5 border border-[#E5DCD0] rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#B58E58] transition-all bg-white shadow-2xs"
                  placeholder="you@company.com"
                  required
                />
              </div>
            </div>

            {/* Phone */}
            <div>
              <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                Phone number
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <FiPhone className="text-gray-400" size={14} />
                </div>
                <input
                  type="tel"
                  name="phone"
                  value={formData.phone}
                  onChange={handleChange}
                  className="block w-full pl-10 pr-3 py-2.5 border border-[#E5DCD0] rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#B58E58] transition-all bg-white shadow-2xs"
                  placeholder="+1 416 555 0123"
                />
              </div>
            </div>

            {/* Password */}
            <div>
              <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                Password
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <FiLock className="text-gray-400" size={14} />
                </div>
                <input
                  type={showPassword ? "text" : "password"}
                  name="password"
                  value={formData.password}
                  onChange={handleChange}
                  className="block w-full pl-10 pr-10 py-2.5 border border-[#E5DCD0] rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#B58E58] transition-all bg-white shadow-2xs"
                  placeholder="Minimum 6 chars"
                  required
                  minLength="6"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-[#B58E58] transition-colors cursor-pointer"
                >
                  {showPassword ? <FiEyeOff size={14} /> : <FiEye size={14} />}
                </button>
              </div>
            </div>

            {/* Confirm Password */}
            <div>
              <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                Confirm password
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <FiLock className="text-gray-400" size={14} />
                </div>
                <input
                  type={showConfirmPassword ? "text" : "password"}
                  name="confirmPassword"
                  value={formData.confirmPassword}
                  onChange={handleChange}
                  className="block w-full pl-10 pr-10 py-2.5 border border-[#E5DCD0] rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#B58E58] transition-all bg-white shadow-2xs"
                  placeholder="Repeat password"
                  required
                  minLength="6"
                />
                <button
                  type="button"
                  onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                  className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-[#B58E58] transition-colors cursor-pointer"
                >
                  {showConfirmPassword ? (
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
                className={`w-full flex justify-center py-3 px-4 rounded-xl shadow-md text-xs font-bold tracking-widest uppercase text-white ${loading ? "bg-gray-400" : "bg-[#031D44] hover:bg-[#B58E58]"} transition-all cursor-pointer`}
              >
                {loading ? "Processing..." : "Create Account"}
              </button>
            </div>
          </form>

          <div className="mt-5 text-center text-xs text-gray-600 font-light">
            Already have an account?{" "}
            <Link
              to="/login"
              className="font-bold text-[#031D44] hover:text-[#B58E58] transition-colors ml-1"
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
