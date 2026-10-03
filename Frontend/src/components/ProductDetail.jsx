import { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import {
  FiShoppingCart,
  FiHeart,
  FiArrowLeft,
  FiCheck,
  FiX,
  FiUser,
  FiDownload,
  FiArrowRight,
  FiStar,
  FiTruck,
  FiShield,
  FiRefreshCw,
  FiAward,
  FiChevronRight,
  FiPackage,
  FiPlus,
  FiMinus,
  FiPercent,
  FiClock,
  FiHeadphones,
  FiZap,
} from "react-icons/fi";
import { jsPDF } from "jspdf";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

const resolveImgPath = (rawImg) => {
  let finalImg =
    "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=800";
  if (rawImg) {
    if (rawImg.startsWith("http")) finalImg = rawImg;
    else if (rawImg.startsWith("/")) finalImg = `http://localhost${rawImg}`;
    else {
      const cleanPath = rawImg.replace(/^\/+/, "");
      if (cleanPath.includes("Gateway-Linen"))
        finalImg = `http://localhost/${cleanPath}`;
      else
        finalImg = `http://localhost/Gateway-Linen/GatewayLinenAdmin-main/${cleanPath}`;
    }
  }
  return finalImg;
};

const ProductDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  const [product, setProduct] = useState(null);
  const [relatedProducts, setRelatedProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedImage, setSelectedImage] = useState("");
  const [selectedSize, setSelectedSize] = useState("Standard");
  const [quantity, setQuantity] = useState(2);
  const [activeTab, setActiveTab] = useState("Details");
  const [showLoginModal, setShowLoginModal] = useState(false);
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [successMessage, setSuccessMessage] = useState("");

  const { addToCart } = useCart();
  const { toggleWishlistItem, isInWishlist } = useWishlist();

  useEffect(() => {
    window.scrollTo(0, 0);
    const fetchProductDetails = async () => {
      setLoading(true);
      try {
        const response = await fetch(
          `http://localhost/Gateway-Linen/GatewayLinenAdmin-main/products/api.php?action=get_product&id=${id}`,
          { method: "GET", headers: { "Content-Type": "application/json" } }
        );
        const result = await response.json();

        let prodData = null;
        if (result.success && result.data) prodData = result.data;
        else if (result.product) prodData = result.product;

        const allRes = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/products/api.php?action=get_products"
        );
        const allResult = await allRes.json();

        let items = [];
        if (allResult.success && Array.isArray(allResult.data)) items = allResult.data;
        else if (allResult.success && allResult.data?.items)
          items = allResult.data.items;
        else if (Array.isArray(allResult)) items = allResult;

        const allItems = items.map((item) => {
          let rawImg = item.imageUrl || item.ImageUrl || item.image || "";
          if (!rawImg && item.images && item.images.length > 0) {
            rawImg = item.images[0].imageUrl || item.images[0].ImageUrl || "";
          }
          return {
            ...item,
            id: item.productId || item.ProductId || item.id,
            resolvedImage: resolveImgPath(rawImg),
          };
        });

        if (!prodData && id)
          prodData = allItems.find((p) => String(p.id) === String(id));

        if (prodData) {
          let imagesList = [];
          if (
            prodData.images &&
            Array.isArray(prodData.images) &&
            prodData.images.length > 0
          ) {
            imagesList = prodData.images.map((img) =>
              resolveImgPath(img.imageUrl || img.ImageUrl)
            );
          } else {
            const raw =
              prodData.imageUrl || prodData.ImageUrl || prodData.image || "";
            imagesList.push(resolveImgPath(raw));
          }

          const currentProd = {
            ...prodData,
            id: prodData.productId || prodData.ProductId || id,
            name: prodData.name || prodData.Name || "Product Name",
            price: Number(
              prodData.basePrice || prodData.price || prodData.Price || 200.04
            ),
            unit: prodData.unit || prodData.Unit || "DZ",
            sku: prodData.sku || prodData.SKU || `OP2-${id}754C01`,
            description:
              prodData.description ||
              prodData.Description ||
              "High quality commercial grade hospitality product designed for superior comfort and durability.",
            category:
              prodData.categoryName || prodData.CategoryName || "Linens",
            images: imagesList,
          };

          setProduct(currentProd);
          setSelectedImage(imagesList[0]);

          const filteredRelated = allItems.filter(
            (p) =>
              String(p.id) !== String(currentProd.id) &&
              (p.categoryName || "").toLowerCase() ===
                (currentProd.category || "").toLowerCase()
          );
          setRelatedProducts(
            filteredRelated.length > 0 ? filteredRelated : allItems.slice(0, 5)
          );
        }
      } catch (error) {
        console.error("Error fetching product details:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchProductDetails();
  }, [id]);

  const downloadSellSheetPDF = () => {
    if (!product) return;
    const doc = new jsPDF("p", "mm", "a4");
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();

    doc.setDrawColor(229, 220, 208);
    doc.setLineWidth(0.5);
    doc.rect(10, 10, pageWidth - 20, pageHeight - 20);

    doc.setFillColor(40, 116, 240);
    doc.rect(10, 10, pageWidth - 20, 25, "F");

    doc.setTextColor(255, 255, 255);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(16);
    doc.text("GATEWAY LINEN - WHOLESALE SELL SHEET", 15, 26);

    doc.setTextColor(251, 100, 27);
    doc.setFontSize(10);
    doc.text(`COLLECTION: ${product.category.toUpperCase()}`, 15, 45);

    doc.setTextColor(3, 29, 68);
    doc.setFontSize(24);
    doc.text(product.name, 15, 55);

    doc.setFillColor(241, 243, 246);
    doc.rect(15, 65, 80, 65, "F");
    doc.setDrawColor(40, 116, 240);
    doc.rect(15, 65, 80, 65, "S");

    doc.setFont("helvetica", "bold");
    doc.setFontSize(11);
    doc.setTextColor(3, 29, 68);
    doc.text("GATEWAY LINEN", 30, 93);
    doc.setFont("helvetica", "normal");
    doc.setFontSize(9);
    doc.setTextColor(100, 100, 100);
    doc.text("Verified Product Image", 31, 101);

    const startX = 102;
    doc.setFont("helvetica", "bold");
    doc.setFontSize(12);
    doc.setTextColor(3, 29, 68);
    doc.text("PRODUCT SPECIFICATIONS", startX, 72);

    doc.setDrawColor(40, 116, 240);
    doc.line(startX, 75, startX + 90, 75);

    doc.setFontSize(10);
    doc.setTextColor(60, 60, 60);

    const details = [
      { label: "SKU Code:", val: product.sku },
      {
        label: "Wholesale Price:",
        val: `$${Number(product.price).toFixed(2)} / ${product.unit}`,
      },
      { label: "Stock Status:", val: "In Stock (Commercial Grade)" },
      { label: "Min Order Qty:", val: "2 Units" },
      { label: "Standard Size:", val: selectedSize },
    ];

    let currentY = 85;
    details.forEach((item) => {
      doc.setFont("helvetica", "bold");
      doc.text(item.label, startX, currentY);
      doc.setFont("helvetica", "normal");
      doc.text(item.val, startX + 35, currentY);
      currentY += 9;
    });

    doc.setDrawColor(229, 220, 208);
    doc.line(15, 145, pageWidth - 15, 145);

    doc.setFont("helvetica", "bold");
    doc.setFontSize(13);
    doc.setTextColor(3, 29, 68);
    doc.text("Product Overview & Commercial Details:", 15, 158);

    doc.setFont("helvetica", "normal");
    doc.setFontSize(10);
    doc.setTextColor(80, 80, 80);
    const splitDesc = doc.splitTextToSize(product.description, pageWidth - 30);
    doc.text(splitDesc, 15, 168);

    let bulletY = 195;
    doc.setFont("helvetica", "bold");
    doc.setTextColor(3, 29, 68);
    doc.text("Key Commercial Features:", 15, bulletY);

    const features = [
      "100% Combed Cotton premium weave engineered for hospitality standards.",
      "High durability designed to withstand industrial laundry cycles.",
      "Enhanced softness, resilience, and liquid-resistant protection.",
    ];

    bulletY += 8;
    doc.setFont("helvetica", "normal");
    doc.setTextColor(80, 80, 80);
    features.forEach((feat) => {
      doc.text(`•  ${feat}`, 18, bulletY);
      bulletY += 7;
    });

    doc.setFillColor(241, 243, 246);
    doc.rect(15, pageHeight - 35, pageWidth - 30, 18, "F");

    doc.setFont("helvetica", "bold");
    doc.setFontSize(9);
    doc.setTextColor(40, 116, 240);
    doc.text(
      "Gateway Linen B2B Wholesale & Hospitality Desk",
      20,
      pageHeight - 26
    );

    doc.setFont("helvetica", "normal");
    doc.setFontSize(8);
    doc.setTextColor(100, 100, 100);
    doc.text(
      "Email: gatewaylinen@gmail.com  |  Phone: +1 (204) 979-4044  |  Web: localhost:5173",
      20,
      pageHeight - 21
    );

    doc.save(`${product.name.replace(/\s+/g, "_")}_SellSheet.pdf`);
  };

  const handleAuthAction = (actionCallback) => {
    const loggedInUser = localStorage.getItem("user");
    if (!loggedInUser) setShowLoginModal(true);
    else actionCallback();
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-[#F1F3F6] flex items-center justify-center font-sans">
        <div className="flex flex-col items-center gap-4">
          <div className="w-12 h-12 border-4 border-[#2874F0] border-t-transparent rounded-full animate-spin" />
          <p className="text-[12px] uppercase tracking-widest font-bold text-gray-500">
            Loading Product...
          </p>
        </div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen bg-[#F1F3F6] flex flex-col items-center justify-center font-sans px-4">
        <FiPackage size={56} className="text-gray-300 mb-4" />
        <h2 className="text-2xl font-bold text-gray-800 mb-3">
          Product Not Found
        </h2>
        <button
          onClick={() => navigate("/")}
          className="px-6 py-2.5 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[12px] font-bold uppercase tracking-widest rounded transition-colors cursor-pointer"
        >
          Back to Home
        </button>
      </div>
    );
  }

  const unitPrice = Number(product.price);
  const totalPrice = unitPrice * quantity;
  const originalPrice = unitPrice * 1.35;
  const discount = Math.round(
    ((originalPrice - unitPrice) / originalPrice) * 100
  );
  const seed = Number(String(product.id).slice(-2)) || 50;
  const rating = (3.8 + (seed % 12) / 10).toFixed(1);
  const reviews = 20 + ((seed * 7) % 200);

  return (
    <div className="w-full bg-[#F1F3F6] min-h-screen font-sans pb-16">
      {/* ============ BREADCRUMB ============ */}
      <div className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3">
          <div className="flex items-center gap-2 text-[12px] text-gray-500 flex-wrap">
            <span
              onClick={() => navigate("/")}
              className="hover:text-[#2874F0] cursor-pointer font-medium"
            >
              Home
            </span>
            <FiChevronRight size={12} />
            <span
              onClick={() => navigate("/products")}
              className="hover:text-[#2874F0] cursor-pointer font-medium"
            >
              Products
            </span>
            <FiChevronRight size={12} />
            <span className="hover:text-[#2874F0] cursor-pointer font-medium">
              {product.category}
            </span>
            <FiChevronRight size={12} />
            <span className="text-[#2874F0] font-bold truncate max-w-[200px]">
              {product.name}
            </span>
          </div>
        </div>
      </div>

      {/* ============ MAIN PRODUCT CARD ============ */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-6">
        <div className="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
          <div className="grid lg:grid-cols-12 gap-0">
            {/* ============ LEFT: IMAGE GALLERY ============ */}
            <div className="lg:col-span-5 p-5 md:p-6 lg:sticky lg:top-4 lg:self-start">
              <div className="flex flex-col-reverse sm:flex-col gap-3">
                {/* Main Image */}
                <div className="relative aspect-square bg-[#F1F3F6] rounded-lg overflow-hidden border border-gray-200">
                  <img
                    src={selectedImage}
                    alt={product.name}
                    className="w-full h-full object-cover"
                    onError={(e) => {
                      e.target.src =
                        "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=800";
                    }}
                  />

                  {/* Discount badge */}
                  {discount > 5 && (
                    <span className="absolute top-3 left-3 bg-[#FB641B] text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded shadow-md">
                      {discount}% OFF
                    </span>
                  )}

                  {/* Wishlist */}
                  <button
                    onClick={() =>
                      handleAuthAction(() =>
                        toggleWishlistItem({
                          ...product,
                          id: product.id,
                          price: unitPrice,
                          image: selectedImage,
                        })
                      )
                    }
                    className="absolute top-3 right-3 w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-md hover:scale-110 transition-transform"
                  >
                    <FiHeart
                      size={17}
                      className={
                        isInWishlist(product.id)
                          ? "fill-red-500 text-red-500"
                          : "text-gray-500"
                      }
                    />
                  </button>
                </div>

                {/* Thumbnails */}
                {product.images && product.images.length > 1 && (
                  <div className="flex gap-2.5 overflow-x-auto scrollbar-hide">
                    {product.images.map((img, idx) => (
                      <button
                        key={idx}
                        onClick={() => setSelectedImage(img)}
                        className={`w-16 h-16 sm:w-20 sm:h-20 rounded-md overflow-hidden border-2 transition-all flex-shrink-0 cursor-pointer ${
                          selectedImage === img
                            ? "border-[#2874F0]"
                            : "border-gray-200 opacity-70 hover:opacity-100"
                        }`}
                      >
                        <img
                          src={img}
                          alt=""
                          className="w-full h-full object-cover"
                        />
                      </button>
                    ))}
                  </div>
                )}

                {/* Quick action buttons */}
                <div className="grid grid-cols-2 gap-2.5 mt-1">
                  <button
                    onClick={downloadSellSheetPDF}
                    className="flex items-center justify-center gap-1.5 text-[11px] font-bold text-[#2874F0] hover:text-white bg-white hover:bg-[#2874F0] border border-[#2874F0] px-3 py-2.5 rounded transition-colors"
                  >
                    <FiDownload size={13} /> Sell Sheet
                  </button>
                  <button className="flex items-center justify-center gap-1.5 text-[11px] font-bold text-[#2874F0] hover:text-white bg-white hover:bg-[#2874F0] border border-[#2874F0] px-3 py-2.5 rounded transition-colors">
                    <FiPackage size={13} /> Compare
                  </button>
                </div>
              </div>
            </div>

            {/* ============ RIGHT: PRODUCT INFO ============ */}
            <div className="lg:col-span-7 p-5 md:p-6 lg:p-8 border-l border-gray-100">
              {/* Category + SKU */}
              <div className="flex items-center justify-between mb-2">
                <span className="text-[11px] font-bold text-[#FB641B] uppercase tracking-wider">
                  {product.category}
                </span>
                <span className="text-[10px] text-gray-500 uppercase tracking-wider font-bold">
                  SKU: <span className="text-gray-700">{product.sku}</span>
                </span>
              </div>

              {/* Title */}
              <h1 className="text-[20px] md:text-[24px] font-bold text-gray-800 leading-tight mb-3">
                {product.name}
              </h1>

              {/* Rating row */}
              <div className="flex items-center gap-2.5 mb-4 flex-wrap">
                <div className="flex items-center gap-0.5 bg-[#10B981] text-white text-[12px] font-bold px-2 py-0.5 rounded">
                  {rating}
                  <FiStar size={11} fill="white" />
                </div>
                <span className="text-[11px] text-gray-500">
                  {reviews} ratings
                </span>
                <span className="w-px h-3 bg-gray-300" />
                <span className="text-[11px] text-[#2874F0] font-bold flex items-center gap-1">
                  <FiCheck size={11} /> Verified Quality
                </span>
              </div>

              {/* Price block */}
              <div className="mb-5 pb-5 border-b border-gray-100">
                <div className="flex items-baseline gap-2 mb-1">
                  <span className="text-[13px] text-gray-500">Starting from</span>
                </div>
                <div className="flex items-baseline gap-3 flex-wrap">
                  <span className="text-[28px] md:text-[32px] font-bold text-gray-800">
                    ${unitPrice.toFixed(2)}
                  </span>
                  <span className="text-[14px] text-gray-400 line-through">
                    ${originalPrice.toFixed(2)}
                  </span>
                  <span className="text-[13px] font-bold text-green-600">
                    Save ${(originalPrice - unitPrice).toFixed(2)} ({discount}%)
                  </span>
                </div>
                <p className="text-[11px] text-gray-500 mt-1">
                  Per <span className="font-bold uppercase">{product.unit}</span>{" "}
                  · Taxes included
                </p>
              </div>

              {/* Trust strip */}
              <div className="grid grid-cols-3 gap-3 mb-5 pb-5 border-b border-gray-100">
                {[
                  { icon: FiTruck, label: "Free Shipping", sub: "Over $350" },
                  { icon: FiRefreshCw, label: "30-Day Returns", sub: "Easy" },
                  { icon: FiShield, label: "Secure Payment", sub: "100%" },
                ].map((item, i) => (
                  <div key={i} className="flex items-center gap-2">
                    <div className="w-9 h-9 bg-[#EAF2FF] rounded-full flex items-center justify-center shrink-0">
                      <item.icon size={15} className="text-[#2874F0]" />
                    </div>
                    <div className="min-w-0">
                      <p className="text-[11px] font-bold text-gray-800 leading-tight">
                        {item.label}
                      </p>
                      <p className="text-[10px] text-gray-500 mt-0.5">
                        {item.sub}
                      </p>
                    </div>
                  </div>
                ))}
              </div>

              {/* Size selector */}
              <div className="mb-5">
                <label className="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2.5">
                  Select Size
                </label>
                <div className="flex flex-wrap gap-2">
                  {["Standard", "Queen Size", "King Size"].map((size) => (
                    <button
                      key={size}
                      onClick={() => setSelectedSize(size)}
                      className={`px-4 py-2.5 rounded text-[12px] font-bold transition-all cursor-pointer border-2 ${
                        selectedSize === size
                          ? "bg-[#2874F0] text-white border-[#2874F0] shadow-md"
                          : "bg-white text-gray-700 border-gray-300 hover:border-[#2874F0]"
                      }`}
                    >
                      {size}
                    </button>
                  ))}
                </div>
              </div>

              {/* Quantity + Add to Cart */}
              <div className="flex flex-col sm:flex-row items-stretch gap-3 mb-4">
                <div className="flex items-center border-2 border-gray-300 rounded overflow-hidden shrink-0">
                  <button
                    onClick={() => setQuantity(Math.max(1, quantity - 1))}
                    className="w-10 h-12 flex items-center justify-center text-[#2874F0] hover:bg-[#EAF2FF] transition-colors cursor-pointer disabled:opacity-30"
                    disabled={quantity <= 1}
                  >
                    <FiMinus size={14} />
                  </button>
                  <span className="w-12 h-12 flex items-center justify-center text-[15px] font-bold text-gray-800 border-x-2 border-gray-300">
                    {quantity}
                  </span>
                  <button
                    onClick={() => setQuantity(quantity + 1)}
                    className="w-10 h-12 flex items-center justify-center text-[#2874F0] hover:bg-[#EAF2FF] transition-colors cursor-pointer"
                  >
                    <FiPlus size={14} />
                  </button>
                </div>

                <button
                  onClick={() =>
                    handleAuthAction(() => {
                      addToCart(
                        {
                          ...product,
                          id: product.id,
                          name: product.name,
                          image: selectedImage,
                        },
                        quantity,
                        selectedSize,
                        totalPrice
                      );
                      setSuccessMessage(
                        `Successfully added ${quantity} item(s) to your cart!`
                      );
                      setShowSuccessModal(true);
                    })
                  }
                  className="flex-1 py-3.5 bg-[#FB641B] hover:bg-[#e55a15] text-white text-[13px] font-bold tracking-widest uppercase rounded transition-all shadow-lg shadow-[#FB641B]/30 hover:shadow-[#FB641B]/50 hover:-translate-y-0.5 flex items-center justify-center gap-2 group"
                >
                  <FiShoppingCart size={16} />
                  Add to Cart · ${totalPrice.toFixed(2)}
                  <FiArrowRight
                    size={14}
                    className="group-hover:translate-x-1 transition-transform"
                  />
                </button>
              </div>

              {/* Wishlist button */}
              <button
                onClick={() =>
                  handleAuthAction(() => {
                    toggleWishlistItem({
                      ...product,
                      id: product.id,
                      price: unitPrice,
                      image: selectedImage,
                    });
                    setSuccessMessage(
                      `Successfully added "${product.name}" to your wishlist!`
                    );
                    setShowSuccessModal(true);
                  })
                }
                className="w-full py-3 bg-white border-2 border-[#2874F0] text-[#2874F0] hover:bg-[#2874F0] hover:text-white text-[12px] font-bold uppercase tracking-widest rounded transition-colors flex items-center justify-center gap-2 mb-5"
              >
                <FiHeart size={14} /> Add to Wishlist
              </button>

              {/* Info boxes */}
              <div className="space-y-2">
                <div className="text-[11.5px] text-gray-700 bg-[#F1F3F6] p-3 rounded border border-gray-200 flex items-start gap-2">
                  <FiPackage
                    size={14}
                    className="text-[#2874F0] mt-0.5 shrink-0"
                  />
                  <span>
                    You will receive{" "}
                    <span className="font-bold">1 box</span> — total{" "}
                    <span className="font-bold">
                      {quantity} {product.unit}
                    </span>{" "}
                    of SKU: {product.sku}
                  </span>
                </div>
                <div className="text-[11.5px] text-gray-700 bg-[#FFF8E6] p-3 rounded border border-[#FF9F00]/30 flex items-center justify-between gap-2">
                  <div className="flex items-center gap-2">
                    <FiPercent size={14} className="text-[#FF9F00] shrink-0" />
                    <span>Min quantity: 2 units</span>
                  </div>
                  <span className="text-[#FB641B] font-bold text-[10px] uppercase tracking-wider">
                    Free shipping $350+
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* ============ TABS SECTION ============ */}
        <div className="bg-white rounded-lg border border-gray-200 shadow-sm mt-6 overflow-hidden">
          <div className="bg-[#F1F3F6] border-b border-gray-200 overflow-x-auto scrollbar-hide">
            <div className="flex">
              {["Details", "Shipping", "Return", "Warranty", "FAQs"].map(
                (tab) => (
                  <button
                    key={tab}
                    onClick={() => setActiveTab(tab)}
                    className={`px-5 md:px-7 py-4 text-[12px] md:text-[13px] font-bold uppercase tracking-wider transition-all cursor-pointer whitespace-nowrap border-b-3 ${
                      activeTab === tab
                        ? "text-[#2874F0] border-[#2874F0] bg-white"
                        : "text-gray-600 border-transparent hover:bg-white/50"
                    }`}
                    style={{
                      borderBottomWidth: activeTab === tab ? "3px" : "3px",
                    }}
                  >
                    {tab}
                  </button>
                )
              )}
            </div>
          </div>

          <div className="p-5 md:p-7 text-[12px] md:text-[13px] text-gray-600 leading-relaxed">
            {activeTab === "Details" && (
              <>
                <h3 className="text-[15px] md:text-[16px] font-bold text-gray-800 mb-3">
                  Product Overview
                </h3>
                <p className="mb-4">{product.description}</p>
                <h4 className="font-bold text-gray-800 mb-2.5">
                  Specifications & Features
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                  {[
                    "100% Combed Cotton premium weave",
                    "Commercial grade hotel standard durability",
                    "Enhanced resilience, softness, and absorbency",
                    "Machine washable & dryer-safe industrial build",
                  ].map((feat, i) => (
                    <div
                      key={i}
                      className="flex items-center gap-2 py-1.5"
                    >
                      <div className="w-4 h-4 rounded-full bg-[#EAF2FF] flex items-center justify-center shrink-0">
                        <FiCheck size={9} className="text-[#2874F0]" />
                      </div>
                      <span>{feat}</span>
                    </div>
                  ))}
                </div>
              </>
            )}

            {activeTab === "Shipping" && (
              <>
                <h3 className="text-[15px] md:text-[16px] font-bold text-gray-800 mb-3">
                  Shipping Information
                </h3>
                <p className="mb-2">
                  We offer priority fast shipping across Canada and the US for
                  all hospitality commercial partners.
                </p>
                <p>
                  Free shipping is automatically applied on all bulk orders
                  above $350.00.
                </p>
              </>
            )}

            {activeTab === "Return" && (
              <>
                <h3 className="text-[15px] md:text-[16px] font-bold text-gray-800 mb-3">
                  Return Policy
                </h3>
                <p>
                  Commercial returns are accepted within 30 days of purchase
                  for unused items in original packaging.
                </p>
              </>
            )}

            {activeTab === "Warranty" && (
              <>
                <h3 className="text-[15px] md:text-[16px] font-bold text-gray-800 mb-3">
                  Product Warranty
                </h3>
                <p>
                  Backed by our 1-year institutional quality guarantee against
                  manufacturing defects.
                </p>
              </>
            )}

            {activeTab === "FAQs" && (
              <>
                <h3 className="text-[15px] md:text-[16px] font-bold text-gray-800 mb-3">
                  Frequently Asked Questions
                </h3>
                <p>
                  <strong>
                    Q: Are these suitable for commercial laundries?
                  </strong>
                  <br />
                  A: Yes, all our textiles are engineered to withstand
                  industrial washing and high-heat drying cycles.
                </p>
              </>
            )}
          </div>
        </div>

        {/* ============ RELATED PRODUCTS ============ */}
        {relatedProducts.length > 0 && (
          <div className="mt-8 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div className="bg-[#F1F3F6] px-5 md:px-6 py-4 border-b border-gray-200 flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 bg-[#EAF2FF] text-[#2874F0] rounded-full flex items-center justify-center">
                  <FiPackage size={16} />
                </div>
                <div>
                  <h2 className="text-[15px] font-bold text-gray-800">
                    Related Products
                  </h2>
                  <p className="text-[11px] text-gray-500">
                    You may also like these
                  </p>
                </div>
              </div>
              <button
                onClick={() => navigate("/products")}
                className="text-[11px] font-bold text-[#2874F0] hover:text-[#FB641B] uppercase tracking-wider transition-colors flex items-center gap-1"
              >
                View All <FiChevronRight size={13} />
              </button>
            </div>

            <div className="p-5 md:p-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
              {relatedProducts.map((item) => {
                const relId = item.id;
                const relName = item.name || item.Name;
                const relPrice = Number(item.basePrice || item.price || 24.78);
                const relUnit = item.unit || item.Unit || "DZ";
                const seedR = Number(String(relId).slice(-2)) || 50;
                const relRating = (3.8 + (seedR % 12) / 10).toFixed(1);

                return (
                  <div
                    key={relId}
                    onClick={() => navigate(`/product/${relId}`)}
                    className="group bg-white rounded-lg border border-gray-200 hover:border-[#2874F0] hover:shadow-lg hover:-translate-y-1 transition-all cursor-pointer overflow-hidden"
                  >
                    <div className="relative aspect-square bg-[#F1F3F6] overflow-hidden">
                      <img
                        src={item.resolvedImage}
                        alt={relName}
                        className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                        onError={(e) => {
                          e.target.src =
                            "https://images.pexels.com/photos/1034584/pexels-photo-1034584.jpeg?auto=compress&cs=tinysrgb&w=500";
                        }}
                      />
                      <span className="absolute top-2 left-2 bg-[#FB641B] text-white text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded">
                        25% OFF
                      </span>
                    </div>
                    <div className="p-3">
                      <h3 className="text-[12px] font-semibold text-gray-800 line-clamp-2 mb-2 min-h-[32px] leading-tight group-hover:text-[#2874F0] transition-colors">
                        {relName}
                      </h3>
                      <div className="flex items-center gap-1 mb-2">
                        <div className="flex items-center gap-0.5 bg-[#10B981] text-white text-[9px] font-bold px-1.5 py-0.5 rounded">
                          {relRating}
                          <FiStar size={8} fill="white" />
                        </div>
                        <span className="text-[9px] text-gray-500">
                          ({20 + ((seedR * 7) % 200)})
                        </span>
                      </div>
                      <div className="flex items-baseline gap-1.5">
                        <span className="text-[14px] font-bold text-gray-800">
                          ${relPrice.toFixed(2)}
                        </span>
                        <span className="text-[10px] text-gray-400">
                          /{relUnit}
                        </span>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )}
      </div>

      {/* ============ LOGIN MODAL ============ */}
      {showLoginModal && (
        <div className="fixed inset-0 z-[300] flex items-center justify-center bg-black/70 backdrop-blur-md p-4">
          <div className="bg-white p-7 rounded-lg shadow-2xl w-full max-w-sm text-center relative">
            <button
              onClick={() => setShowLoginModal(false)}
              className="absolute top-3 right-3 text-gray-400 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 p-1.5 rounded-full transition-colors"
            >
              <FiX size={16} />
            </button>
            <div className="w-14 h-14 bg-[#2874F0] text-white rounded-full flex items-center justify-center mx-auto mb-4">
              <FiUser size={26} />
            </div>
            <h3 className="text-[18px] font-bold text-gray-800 mb-2">
              Login Required
            </h3>
            <p className="text-[12px] text-gray-600 mb-6 leading-relaxed">
              Please login first to add items to your cart, wishlist, or
              proceed to checkout.
            </p>
            <button
              onClick={() => navigate("/login")}
              className="w-full py-3.5 bg-[#FB641B] text-white rounded text-[12px] font-bold uppercase tracking-wider hover:bg-[#e55a15] transition-all shadow-md"
            >
              Login Now
            </button>
          </div>
        </div>
      )}

      {/* ============ SUCCESS MODAL ============ */}
      {showSuccessModal && (
        <div className="fixed inset-0 z-[300] flex items-center justify-center bg-black/70 backdrop-blur-md p-4">
          <div className="bg-white p-7 rounded-lg shadow-2xl w-full max-w-sm text-center relative">
            <button
              onClick={() => setShowSuccessModal(false)}
              className="absolute top-3 right-3 text-gray-400 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 p-1.5 rounded-full transition-colors"
            >
              <FiX size={16} />
            </button>
            <div className="relative mx-auto mb-4 w-fit">
              <div className="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center relative z-10">
                <FiCheck size={28} />
              </div>
              <div className="absolute inset-0 rounded-full bg-green-500/20 animate-ping" />
            </div>
            <h3 className="text-[18px] font-bold text-gray-800 mb-2">
              Added Successfully!
            </h3>
            <p className="text-[12px] text-gray-600 mb-6 leading-relaxed">
              {successMessage}
            </p>
            <div className="flex flex-col gap-2.5">
              <button
                onClick={() => {
                  setShowSuccessModal(false);
                  navigate("/cart");
                }}
                className="w-full py-3.5 bg-[#FB641B] text-white rounded text-[12px] font-bold uppercase tracking-wider hover:bg-[#e55a15] transition-all shadow-md"
              >
                View Cart & Checkout
              </button>
              <button
                onClick={() => setShowSuccessModal(false)}
                className="w-full py-3 bg-white border border-gray-300 text-gray-700 rounded text-[12px] font-bold uppercase tracking-wider hover:bg-gray-50 transition-all"
              >
                Continue Shopping
              </button>
            </div>
          </div>
        </div>
      )}

      <style>{`
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
      `}</style>
    </div>
  );
};

export default ProductDetail;