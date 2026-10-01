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

  const [allVariants, setAllVariants] = useState([]);
  const [loadingVariants, setLoadingVariants] = useState(true);

  const [selectedVariantDetail, setSelectedVariantDetail] = useState(null);
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
    const fetchData = async () => {
      try {
        const variantsResponse = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/variants/api.php?action=get_variants",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-API-KEY": "GatewayLinen@2026",
            },
          },
        );
        const variantsResult = await variantsResponse.json();

        const productsResponse = await fetch(
          "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/products/api.php",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-API-KEY": "GatewayLinen@2026",
            },
          },
        );
        const productsResult = await productsResponse.json();

        let productsList = [];
        if (productsResult.success && productsResult.data) {
          productsList = Array.isArray(productsResult.data)
            ? productsResult.data
            : productsResult.data.items || [];
        }

        let rawVariants = [];
        if (variantsResult) {
          if (Array.isArray(variantsResult)) {
            rawVariants = variantsResult;
          } else if (
            variantsResult.data &&
            Array.isArray(variantsResult.data)
          ) {
            rawVariants = variantsResult.data;
          }
        }

        const formattedVariants = rawVariants.map((variant) => {
          const parentProduct = productsList.find(
            (p) =>
              (p.productId || p.ProductId || p.id) ==
              (variant.productId || variant.product_id || variant.ProductId),
          );

          let rawImg = variant.image || variant.Image || "";
          if (!rawImg && parentProduct) {
            rawImg =
              parentProduct.imageUrl ||
              parentProduct.ImageUrl ||
              parentProduct.image ||
              parentProduct.Image ||
              "";
            if (
              !rawImg &&
              parentProduct.images &&
              parentProduct.images.length > 0
            ) {
              rawImg =
                parentProduct.images[0].imageUrl ||
                parentProduct.images[0].ImageUrl ||
                "";
            }
          }

          let finalImg = "";
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

          return {
            ...variant,
            variant_id: variant.variant_id || variant.VariantId || variant.id,
            product_name:
              variant.product_name ||
              variant.VariantName ||
              variant.Name ||
              (parentProduct
                ? parentProduct.name || parentProduct.Name
                : "Product"),
            sku: variant.sku || variant.SKU || "VAR",
            pricing: variant.pricing || {
              price: variant.price || variant.Price || 0,
            },
            resolvedImage: finalImg,
          };
        });

        setAllVariants(formattedVariants);
      } catch (err) {
        console.error("Fetch Error:", err);
        setAllVariants([]);
      } finally {
        setLoadingVariants(false);
      }
    };

    fetchData();
  }, []);

  const handleAddVariant = (variant) => {
    const variantId = variant.variant_id;
    if (!variantId) return;

    if (!quoteItems.find((item) => item.variant_id === variantId)) {
      const defaultBucket = "50 - 100 Units";
      const limits = getBucketLimits(defaultBucket);
      setQuoteItems([
        ...quoteItems,
        {
          ...variant,
          bucket: defaultBucket,
          exactQuantity: limits.min,
        },
      ]);
    }
  };

  const handleRemoveVariant = (variantId) => {
    setQuoteItems(quoteItems.filter((item) => item.variant_id !== variantId));
  };

  const updateItemQuantity = (variantId, field, value) => {
    setQuoteItems(
      quoteItems.map((item) => {
        if (item.variant_id === variantId) {
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
      const itemPrice = Number(item.pricing?.price || item.price || 0);
      const qty = Number(item.exactQuantity || 0);
      return total + itemPrice * qty;
    }, 0);
  };

  const subtotal = calculateSubtotal();
  const tax = subtotal * 0.13;
  const grandTotal = Math.max(0, subtotal + tax - discount);

  const handleSubmitQuote = async () => {
    try {
      setSavingAddress(true);
      const payload = {
        action: "submit_quote",
        userId: 11,
        companyDetails: companyDetails,
        quoteItems: quoteItems,
        grandTotal: grandTotal,
      };

      const response = await fetch(
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/quotes/submit_quote_api.php",
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
        setStep(6);
      } else {
        setWarningMessage(result.message || "Failed to submit quote.");
      }
    } catch (err) {
      console.error("Submit Quote Error:", err);
      setWarningMessage("Server error while submitting the quote.");
    } finally {
      setSavingAddress(false);
    }
  };

  return (
    <div className="min-h-screen bg-white py-8 sm:py-12 px-3 sm:px-6 lg:px-8 font-sans relative">
      <div className="max-w-[1200px] mx-auto">
        <div className="text-center mb-8 sm:mb-10">
          <div className="inline-flex items-center justify-center gap-2 bg-[#B58E58]/10 px-3.5 py-1.5 rounded-full mb-3 border border-[#B58E58]/20 shadow-2xs">
            <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
            <span className="text-[#B58E58] text-[9.5px] font-bold tracking-[0.2em] uppercase">
              Wholesale Division
            </span>
          </div>
          <h1 className="text-2xl sm:text-4xl md:text-5xl font-serif font-bold text-[#031D44] mb-2.5">
            B2B Quote Builder
          </h1>
          <p className="text-xs sm:text-sm md:text-base text-gray-600 font-light max-w-xl mx-auto px-2">
            Build your custom commercial package for special wholesale pricing.
            Secure your preferred inventory today.
          </p>
        </div>

        {/* Stepper Navigation */}
        <div className="flex items-center justify-start sm:justify-center bg-[#FAF7F2] p-2.5 sm:p-4 rounded-[20px] shadow-sm border border-[#E5DCD0] mb-6 sm:mb-8 overflow-x-auto scrollbar-hide max-w-4xl mx-auto gap-1.5 sm:gap-3">
          {[
            { num: 1, label: "Variants", icon: FiBox },
            { num: 2, label: "Quantities", icon: FiList },
            { num: 3, label: "Pricing", icon: FiDollarSign },
            { num: 4, label: "Details", icon: FiUser },
            { num: 5, label: "Review", icon: FiCheckCircle },
          ].map((s, idx, arr) => (
            <div key={s.num} className="flex items-center shrink-0">
              <div
                className={`flex items-center gap-1.5 px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl text-[9.5px] sm:text-[11px] font-bold uppercase tracking-widest whitespace-nowrap transition-all ${
                  step >= s.num
                    ? "bg-[#031D44] text-[#B58E58] shadow-md border border-[#031D44]"
                    : "text-gray-500 bg-white border border-[#E5DCD0]"
                }`}
              >
                <s.icon
                  size={13}
                  className={step >= s.num ? "text-white" : "text-gray-400"}
                />
                <span
                  className={step >= s.num ? "text-white" : "text-gray-600"}
                >
                  {s.label}
                </span>
              </div>
              {idx < arr.length - 1 && (
                <div
                  className={`w-3 sm:w-6 h-px mx-1 ${step > s.num ? "bg-[#031D44]" : "bg-[#E5DCD0]"}`}
                ></div>
              )}
            </div>
          ))}
        </div>

        {warningMessage && (
          <div className="max-w-4xl mx-auto mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center justify-between text-xs font-bold shadow-sm animate-in fade-in">
            <div className="flex items-center gap-2.5">
              <FiAlertCircle size={16} className="shrink-0" />
              <span>{warningMessage}</span>
            </div>
            <button
              onClick={() => setWarningMessage("")}
              className="text-red-400 hover:text-red-700 cursor-pointer p-1"
            >
              <FiX size={15} />
            </button>
          </div>
        )}

        {/* Inner Container */}
        <div className="bg-[#FAF7F2] rounded-[20px] sm:rounded-[24px] lg:rounded-[32px] shadow-xl border border-[#E5DCD0] p-4 sm:p-8 md:p-10 min-h-[450px] relative max-w-[1200px] mx-auto overflow-hidden">
          {/* STEP 1: VARIANTS */}
          {step === 1 && (
            <div className="animate-in fade-in slide-in-from-bottom-4 duration-500 z-10 relative">
              <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 sm:mb-6 border-b border-[#E5DCD0] pb-4 gap-3">
                <h2 className="text-lg sm:text-2xl font-serif font-bold text-[#031D44]">
                  Select Product Variants
                </h2>
                <div className="bg-white px-3.5 py-1.5 rounded-xl border border-[#E5DCD0] text-[10px] font-bold text-[#B58E58] uppercase tracking-widest shadow-2xs">
                  Items Selected: {quoteItems.length}
                </div>
              </div>

              {loadingVariants ? (
                <div className="flex flex-col items-center justify-center py-20">
                  <div className="w-9 h-9 border-4 border-[#E5DCD0] border-t-[#031D44] rounded-full animate-spin mb-3"></div>
                  <p className="text-[11px] font-bold tracking-widest uppercase text-[#031D44]">
                    Loading Wholesale Catalog...
                  </p>
                </div>
              ) : !Array.isArray(allVariants) || allVariants.length === 0 ? (
                <div className="text-center py-20 text-xs text-gray-500 font-light border-2 border-dashed border-[#E5DCD0] rounded-2xl bg-white">
                  No active variants found in the database.
                </div>
              ) : (
                <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-5">
                  {allVariants.map((variant) => {
                    const variantId = variant.variant_id;
                    const isAdded = quoteItems.some(
                      (item) => item.variant_id === variantId,
                    );
                    const itemImage =
                      variant.resolvedImage &&
                      variant.resolvedImage.trim() !== ""
                        ? variant.resolvedImage
                        : "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
                    const variantName =
                      variant.product_name || "Unnamed Variant";
                    const sku = variant.sku || "N/A";
                    const price = variant.pricing?.price || 0;

                    return (
                      <div
                        key={`var-${variantId}`}
                        className={`flex flex-col bg-white rounded-2xl overflow-hidden border transition-all duration-300 justify-between ${
                          isAdded
                            ? "border-[#031D44] shadow-md ring-1 ring-[#031D44]"
                            : "border-[#E5DCD0] hover:border-[#B58E58] shadow-2xs"
                        }`}
                      >
                        <div
                          onClick={() => {
                            setSelectedVariantDetail(variant);
                            setActiveImageIndex(0);
                          }}
                          className="aspect-square overflow-hidden bg-gray-50 relative group cursor-pointer border-b border-[#E5DCD0]"
                        >
                          <img
                            src={itemImage}
                            alt={variantName}
                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                            onError={(e) => {
                              e.target.src =
                                "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
                            }}
                          />
                          <div className="absolute inset-0 bg-[#031D44]/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] font-bold gap-1 tracking-wider uppercase backdrop-blur-xs">
                            <FiInfo size={13} /> View
                          </div>
                          {isAdded && (
                            <div className="absolute top-2.5 right-2.5 bg-[#031D44] text-white p-1 rounded-full shadow-md">
                              <FiCheckCircle size={14} />
                            </div>
                          )}
                        </div>

                        <div className="p-3 sm:p-4 flex flex-col flex-grow justify-between">
                          <div>
                            <p className="text-[8.5px] text-gray-400 uppercase font-bold tracking-widest mb-0.5 truncate">
                              SKU: {sku}
                            </p>
                            <h3
                              onClick={() => {
                                setSelectedVariantDetail(variant);
                                setActiveImageIndex(0);
                              }}
                              className="text-xs sm:text-sm font-bold text-[#031D44] mb-2 line-clamp-2 cursor-pointer hover:text-[#B58E58] transition-colors leading-tight"
                            >
                              {variantName}
                            </h3>
                          </div>

                          <div>
                            <p className="text-xs sm:text-sm font-serif font-bold text-[#B58E58] mb-3">
                              CAD ${Number(price).toFixed(2)}
                            </p>

                            <button
                              onClick={() =>
                                isAdded
                                  ? handleRemoveVariant(variantId)
                                  : handleAddVariant(variant)
                              }
                              className={`w-full py-2.5 rounded-xl text-[10px] font-bold tracking-widest uppercase transition-all cursor-pointer shadow-2xs ${
                                isAdded
                                  ? "bg-[#FAF7F2] text-red-500 border border-red-200 hover:bg-red-50"
                                  : "bg-[#031D44] text-white hover:bg-[#B58E58]"
                              }`}
                            >
                              {isAdded ? "Remove" : "Add to Quote"}
                            </button>
                          </div>
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
            <div className="animate-in fade-in slide-in-from-bottom-4 duration-500 max-w-4xl mx-auto z-10 relative">
              <h2 className="text-lg sm:text-2xl font-serif font-bold text-[#031D44] mb-5 border-b border-[#E5DCD0] pb-3">
                Specify Bulk Quantities
              </h2>

              {quoteItems.length === 0 ? (
                <div className="text-center py-16 bg-white rounded-2xl border-2 border-dashed border-[#E5DCD0]">
                  <FiBox size={40} className="mx-auto text-gray-300 mb-3" />
                  <p className="text-xs sm:text-sm text-gray-600 font-light max-w-xs mx-auto">
                    Your quote list is empty. Please go back and select at least
                    one item.
                  </p>
                </div>
              ) : (
                <div className="space-y-3.5">
                  {quoteItems.map((item) => {
                    const variantId = item.variant_id;
                    const itemPrice = item.pricing?.price || 0;
                    const limits = getBucketLimits(item.bucket);

                    return (
                      <div
                        key={`cart-${variantId}`}
                        className="flex flex-col sm:flex-row items-center gap-4 border border-[#E5DCD0] p-3.5 sm:p-5 rounded-2xl bg-white shadow-2xs"
                      >
                        <img
                          src={
                            item.resolvedImage ||
                            "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600"
                          }
                          onError={(e) => {
                            e.target.src =
                              "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
                          }}
                          className="w-16 h-16 sm:w-20 sm:h-20 rounded-xl object-cover border border-[#E5DCD0] shrink-0"
                          alt={item.product_name}
                        />
                        <div className="flex-1 text-center sm:text-left min-w-0 w-full">
                          <p className="text-[9px] text-gray-400 uppercase font-bold tracking-widest mb-0.5 truncate">
                            SKU: {item.sku}
                          </p>
                          <h3 className="font-bold text-xs sm:text-sm text-[#031D44] mb-1 truncate">
                            {item.product_name}
                          </h3>
                          <p className="text-xs text-gray-600 font-light">
                            Unit Price:{" "}
                            <span className="text-[#B58E58] font-bold">
                              CAD ${Number(itemPrice).toFixed(2)}
                            </span>
                          </p>
                        </div>

                        <div className="flex flex-col gap-2.5 w-full sm:w-auto bg-[#FAF7F2] p-3.5 rounded-xl border border-[#E5DCD0] shrink-0">
                          <div className="flex items-center justify-between gap-3">
                            <span className="text-[9.5px] text-[#031D44] font-bold uppercase tracking-widest">
                              Range:
                            </span>
                            <select
                              value={item.bucket}
                              onChange={(e) =>
                                updateItemQuantity(
                                  variantId,
                                  "bucket",
                                  e.target.value,
                                )
                              }
                              className="px-2.5 py-1.5 border border-[#E5DCD0] rounded-lg text-[10.5px] font-bold text-[#031D44] bg-white focus:outline-none focus:border-[#B58E58] cursor-pointer shadow-2xs"
                            >
                              {QUANTITY_BUCKETS.map((bucket) => (
                                <option key={bucket} value={bucket}>
                                  {bucket}
                                </option>
                              ))}
                            </select>
                          </div>
                          <div className="flex items-center justify-between gap-3 pt-2.5 border-t border-[#E5DCD0]">
                            <span className="text-[9.5px] text-[#031D44] font-bold uppercase tracking-widest">
                              Units ({limits.min}-{limits.max}):
                            </span>
                            <input
                              type="number"
                              min={limits.min}
                              max={limits.max}
                              value={item.exactQuantity}
                              onChange={(e) =>
                                updateItemQuantity(
                                  variantId,
                                  "exactQuantity",
                                  e.target.value,
                                )
                              }
                              className="w-20 px-2 py-1.5 border border-[#E5DCD0] rounded-lg text-xs font-bold text-[#031D44] bg-white text-center focus:outline-none focus:border-[#B58E58] shadow-2xs"
                            />
                          </div>
                        </div>

                        <button
                          onClick={() => handleRemoveVariant(variantId)}
                          className="w-full sm:w-auto p-3 text-gray-400 hover:text-white hover:bg-red-500 rounded-xl transition-colors cursor-pointer flex justify-center shrink-0 border border-transparent"
                          title="Remove item"
                        >
                          <FiTrash2 size={16} />
                        </button>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          )}

          {/* STEP 3: PRICING */}
          {step === 3 && (
            <div className="animate-in fade-in slide-in-from-bottom-4 duration-500 max-w-3xl mx-auto z-10 relative">
              <h2 className="text-lg sm:text-2xl font-serif font-bold text-[#031D44] mb-5 border-b border-[#E5DCD0] pb-3">
                Price & Tax Calculation Summary
              </h2>

              <div className="bg-white p-5 sm:p-6 rounded-[24px] border border-[#E5DCD0] shadow-2xs mb-5">
                <h3 className="text-[10px] font-bold text-[#B58E58] uppercase tracking-widest mb-3.5 flex items-center gap-1.5">
                  <FiList /> Bulk Calculation Breakdown
                </h3>

                <div className="space-y-2.5 mb-5">
                  {quoteItems.map((item) => {
                    const itemPrice = Number(item.pricing?.price || 0);
                    const qty = Number(item.exactQuantity || 0);
                    const itemTotal = itemPrice * qty;
                    return (
                      <div
                        key={`summary-${item.variant_id}`}
                        className="flex justify-between items-center bg-[#FAF7F2] p-3.5 rounded-xl border border-[#E5DCD0] text-xs shadow-2xs"
                      >
                        <div className="min-w-0 pr-3">
                          <p className="font-bold text-[#031D44] truncate mb-0.5">
                            {item.product_name}
                          </p>
                          <p className="text-[10px] text-gray-500 font-medium">
                            {qty} Units <span className="mx-1">×</span> CAD $
                            {itemPrice.toFixed(2)}
                          </p>
                        </div>
                        <p className="font-bold text-[#4A5D4E] shrink-0 text-right">
                          CAD ${itemTotal.toFixed(2)}
                        </p>
                      </div>
                    );
                  })}
                </div>

                <div className="pt-4 border-t border-[#E5DCD0] space-y-2.5 text-xs text-gray-700 bg-[#FAF7F2] p-4 rounded-xl border border-[#E5DCD0] shadow-2xs">
                  <div className="flex justify-between items-center">
                    <span className="font-medium text-gray-600">
                      Base Subtotal
                    </span>
                    <span className="font-bold text-[#031D44]">
                      CAD ${subtotal.toFixed(2)}
                    </span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="font-medium text-gray-600">
                      Estimated Taxes (13%)
                    </span>
                    <span className="font-bold text-[#031D44]">
                      CAD ${tax.toFixed(2)}
                    </span>
                  </div>
                  {discount > 0 && (
                    <div className="flex justify-between items-center text-green-600 font-bold bg-green-50 p-2 rounded-lg border border-green-100">
                      <span>Coupon Discount (B2B50)</span>
                      <span>- CAD ${discount.toFixed(2)}</span>
                    </div>
                  )}
                  <div className="pt-3 mt-1 border-t-2 border-[#031D44]/10 flex justify-between items-center">
                    <span className="text-[11px] font-bold text-[#031D44] uppercase tracking-widest">
                      Grand Total
                    </span>
                    <span className="text-xl sm:text-2xl font-serif font-bold text-[#031D44]">
                      CAD ${grandTotal.toFixed(2)}
                    </span>
                  </div>
                </div>
              </div>

              {/* Coupon Code Section */}
              <div className="bg-white p-5 rounded-[20px] border border-[#E5DCD0] shadow-2xs">
                <label className="block text-[10px] uppercase tracking-widest text-[#031D44] font-bold mb-2.5 flex items-center gap-1.5">
                  <FiTag className="text-[#B58E58]" size={13} /> Have a
                  Wholesale Coupon Code?
                </label>
                <div className="flex gap-2.5">
                  <input
                    type="text"
                    value={couponCode}
                    onChange={(e) => setCouponCode(e.target.value)}
                    placeholder="Try 'B2B50'"
                    className="flex-1 px-3.5 py-2.5 bg-[#FAF7F2] border border-[#E5DCD0] rounded-xl text-xs text-[#031D44] font-bold uppercase focus:outline-none focus:border-[#B58E58] focus:bg-white transition-colors"
                  />
                  <button
                    onClick={handleApplyCoupon}
                    className="px-6 py-2.5 bg-[#031D44] hover:bg-[#B58E58] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest transition-colors cursor-pointer shadow-sm"
                  >
                    Apply
                  </button>
                </div>
                {couponMessage.text && (
                  <div
                    className={`mt-3 p-3 rounded-xl text-xs flex items-center gap-2 font-bold ${
                      couponMessage.type === "success"
                        ? "bg-green-50 text-green-700 border border-green-200"
                        : "bg-red-50 text-red-700 border border-red-200"
                    }`}
                  >
                    {couponMessage.type === "success" ? (
                      <FiCheck size={15} />
                    ) : (
                      <FiAlertCircle size={15} />
                    )}
                    <span>{couponMessage.text}</span>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* STEP 4: COMPANY DETAILS & ADDRESS */}
          {step === 4 && (
            <div className="animate-in fade-in slide-in-from-bottom-4 duration-500 max-w-3xl mx-auto z-10 relative">
              <h2 className="text-lg sm:text-2xl font-serif font-bold text-[#031D44] mb-5 border-b border-[#E5DCD0] pb-3">
                Company Details & Shipping Address
              </h2>

              <div className="bg-white p-5 sm:p-8 rounded-[24px] border border-[#E5DCD0] shadow-2xs">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                      className="w-full px-3.5 py-3 bg-[#FAF7F2] border border-[#E5DCD0] rounded-xl text-xs text-[#031D44] focus:outline-none focus:border-[#B58E58] focus:bg-white shadow-2xs transition-colors"
                      placeholder="John Doe"
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
                      className="w-full px-3.5 py-3 bg-[#FAF7F2] border border-[#E5DCD0] rounded-xl text-xs text-[#031D44] focus:outline-none focus:border-[#B58E58] focus:bg-white shadow-2xs transition-colors"
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
                      className="w-full px-3.5 py-3 bg-[#FAF7F2] border border-[#E5DCD0] rounded-xl text-xs text-[#031D44] focus:outline-none focus:border-[#B58E58] focus:bg-white shadow-2xs transition-colors"
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
                      className="w-full px-3.5 py-3 bg-[#FAF7F2] border border-[#E5DCD0] rounded-xl text-xs text-[#031D44] focus:outline-none focus:border-[#B58E58] focus:bg-white shadow-2xs transition-colors"
                      placeholder="Grand Plaza Suites"
                    />
                  </div>

                  <div className="sm:col-span-2 my-1">
                    <div className="flex items-center gap-3">
                      <div className="h-px bg-[#E5DCD0] flex-1"></div>
                      <span className="text-[9.5px] font-bold text-[#B58E58] uppercase tracking-widest">
                        Shipping Location
                      </span>
                      <div className="h-px bg-[#E5DCD0] flex-1"></div>
                    </div>
                  </div>

                  <div className="sm:col-span-2">
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                      Address Line 1 <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="addressLine1"
                      required
                      value={companyDetails.addressLine1}
                      onChange={handleInputChange}
                      className="w-full px-3.5 py-3 bg-[#FAF7F2] border border-[#E5DCD0] rounded-xl text-xs text-[#031D44] focus:outline-none focus:border-[#B58E58] focus:bg-white shadow-2xs transition-colors"
                      placeholder="123 Parliament Street"
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
                      className="w-full px-3.5 py-3 bg-[#FAF7F2] border border-[#E5DCD0] rounded-xl text-xs text-[#031D44] focus:outline-none focus:border-[#B58E58] focus:bg-white shadow-2xs transition-colors"
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
                      className="w-full px-3.5 py-3 bg-[#FAF7F2] border border-[#E5DCD0] rounded-xl text-xs text-[#031D44] focus:outline-none focus:border-[#B58E58] focus:bg-white shadow-2xs transition-colors"
                      placeholder="Ontario"
                    />
                  </div>

                  <div className="sm:col-span-2">
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1.5">
                      Postal Code <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="postalCode"
                      required
                      value={companyDetails.postalCode}
                      onChange={handleInputChange}
                      className="w-full px-3.5 py-3 bg-[#FAF7F2] border border-[#E5DCD0] rounded-xl text-xs text-[#031D44] focus:outline-none focus:border-[#B58E58] focus:bg-white shadow-2xs transition-colors uppercase"
                      placeholder="K1A 0A1"
                    />
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* STEP 5: REVIEW & SUBMIT */}
          {step === 5 && (
            <div className="animate-in fade-in slide-in-from-bottom-4 duration-500 flex flex-col lg:flex-row gap-6 lg:gap-8 z-10 relative">
              <div className="flex-1 min-w-0 space-y-5">
                <h2 className="text-lg sm:text-2xl font-serif font-bold text-[#031D44] border-b border-[#E5DCD0] pb-3">
                  Review Your Quote Request
                </h2>

                <div className="bg-white p-5 rounded-[24px] border border-[#E5DCD0] shadow-2xs">
                  <div className="flex items-center gap-2 mb-3">
                    <FiUser className="text-[#B58E58]" size={15} />
                    <h3 className="text-[10.5px] font-bold text-[#031D44] uppercase tracking-widest">
                      Applicant & Address Details
                    </h3>
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-[#FAF7F2] p-4 rounded-xl border border-[#E5DCD0] text-xs shadow-2xs">
                    <div>
                      <p className="text-[8.5px] text-gray-400 uppercase font-bold tracking-widest mb-0.5">
                        Business Name
                      </p>
                      <p className="font-bold text-[#031D44] truncate">
                        {companyDetails.hotelName || "N/A"}
                      </p>
                    </div>
                    <div>
                      <p className="text-[8.5px] text-gray-400 uppercase font-bold tracking-widest mb-0.5">
                        Contact Person
                      </p>
                      <p className="font-bold text-[#031D44] truncate">
                        {companyDetails.fullName || "N/A"}
                      </p>
                    </div>
                    <div className="sm:col-span-2 pt-2.5 mt-0.5 border-t border-[#E5DCD0]">
                      <p className="text-[8.5px] text-gray-400 uppercase font-bold tracking-widest mb-0.5">
                        Shipping Destination
                      </p>
                      <p className="text-gray-700 font-medium">
                        {companyDetails.addressLine1}, {companyDetails.city},{" "}
                        {companyDetails.stateProvince}{" "}
                        {companyDetails.postalCode}
                      </p>
                    </div>
                  </div>
                </div>

                <div className="bg-white p-5 rounded-[24px] border border-[#E5DCD0] shadow-2xs">
                  <h3 className="text-[10.5px] font-bold text-[#031D44] uppercase tracking-widest mb-3.5 flex items-center gap-1.5">
                    <FiBox className="text-[#B58E58]" size={15} /> Required
                    Inventory
                  </h3>
                  <div className="space-y-2.5">
                    {quoteItems.map((item) => {
                      const itemPrice = item.pricing?.price || 0;
                      return (
                        <div
                          key={`review-${item.variant_id}`}
                          className="flex justify-between items-center bg-[#FAF7F2] p-3.5 rounded-xl border border-[#E5DCD0] text-xs"
                        >
                          <div className="flex items-center gap-3 min-w-0 pr-3">
                            <img
                              src={
                                item.resolvedImage ||
                                "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600"
                              }
                              onError={(e) => {
                                e.target.src =
                                  "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
                              }}
                              className="w-10 h-10 rounded-lg object-cover border border-[#E5DCD0] shrink-0"
                              alt="product"
                            />
                            <div className="min-w-0">
                              <p className="font-bold text-[#031D44] truncate mb-0.5">
                                {item.product_name}
                              </p>
                              <p className="text-[9.5px] font-bold text-[#B58E58] uppercase tracking-wider">
                                {item.bucket} • {item.exactQuantity} Qty
                              </p>
                            </div>
                          </div>
                          <p className="font-bold text-[#4A5D4E] shrink-0 text-right">
                            CAD ${(itemPrice * item.exactQuantity).toFixed(2)}
                          </p>
                        </div>
                      );
                    })}
                  </div>
                </div>
              </div>

              {/* Sidebar Total Summary */}
              <div className="w-full lg:w-[340px] shrink-0">
                <div className="bg-[#031D44] p-5 sm:p-6 rounded-[24px] shadow-xl sticky top-6 text-white border border-[#031D44]/50">
                  <h3 className="text-xs font-serif font-bold mb-4 border-b border-white/20 pb-2.5 flex items-center gap-1.5">
                    <FiList className="text-[#B58E58]" size={16} /> Final Quote
                    Summary
                  </h3>

                  <div className="space-y-3 text-xs text-gray-300 mb-5">
                    <div className="flex justify-between items-center">
                      <span>Base Subtotal</span>
                      <span className="font-medium text-white">
                        CAD ${subtotal.toFixed(2)}
                      </span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span>Estimated Tax (13%)</span>
                      <span className="font-medium text-white">
                        CAD ${tax.toFixed(2)}
                      </span>
                    </div>
                    {discount > 0 && (
                      <div className="flex justify-between text-[#B58E58] font-bold bg-white/10 p-2 rounded-lg -mx-1 px-2">
                        <span>Coupon Discount</span>
                        <span>- CAD ${discount.toFixed(2)}</span>
                      </div>
                    )}
                  </div>

                  <div className="border-t border-white/20 pt-4 mb-6">
                    <div className="flex justify-between items-end">
                      <span className="font-bold text-gray-400 uppercase tracking-widest text-[9.5px]">
                        Grand Total
                      </span>
                      <span className="text-xl sm:text-2xl font-serif font-bold text-[#B58E58]">
                        CAD ${grandTotal.toFixed(2)}
                      </span>
                    </div>
                  </div>

                  <button
                    onClick={handleSubmitQuote}
                    disabled={savingAddress}
                    className="w-full py-3.5 bg-[#B58E58] hover:bg-white text-white hover:text-[#031D44] rounded-xl text-[10.5px] font-bold uppercase tracking-widest flex items-center justify-center gap-2 transition-all cursor-pointer shadow-md"
                  >
                    {savingAddress ? (
                      <div className="flex items-center gap-2">
                        <div className="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        Transmitting...
                      </div>
                    ) : (
                      <>
                        <FiSend size={14} /> Submit to Gateway B2B
                      </>
                    )}
                  </button>
                  <p className="text-center text-[9.5px] text-gray-400 mt-3 font-light">
                    By submitting, you agree to our wholesale terms.
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* STEP 6: SUCCESS */}
          {step === 6 && (
            <div className="animate-in zoom-in slide-in-from-bottom-4 text-center py-12 px-3 max-w-md mx-auto z-10 relative">
              <div className="w-20 h-20 bg-white text-[#B58E58] border-6 border-[#FAF7F2] shadow-xl rounded-full flex items-center justify-center mx-auto mb-5">
                <FiCheckCircle size={40} />
              </div>
              <h2 className="text-xl sm:text-2xl font-serif font-bold text-[#031D44] mb-2.5">
                Quote Transmitted!
              </h2>
              <p className="text-xs text-gray-600 mb-6 leading-relaxed font-light">
                Your commercial bulk inquiry and shipping address have been
                successfully saved and routed to the Gateway Linen Wholesale
                Division.
              </p>
              <button
                onClick={() => navigate("/")}
                className="px-7 py-3 bg-[#031D44] hover:bg-[#B58E58] text-white rounded-xl text-[10.5px] font-bold uppercase tracking-widest shadow-md transition-colors cursor-pointer"
              >
                Return to Dashboard
              </button>
            </div>
          )}

          {/* Bottom Navigation Buttons */}
          {step < 6 && (
            <div className="flex justify-between items-center mt-8 pt-5 border-t border-[#E5DCD0] z-10 relative">
              <button
                onClick={() => (step > 1 ? setStep(step - 1) : navigate("/"))}
                className="flex items-center gap-1.5 px-4 py-2 text-[10px] sm:text-[10.5px] font-bold text-gray-600 hover:text-[#031D44] uppercase tracking-widest transition-colors cursor-pointer bg-white border border-[#E5DCD0] hover:border-[#031D44] rounded-xl shadow-2xs"
              >
                <FiArrowLeft size={13} /> {step === 1 ? "Cancel" : "Go Back"}
              </button>

              {step < 5 && (
                <button
                  onClick={() => {
                    if (step === 1 && quoteItems.length === 0) {
                      setWarningMessage(
                        "Please select at least one premium product variant from the catalog before proceeding.",
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
                        "Please complete all required (*) contact and shipping fields to ensure accurate delivery.",
                      );
                      return;
                    }
                    setWarningMessage("");
                    setStep(step + 1);
                  }}
                  className="flex items-center gap-1.5 px-6 py-2.5 bg-[#031D44] text-white rounded-xl text-[10px] sm:text-[10.5px] font-bold uppercase tracking-widest hover:bg-[#B58E58] transition-colors cursor-pointer shadow-md"
                >
                  Proceed Next <FiArrowRight size={13} />
                </button>
              )}
            </div>
          )}
        </div>
      </div>

      {/* --- VARIANT DETAIL POPUP MODAL --- */}
      {selectedVariantDetail &&
        (() => {
          const productImages = [
            selectedVariantDetail.resolvedImage ||
              "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600",
          ];
          const attr = selectedVariantDetail.attributes || {};

          return (
            <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
              <div className="bg-[#FAF7F2] border border-[#E5DCD0] rounded-[24px] max-w-md w-full p-6 sm:p-7 shadow-2xl relative animate-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto">
                <button
                  type="button"
                  onClick={() => setSelectedVariantDetail(null)}
                  className="absolute top-4 right-4 text-gray-400 hover:text-[#031D44] bg-white p-2 rounded-full transition-colors cursor-pointer border border-[#E5DCD0] shadow-sm z-10"
                >
                  <FiX size={16} />
                </button>

                <div className="w-full h-48 sm:h-56 bg-white rounded-xl overflow-hidden mb-4 border border-[#E5DCD0] shadow-2xs relative group">
                  <img
                    src={productImages[activeImageIndex] || productImages[0]}
                    onError={(e) => {
                      e.target.src =
                        "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
                    }}
                    alt="Variant Preview"
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                  />
                </div>

                <span className="text-[9px] font-bold text-[#B58E58] tracking-widest uppercase mb-1 block">
                  SKU: {selectedVariantDetail.sku || "N/A"}
                </span>
                <h3 className="text-lg sm:text-xl font-serif font-bold text-[#031D44] mb-3 leading-snug">
                  {selectedVariantDetail.product_name || "Unnamed Product"}
                </h3>

                <div className="bg-white p-4 rounded-xl border border-[#E5DCD0] mb-5 space-y-2.5 text-xs text-gray-700 shadow-2xs">
                  <div className="flex justify-between items-center">
                    <span className="font-bold text-[#031D44] uppercase tracking-wider text-[10px]">
                      Base Wholesale Price:
                    </span>
                    <span className="font-serif font-bold text-base text-[#B58E58]">
                      CAD $
                      {Number(
                        selectedVariantDetail.pricing?.price || 0,
                      ).toFixed(2)}
                    </span>
                  </div>

                  <div className="pt-2.5 border-t border-gray-100 grid grid-cols-2 gap-2.5 mt-2">
                    {attr.size && (
                      <div className="bg-[#FAF7F2] p-2 rounded-lg border border-[#E5DCD0]">
                        <p className="text-[8.5px] font-bold uppercase text-gray-400 mb-0.5 tracking-widest">
                          Size
                        </p>
                        <p className="text-[#031D44] font-bold text-xs truncate">
                          {attr.size}
                        </p>
                      </div>
                    )}
                    {attr.color && (
                      <div className="bg-[#FAF7F2] p-2 rounded-lg border border-[#E5DCD0]">
                        <p className="text-[8.5px] font-bold uppercase text-gray-400 mb-0.5 tracking-widest">
                          Color
                        </p>
                        <p className="text-[#031D44] font-bold text-xs truncate">
                          {attr.color}
                        </p>
                      </div>
                    )}
                    {attr.material && (
                      <div className="bg-[#FAF7F2] p-2 rounded-lg border border-[#E5DCD0]">
                        <p className="text-[8.5px] font-bold uppercase text-gray-400 mb-0.5 tracking-widest">
                          Material
                        </p>
                        <p className="text-[#031D44] font-bold text-xs truncate">
                          {attr.material}
                        </p>
                      </div>
                    )}
                    {attr.weight_gsm && (
                      <div className="bg-[#FAF7F2] p-2 rounded-lg border border-[#E5DCD0]">
                        <p className="text-[8.5px] font-bold uppercase text-gray-400 mb-0.5 tracking-widest">
                          Weight (GSM)
                        </p>
                        <p className="text-[#031D44] font-bold text-xs truncate">
                          {attr.weight_gsm}
                        </p>
                      </div>
                    )}
                  </div>
                </div>

                <div className="flex gap-2.5">
                  <button
                    type="button"
                    onClick={() => setSelectedVariantDetail(null)}
                    className="w-1/2 py-3 bg-white border border-[#E5DCD0] hover:bg-gray-50 hover:border-[#031D44] text-[#031D44] text-[10.5px] font-bold tracking-widest uppercase rounded-xl transition-all cursor-pointer shadow-2xs"
                  >
                    Close
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      handleAddVariant(selectedVariantDetail);
                      setSelectedVariantDetail(null);
                    }}
                    className="w-1/2 py-3 bg-[#031D44] hover:bg-[#B58E58] text-white text-[10.5px] font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer flex items-center justify-center gap-1.5"
                  >
                    <FiBox size={14} /> Add to Quote
                  </button>
                </div>
              </div>
            </div>
          );
        })}
    </div>
  );
}
