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
} from "react-icons/fi";
import { jsPDF } from "jspdf";
import { useWishlist } from "../context/WishlistContext";
import { useCart } from "../context/CartContext";

const resolveImgPath = (rawImg) => {
  let finalImg =
    "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=800";
  if (rawImg) {
    if (rawImg.startsWith("http")) {
      finalImg = rawImg;
    } else if (rawImg.startsWith("/")) {
      finalImg = `http://localhost${rawImg}`;
    } else {
      const cleanPath = rawImg.replace(/^\/+/, "");
      if (cleanPath.includes("Gateway-Linen")) {
        finalImg = `http://localhost/${cleanPath}`;
      } else {
        finalImg = `http://localhost/Gateway-Linen/GatewayLinenAdmin-main/${cleanPath}`;
      }
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

  // Custom Success Modal State
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
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
            },
          },
        );
        const result = await response.json();

        let prodData = null;
        if (result.success && result.data) {
          prodData = result.data;
        } else if (result.product) {
          prodData = result.product;
        }

        const allRes = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/products/api.php?action=get_products",
        );
        const allResult = await allRes.json();

        let items = [];
        if (allResult.success && Array.isArray(allResult.data)) {
          items = allResult.data;
        } else if (allResult.success && allResult.data?.items) {
          items = allResult.data.items;
        } else if (Array.isArray(allResult)) {
          items = allResult;
        }

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

        if (!prodData && id) {
          prodData = allItems.find((p) => String(p.id) === String(id));
        }

        if (prodData) {
          let imagesList = [];
          if (
            prodData.images &&
            Array.isArray(prodData.images) &&
            prodData.images.length > 0
          ) {
            imagesList = prodData.images.map((img) =>
              resolveImgPath(img.imageUrl || img.ImageUrl),
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
              prodData.basePrice || prodData.price || prodData.Price || 200.04,
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
                (currentProd.category || "").toLowerCase(),
          );
          setRelatedProducts(
            filteredRelated.length > 0 ? filteredRelated : allItems.slice(0, 5),
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

    doc.setFillColor(3, 29, 68);
    doc.rect(10, 10, pageWidth - 20, 25, "F");

    doc.setTextColor(255, 255, 255);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(16);
    doc.text("GATEWAY LINEN - WHOLESALE SELL SHEET", 15, 26);

    doc.setTextColor(181, 142, 88);
    doc.setFontSize(10);
    doc.text(`COLLECTION: ${product.category.toUpperCase()}`, 15, 45);

    doc.setTextColor(3, 29, 68);
    doc.setFontSize(24);
    doc.text(product.name, 15, 55);

    doc.setFillColor(250, 247, 242);
    doc.rect(15, 65, 80, 65, "F");
    doc.setDrawColor(181, 142, 88);
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

    doc.setDrawColor(181, 142, 88);
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

    doc.setFillColor(250, 247, 242);
    doc.rect(15, pageHeight - 35, pageWidth - 30, 18, "F");

    doc.setFont("helvetica", "bold");
    doc.setFontSize(9);
    doc.setTextColor(3, 29, 68);
    doc.text(
      "Gateway Linen B2B Wholesale & Hospitality Desk",
      20,
      pageHeight - 26,
    );

    doc.setFont("helvetica", "normal");
    doc.setFontSize(8);
    doc.setTextColor(100, 100, 100);
    doc.text(
      "Email: gatewaylinen@gmail.com  |  Phone: +1 (204) 979-4044  |  Web: localhost:5173",
      20,
      pageHeight - 21,
    );

    doc.save(`${product.name.replace(/\s+/g, "_")}_SellSheet.pdf`);
  };

  const handleAuthAction = (actionCallback) => {
    const loggedInUser = localStorage.getItem("user");
    if (!loggedInUser) {
      setShowLoginModal(true);
    } else {
      actionCallback();
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-white flex items-center justify-center font-sans text-xs uppercase tracking-widest font-bold text-gray-500">
        Loading Product Details...
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen bg-white flex flex-col items-center justify-center font-sans px-4">
        <h2 className="text-2xl font-serif font-bold text-[#031D44] mb-3">
          Product Not Found
        </h2>
        <button
          onClick={() => navigate("/")}
          className="px-6 py-2.5 bg-[#031D44] text-white text-xs font-bold uppercase tracking-widest rounded-xl cursor-pointer"
        >
          Back to Home
        </button>
      </div>
    );
  }

  const unitPrice = Number(product.price);
  const totalPrice = unitPrice * quantity;
  const formattedPrice = `$${totalPrice.toFixed(2)} / ${product.unit}`;

  return (
    <div className="w-full bg-white min-h-screen py-6 sm:py-10 px-4 sm:px-6 md:px-12 font-sans text-gray-800">
      <div className="max-w-[1300px] mx-auto">
        {/* Back Button */}
        <button
          onClick={() => navigate(-1)}
          className="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 bg-gray-50 border border-gray-200 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl text-[#031D44] hover:border-[#B58E58] transition-all cursor-pointer mb-5 shadow-2xs"
        >
          <FiArrowLeft size={13} /> Back
        </button>

        {/* Main Product Section */}
        <div className="bg-[#FAF7F2] border border-[#E5DCD0] rounded-[20px] md:rounded-[24px] p-4 sm:p-6 md:p-10 shadow-sm grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 lg:gap-10 mb-8 sm:mb-12">
          {/* Left: Image Gallery */}
          <div className="lg:col-span-6 flex flex-col gap-3">
            <div className="w-full aspect-[4/3] sm:aspect-[4/3] bg-white rounded-[16px] sm:rounded-2xl overflow-hidden border border-[#E5DCD0] relative shadow-inner">
              <img
                src={selectedImage}
                alt={product.name}
                className="w-full h-full object-cover"
                onError={(e) => {
                  e.target.src =
                    "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=800";
                }}
              />
            </div>

            {/* Thumbnail Row */}
            {product.images && product.images.length > 1 && (
              <div className="flex gap-2.5 overflow-x-auto pb-1 scrollbar-hide">
                {product.images.map((img, idx) => (
                  <button
                    key={idx}
                    onClick={() => setSelectedImage(img)}
                    className={`w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden border-2 transition-all flex-shrink-0 cursor-pointer ${
                      selectedImage === img
                        ? "border-[#4A5D4E]"
                        : "border-[#E5DCD0] opacity-70 hover:opacity-100"
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
          </div>

          {/* Right: Product Info & Actions */}
          <div className="lg:col-span-6 flex flex-col justify-between">
            <div>
              <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-2 sm:mb-3">
                <span className="text-[10px] sm:text-xs font-bold text-[#B58E58] tracking-widest uppercase">
                  {product.category}
                </span>

                <div className="flex flex-col items-start sm:items-end gap-1.5 w-full sm:w-auto">
                  <div className="flex items-center justify-between sm:justify-end gap-2.5 w-full sm:w-auto">
                    <button
                      onClick={downloadSellSheetPDF}
                      className="inline-flex items-center gap-1.5 text-[10px] sm:text-xs font-bold text-[#031D44] hover:text-[#B58E58] transition-colors cursor-pointer bg-white px-3 sm:px-3.5 py-1.5 rounded-full border border-[#E5DCD0] shadow-2xs"
                    >
                      <FiDownload size={12} />{" "}
                      <span className="hidden sm:inline">Download</span> Sell
                      Sheet
                    </button>
                    <span className="inline-flex items-center gap-1 text-emerald-600 font-bold text-[9px] sm:text-[10px] uppercase bg-emerald-50 px-2 py-1 rounded-full border border-emerald-200">
                      <FiCheck size={10} /> IN STOCK
                    </span>
                  </div>
                  <div className="text-[9px] sm:text-[10px] text-gray-500 uppercase tracking-widest">
                    SKU:{" "}
                    <span className="font-bold text-gray-700">
                      {product.sku}
                    </span>
                  </div>
                </div>
              </div>

              <h1 className="text-xl sm:text-2xl md:text-4xl font-serif font-bold text-[#031D44] mb-2 sm:mb-3 leading-tight">
                {product.name}
              </h1>

              <div className="text-lg sm:text-xl md:text-2xl font-bold text-[#4A5D4E] mb-5 sm:mb-6">
                {formattedPrice}
              </div>

              <hr className="border-[#E5DCD0] mb-5 sm:mb-6" />

              {/* Size Selector */}
              <div className="mb-5 sm:mb-6">
                <label className="block text-[10px] sm:text-xs font-bold text-[#031D44] uppercase tracking-wider mb-2 sm:mb-2.5">
                  Select Size Option:
                </label>
                <div className="flex flex-wrap gap-2 sm:gap-2.5">
                  {["Standard", "Queen Size", "King Size"].map((size) => (
                    <button
                      key={size}
                      onClick={() => setSelectedSize(size)}
                      className={`px-3 sm:px-4 py-1.5 sm:py-2 rounded-[10px] sm:rounded-xl text-[10px] sm:text-xs font-bold transition-all cursor-pointer border ${
                        selectedSize === size
                          ? "bg-[#4A5D4E] text-white border-[#4A5D4E] shadow-sm"
                          : "bg-white text-gray-700 border-[#E5DCD0] hover:border-[#4A5D4E]"
                      }`}
                    >
                      {size}
                    </button>
                  ))}
                </div>
              </div>

              {/* Quantity & Add to Cart */}
              <div className="flex flex-row items-stretch sm:items-center gap-2.5 sm:gap-4 mb-4">
                <div className="flex items-center border border-[#E5DCD0] rounded-xl bg-white overflow-hidden shrink-0">
                  <button
                    onClick={() => setQuantity(Math.max(1, quantity - 1))}
                    className="px-3 sm:px-4 py-2.5 sm:py-3 text-gray-600 hover:bg-[#E5DCD0]/50 transition-colors font-bold cursor-pointer"
                  >
                    -
                  </button>
                  <span className="w-8 sm:w-10 text-center text-xs sm:text-sm font-bold text-[#031D44]">
                    {quantity}
                  </span>
                  <button
                    onClick={() => setQuantity(quantity + 1)}
                    className="px-3 sm:px-4 py-2.5 sm:py-3 text-gray-600 hover:bg-[#E5DCD0]/50 transition-colors font-bold cursor-pointer"
                  >
                    +
                  </button>
                </div>

                <button
                  onClick={() => {
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
                        totalPrice,
                      );
                      setSuccessMessage(
                        `Successfully added ${quantity} item(s) to your cart!`,
                      );
                      setShowSuccessModal(true);
                    });
                  }}
                  className="flex-1 py-2.5 sm:py-3.5 px-4 sm:px-6 bg-[#4A5D4E] hover:bg-[#031D44] text-white text-[10px] sm:text-xs font-bold tracking-widest uppercase rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5 cursor-pointer"
                >
                  <FiShoppingCart size={15} /> Add to Cart
                </button>
              </div>

              {/* Eden Textile Extra Info Boxes */}
              <div className="space-y-2 mb-5">
                <div className="text-[10px] sm:text-xs text-gray-600 font-light bg-white p-2.5 sm:p-3 rounded-xl border border-[#E5DCD0]">
                  You will receive{" "}
                  <span className="font-bold text-gray-800">1 box</span>, making
                  your total quantity{" "}
                  <span className="font-bold text-gray-800">
                    {quantity} {product.unit}
                  </span>{" "}
                  of the SKU: {product.sku}
                </div>
                <div className="text-[10px] sm:text-xs text-gray-600 font-medium bg-white p-2.5 rounded-xl border border-[#E5DCD0] flex justify-between items-center">
                  <span>Min Quantity: 2</span>
                  <span className="text-[#4A5D4E] font-bold">
                    Free shipping over $350
                  </span>
                </div>
              </div>
            </div>

            {/* Wishlist action */}
            <div className="flex items-center gap-4 pt-3.5 border-t border-[#E5DCD0] text-[10px] sm:text-xs text-gray-600 font-semibold">
              <button
                onClick={() => {
                  handleAuthAction(() => {
                    toggleWishlistItem({
                      ...product,
                      id: product.id,
                      price: formattedPrice,
                      image: selectedImage,
                    });
                    setSuccessMessage(
                      `Successfully added "${product.name}" to your wishlist!`,
                    );
                    setShowSuccessModal(true);
                  });
                }}
                className="flex items-center gap-1.5 hover:text-[#B58E58] transition-colors cursor-pointer"
              >
                <FiHeart
                  size={15}
                  className={
                    isInWishlist(product.id)
                      ? "fill-red-500 text-red-500"
                      : "text-gray-400"
                  }
                />
                <span>ADD TO WISH LIST</span>
              </button>
            </div>
          </div>
        </div>

        {/* Tabs Section */}
        <div className="bg-[#FAF7F2] border border-[#E5DCD0] rounded-[20px] md:rounded-[24px] p-5 sm:p-6 md:p-10 shadow-sm mb-12 sm:mb-16">
          <div className="flex items-center gap-4 sm:gap-6 md:gap-10 border-b border-[#E5DCD0] pb-3 sm:pb-4 mb-4 sm:mb-6 overflow-x-auto scrollbar-hide">
            {["Details", "Shipping", "Return", "Warranty", "FAQs"].map(
              (tab) => (
                <button
                  key={tab}
                  onClick={() => setActiveTab(tab)}
                  className={`text-xs sm:text-sm font-serif font-bold pb-1.5 sm:pb-2 transition-colors cursor-pointer relative whitespace-nowrap ${
                    activeTab === tab
                      ? "text-[#4A5D4E]"
                      : "text-gray-400 hover:text-gray-700"
                  }`}
                >
                  {tab}
                  {activeTab === tab && (
                    <span className="absolute bottom-[-13px] sm:bottom-[-17px] left-0 w-full h-[2px] bg-[#4A5D4E]"></span>
                  )}
                </button>
              ),
            )}
          </div>

          <div className="text-[11px] sm:text-sm text-gray-600 font-light leading-relaxed space-y-3 sm:space-y-4 pt-1 sm:pt-2">
            {activeTab === "Details" && (
              <>
                <h3 className="text-sm sm:text-base font-serif font-bold text-[#031D44]">
                  Product Overview
                </h3>
                <p>{product.description}</p>
                <h4 className="font-bold text-[#031D44] pt-1.5 sm:pt-2">
                  Specifications & Features:
                </h4>
                <ul className="list-disc pl-4 sm:pl-5 space-y-1 sm:space-y-1.5 text-[11px] sm:text-xs text-gray-600">
                  <li>100% Combed Cotton premium weave</li>
                  <li>Commercial grade hotel standard durability</li>
                  <li>Enhanced resilience, softness, and absorbency</li>
                  <li>Machine washable & dryer-safe industrial build</li>
                </ul>
              </>
            )}

            {activeTab === "Shipping" && (
              <>
                <h3 className="text-sm sm:text-base font-serif font-bold text-[#031D44]">
                  Shipping Information
                </h3>
                <p>
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
                <h3 className="text-sm sm:text-base font-serif font-bold text-[#031D44]">
                  Return Policy
                </h3>
                <p>
                  Commercial returns are accepted within 30 days of purchase for
                  unused items in original packaging.
                </p>
              </>
            )}

            {activeTab === "Warranty" && (
              <>
                <h3 className="text-sm sm:text-base font-serif font-bold text-[#031D44]">
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
                <h3 className="text-sm sm:text-base font-serif font-bold text-[#031D44]">
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

        {/* RELATED PRODUCTS SECTION */}
        {relatedProducts.length > 0 && (
          <div className="mb-10">
            <div className="flex justify-between items-center mb-5 sm:mb-6">
              <h2 className="text-xl sm:text-2xl font-serif font-bold text-[#031D44]">
                Related Products
              </h2>
              <div className="flex items-center gap-2 sm:gap-3">
                <button
                  onClick={() => navigate("/products")}
                  className="hidden sm:inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-[#B58E58] hover:text-[#031D44] transition-colors mr-2"
                >
                  View All <FiArrowRight size={12} />
                </button>
              </div>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-5">
              {relatedProducts.map((item) => {
                const relId = item.id;
                const relName = item.name || item.Name;
                const relPrice = Number(item.basePrice || item.price || 24.78);
                const relUnit = item.unit || item.Unit || "DZ";

                return (
                  <div
                    key={relId}
                    onClick={() => navigate(`/product/${relId}`)}
                    className="bg-white border border-[#E5DCD0] rounded-[16px] sm:rounded-2xl p-3 sm:p-4 flex flex-col justify-between group cursor-pointer shadow-2xs hover:shadow-md hover:border-[#B58E58] transition-all"
                  >
                    <div>
                      <div className="w-full aspect-square bg-gray-50 rounded-xl overflow-hidden mb-2.5 sm:mb-3 relative border border-gray-100">
                        <img
                          src={item.resolvedImage}
                          alt={relName}
                          className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                          onError={(e) => {
                            e.target.src =
                              "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=500";
                          }}
                        />
                      </div>
                      <h3 className="text-[11px] sm:text-xs font-bold text-[#031D44] mb-2 line-clamp-2 min-h-[32px] leading-tight">
                        {relName}
                      </h3>
                    </div>

                    <div className="pt-2 sm:pt-3 border-t border-[#E5DCD0] flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-0.5 sm:gap-1">
                      <span className="text-[9px] sm:text-[10px] text-gray-400">
                        Starting at:
                      </span>
                      <span className="text-[11px] sm:text-xs font-bold text-[#B58E58]">
                        $ {relPrice.toFixed(2)} / {relUnit}
                      </span>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Mobile View All Button */}
            <div className="mt-4 text-center sm:hidden">
              <button
                onClick={() => navigate("/products")}
                className="w-full py-3 bg-white border border-[#E5DCD0] text-[#031D44] rounded-xl text-[10px] font-bold uppercase tracking-widest flex items-center justify-center gap-1.5 shadow-2xs hover:bg-gray-50"
              >
                View All Related <FiArrowRight size={12} />
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Login Modal */}
      {showLoginModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-[#FAF7F2] border border-[#E5DCD0] p-6 sm:p-8 rounded-[20px] sm:rounded-[24px] shadow-2xl w-full max-w-sm text-center relative">
            <button
              onClick={() => setShowLoginModal(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-800 bg-white p-1.5 rounded-full transition-colors cursor-pointer border border-[#E5DCD0]"
            >
              <FiX size={16} />
            </button>
            <div className="w-12 h-12 sm:w-14 sm:h-14 bg-[#031D44] text-[#B58E58] rounded-xl flex items-center justify-center mx-auto mb-3 sm:mb-4 shadow-md">
              <FiUser size={22} />
            </div>
            <h3 className="text-base sm:text-lg font-serif font-bold text-[#031D44] mb-1.5 sm:mb-2">
              Login Required
            </h3>
            <p className="text-[11px] sm:text-xs text-gray-600 mb-5 sm:mb-6 font-light leading-relaxed">
              Please login first to add items to your cart, wishlist, or proceed
              to checkout.
            </p>
            <button
              onClick={() => navigate("/login")}
              className="w-full py-2.5 sm:py-3 bg-[#031D44] text-white rounded-xl text-[10px] sm:text-xs font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all cursor-pointer"
            >
              Login Now
            </button>
          </div>
        </div>
      )}

      {/* Custom Luxurious Success Modal */}
      {showSuccessModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-in fade-in duration-200">
          <div className="bg-[#FAF7F2] border border-[#E5DCD0] p-6 sm:p-8 rounded-[20px] sm:rounded-[24px] shadow-2xl w-full max-w-sm text-center relative">
            <button
              onClick={() => setShowSuccessModal(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-800 bg-white p-1.5 rounded-full transition-colors cursor-pointer border border-[#E5DCD0]"
            >
              <FiX size={16} />
            </button>
            <div className="w-12 h-12 sm:w-14 sm:h-14 bg-[#4A5D4E] text-white rounded-xl flex items-center justify-center mx-auto mb-3 sm:mb-4 shadow-md">
              <FiCheck size={24} />
            </div>
            <h3 className="text-base sm:text-lg font-serif font-bold text-[#031D44] mb-1.5 sm:mb-2">
              Successfully Added
            </h3>
            <p className="text-[11px] sm:text-xs text-gray-600 mb-5 sm:mb-6 font-light leading-relaxed">
              {successMessage}
            </p>
            <div className="flex flex-col gap-2">
              <button
                onClick={() => {
                  setShowSuccessModal(false);
                  navigate("/cart");
                }}
                className="w-full py-2.5 sm:py-3 bg-[#031D44] text-white rounded-xl text-[10px] sm:text-xs font-bold tracking-widest uppercase shadow-md hover:bg-[#B58E58] transition-all cursor-pointer"
              >
                View Cart & Checkout
              </button>
              <button
                onClick={() => setShowSuccessModal(false)}
                className="w-full py-2 sm:py-2.5 bg-white border border-[#E5DCD0] text-gray-700 rounded-xl text-[10px] sm:text-xs font-bold tracking-widest uppercase hover:bg-gray-50 transition-all cursor-pointer"
              >
                Continue Shopping
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ProductDetail;
