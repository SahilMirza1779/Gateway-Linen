import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
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

const Login = () => {
  const navigate = useNavigate();
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: "", text: "" });

  const savedEmail = localStorage.getItem("rememberedEmail") || "";

  const [formData, setFormData] = useState({
    email: savedEmail,
    password: "",
  });

  const [rememberMe, setRememberMe] = useState(Boolean(savedEmail));

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

        if (rememberMe) {
          localStorage.setItem("rememberedEmail", formData.email);
        } else {
          localStorage.removeItem("rememberedEmail");
        }

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

  const handleGoogleLogin = () => {
    setMessage({
      type: "error",
      text: "Google login is currently disabled. Please use email/password.",
    });
  };

  return (
    <div className="min-h-screen w-full flex flex-col lg:flex-row font-sans bg-[#F0EAE1] relative overflow-x-hidden py-3 px-3 lg:py-0 lg:px-0">
      <div className="absolute top-1/2 left-1/4 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] lg:w-[900px] lg:h-[900px] bg-[#B58E58]/10 rounded-full blur-[100px] pointer-events-none z-0"></div>

      <div className="w-full lg:w-[55%] flex flex-col justify-center px-2 pt-2 pb-2 lg:p-16 xl:px-24 z-10">
        <div className="flex items-center justify-between lg:block">
          <Link
            to="/"
            className="inline-block w-[45px] h-[45px] lg:w-[80px] lg:h-[80px] bg-white rounded-xl lg:rounded-2xl shadow-sm flex items-center justify-center p-1 overflow-hidden border border-[#E5DCD0] mb-2 lg:mb-8"
          >
            <img
              src={logo}
              alt="Gateway Linen"
              className="w-full h-full object-contain rounded-lg"
            />
          </Link>

          <Link
            to="/"
            className="lg:hidden inline-flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-wider text-[#031D44] bg-white border border-[#E5DCD0] px-3 py-1.5 rounded-full shadow-2xs"
          >
            <FiArrowLeft size={12} />
            <span>Home</span>
          </Link>
        </div>

        <h1 className="text-2xl sm:text-4xl lg:text-6xl font-serif font-bold text-[#031D44] mb-1 lg:mb-2 tracking-tight">
          Gateway<span className="text-[#B58E58]">Linen</span>
        </h1>

        <div className="flex items-center gap-2 mb-2 lg:mb-6">
          <div className="w-1.5 h-1.5 bg-[#B58E58] rounded-full"></div>
          <p className="text-[#B58E58] text-[9px] lg:text-xs font-bold tracking-[0.25em] uppercase">
            Premium Linen Solutions
          </p>
        </div>

        <div className="hidden lg:block mt-2">
          <p className="text-gray-600 text-sm leading-relaxed max-w-md mb-10 font-light">
            Welcome to the Gateway Linen Customer Portal. Manage your orders,
            review your wishlist, and track wholesale inquiries from one secure
            workspace.
          </p>

          <div className="grid grid-cols-2 gap-4 max-w-lg">
            <div className="flex items-center gap-3 bg-white border border-[#E5DCD0] p-4 rounded-xl shadow-sm">
              <FiShield className="text-[#B58E58]" size={18} />
              <span className="text-[#031D44] text-xs font-bold tracking-wide">
                Secure Access
              </span>
            </div>
            <div className="flex items-center gap-3 bg-white border border-[#E5DCD0] p-4 rounded-xl shadow-sm">
              <FiShoppingBag className="text-[#B58E58]" size={18} />
              <span className="text-[#031D44] text-xs font-bold tracking-wide">
                Order Management
              </span>
            </div>
            <div className="flex items-center gap-3 bg-white border border-[#E5DCD0] p-4 rounded-xl shadow-sm">
              <FiBox className="text-[#B58E58]" size={18} />
              <span className="text-[#031D44] text-xs font-bold tracking-wide">
                Wholesale Catalog
              </span>
            </div>
            <div className="flex items-center gap-3 bg-white border border-[#E5DCD0] p-4 rounded-xl shadow-sm">
              <FiHeadphones className="text-[#B58E58]" size={18} />
              <span className="text-[#031D44] text-xs font-bold tracking-wide">
                Priority Support
              </span>
            </div>
          </div>
        </div>
      </div>

      <div className="w-full lg:w-[45%] flex flex-col items-center lg:items-end justify-center px-0 pb-2 pt-2 lg:p-12 z-10 relative">
        <div className="hidden lg:flex w-full max-w-[420px] mb-6 justify-start">
          <Link
            to="/"
            className="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-[#031D44] hover:text-white transition-all bg-white hover:bg-[#031D44] border border-[#E5DCD0] px-4 py-2.5 rounded-full shadow-sm"
          >
            <FiArrowLeft size={14} />
            <span>Return to Home</span>
          </Link>
        </div>

        <div className="w-full max-w-[420px] bg-white py-5 px-5 sm:py-8 sm:px-8 shadow-md rounded-[20px] lg:rounded-[32px] border border-[#E5DCD0] relative z-20">
          <div className="mb-4 lg:mb-6">
            <div className="inline-flex items-center gap-1.5 bg-[#B58E58]/10 px-2.5 py-0.5 rounded-full mb-2 border border-[#B58E58]/20">
              <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
              <span className="text-[#B58E58] text-[8.5px] font-bold tracking-[0.2em] uppercase">
                Customer Sign-In
              </span>
            </div>
            <h2 className="text-xl sm:text-3xl font-serif font-bold text-[#031D44] mb-1 leading-tight">
              Welcome back
            </h2>
            <p className="text-[11px] text-gray-500 font-light">
              Sign in securely to access your dashboard.
            </p>
          </div>

          {message.text && (
            <div
              className={`mb-3 text-center text-xs font-bold p-2.5 rounded-xl ${message.type === "error" ? "bg-red-50 text-red-600 border border-red-200" : "bg-green-50 text-green-700 border border-green-200"}`}
            >
              {message.text}
            </div>
          )}

          <form className="space-y-3" onSubmit={handleLogin}>
            <div>
              <label className="block text-[9.5px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
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
                  className="block w-full pl-9 pr-3 py-2.5 border border-[#E5DCD0] rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#B58E58] bg-[#FAF7F2]"
                  placeholder="Enter your email"
                  required
                />
              </div>
            </div>

            <div>
              <label className="block text-[9.5px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
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
                  className="block w-full pl-9 pr-9 py-2.5 border border-[#E5DCD0] rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#B58E58] bg-[#FAF7F2]"
                  placeholder="Enter password"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-[#B58E58]"
                >
                  {showPassword ? <FiEyeOff size={14} /> : <FiEye size={14} />}
                </button>
              </div>
            </div>

            <div className="flex items-center justify-between pt-0.5">
              <div className="flex items-center">
                <input
                  type="checkbox"
                  checked={rememberMe}
                  onChange={(e) => setRememberMe(e.target.checked)}
                  className="h-3.5 w-3.5 accent-[#031D44] border-[#E5DCD0] rounded cursor-pointer"
                />
                <label className="ml-1.5 text-[11px] text-gray-600 font-light cursor-pointer select-none">
                  Remember me
                </label>
              </div>
              <div className="text-[11px]">
                <Link
                  to="/forgot-password"
                  className="font-bold text-[#B58E58] hover:underline"
                >
                  Forgot password?
                </Link>
              </div>
            </div>

            <div className="pt-1">
              <button
                disabled={loading}
                type="submit"
                className={`w-full flex justify-center py-3 px-4 rounded-xl text-xs font-bold tracking-widest uppercase text-white ${loading ? "bg-gray-400" : "bg-[#031D44] hover:bg-[#B58E58]"}`}
              >
                {loading ? "Verifying..." : "Sign In to Account"}
              </button>
            </div>
          </form>

          <div className="mt-3.5 relative">
            <div className="absolute inset-0 flex items-center">
              <div className="w-full border-t border-[#E5DCD0]" />
            </div>
            <div className="relative flex justify-center text-xs">
              <span className="px-2 bg-white text-gray-400 text-[9.5px] font-bold uppercase">
                Or
              </span>
            </div>
          </div>

          <button
            type="button"
            onClick={handleGoogleLogin}
            className="mt-3.5 w-full flex items-center justify-center gap-2.5 py-2.5 px-4 border border-[#E5DCD0] rounded-xl bg-white hover:bg-gray-50 text-[11px] font-bold text-[#031D44] uppercase tracking-wider"
          >
            <svg className="w-3.5 h-3.5" viewBox="0 0 24 24">
              <path
                fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
              />
              <path
                fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
              />
              <path
                fill="#FBBC05"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
              />
              <path
                fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
              />
            </svg>
            Sign in with Google
          </button>

          <div className="mt-4 text-center border-t border-[#E5DCD0] pt-4">
            <span className="text-[11px] text-gray-500 font-light">
              New to Gateway Linen?
            </span>
            <Link
              to="/register"
              className="ml-1 text-[11px] font-bold text-[#031D44] hover:underline uppercase tracking-wider"
            >
              Create Account
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;
