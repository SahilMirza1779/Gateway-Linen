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
    <div className="min-h-screen bg-[#F0EAE1] py-6 sm:py-10 px-3 sm:px-6 font-sans relative">
      <div className="max-w-6xl mx-auto">
        <div className="text-center mb-6 sm:mb-8">
          <h1 className="text-2xl sm:text-4xl font-serif font-bold text-[#031D44] mb-1.5">
            B2B Quote Builder
          </h1>
          <p className="text-xs sm:text-sm text-gray-600">
            Build your custom commercial package for special wholesale pricing.
          </p>
        </div>

        {/* Stepper Navigation */}
        <div className="flex items-center bg-white p-2 sm:p-3 rounded-2xl shadow-sm border border-[#E5DCD0] mb-6 sm:mb-8 overflow-x-auto gap-2 scrollbar-none">
          {[
            { num: 1, label: "Variants", icon: FiBox },
            { num: 2, label: "Quantities", icon: FiList },
            { num: 3, label: "Pricing", icon: FiDollarSign },
            { num: 4, label: "Details", icon: FiUser },
            { num: 5, label: "Review", icon: FiCheckCircle },
          ].map((s) => (
            <div
              key={s.num}
              className={`flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl text-[11px] sm:text-xs font-bold uppercase tracking-widest whitespace-nowrap transition-colors shrink-0 ${
                step >= s.num
                  ? "bg-[#031D44] text-white shadow-md"
                  : "text-gray-400 bg-transparent"
              }`}
            >
              <s.icon size={13} />
              <span>{s.label}</span>
            </div>
          ))}
        </div>

        {warningMessage && (
          <div className="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl flex items-center justify-between text-xs font-bold shadow-sm">
            <div className="flex items-center gap-2">
              <FiAlertCircle size={15} />
              <span>{warningMessage}</span>
            </div>
            <button
              onClick={() => setWarningMessage("")}
              className="text-red-400 hover:text-red-700 cursor-pointer"
            >
              <FiX size={15} />
            </button>
          </div>
        )}

        <div className="bg-white rounded-[24px] sm:rounded-[32px] shadow-xl border border-[#E5DCD0] p-3 sm:p-8 md:p-10 min-h-[450px] relative">
          {/* STEP 1: VARIANTS */}
          {step === 1 && (
            <div className="animate-in fade-in">
              <h2 className="text-lg sm:text-xl font-serif font-bold text-[#031D44] mb-5 border-b border-[#E5DCD0] pb-3">
                Select Product Variants for Quote
              </h2>

              {loadingVariants ? (
                <div className="flex flex-col items-center justify-center py-16">
                  <div className="w-8 h-8 border-4 border-[#E5DCD0] border-t-[#B58E58] rounded-full animate-spin mb-3"></div>
                  <p className="text-[11px] font-bold tracking-widest uppercase text-[#031D44]">
                    Loading Variants Catalog...
                  </p>
                </div>
              ) : !Array.isArray(allVariants) || allVariants.length === 0 ? (
                <div className="text-center py-16 text-xs text-gray-400 font-medium">
                  No active variants found in the database.
                </div>
              ) : (
                <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-5">
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
                        className={`flex flex-col bg-white rounded-xl sm:rounded-2xl overflow-hidden border transition-all duration-300 ${
                          isAdded
                            ? "border-[#B58E58] shadow-md ring-2 ring-[#B58E58]/20"
                            : "border-[#E5DCD0] hover:border-[#031D44] hover:shadow-md"
                        }`}
                      >
                        <div
                          onClick={() => {
                            setSelectedVariantDetail(variant);
                            setActiveImageIndex(0);
                          }}
                          className="h-28 sm:h-40 overflow-hidden bg-gray-50 relative group cursor-pointer"
                        >
                          <img
                            src={itemImage}
                            alt={variantName}
                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            onError={(e) => {
                              e.target.src =
                                "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
                            }}
                          />
                          <div className="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] sm:text-xs font-bold gap-1">
                            <FiInfo size={13} /> Quick View
                          </div>
                          {isAdded && (
                            <div className="absolute top-1.5 right-1.5 sm:top-2 sm:right-2 bg-green-500 text-white p-1 rounded-full shadow-md">
                              <FiCheckCircle size={13} />
                            </div>
                          )}
                        </div>
                        <div className="p-2.5 sm:p-4 flex flex-col flex-grow">
                          <p className="text-[8.5px] sm:text-[9px] text-[#B58E58] uppercase font-bold tracking-widest mb-0.5 truncate">
                            SKU: {sku}
                          </p>
                          <h3
                            onClick={() => {
                              setSelectedVariantDetail(variant);
                              setActiveImageIndex(0);
                            }}
                            className="text-[11px] sm:text-sm font-bold text-[#031D44] mb-1 line-clamp-2 cursor-pointer hover:text-[#B58E58] transition-colors"
                          >
                            {variantName}
                          </h3>
                          <p className="text-[11px] sm:text-xs font-bold text-[#B58E58] mb-2.5 sm:mb-3 flex-grow">
                            CAD ${Number(price).toFixed(2)}
                          </p>

                          <button
                            onClick={() =>
                              isAdded
                                ? handleRemoveVariant(variantId)
                                : handleAddVariant(variant)
                            }
                            className={`w-full py-2 sm:py-2.5 rounded-lg sm:rounded-xl text-[9.5px] sm:text-[11px] font-bold tracking-wider sm:tracking-widest uppercase transition-all cursor-pointer ${
                              isAdded
                                ? "bg-red-50 text-red-600 border border-red-200 hover:bg-red-100"
                                : "bg-[#031D44] text-white hover:bg-[#B58E58] shadow-sm"
                            }`}
                          >
                            {isAdded ? "Remove" : "Add to Quote"}
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
              <h2 className="text-lg sm:text-xl font-serif font-bold text-[#031D44] mb-5 border-b border-[#E5DCD0] pb-3">
                Specify Bulk Quantities
              </h2>
              {quoteItems.length === 0 ? (
                <div className="text-center py-16">
                  <FiBox size={40} className="mx-auto text-gray-300 mb-3" />
                  <p className="text-xs text-gray-500 font-medium">
                    Please go back and select at least one variant to continue.
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
                        className="flex flex-col sm:flex-row items-center gap-3.5 border border-[#E5DCD0] p-3.5 rounded-2xl bg-[#FAF7F2] shadow-2xs"
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
                          className="w-16 h-16 sm:w-20 sm:h-20 rounded-xl object-cover border border-gray-200 shrink-0"
                          alt={item.product_name}
                        />
                        <div className="flex-1 text-center sm:text-left min-w-0 w-full">
                          <p className="text-[9px] text-[#B58E58] uppercase font-bold tracking-widest mb-0.5 truncate">
                            SKU: {item.sku}
                          </p>
                          <h3 className="font-bold text-xs sm:text-sm text-[#031D44] mb-1 truncate">
                            {item.product_name}
                          </h3>
                          <p className="text-xs text-gray-600 font-medium">
                            Unit Price:{" "}
                            <span className="text-[#B58E58] font-bold">
                              CAD ${Number(itemPrice).toFixed(2)}
                            </span>
                          </p>
                        </div>

                        <div className="flex flex-col gap-2 w-full sm:w-auto bg-white p-3 rounded-xl border border-[#E5DCD0] shrink-0">
                          <div className="flex items-center justify-between gap-3">
                            <span className="text-[10px] text-[#031D44] font-bold uppercase tracking-wider">
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
                              className="px-2.5 py-1 border border-gray-200 rounded-lg text-xs font-bold text-[#031D44] bg-gray-50 focus:outline-none focus:border-[#B58E58] cursor-pointer"
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
                              className="w-20 px-2.5 py-1 border border-gray-200 rounded-lg text-xs font-bold text-[#031D44] bg-gray-50 text-center focus:outline-none focus:border-[#B58E58]"
                            />
                          </div>
                        </div>

                        <button
                          onClick={() => handleRemoveVariant(variantId)}
                          className="w-full sm:w-auto p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors cursor-pointer flex justify-center shrink-0"
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

          {/* STEP 3: PRICE & TAX BREAKDOWN */}
          {step === 3 && (
            <div className="animate-in fade-in max-w-2xl mx-auto">
              <h2 className="text-lg sm:text-xl font-serif font-bold text-[#031D44] mb-5 border-b border-[#E5DCD0] pb-3">
                Price & Tax Calculation Summary
              </h2>

              <div className="bg-[#FAF7F2] p-4 sm:p-6 rounded-[24px] border border-[#E5DCD0] shadow-2xs mb-5 space-y-3">
                <h3 className="text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-2">
                  Bulk Calculation Breakdown
                </h3>

                {quoteItems.map((item) => {
                  const itemPrice = Number(item.pricing?.price || 0);
                  const qty = Number(item.exactQuantity || 0);
                  const itemTotal = itemPrice * qty;
                  return (
                    <div
                      key={`summary-${item.variant_id}`}
                      className="flex justify-between items-center bg-white p-3 rounded-xl border border-gray-100 text-xs"
                    >
                      <div className="min-w-0 pr-2">
                        <p className="font-bold text-[#031D44] truncate">
                          {item.product_name}{" "}
                          <span className="text-gray-400 font-normal">
                            ({item.sku})
                          </span>
                        </p>
                        <p className="text-[10px] text-gray-500">
                          {qty} Units × CAD ${itemPrice.toFixed(2)}
                        </p>
                      </div>
                      <p className="font-bold text-[#B58E58] shrink-0">
                        CAD ${itemTotal.toFixed(2)}
                      </p>
                    </div>
                  );
                })}

                <div className="pt-3 border-t border-[#E5DCD0] space-y-1.5 text-xs text-gray-700">
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

                <div className="pt-3 border-t-2 border-[#031D44]/10 flex justify-between items-center">
                  <span className="text-xs font-bold text-[#031D44] uppercase tracking-widest">
                    Grand Total
                  </span>
                  <span className="text-xl sm:text-2xl font-serif font-bold text-[#B58E58]">
                    CAD ${grandTotal.toFixed(2)}
                  </span>
                </div>
              </div>

              {/* Coupon Code Section */}
              <div className="bg-white p-4 sm:p-5 rounded-2xl border border-[#E5DCD0] shadow-2xs">
                <label className="block text-[10px] uppercase tracking-widest text-[#031D44] font-bold mb-2 flex items-center gap-1.5">
                  <FiTag size={12} /> Have a Wholesale Coupon Code? (Try B2B50)
                </label>
                <div className="flex gap-2.5">
                  <input
                    type="text"
                    value={couponCode}
                    onChange={(e) => setCouponCode(e.target.value)}
                    placeholder="e.g. B2B50"
                    className="flex-1 px-3.5 py-2.5 bg-gray-50 border border-[#E5DCD0] rounded-xl text-xs text-[#031D44] font-bold uppercase focus:outline-none focus:border-[#B58E58]"
                  />
                  <button
                    onClick={handleApplyCoupon}
                    className="px-5 py-2.5 bg-[#B58E58] hover:bg-[#031D44] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest transition-colors cursor-pointer shadow-sm"
                  >
                    Apply
                  </button>
                </div>
                {couponMessage.text && (
                  <div
                    className={`mt-2.5 p-2.5 rounded-xl text-xs flex items-center gap-2 ${
                      couponMessage.type === "success"
                        ? "bg-green-50 text-green-700 border border-green-200"
                        : "bg-red-50 text-red-700 border border-red-200"
                    }`}
                  >
                    {couponMessage.type === "success" ? (
                      <FiCheck size={13} />
                    ) : (
                      <FiAlertCircle size={13} />
                    )}
                    <span className="font-medium">{couponMessage.text}</span>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* STEP 4: COMPANY DETAILS & ADDRESS */}
          {step === 4 && (
            <div className="animate-in fade-in max-w-2xl mx-auto">
              <h2 className="text-lg sm:text-xl font-serif font-bold text-[#031D44] mb-5 border-b border-[#E5DCD0] pb-3">
                Hotel / Company Details & Shipping Address
              </h2>
              <div className="bg-[#FAF7F2] p-4 sm:p-6 rounded-[24px] border border-[#E5DCD0] shadow-2xs">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                      Full Name <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="fullName"
                      required
                      value={companyDetails.fullName}
                      onChange={handleInputChange}
                      className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="Sahil Mirza"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                      Email Address <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="email"
                      name="email"
                      required
                      value={companyDetails.email}
                      onChange={handleInputChange}
                      className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="purchasing@hotel.ca"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                      Phone Number <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="phone"
                      required
                      value={companyDetails.phone}
                      onChange={handleInputChange}
                      className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="+1 (555) 000-0000"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                      Hotel / Business Name{" "}
                      <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="hotelName"
                      required
                      value={companyDetails.hotelName}
                      onChange={handleInputChange}
                      className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="Grand Plaza Suites"
                    />
                  </div>
                  <div className="sm:col-span-2">
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                      Address Line 1 <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="addressLine1"
                      required
                      value={companyDetails.addressLine1}
                      onChange={handleInputChange}
                      className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="Parliament Hill / Street"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                      City <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="city"
                      required
                      value={companyDetails.city}
                      onChange={handleInputChange}
                      className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="Ottawa"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                      State / Province
                    </label>
                    <input
                      type="text"
                      name="stateProvince"
                      value={companyDetails.stateProvince}
                      onChange={handleInputChange}
                      className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="Ontario"
                    />
                  </div>
                  <div className="sm:col-span-2">
                    <label className="block text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-1">
                      Postal Code <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="postalCode"
                      required
                      value={companyDetails.postalCode}
                      onChange={handleInputChange}
                      className="w-full px-3.5 py-2.5 bg-white border border-[#E5DCD0] rounded-xl text-xs focus:outline-none focus:border-[#B58E58]"
                      placeholder="K1A 0A1"
                    />
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* STEP 5: REVIEW & SUBMIT */}
          {step === 5 && (
            <div className="animate-in fade-in flex flex-col lg:flex-row gap-6">
              <div className="flex-1 min-w-0">
                <h2 className="text-lg sm:text-xl font-serif font-bold text-[#031D44] mb-5 border-b border-[#E5DCD0] pb-3">
                  Review Your Quote Request
                </h2>

                <div className="bg-[#FAF7F2] p-4 sm:p-5 rounded-2xl border border-[#E5DCD0] mb-5 shadow-2xs">
                  <div className="flex items-center gap-2 mb-2.5">
                    <FiUser className="text-[#B58E58]" size={14} />
                    <h3 className="text-[10px] font-bold text-[#031D44] uppercase tracking-widest">
                      Applicant & Address Details
                    </h3>
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5 bg-white p-3.5 rounded-xl border border-gray-100 text-xs">
                    <div>
                      <p className="text-[9px] text-gray-400 uppercase font-bold">
                        Business
                      </p>
                      <p className="font-bold text-[#031D44] truncate">
                        {companyDetails.hotelName || "N/A"}
                      </p>
                    </div>
                    <div>
                      <p className="text-[9px] text-gray-400 uppercase font-bold">
                        Contact Name
                      </p>
                      <p className="font-bold text-[#031D44] truncate">
                        {companyDetails.fullName || "N/A"}
                      </p>
                    </div>
                    <div className="sm:col-span-2 pt-2 border-t border-gray-50">
                      <p className="text-[9px] text-gray-400 uppercase font-bold">
                        Shipping Address
                      </p>
                      <p className="text-gray-700 text-xs">
                        {companyDetails.addressLine1}, {companyDetails.city},{" "}
                        {companyDetails.stateProvince}{" "}
                        {companyDetails.postalCode}
                      </p>
                    </div>
                  </div>
                </div>

                <h3 className="text-[10px] font-bold text-[#031D44] uppercase tracking-widest mb-2.5 flex items-center gap-2">
                  <FiBox className="text-[#B58E58]" size={14} /> Required
                  Inventory
                </h3>
                <div className="space-y-2.5 bg-white p-3.5 rounded-2xl border border-[#E5DCD0] shadow-2xs">
                  {quoteItems.map((item) => {
                    const itemPrice = item.pricing?.price || 0;
                    return (
                      <div
                        key={`review-${item.variant_id}`}
                        className="flex justify-between items-center border-b border-gray-100 last:border-0 pb-2.5 last:pb-0 text-xs"
                      >
                        <div className="flex items-center gap-2.5 min-w-0 pr-2">
                          <img
                            src={
                              item.resolvedImage ||
                              "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600"
                            }
                            onError={(e) => {
                              e.target.src =
                                "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
                            }}
                            className="w-9 h-9 rounded-lg object-cover border border-gray-200 shrink-0"
                            alt="product"
                          />
                          <div className="min-w-0">
                            <p className="font-bold text-[#031D44] truncate">
                              {item.product_name}
                            </p>
                            <p className="text-[9px] text-gray-500 uppercase tracking-widest">
                              {item.bucket} • {item.exactQuantity} Qty
                            </p>
                          </div>
                        </div>
                        <p className="font-bold text-[#B58E58] shrink-0">
                          CAD ${(itemPrice * item.exactQuantity).toFixed(2)}
                        </p>
                      </div>
                    );
                  })}
                </div>
              </div>

              <div className="w-full lg:w-[320px] shrink-0">
                <div className="bg-[#031D44] p-5 sm:p-6 rounded-2xl shadow-xl sticky top-6 text-white">
                  <h3 className="text-xs font-serif font-bold mb-3.5 border-b border-white/20 pb-2.5 flex items-center gap-2">
                    <FiList className="text-[#B58E58]" size={14} /> Final Totals
                  </h3>

                  <div className="space-y-2.5 text-xs text-gray-300 mb-4">
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

                  <div className="border-t border-white/20 pt-3.5 mb-5">
                    <div className="flex justify-between items-end">
                      <span className="font-bold text-gray-200 uppercase tracking-widest text-[9px]">
                        Grand Total
                      </span>
                      <span className="text-lg sm:text-xl font-serif font-bold text-[#B58E58]">
                        CAD ${grandTotal.toFixed(2)}
                      </span>
                    </div>
                  </div>

                  <button
                    onClick={handleSubmitQuote}
                    disabled={savingAddress}
                    className="w-full py-3.5 bg-[#B58E58] hover:bg-white text-white hover:text-[#031D44] rounded-xl text-[10px] font-bold uppercase tracking-widest flex items-center justify-center gap-2 transition-all cursor-pointer shadow-md"
                  >
                    {savingAddress ? (
                      "Transmitting Quote..."
                    ) : (
                      <>
                        <FiSend size={13} /> Send to Admin
                      </>
                    )}
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* STEP 6: SUCCESS */}
          {step === 6 && (
            <div className="animate-in zoom-in text-center py-10 px-3 max-w-md mx-auto">
              <div className="w-20 h-20 bg-[#F7F2EB] text-[#B58E58] border-4 border-white shadow-lg rounded-full flex items-center justify-center mx-auto mb-5">
                <FiCheckCircle size={40} />
              </div>
              <h2 className="text-xl sm:text-2xl font-serif font-bold text-[#031D44] mb-2.5">
                Quote Transmitted!
              </h2>
              <p className="text-xs sm:text-sm text-gray-600 mb-6 leading-relaxed">
                Your commercial bulk inquiry and shipping address have been
                successfully saved to your profile and sent to the Gateway Linen
                Wholesale Division.
              </p>
              <button
                onClick={() => navigate("/dashboard")}
                className="px-6 py-3 bg-[#031D44] hover:bg-[#B58E58] text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-md transition-colors cursor-pointer"
              >
                Go to Dashboard
              </button>
            </div>
          )}

          {/* Bottom Navigation Buttons */}
          {step < 6 && (
            <div className="flex justify-between items-center mt-8 pt-5 border-t border-[#E5DCD0]">
              <button
                onClick={() => (step > 1 ? setStep(step - 1) : navigate("/"))}
                className="flex items-center gap-1.5 px-3.5 py-2 text-[10px] sm:text-[11px] font-bold text-gray-500 hover:text-[#031D44] uppercase tracking-widest transition-colors cursor-pointer bg-gray-50 hover:bg-gray-100 rounded-lg"
              >
                <FiArrowLeft size={13} /> {step === 1 ? "Cancel" : "Go Back"}
              </button>

              {step < 5 && (
                <button
                  onClick={() => {
                    if (step === 1 && quoteItems.length === 0) {
                      setWarningMessage(
                        "Please select at least one product variant from the catalog before proceeding.",
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
                  className="flex items-center gap-1.5 px-5 py-2.5 bg-[#031D44] text-white rounded-xl text-[10px] sm:text-[11px] font-bold uppercase tracking-widest hover:bg-[#B58E58] transition-colors cursor-pointer shadow-sm"
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
            <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-3 sm:p-4">
              <div className="bg-[#F7F2EB] border border-[#E5DCD0] rounded-[24px] max-w-md w-full p-5 sm:p-6 shadow-2xl relative animate-in zoom-in-95 max-h-[90vh] overflow-y-auto">
                <button
                  type="button"
                  onClick={() => setSelectedVariantDetail(null)}
                  className="absolute top-4 right-4 text-gray-400 hover:text-gray-800 bg-white p-1.5 rounded-full transition-colors cursor-pointer border border-gray-200 shadow-2xs z-10"
                >
                  <FiX size={15} />
                </button>

                <div className="w-full h-48 sm:h-52 bg-white rounded-xl overflow-hidden mb-3 border border-[#E5DCD0] shadow-2xs">
                  <img
                    src={productImages[activeImageIndex] || productImages[0]}
                    onError={(e) => {
                      e.target.src =
                        "https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?q=80&w=600";
                    }}
                    alt="Variant Preview"
                    className="w-full h-full object-cover transition-all duration-300"
                  />
                </div>

                <span className="text-[9px] font-bold text-[#B58E58] tracking-widest uppercase mb-1 block">
                  SKU: {selectedVariantDetail.sku || "N/A"}
                </span>
                <h3 className="text-lg font-serif font-bold text-[#031D44] mb-2">
                  {selectedVariantDetail.product_name || "Unnamed Product"}
                </h3>

                <div className="bg-white p-3.5 rounded-xl border border-[#E5DCD0] mb-4 space-y-2 text-xs text-gray-700 shadow-2xs">
                  <div className="flex justify-between items-center">
                    <span className="font-bold text-[#031D44]">
                      Base Wholesale Price:
                    </span>
                    <span className="font-serif font-bold text-sm sm:text-base text-[#B58E58]">
                      CAD $
                      {Number(
                        selectedVariantDetail.pricing?.price || 0,
                      ).toFixed(2)}
                    </span>
                  </div>

                  <div className="pt-2 border-t border-gray-100 grid grid-cols-2 gap-2 mt-2">
                    {attr.size && (
                      <div>
                        <p className="text-[9px] font-bold uppercase text-gray-400 mb-0.5">
                          Size
                        </p>
                        <p className="text-[#031D44] font-medium text-xs">
                          {attr.size}
                        </p>
                      </div>
                    )}
                    {attr.color && (
                      <div>
                        <p className="text-[9px] font-bold uppercase text-gray-400 musium mb-0.5">
                          Color
                        </p>
                        <p className="text-[#031D44] font-medium text-xs">
                          {attr.color}
                        </p>
                      </div>
                    )}
                    {attr.material && (
                      <div>
                        <p className="text-[9px] font-bold uppercase text-gray-400 mb-0.5">
                          Material
                        </p>
                        <p className="text-[#031D44] font-medium text-xs">
                          {attr.material}
                        </p>
                      </div>
                    )}
                    {attr.weight_gsm && (
                      <div>
                        <p className="text-[9px] font-bold uppercase text-gray-400 mb-0.5">
                          Weight (GSM)
                        </p>
                        <p className="text-[#031D44] font-medium text-xs">
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
                    className="w-1/2 py-2.5 bg-white border border-[#E5DCD0] hover:bg-gray-50 text-[#031D44] text-[10px] font-bold tracking-widest uppercase rounded-xl transition-all cursor-pointer shadow-2xs"
                  >
                    Close
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      handleAddVariant(selectedVariantDetail);
                      setSelectedVariantDetail(null);
                    }}
                    className="w-1/2 py-2.5 bg-[#031D44] hover:bg-[#B58E58] text-white text-[10px] font-bold tracking-widest uppercase rounded-xl shadow-sm transition-all cursor-pointer"
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
