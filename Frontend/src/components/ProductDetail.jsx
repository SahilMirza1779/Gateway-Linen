import { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import {
  FiShoppingCart,
  FiTruck,
  FiShield,
  FiX,
  FiUser,
  FiHeart,
  FiArrowLeft,
} from "react-icons/fi";
import { useCart } from "../context/CartContext";
import { useWishlist } from "../context/WishlistContext";

export default function ProductDetail() {
  const { id } = useParams();
  const navigate = useNavigate();

  const { addToCart } = useCart();
  const { toggleWishlistItem, isInWishlist } = useWishlist();

  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedSize, setSelectedSize] = useState("Standard");
  const [quantity, setQuantity] = useState(1);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [showLoginModal, setShowLoginModal] = useState(false);

  useEffect(() => {
    window.scrollTo(0, 0);
    const fetchProductDetails = async () => {
      try {
        const response = await fetch(
          `http://localhost/Gateway-Linen/GatewayLinenAdmin-main/products/api.php?id=${id}`,
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
            },
          },
        );
        const result = await response.json();
        if (result.success && result.data) {
          const item = result.data;

          let formattedMainImage =
            "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=800";
          const rawMainImg = item.imageUrl || item.ImageUrl || item.image || "";

          if (rawMainImg.startsWith("http")) {
            formattedMainImage = rawMainImg;
          } else if (rawMainImg !== "") {
            const cleanPath = rawMainImg.replace(/^\/+/, "");
            formattedMainImage = `http://localhost/Gateway-Linen/GatewayLinenAdmin-main/${cleanPath}`;
          }

          let formattedImages = [];
          if (item.images && item.images.length > 0) {
            formattedImages = item.images.map((imgObj) => {
              const path = imgObj.imageUrl || imgObj.ImageUrl || "";
              if (path.startsWith("http")) return path;
              if (path !== "") {
                return `http://localhost/Gateway-Linen/GatewayLinenAdmin-main/${path.replace(/^\/+/, "")}`;
              }
              return formattedMainImage;
            });
          } else {
            formattedImages = [formattedMainImage];
          }

          setProduct({
            ...item,
            imageUrl: formattedMainImage,
            images: formattedImages,
            image: formattedMainImage, // Cart aur direct buy ke liye
          });
        }
      } catch (error) {
        console.error("Error fetching product details:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchProductDetails();
  }, [id]);

  const basePrice = product?.basePrice || product?.BasePrice || 0;
  const sizePricing = {
    Standard: basePrice,
    "Queen Size": Number((basePrice * 1.15).toFixed(2)),
    "King Size": Number((basePrice * 1.3).toFixed(2)),
  };

  const unitPrice = sizePricing[selectedSize] || basePrice;
  const totalPrice = Number((unitPrice * quantity).toFixed(2));
  const sizes = ["Standard", "Queen Size", "King Size"];

  const galleryImages =
    product?.images && product.images.length > 0
      ? product.images
      : [
          product?.imageUrl ||
            product?.image ||
            "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=800",
        ];

  useEffect(() => {
    if (galleryImages.length > 1) {
      const interval = setInterval(() => {
        setCurrentIndex((prevIndex) => (prevIndex + 1) % galleryImages.length);
      }, 10000);
      return () => clearInterval(interval);
    }
  }, [galleryImages.length]);

  const handleAuthAction = (actionCallback) => {
    const loggedInUser = localStorage.getItem("user");
    if (!loggedInUser) {
      setShowLoginModal(true);
    } else {
      actionCallback();
    }
  };

  const handleAddToCart = () => {
    handleAuthAction(() => {
      addToCart(
        {
          ...product,
          id: product.productId || product.ProductId,
          price: `CAD $${basePrice}`,
          image: galleryImages[0],
        },
        quantity,
        selectedSize,
        unitPrice,
      );
      alert(`Added ${product.name || product.Name} to cart!`);
    });
  };

  const handleBuyNow = () => {
    handleAuthAction(() => {
      navigate("/checkout", {
        state: {
          product: {
            ...product,
            id: product.productId || product.ProductId,
            name: product.name || product.Name,
            price: unitPrice,
            image: galleryImages[0],
          },
          quantity,
          selectedSize,
          currentPrice: totalPrice,
        },
      });
    });
  };

  if (loading) {
    return (
      <div className="w-full min-h-screen bg-[#F0EAE1] flex items-center justify-center font-bold text-xs uppercase tracking-widest text-[#031D44]">
        Loading Product Details...
      </div>
    );
  }

  if (!product) {
    return (
      <div className="w-full min-h-screen bg-[#F0EAE1] flex flex-col items-center justify-center font-sans">
        <h2 className="text-xl font-serif font-bold text-[#031D44] mb-3">
          Product Not Found
        </h2>
        <button
          onClick={() => navigate("/")}
          className="px-4 py-2 bg-[#031D44] text-white text-xs rounded-xl uppercase"
        >
          Back to Home
        </button>
      </div>
    );
  }

  return (
    <div className="w-full min-h-screen bg-[#F0EAE1] py-4 md:py-6 px-3 md:px-8 font-sans text-gray-800 relative">
      <div className="max-w-[1300px] mx-auto">
        <button
          onClick={() => navigate(-1)}
          className="mb-3 inline-flex items-center gap-2 px-3 py-1.5 bg-[#F7F2EB] border border-[#E5DCD0] text-[10px] md:text-[11px] font-bold uppercase tracking-wider rounded-xl text-[#031D44] hover:border-[#B58E58] transition-all cursor-pointer shadow-2xs"
        >
          <FiArrowLeft size={13} /> Back
        </button>

        <div className="bg-[#F7F2EB] p-4 md:p-8 rounded-[24px] md:rounded-[28px] border border-[#E5DCD0] shadow-xl grid grid-cols-1 lg:grid-cols-12 gap-6 md:gap-8 items-start">
          {/* Left Gallery Section */}
          <div className="lg:col-span-5 flex flex-col gap-3">
            <div className="relative w-full h-[260px] sm:h-[320px] md:h-[360px] bg-[#FAF7F2] rounded-2xl overflow-hidden border border-[#E5DCD0] shadow-sm flex items-center justify-center">
              <button
                onClick={() =>
                  handleAuthAction(() =>
                    toggleWishlistItem({
                      ...product,
                      id: product.productId || product.ProductId,
                      price: `CAD $${basePrice}`,
                      name: product.name || product.Name,
                      image: galleryImages[0],
                    }),
                  )
                }
                className="absolute top-3 right-3 z-10 p-2.5 bg-white rounded-full shadow-md hover:scale-110 transition-transform cursor-pointer border border-gray-100"
                title="Add to Wishlist"
              >
                <FiHeart
                  size={16}
                  className={
                    isInWishlist(product.productId || product.ProductId)
                      ? "fill-red-500 text-red-500"
                      : "text-gray-400"
                  }
                />
              </button>

              <img
                src={galleryImages[currentIndex]}
                alt={product.name || product.Name}
                onError={(e) => {
                  e.target.src =
                    "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=800";
                }}
                className="w-full h-full object-cover transition-all duration-500"
              />
            </div>

            {/* Thumbnails */}
            <div className="flex gap-2 overflow-x-auto pb-1 scrollbar-none">
              {galleryImages.map((img, index) => (
                <div
                  key={index}
                  onClick={() => setCurrentIndex(index)}
                  className={`min-w-[55px] h-12 md:min-w-[65px] md:h-14 bg-[#FAF7F2] rounded-xl overflow-hidden border-2 cursor-pointer transition-all flex-shrink-0 shadow-2xs ${currentIndex === index ? "border-[#B58E58] scale-105" : "border-[#E5DCD0]"}`}
                >
                  <img
                    src={img}
                    alt={`Thumb ${index}`}
                    onError={(e) => {
                      e.target.src =
                        "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=800";
                    }}
                    className="w-full h-full object-cover"
                  />
                </div>
              ))}
            </div>
          </div>

          {/* Right Info Section */}
          <div className="lg:col-span-7 flex flex-col justify-between">
            <div>
              <div className="inline-flex items-center gap-1.5 bg-[#B58E58]/15 px-2.5 py-0.5 rounded-full mb-1.5 border border-[#B58E58]/30">
                <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
                <span className="text-[9px] md:text-[9.5px] font-bold text-[#B58E58] tracking-[0.2em] uppercase">
                  {product.categoryName || product.CategoryName || "LINEN"}
                </span>
              </div>

              <h1 className="text-xl sm:text-2xl md:text-3xl font-serif font-bold text-[#031D44] tracking-tight mb-1.5">
                {product.name || product.Name}
              </h1>

              <div className="text-lg md:text-2xl font-bold text-[#031D44] mb-2.5 flex items-baseline gap-2.5">
                <span>CAD ${totalPrice.toFixed(2)}</span>
                <span className="text-[10px] md:text-[11px] font-normal text-gray-500">
                  ({quantity} item{quantity > 1 ? "s" : ""} &bull;{" "}
                  {selectedSize})
                </span>
              </div>

              <p className="text-[11px] md:text-xs text-gray-600 leading-relaxed mb-3.5 border-b border-[#E5DCD0] pb-3.5 font-light">
                {product.shortDescription ||
                  product.ShortDescription ||
                  product.description ||
                  product.Description ||
                  "Premium hospitality linen crafted for superior comfort, durability, and luxury feel."}
              </p>

              {/* Size Selector */}
              <div className="mb-3.5">
                <label className="block text-[10px] md:text-[10.5px] font-bold text-[#031D44] uppercase tracking-wider mb-1.5">
                  Select Size Option
                </label>
                <div className="flex gap-2 flex-wrap">
                  {sizes.map((size) => (
                    <button
                      key={size}
                      onClick={() => setSelectedSize(size)}
                      className={`px-3.5 py-1.5 md:px-4 md:py-2 rounded-xl text-[10px] md:text-[11px] font-bold transition-all cursor-pointer shadow-2xs ${selectedSize === size ? "bg-[#031D44] text-white shadow-md border border-[#031D44]" : "bg-white text-gray-700 border border-[#E5DCD0] hover:border-[#B58E58]"}`}
                    >
                      {size}
                    </button>
                  ))}
                </div>
              </div>

              {/* Quantity */}
              <div className="mb-4">
                <label className="block text-[10px] md:text-[10.5px] font-bold text-[#031D44] uppercase tracking-wider mb-1.5">
                  Quantity
                </label>
                <div className="flex items-center gap-3">
                  <div className="flex items-center border border-[#E5DCD0] rounded-xl overflow-hidden bg-white shadow-2xs">
                    <button
                      onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                      className="w-8 h-8 md:w-9 md:h-9 flex items-center justify-center text-gray-700 hover:bg-gray-100 font-bold transition-all cursor-pointer text-xs"
                    >
                      -
                    </button>
                    <span className="w-8 md:w-10 text-center text-xs font-bold text-[#031D44]">
                      {quantity}
                    </span>
                    <button
                      onClick={() => setQuantity((q) => q + 1)}
                      className="w-8 h-8 md:w-9 md:h-9 flex items-center justify-center text-gray-700 hover:bg-gray-100 font-bold transition-all cursor-pointer text-xs"
                    >
                      +
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <div className="flex flex-col sm:flex-row gap-2.5 mb-4">
                <button
                  onClick={handleAddToCart}
                  className="flex-1 flex items-center justify-center gap-2 py-2.5 md:py-3 bg-white border border-[#031D44] rounded-xl text-[10px] md:text-[11px] font-bold tracking-widest uppercase text-[#031D44] hover:bg-[#031D44] hover:text-white transition-all shadow-2xs cursor-pointer"
                >
                  <FiShoppingCart size={14} />
                  <span>Add to Cart</span>
                </button>
                <button
                  onClick={handleBuyNow}
                  className="flex-1 py-2.5 md:py-3 bg-[#031D44] text-white rounded-xl text-[10px] md:text-[11px] font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all cursor-pointer"
                >
                  Buy Now
                </button>
              </div>

              <div className="grid grid-cols-2 gap-2.5 pt-3 border-t border-[#E5DCD0] text-[10px] md:text-[11px] text-gray-600 font-light">
                <div className="flex items-center gap-1.5 bg-white/50 p-2 rounded-lg border border-gray-200/30">
                  <FiTruck className="text-[#B58E58] shrink-0" size={15} />
                  <span>Fast Shipping Across Canada</span>
                </div>
                <div className="flex items-center gap-1.5 bg-white/50 p-2 rounded-lg border border-gray-200/30">
                  <FiShield className="text-[#B58E58] shrink-0" size={15} />
                  <span>Hospital Grade Quality</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {showLoginModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity p-4">
          <div className="bg-[#F7F2EB] border border-[#E5DCD0] p-6 rounded-2xl shadow-2xl w-full max-w-sm text-center relative">
            <button
              onClick={() => setShowLoginModal(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-800 bg-white p-2 rounded-full transition-colors cursor-pointer border border-gray-200"
            >
              <FiX size={16} />
            </button>
            <div className="w-14 h-14 bg-[#031D44] text-[#B58E58] rounded-xl flex items-center justify-center mx-auto mb-4 shadow-md">
              <FiUser size={24} />
            </div>
            <h3 className="text-lg font-serif font-bold text-[#031D44] mb-1">
              Login Required
            </h3>
            <p className="text-[11px] text-gray-600 mb-6 font-light leading-relaxed px-2">
              Please login first to add items to your cart, wishlist, or proceed
              to checkout.
            </p>
            <button
              onClick={() => navigate("/login")}
              className="w-full py-3 bg-[#031D44] text-white rounded-xl text-[11px] font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all cursor-pointer"
            >
              Login Now
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
