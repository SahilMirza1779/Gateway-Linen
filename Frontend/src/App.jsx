import {
  BrowserRouter as Router,
  Routes,
  Route,
  useLocation,
} from "react-router-dom";
import Navbar from "./components/Navbar";
import Hero from "./components/Hero";
import CategoryGrid from "./components/CategoryGrid";
import FeaturedProducts from "./components/FeaturedProducts";
import WholesaleSection from "./components/WholesaleSection";
import Footer from "./components/Footer";
import Login from "./components/Login";
import Register from "./components/Register";
import ForgotPassword from "./components/ForgotPassword";
import CategoryPage from "./components/CategoryPage";
import ContactPage from "./components/ContactPage";
import Dashboard from "./components/Dashboard";
import ProductDetail from "./components/ProductDetail";
import Checkout from "./components/Checkout";
import { CartProvider } from "./context/CartContext";
import CartDrawer from "./components/CartDrawer";
import { WishlistProvider } from "./context/WishlistContext"; // Naya Import
import WishlistDrawer from "./components/WishlistDrawer"; // Naya Import

const Home = () => {
  return (
    <>
      <Hero />
      <CategoryGrid />
      <FeaturedProducts />
      <WholesaleSection />
      <Footer />
    </>
  );
};

const AppLayout = () => {
  const location = useLocation();
  const isAuthPage =
    location.pathname === "/login" ||
    location.pathname === "/register" ||
    location.pathname === "/forgot-password";

  return (
    <WishlistProvider>
      {" "}
      {/* NAYA: Wishlist se Wrap Kiya */}
      <CartProvider>
        <div className="min-h-screen bg-white font-sans flex flex-col relative">
          {!isAuthPage && <Navbar />}
          <CartDrawer />
          <WishlistDrawer /> {/* NAYA: Wishlist Drawer Lagaya */}
          <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
            <Route path="/forgot-password" element={<ForgotPassword />} />
            <Route path="/category/:categoryName" element={<CategoryPage />} />
            <Route path="/contact" element={<ContactPage />} />
            <Route path="/dashboard" element={<Dashboard />} />
            <Route path="/product/:id" element={<ProductDetail />} />
            <Route path="/checkout" element={<Checkout />} />
          </Routes>
        </div>
      </CartProvider>
    </WishlistProvider>
  );
};

export default function App() {
  return (
    <Router>
      <AppLayout />
    </Router>
  );
}
