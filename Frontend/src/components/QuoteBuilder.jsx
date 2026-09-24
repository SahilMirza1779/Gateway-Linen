import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import {
  FiBox,
  FiList,
  FiUser,
  FiCheckCircle,
  FiArrowRight,
  FiArrowLeft,
  FiTrash2,
  FiSend,
  FiTag,
  FiX,
  FiInfo,
  FiDollarSign,
  FiCheck,
  FiAlertCircle,
} from "react-icons/fi";

const QUANTITY_BUCKETS = [
  "50 - 100 Units",
  "100 - 200 Units",
  "200 - 500 Units",
  "500 - 1000 Units",
  "1000+ Units",
];

const getBucketLimits = (bucketStr) => {
  if (bucketStr.includes("1000+")) {
    return { min: 1000, max: 5000 };
  }
  const parts = bucketStr.split("-");
  if (parts.length === 2) {
    const min = parseInt(parts[0].trim(), 10) || 50;
    const max = parseInt(parts[1].trim(), 10) || 100;
    return { min, max };
  }
  return { min: 50, max: 100 };
};

export default function QuoteBuilder() {
  const navigate = useNavigate();
  const [step, setStep] = useState(1);

  const [allProducts, setAllProducts] = useState([]);
  const [loadingProducts, setLoadingProducts] = useState(true);

  const [selectedProductDetail, setSelectedProductDetail] = useState(null);
  const [activeImageIndex, setActiveImageIndex] = useState(0);

  const [quoteItems, setQuoteItems] = useState([]);
  const [companyDetails, setCompanyDetails] = useState({
    fullName: "",
    email: "",
    phone: "",
    hotelName: "",
    addressLine1: "",
    city: "",
    stateProvince: "",
    postalCode: "",
    notes: "",
  });

  const [couponCode, setCouponCode] = useState("");
  const [discount, setDiscount] = useState(0);
  const [couponMessage, setCouponMessage] = useState({ text: "", type: "" });
  const [warningMessage, setWarningMessage] = useState("");
  const [savingAddress, setSavingAddress] = useState(false);

  useEffect(() => {
    const fetchProducts = async () => {
      try {
        const response = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/products/api.php",
        );
        const result = await response.json();

        let fetchedItems = [];
        if (result && result.success && result.data) {
          if (Array.isArray(result.data.items)) {
            fetchedItems = result.data.items;
          } else if (Array.isArray(result.data)) {
            fetchedItems = result.data;
          }
        } else if (Array.isArray(result)) {
          fetchedItems = result;
        }

        setAllProducts(fetchedItems);
      } catch (err) {
        console.error("Fetch Error:", err);
        setAllProducts([]);
      } finally {
        setLoadingProducts(false);
      }
    };

    fetchProducts();
  }, []);

  const resolveImage = (rawImg) => {
    if (!rawImg)
      return "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
    if (rawImg.startsWith("http")) return rawImg;
    const cleanPath = rawImg.replace(/^\/+/, "");
    return `http://localhost/Gateway-Linen/GatewayLinenAdmin-main/${cleanPath}`;
  };

  const getProductImageUrls = (product) => {
    if (!product)
      return [
        "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600",
      ];
    let urls = [];

    if (
      product.images &&
      Array.isArray(product.images) &&
      product.images.length > 0
    ) {
      product.images.forEach((imgObj) => {
        const path = imgObj.imageUrl || imgObj.ImageUrl || imgObj;
        if (path) {
          const resolved = resolveImage(path);
          if (!urls.includes(resolved)) urls.push(resolved);
        }
      });
    }

    const singleImg = product.imageUrl || product.ImageUrl || product.image;
    if (singleImg) {
      const resolved = resolveImage(singleImg);
      if (!urls.includes(resolved)) {
        urls.unshift(resolved);
      }
    }

    if (urls.length === 0) {
      urls.push(
        "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600",
      );
    }

    return urls;
  };

  const handleAddProduct = (product) => {
    const productId = product.productId || product.ProductId || product.id;
    if (!productId) return;

    if (
      !quoteItems.find(
        (item) => (item.productId || item.ProductId || item.id) === productId,
      )
    ) {
      const defaultBucket = "50 - 100 Units";
      const limits = getBucketLimits(defaultBucket);
      setQuoteItems([
        ...quoteItems,
        {
          ...product,
          bucket: defaultBucket,
          exactQuantity: limits.min,
        },
      ]);
    }
  };

  const handleRemoveProduct = (productId) => {
    setQuoteItems(
      quoteItems.filter(
        (item) => (item.productId || item.ProductId || item.id) !== productId,
      ),
    );
  };

  const updateItemQuantity = (productId, field, value) => {
    setQuoteItems(
      quoteItems.map((item) => {
        if ((item.productId || item.ProductId || item.id) === productId) {
          if (field === "bucket") {
            const newLimits = getBucketLimits(value);
            return { ...item, bucket: value, exactQuantity: newLimits.min };
          }
          if (field === "exactQuantity") {
            const limits = getBucketLimits(item.bucket);
            let val = Number(value);
            if (val < limits.min) val = limits.min;
            if (val > limits.max) val = limits.max;
            return { ...item, exactQuantity: val };
          }
          return { ...item, [field]: value };
        }
        return item;
      }),
    );
  };

  const handleInputChange = (e) => {
    setCompanyDetails({ ...companyDetails, [e.target.name]: e.target.value });
  };

  const handleApplyCoupon = () => {
    if (couponCode.toUpperCase() === "B2B50") {
      setDiscount(50);
      setCouponMessage({
        text: "Coupon applied successfully! CAD $50 off.",
        type: "success",
      });
    } else {
      setDiscount(0);
      setCouponMessage({
        text: "Invalid or expired coupon code. Try 'B2B50'.",
        type: "error",
      });
    }
  };

  const calculateSubtotal = () => {
    return quoteItems.reduce((total, item) => {
      const itemPrice = Number(
        item.basePrice || item.BasePrice || item.price || 0,
      );
      const qty = Number(item.exactQuantity || 0);
      return total + itemPrice * qty;
    }, 0);
  };

  const subtotal = calculateSubtotal();
  const tax = subtotal * 0.13;
  const grandTotal = Math.max(0, subtotal + tax - discount);

  // --- SAVE ADDRESS TO DATABASE VIA CORRECTED `/users/address_api.php` ---
  const saveAddressToDatabase = async () => {
    try {
      setSavingAddress(true);
      const payload = {
        action: "add_address",
        userId: 11, // Standard User ID from your DB
        recipientName: companyDetails.fullName,
        phone: companyDetails.phone,
        addressLine1: companyDetails.addressLine1 || "Main Street",
        city: companyDetails.city || "Surat",
        stateProvince: companyDetails.stateProvince || "Gujarat",
        postalCode: companyDetails.postalCode || "395006",
      };

      const response = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/users/address_api.php",
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
      console.log("Address save response:", result);
    } catch (err) {
      console.error("Error saving address:", err);
    } finally {
      setSavingAddress(false);
    }
  };

  const handleSubmitQuote = () => {
    console.log("Submitting Quote:", {
      quoteItems,
      companyDetails,
      grandTotal,
    });
    setStep(6);
  };

  return (
    <div className="min-h-screen bg-[#F0EAE1] py-10 px-4 font-sans relative">
      <div className="max-w-6xl mx-auto">
        <div className="text-center mb-8">
          <h1 className="text-3xl md:text-4xl font-serif font-bold text-[#031D44] mb-2">
            B2B Quote Builder
          </h1>
          <p className="text-sm text-gray-600">
            Build your custom commercial package for special wholesale pricing.
          </p>
        </div>

        {/* Stepper Navigation */}
        <div className="flex justify-between items-center bg-white p-3 rounded-2xl shadow-sm border border-[#E5DCD0] mb-8 overflow-x-auto">
          {[
            { num: 1, label: "Products", icon: FiBox },
            { num: 2, label: "Quantities", icon: FiList },
            { num: 3, label: "Pricing & Tax", icon: FiDollarSign },
            { num: 4, label: "Details", icon: FiUser },
            { num: 5, label: "Review", icon: FiCheckCircle },
          ].map((s) => (
            <div
              key={s.num}
              className={`flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest whitespace-nowrap transition-colors ${step >= s.num ? "bg-[#031D44] text-white shadow-md" : "text-gray-400 bg-transparent"}`}
            >
              <s.icon size={14} />
              <span className="hidden md:inline">{s.label}</span>
            </div>
          ))}
        </div>

        {/* Custom Warning Banner */}
        {warningMessage && (
          <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-5 py-3.5 rounded-2xl flex items-center justify-between text-xs font-bold shadow-sm animate-in fade-in">
            <div className="flex items-center gap-2.5">
              <FiAlertCircle size={16} />
              <span>{warningMessage}</span>
            </div>
            <button
              onClick={() => setWarningMessage("")}
              className="text-red-400 hover:text-red-700 cursor-pointer"
            >
              <FiX size={16} />
            </button>
          </div>
        )}

        <div className="bg-white rounded-[32px] shadow-xl border border-[#E5DCD0] p-6 md:p-10 min-h-[500px] relative">
          {/* STEP 1: PRODUCTS */}
          {step === 1 && (
            <div className="animate-in fade-in">
              <h2 className="text-xl font-serif font-bold text-[#031D44] mb-6 border-b border-[#E5DCD0] pb-3">
                Select Products for Quote
              </h2>

              {loadingProducts ? (
                <div className="flex flex-col items-center justify-center py-20">
                  <div className="w-10 h-10 border-4 border-[#E5DCD0] border-t-[#B58E58] rounded-full animate-spin mb-4"></div>
                  <p className="text-xs font-bold tracking-widest uppercase text-[#031D44]">
                    Loading Catalog...
                  </p>
                </div>
              ) : !Array.isArray(allProducts) || allProducts.length === 0 ? (
                <div className="text-center py-20 text-gray-400 font-medium">
                  No products found in the database.
                </div>
              ) : (
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                  {allProducts.map((product, idx) => {
                    const productId =
                      product.productId || product.ProductId || idx;
                    const isAdded = quoteItems.some(
                      (item) =>
                        (item.productId || item.ProductId) === productId,
                    );
                    const itemImage = getProductImageUrls(product)[0];
                    const productName =
                      product.name || product.Name || "Unnamed Product";
                    const productCategory =
                      product.categoryName || product.CategoryName || "Linen";

                    return (
                      <div
                        key={`prod-${productId}`}
                        className={`flex flex-col bg-white rounded-2xl overflow-hidden border transition-all duration-300 ${isAdded ? "border-[#B58E58] shadow-md ring-2 ring-[#B58E58]/20" : "border-[#E5DCD0] hover:border-[#031D44] hover:shadow-lg"}`}
                      >
                        <div
                          onClick={() => {
                            setSelectedProductDetail(product);
                            setActiveImageIndex(0);
                          }}
                          className="h-40 overflow-hidden bg-gray-50 relative group cursor-pointer"
                        >
                          <img
                            src={itemImage}
                            alt={productName}
                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            onError={(e) => {
                              e.target.src =
                                "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
                            }}
                          />
                          <div className="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold gap-1">
                            <FiInfo size={14} /> Quick View
                          </div>
                          {isAdded && (
                            <div className="absolute top-2 right-2 bg-green-500 text-white p-1 rounded-full shadow-md">
                              <FiCheckCircle size={16} />
                            </div>
                          )}
                        </div>
                        <div className="p-4 flex flex-col flex-grow">
                          <p className="text-[9px] text-[#B58E58] uppercase font-bold tracking-widest mb-1 line-clamp-1">
                            {productCategory}
                          </p>
                          <h3
                            onClick={() => {
                              setSelectedProductDetail(product);
                              setActiveImageIndex(0);
                            }}
                            className="text-xs sm:text-sm font-bold text-[#031D44] mb-3 line-clamp-2 flex-grow cursor-pointer hover:text-[#B58E58] transition-colors"
                          >
                            {productName}
                          </h3>

                          <button
                            onClick={() =>
                              isAdded
                                ? handleRemoveProduct(productId)
                                : handleAddProduct(product)
                            }
                            className={`w-full py-2.5 rounded-xl text-[11px] font-bold tracking-widest uppercase transition-all cursor-pointer ${isAdded ? "bg-red-50 text-red-600 border border-red-200 hover:bg-red-100" : "bg-[#031D44] text-white hover:bg-[#B58E58] shadow-md"}`}
                          >
                            {isAdded ? "Remove Item" : "Add to Quote"}
                          </button>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          )}

          {/* STEP 2: QUANTITIES */}
          {step === 2 && (
            <div className="animate-in fade-in">
              <h2 className="text-xl font-serif font-bold text-[#031D44] mb-6 border-b border-[#E5DCD0] pb-3">
                Specify Bulk Quantities
              </h2>
              {quoteItems.length === 0 ? (
                <div className="text-center py-20">
                  <FiBox size={48} className="mx-auto text-gray-300 mb-4" />
                  <p className="text-gray-500 font-medium">
                    Please go back and select at least one product to continue.
                  </p>
                </div>
              ) : (
                <div className="space-y-4">
                  {quoteItems.map((item) => {
                    const productId = item.productId || item.ProductId;
                    const itemPrice = item.basePrice || item.BasePrice || 0;
                    const limits = getBucketLimits(item.bucket);

                    return (
                      <div
                        key={`cart-${productId}`}
                        className="flex flex-col md:flex-row items-center gap-4 border border-[#E5DCD0] p-4 rounded-2xl bg-[#FAF7F2] shadow-sm hover:shadow-md transition-shadow"
                      >
                        <img
                          src={getProductImageUrls(item)[0]}
                          className="w-20 h-20 rounded-xl object-cover border border-gray-200"
                          alt={item.name || item.Name}
                        />
                        <div className="flex-1 text-center md:text-left">
                          <p className="text-[9px] text-[#B58E58] uppercase font-bold tracking-widest mb-0.5">
                            {item.categoryName || item.CategoryName || "Linen"}
                          </p>
                          <h3 className="font-bold text-sm text-[#031D44] mb-1">
                            {item.name || item.Name}
                          </h3>
                          <p className="text-xs text-gray-600 font-medium">
                            Est. Unit Price:{" "}
                            <span className="text-[#B58E58]">
                              CAD ${Number(itemPrice).toFixed(2)}
                            </span>
                          </p>
                        </div>

                        <div className="flex flex-col gap-2.5 w-full md:w-auto bg-white p-3 rounded-xl border border-[#E5DCD0]">
                          <div className="flex items-center justify-between gap-3">
                            <span className="text-[10px] text-[#031D44] font-bold uppercase tracking-wider">
                              Range:
                            </span>
                            <select
                              value={item.bucket}
                              onChange={(e) =>
                                updateItemQuantity(
                                  productId,
                                  "bucket",
                                  e.target.value,
                                )
                              }
                              className="px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-bold text-[#031D44] bg-gray-50 focus:outline-none focus:border-[#B58E58] cursor-pointer"
                            >
                              {QUANTITY_BUCKETS.map((bucket) => (
                                <option key={bucket} value={bucket}>
                                  {bucket}
                                </option>
                              ))}
                            </select>
                          </div>
                          <div className="flex items-center justify-between gap-3 pt-2 border-t border-gray-100">
                            <span className="text-[10px] text-[#031D44] font-bold uppercase tracking-wider">
                              Exact Units ({limits.min} - {limits.max}):
                            </span>
                            <input
                              type="number"
                              min={limits.min}
                              max={limits.max}
                              value={item.exactQuantity}
                              onChange={(e) =>
                                updateItemQuantity(
                                  productId,
                                  "exactQuantity",
                                  e.target.value,
                                )
                              }
                              className="w-24 px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-bold text-[#031D44] bg-gray-50 text-center focus:outline-none focus:border-[#B58E58]"
                            />
                          </div>
                        </div>

                        <button
                          onClick={() => handleRemoveProduct(productId)}
                          className="w-full md:w-auto mt-2 md:mt-0 p-3 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors cursor-pointer flex justify-center"
                          title="Remove item"
                        >
                          <FiTrash2 size={18} />
                        </button>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          )}

          {/* STEP 3: PRICE & TAX BREAKDOWN */}
          {step === 3 && (
            <div className="animate-in fade-in max-w-3xl mx-auto">
              <h2 className="text-xl font-serif font-bold text-[#031D44] mb-6 border-b border-[#E5DCD0] pb-3">
                Price & Tax Calculation Summary
              </h2>

              <div className="bg-[#FAF7F2] p-6 rounded-[28px] border border-[#E5DCD0] shadow-sm mb-6 space-y-4">
                <h3 className="text-xs font-bold text-[#031D44] uppercase tracking-widest mb-3">
                  Bulk Calculation Breakdown
                </h3>

                {quoteItems.map((item) => {
                  const itemPrice = Number(
                    item.basePrice || item.BasePrice || 0,
                  );
                  const qty = Number(item.exactQuantity || 0);
                  const itemTotal = itemPrice * qty;
                  return (
                    <div
                      key={`summary-${item.productId || item.ProductId}`}
                      className="flex justify-between items-center bg-white p-3.5 rounded-xl border border-gray-100"
                    >
                      <div>
                        <p className="text-xs font-bold text-[#031D44]">
                          {item.name || item.Name}
                        </p>
                        <p className="text-[10px] text-gray-500">
                          {qty} Units × CAD ${itemPrice.toFixed(2)}
                        </p>
                      </div>
                      <p className="text-xs font-bold text-[#B58E58]">
                        CAD ${itemTotal.toFixed(2)}
                      </p>
                    </div>
                  );
                })}

                <div className="pt-4 border-t border-[#E5DCD0] space-y-2 text-xs text-gray-700">
                  <div className="flex justify-between">
                    <span className="font-medium">Subtotal</span>
                    <span className="font-bold text-[#031D44]">
                      CAD ${subtotal.toFixed(2)}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="font-medium">
                      Estimated Taxes (GST/PST 13%)
                    </span>
                    <span className="font-bold text-[#031D44]">
                      CAD ${tax.toFixed(2)}
                    </span>
                  </div>
                  {discount > 0 && (
                    <div className="flex justify-between text-[#B58E58] font-bold">
                      <span>Coupon Discount (B2B50)</span>
                      <span>- CAD ${discount.toFixed(2)}</span>
                    </div>
                  )}
                </div>

                <div className="pt-4 border-t-2 border-[#031D44]/10 flex justify-between items-center">
                  <span className="text-sm font-bold text-[#031D44] uppercase tracking-widest">
                    Grand Total
                  </span>
                  <span className="text-2xl font-serif font-bold text-[#B58E58]">
                    CAD ${grandTotal.toFixed(2)}
                  </span>
                </div>
              </div>

              {/* Coupon Code Section */}
              <div className="bg-white p-5 rounded-2xl border border-[#E5DCD0] shadow-sm">
                <label className="block text-[10px] uppercase tracking-widest text-[#031D44] font-bold mb-2 flex items-center gap-1.5">
                  <FiTag size={12} /> Have a Wholesale Coupon Code? (Try B2B50)
                </label>
                <div className="flex gap-3">
                  <input
                    type="text"
                    value={couponCode}
                    onChange={(e) => setCouponCode(e.target.value)}
                    placeholder="e.g. B2B50"
                    className="flex-1 px-4 py-2.5 bg-gray-50 border border-[#E5DCD0] rounded-xl text-xs text-[#031D44] font-bold uppercase focus:outline-none focus:border-[#B58E58]"
                  />
                  <button
                    onClick={handleApplyCoupon}
                    className="px-6 py-2.5 bg-[#B58E58] hover:bg-[#031D44] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest transition-colors cursor-pointer shadow-md"
                  >
                    Apply Coupon
                  </button>
                </div>
                {couponMessage.text && (
                  <div
                    className={`mt-3 p-3 rounded-xl text-xs flex items-center gap-2 ${couponMessage.type === "success" ? "bg-green-50 text-green-700 border border-green-200" : "bg-red-50 text-red-700 border border-red-200"}`}
                  >
                    {couponMessage.type === "success" ? (
                      <FiCheck size={14} />
                    ) : (
                      <FiAlertCircle size={14} />
                    )}
                    <span className="font-medium">{couponMessage.text}</span>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* STEP 4: COMPANY DETAILS & ADDRESS */}
          {step === 4 && (
            <div className="animate-in fade-in max-w-3xl mx-auto">
              <h2 className="text-xl font-serif font-bold text-[#031D44] mb-6 border-b border-[#E5DCD0] pb-3">
                Hotel / Company Details & Shipping Address
              </h2>
              <div className="bg-[#FAF7F2] p-6 sm:p-8 rounded-[24px] border border-[#E5DCD0] shadow-sm">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                      Full Name <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="fullName"
                      required
                      value={companyDetails.fullName}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="Sahil Mirza"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                      Email Address <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="email"
                      name="email"
                      required
                      value={companyDetails.email}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="purchasing@hotel.ca"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                      Phone Number <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="phone"
                      required
                      value={companyDetails.phone}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="+1 (555) 000-0000"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                      Hotel / Business Name{" "}
                      <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="hotelName"
                      required
                      value={companyDetails.hotelName}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="Grand Plaza Suites"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                      Address Line 1 <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="addressLine1"
                      required
                      value={companyDetails.addressLine1}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="Parliament Hill / Street"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                      City <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="city"
                      required
                      value={companyDetails.city}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="Ottawa"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                      State / Province
                    </label>
                    <input
                      type="text"
                      name="stateProvince"
                      value={companyDetails.stateProvince}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="Ontario"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                      Postal Code <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="postalCode"
                      required
                      value={companyDetails.postalCode}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="K1A 0A1"
                    />
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* STEP 5: REVIEW & SUBMIT */}
          {step === 5 && (
            <div className="animate-in fade-in flex flex-col lg:flex-row gap-8">
              <div className="flex-1">
                <h2 className="text-xl font-serif font-bold text-[#031D44] mb-6 border-b border-[#E5DCD0] pb-3">
                  Review Your Quote Request
                </h2>

                <div className="bg-[#FAF7F2] p-5 md:p-6 rounded-2xl border border-[#E5DCD0] mb-6 shadow-sm">
                  <div className="flex items-center gap-2 mb-3">
                    <FiUser className="text-[#B58E58]" />
                    <h3 className="text-[11px] font-bold text-[#031D44] uppercase tracking-widest">
                      Applicant & Address Details
                    </h3>
                  </div>
                  <div className="grid grid-cols-2 gap-y-3 gap-x-4 bg-white p-4 rounded-xl border border-gray-100 text-xs">
                    <div>
                      <p className="text-[9px] text-gray-400 uppercase font-bold tracking-wider">
                        Business
                      </p>
                      <p className="font-bold text-[#031D44]">
                        {companyDetails.hotelName || "N/A"}
                      </p>
                    </div>
                    <div>
                      <p className="text-[9px] text-gray-400 uppercase font-bold tracking-wider">
                        Contact Name
                      </p>
                      <p className="font-bold text-[#031D44]">
                        {companyDetails.fullName || "N/A"}
                      </p>
                    </div>
                    <div className="col-span-2 pt-2 border-t border-gray-50">
                      <p className="text-[9px] text-gray-400 uppercase font-bold tracking-wider">
                        Shipping Address
                      </p>
                      <p className="text-gray-700">
                        {companyDetails.addressLine1}, {companyDetails.city},{" "}
                        {companyDetails.stateProvince}{" "}
                        {companyDetails.postalCode}
                      </p>
                    </div>
                    <div className="col-span-2 pt-2 border-t border-gray-50">
                      <p className="text-[9px] text-gray-400 uppercase font-bold tracking-wider">
                        Contact Info
                      </p>
                      <p className="text-gray-700">
                        {companyDetails.email} • {companyDetails.phone}
                      </p>
                    </div>
                  </div>
                </div>

                <h3 className="text-[11px] font-bold text-[#031D44] uppercase tracking-widest mb-3 flex items-center gap-2">
                  <FiBox className="text-[#B58E58]" /> Required Inventory
                </h3>
                <div className="space-y-3 bg-white p-4 rounded-2xl border border-[#E5DCD0] shadow-sm">
                  {quoteItems.map((item) => {
                    const itemPrice = item.basePrice || item.BasePrice || 0;
                    return (
                      <div
                        key={`review-${item.productId || item.ProductId}`}
                        className="flex justify-between items-center border-b border-gray-100 last:border-0 pb-3 last:pb-0"
                      >
                        <div className="flex items-center gap-3">
                          <img
                            src={getProductImageUrls(item)[0]}
                            className="w-10 h-10 rounded-lg object-cover border border-gray-200"
                            alt="product"
                          />
                          <div>
                            <p className="text-xs font-bold text-[#031D44] line-clamp-1">
                              {item.name || item.Name}
                            </p>
                            <p className="text-[9px] text-gray-500 uppercase tracking-widest">
                              {item.bucket} • {item.exactQuantity} Qty
                            </p>
                          </div>
                        </div>
                        <p className="text-xs font-bold text-[#B58E58] whitespace-nowrap">
                          CAD ${(itemPrice * item.exactQuantity).toFixed(2)}
                        </p>
                      </div>
                    );
                  })}
                </div>
              </div>

              <div className="lg:w-[35%]">
                <div className="bg-[#031D44] p-6 rounded-2xl shadow-xl sticky top-6 text-white">
                  <h3 className="text-sm font-serif font-bold mb-4 border-b border-white/20 pb-3 flex items-center gap-2">
                    <FiList className="text-[#B58E58]" /> Final Totals
                  </h3>

                  <div className="space-y-3 text-xs text-gray-300 mb-5">
                    <div className="flex justify-between">
                      <span>Base Subtotal</span>
                      <span className="font-medium text-white">
                        CAD ${subtotal.toFixed(2)}
                      </span>
                    </div>
                    <div className="flex justify-between">
                      <span>Estimated Tax (13%)</span>
                      <span className="font-medium text-white">
                        CAD ${tax.toFixed(2)}
                      </span>
                    </div>
                    {discount > 0 && (
                      <div className="flex justify-between text-[#B58E58] font-bold">
                        <span>Coupon Discount</span>
                        <span>- CAD ${discount.toFixed(2)}</span>
                      </div>
                    )}
                  </div>

                  <div className="border-t border-white/20 pt-4 mb-6">
                    <div className="flex justify-between items-end">
                      <span className="font-bold text-gray-200 uppercase tracking-widest text-[10px]">
                        Grand Total
                      </span>
                      <span className="text-xl font-serif font-bold text-[#B58E58]">
                        CAD ${grandTotal.toFixed(2)}
                      </span>
                    </div>
                  </div>

                  <button
                    onClick={() => {
                      saveAddressToDatabase();
                      handleSubmitQuote();
                    }}
                    disabled={savingAddress}
                    className="w-full py-4 bg-[#B58E58] hover:bg-white text-white hover:text-[#031D44] rounded-xl text-[11px] font-bold uppercase tracking-widest flex items-center justify-center gap-2 transition-all cursor-pointer shadow-lg"
                  >
                    {savingAddress ? (
                      "Saving Address..."
                    ) : (
                      <>
                        <FiSend size={14} /> Send to Admin
                      </>
                    )}
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* STEP 6: SUCCESS */}
          {step === 6 && (
            <div className="animate-in zoom-in text-center py-12 px-4 max-w-lg mx-auto">
              <div className="w-24 h-24 bg-[#F7F2EB] text-[#B58E58] border-4 border-white shadow-xl rounded-full flex items-center justify-center mx-auto mb-6">
                <FiCheckCircle size={48} />
              </div>
              <h2 className="text-2xl md:text-3xl font-serif font-bold text-[#031D44] mb-3">
                Quote Transmitted!
              </h2>
              <p className="text-sm text-gray-600 mb-8 leading-relaxed">
                Your commercial bulk inquiry and shipping address have been
                successfully saved to your profile and sent to the Gateway Linen
                Wholesale Division.
              </p>
              <button
                onClick={() => navigate("/dashboard")}
                className="px-8 py-3.5 bg-[#031D44] hover:bg-[#B58E58] text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-md transition-colors cursor-pointer"
              >
                Go to Dashboard
              </button>
            </div>
          )}

          {/* Bottom Navigation Buttons */}
          {step < 6 && (
            <div className="flex justify-between items-center mt-10 pt-6 border-t border-[#E5DCD0]">
              <button
                onClick={() => (step > 1 ? setStep(step - 1) : navigate("/"))}
                className="flex items-center gap-2 px-4 py-2 text-[11px] font-bold text-gray-500 hover:text-[#031D44] uppercase tracking-widest transition-colors cursor-pointer bg-gray-50 hover:bg-gray-100 rounded-lg"
              >
                <FiArrowLeft size={14} /> {step === 1 ? "Cancel" : "Go Back"}
              </button>

              {step < 5 && (
                <button
                  onClick={() => {
                    if (step === 1 && quoteItems.length === 0) {
                      setWarningMessage(
                        "Please select at least one product from the catalog before proceeding.",
                      );
                      return;
                    }
                    if (
                      step === 4 &&
                      (!companyDetails.fullName ||
                        !companyDetails.email ||
                        !companyDetails.hotelName ||
                        !companyDetails.addressLine1 ||
                        !companyDetails.city ||
                        !companyDetails.postalCode)
                    ) {
                      setWarningMessage(
                        "Please fill all the required (*) details and address fields before reviewing your quote.",
                      );
                      return;
                    }
                    setWarningMessage("");
                    setStep(step + 1);
                  }}
                  className="flex items-center gap-2 px-6 py-3 bg-[#031D44] text-white rounded-xl text-[11px] font-bold uppercase tracking-widest hover:bg-[#B58E58] transition-colors cursor-pointer shadow-md"
                >
                  Proceed Next <FiArrowRight size={14} />
                </button>
              )}
            </div>
          )}
        </div>
      </div>

      {/* --- PRODUCT DETAIL POPUP MODAL --- */}
      {selectedProductDetail &&
        (() => {
          const productImages = getProductImageUrls(selectedProductDetail);
          return (
            <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
              <div className="bg-[#F7F2EB] border border-[#E5DCD0] rounded-[28px] max-w-lg w-full p-6 sm:p-8 shadow-2xl relative animate-in zoom-in-95 max-h-[90vh] overflow-y-auto">
                <button
                  onClick={() => setSelectedProductDetail(null)}
                  className="absolute top-5 right-5 text-gray-400 hover:text-gray-800 bg-white p-2 rounded-full transition-colors cursor-pointer border border-gray-200 shadow-2xs z-10"
                >
                  <FiX size={16} />
                </button>

                <div className="w-full h-56 bg-white rounded-2xl overflow-hidden mb-3 border border-[#E5DCD0] shadow-sm">
                  <img
                    src={productImages[activeImageIndex] || productImages[0]}
                    alt="Product Preview"
                    className="w-full h-full object-cover transition-all duration-300"
                  />
                </div>

                {productImages.length > 1 && (
                  <div className="flex gap-2 mb-4 overflow-x-auto pb-1">
                    {productImages.map((imgUrl, i) => (
                      <button
                        key={i}
                        onClick={() => setActiveImageIndex(i)}
                        className={`w-14 h-14 rounded-xl overflow-hidden border-2 flex-shrink-0 transition-all cursor-pointer ${activeImageIndex === i ? "border-[#B58E58] ring-2 ring-[#B58E58]/30 scale-105" : "border-[#E5DCD0] opacity-70 hover:opacity-100"}`}
                      >
                        <img
                          src={imgUrl}
                          alt={`Thumb ${i}`}
                          className="w-full h-full object-cover"
                        />
                      </button>
                    ))}
                  </div>
                )}

                <span className="text-[9.5px] font-bold text-[#B58E58] tracking-widest uppercase mb-1 block">
                  {selectedProductDetail.categoryName ||
                    selectedProductDetail.CategoryName ||
                    "Commercial Linen"}
                </span>
                <h3 className="text-xl font-serif font-bold text-[#031D44] mb-2">
                  {selectedProductDetail.name || selectedProductDetail.Name}
                </h3>

                <div className="bg-white p-4 rounded-2xl border border-[#E5DCD0] mb-4 space-y-2 text-xs text-gray-700 shadow-2xs">
                  <div className="flex justify-between items-center">
                    <span className="font-bold text-[#031D44]">
                      Base Wholesale Price:
                    </span>
                    <span className="font-serif font-bold text-base text-[#B58E58]">
                      CAD $
                      {Number(
                        selectedProductDetail.basePrice ||
                          selectedProductDetail.BasePrice ||
                          0,
                      ).toFixed(2)}
                    </span>
                  </div>
                  {selectedProductDetail.shortDescription && (
                    <div className="pt-2 border-t border-gray-100">
                      <p className="text-[10px] font-bold uppercase text-gray-400 mb-0.5">
                        Overview
                      </p>
                      <p className="text-gray-600 font-light leading-relaxed">
                        {selectedProductDetail.shortDescription}
                      </p>
                    </div>
                  )}
                  {selectedProductDetail.description && (
                    <div className="pt-2 border-t border-gray-100">
                      <p className="text-[10px] font-bold uppercase text-gray-400 mb-0.5">
                        Description
                      </p>
                      <p className="text-gray-600 font-light leading-relaxed">
                        {selectedProductDetail.description}
                      </p>
                    </div>
                  )}
                </div>

                <div className="flex gap-3">
                  <button
                    type="button"
                    onClick={() => setSelectedProductDetail(null)}
                    className="w-1/2 py-3 bg-white border border-[#E5DCD0] hover:bg-gray-50 text-[#031D44] text-[11px] font-bold tracking-widest uppercase rounded-xl transition-all cursor-pointer shadow-2xs"
                  >
                    Close
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      handleAddProduct(selectedProductDetail);
                      setSelectedProductDetail(null);
                    }}
                    className="w-1/2 py-3 bg-[#031D44] hover:bg-[#B58E58] text-white text-[11px] font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer"
                  >
                    Add to Quote
                  </button>
                </div>
              </div>
            </div>
          );
        })()}
    </div>
  );
}
