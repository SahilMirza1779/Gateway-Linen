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
import { WishlistProvider } from "./context/WishlistContext";
import WishlistDrawer from "./components/WishlistDrawer";
import ProductsPage from "./components/ProductsPage";

const Home = () => {
  return (
    <>
      <Hero />
      <CategoryGrid />
      <FeaturedProducts />
      <WholesaleSection />
      {/* Footer yahan se hata diya hai taaki global AppLayout handle kare */}
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
      <CartProvider>
        <div className="min-h-screen bg-white font-sans flex flex-col relative">
          {!isAuthPage && <Navbar />}
          <CartDrawer />
          <WishlistDrawer />

          <div className="flex-grow">
            <Routes>
              <Route path="/" element={<Home />} />
              <Route path="/login" element={<Login />} />
              <Route path="/register" element={<Register />} />
              <Route path="/forgot-password" element={<ForgotPassword />} />
              <Route
                path="/category/:categoryName"
                element={<CategoryPage />}
              />
              <Route path="/contact" element={<ContactPage />} />
              <Route path="/dashboard" element={<Dashboard />} />
              <Route path="/product/:id" element={<ProductDetail />} />
              <Route path="/checkout" element={<Checkout />} />
              <Route path="/products" element={<ProductsPage />} />
            </Routes>
          </div>

          {/* Global Footer jo ab har page par (chahe Home ho ya Contact) bilkul sahi jagah dikhega */}
          {!isAuthPage && <Footer />}
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
