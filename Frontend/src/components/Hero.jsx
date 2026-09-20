import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { FiArrowRight } from "react-icons/fi";

import badsheet from "../assets/badsheet.jpg";
import bathcurtains from "../assets/bathcurtains.jpg";
import bathmat from "../assets/bathmat.jpg";
import blanket from "../assets/blanket.jpg";
import matterspad from "../assets/matterspad.jpg";
import pillow from "../assets/pillow.jpg";
import towel from "../assets/towel.jpg";

const Hero = () => {
  const navigate = useNavigate();

  const images = [
    badsheet,
    bathcurtains,
    bathmat,
    blanket,
    matterspad,
    pillow,
    towel,
  ];

  const [currentIndex, setCurrentIndex] = useState(0);

  useEffect(() => {
    const interval = setInterval(() => {
      setCurrentIndex((prevIndex) => (prevIndex + 1) % images.length);
    }, 5000);

    return () => clearInterval(interval);
  }, [images.length]);

  // Shop Now button click handler - All products page par bhejega
  const handleShopNow = () => {
    navigate("/category/all");
  };

  return (
    // Main Wrapper - Solid Navy Blue Background
    <div className="w-full bg-[#031D44] relative flex items-center min-h-[450px] md:min-h-[500px] overflow-hidden">
      {/* IMAGE LAYER - Sirf Right side par (55% width) */}
      <div className="absolute inset-y-0 right-0 w-full md:w-[55%] h-full">
        {images.map((img, index) => (
          <img
            key={index}
            src={img}
            alt="Gateway Linen Product"
            className={`absolute inset-0 w-full h-full object-cover transition-opacity duration-1000 ease-in-out ${
              index === currentIndex ? "opacity-100" : "opacity-0"
            }`}
          />
        ))}
        {/* THE MAGIC FADE - Image ke left kinare ko background se mix karne ke liye */}
        <div className="absolute inset-y-0 left-0 w-[150px] md:w-[250px] bg-gradient-to-r from-[#031D44] to-transparent"></div>
      </div>

      {/* CONTENT LAYER - Left side jahan text hoga */}
      <div className="relative z-10 w-full max-w-[1536px] mx-auto px-4 md:px-10 flex">
        <div className="w-full md:w-[50%] py-12 md:py-20">
          <p className="text-[#B58E58] text-[10px] md:text-[11px] font-bold tracking-[0.2em] uppercase mb-4">
            Premium Hospitality Linen Supplies
          </p>

          <h1 className="text-[38px] md:text-[54px] font-serif font-bold text-white leading-[1.1] mb-5 tracking-tight drop-shadow-md">
            Premium Linen <br />
            Solutions for <br />
            Hospitality
          </h1>

          <p className="text-gray-300 text-[13px] md:text-[14px] font-light mb-8 max-w-sm drop-shadow">
            Quality linens. Reliable supply. Your trusted partner in
            hospitality.
          </p>

          <button
            onClick={handleShopNow}
            className="bg-[#B58E58] hover:bg-[#9a7745] text-white text-[12.5px] font-medium py-3 px-6 rounded-[3px] transition-colors flex items-center gap-2 shadow-lg cursor-pointer group"
          >
            Shop Now{" "}
            <FiArrowRight
              size={15}
              className="group-hover:translate-x-1 transition-transform"
            />
          </button>
        </div>
      </div>
    </div>
  );
};

export default Hero;
