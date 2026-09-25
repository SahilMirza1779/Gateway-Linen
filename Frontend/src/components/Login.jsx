import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { FiMail, FiLock, FiArrowLeft, FiEye, FiEyeOff } from "react-icons/fi";
import logo from "../assets/GatewayLinen-logo.png";

const Login = () => {
  const navigate = useNavigate();
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: "", text: "" });

  // Form states
  const [formData, setFormData] = useState({
    email: "",
    password: "",
  });

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleLogin = async (e) => {
    e.preventDefault();
    setMessage({ type: "", text: "" });
    setLoading(true);

    try {
      const payload = {
        entity: "user",
        action: "login",
        email: formData.email,
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
          text: "Login successful! Redirecting...",
        });

        localStorage.setItem("user", JSON.stringify(result.data));

        setTimeout(() => {
          navigate("/");
        }, 1500);
      } else {
        setMessage({
          type: "error",
          text: result.message || "Invalid email or password.",
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
      <div className="w-full max-w-[420px] mb-3 z-50 flex justify-start">
        <Link
          to="/"
          className="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-[#031D44] hover:text-[#B58E58] transition-all bg-[#F7F2EB] px-3.5 py-2 rounded-xl border border-[#E5DCD0] shadow-md hover:shadow-lg hover:-translate-x-1"
        >
          <FiArrowLeft size={13} />
          <span>Return to Home</span>
        </Link>
      </div>

      <div className="w-full max-w-[420px] z-10">
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
              Welcome Back
            </span>
            <h2 className="text-xl sm:text-[22px] font-serif font-bold text-[#031D44] mt-0.5 leading-tight">
              Sign in to your account
            </h2>
          </div>

          {/* Messages Alert */}
          {message.text && (
            <div
              className={`mb-3 text-center text-xs font-bold p-2.5 rounded-xl ${message.type === "error" ? "bg-red-50 text-red-600 border border-red-200" : "bg-green-50 text-green-700 border border-green-200"}`}
            >
              {message.text}
            </div>
          )}

          <form className="space-y-3.5" onSubmit={handleLogin}>
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
                  className="block w-full pl-10 pr-3 py-2.5 sm:py-3 border border-[#E5DCD0] rounded-xl text-xs sm:text-[13px] text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#B58E58] transition-all bg-white shadow-2xs"
                  placeholder="you@company.com"
                  required
                />
              </div>
            </div>

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
                  className="block w-full pl-10 pr-10 py-2.5 sm:py-3 border border-[#E5DCD0] rounded-xl text-xs sm:text-[13px] text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#B58E58] transition-all bg-white shadow-2xs"
                  placeholder="Enter your password"
                  required
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

            <div className="flex items-center justify-between pt-0.5">
              <div className="flex items-center">
                <input
                  type="checkbox"
                  className="h-3.5 w-3.5 text-[#031D44] focus:ring-[#B58E58] border-[#E5DCD0] rounded cursor-pointer"
                />
                <label className="ml-2 block text-[11px] text-gray-600 cursor-pointer font-light">
                  Remember me
                </label>
              </div>
              <div className="text-[11px]">
                <Link
                  to="/forgot-password"
                  className="font-semibold text-[#B58E58] hover:underline transition-colors"
                >
                  Forgot password?
                </Link>
              </div>
            </div>

            <div className="pt-2">
              <button
                disabled={loading}
                type="submit"
                className={`w-full flex justify-center py-3 px-4 rounded-xl shadow-md text-xs font-bold tracking-widest uppercase text-white ${loading ? "bg-gray-400" : "bg-[#031D44] hover:bg-[#B58E58]"} transition-all cursor-pointer`}
              >
                {loading ? "Verifying..." : "Sign In"}
              </button>
            </div>
          </form>

          <div className="mt-5 relative">
            <div className="absolute inset-0 flex items-center">
              <div className="w-full border-t border-[#E5DCD0]" />
            </div>
            <div className="relative flex justify-center text-sm">
              <span className="px-3 bg-[#F7F2EB] text-gray-500 text-[10px] tracking-wide font-light">
                New to Gateway Linen?
              </span>
            </div>
          </div>

          <div className="mt-4">
            <Link
              to="/register"
              className="w-full flex justify-center py-2.5 px-4 border border-[#E5DCD0] rounded-xl shadow-2xs text-xs font-bold tracking-widest uppercase text-[#031D44] bg-white hover:bg-[#031D44] hover:text-white hover:border-[#031D44] transition-all"
            >
              Create an Account
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;