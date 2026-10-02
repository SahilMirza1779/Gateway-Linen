import { useState } from "react";
import {
  FiMapPin,
  FiPhone,
  FiMail,
  FiSend,
  FiCheckCircle,
  FiShield,
  FiClock,
  FiHeadphones,
} from "react-icons/fi";

const ContactPage = () => {
  const [submitted, setSubmitted] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    subject: "",
    message: "",
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    setSubmitted(true);
    setTimeout(() => {
      setSubmitted(false);
      setFormData({ name: "", email: "", phone: "", subject: "", message: "" });
      alert(
        "Your message has been sent successfully! Our team will contact you soon.",
      );
    }, 1500);
  };

  return (
    <div className="w-full bg-[#FAF7F2] min-h-screen py-10 md:py-16 px-3 sm:px-6 md:px-12 font-sans relative overflow-hidden">
      {/* Background Decorative Gold Glows */}
      <div className="absolute -left-32 top-20 w-80 h-80 bg-[#B58E58]/10 rounded-full blur-3xl pointer-events-none"></div>
      <div className="absolute -right-32 bottom-20 w-80 h-80 bg-[#B58E58]/10 rounded-full blur-3xl pointer-events-none"></div>

      <div className="max-w-[1300px] mx-auto relative z-10">
        {/* Header Section */}
        <div className="text-center max-w-2xl mx-auto mb-8 md:mb-14">
          <div className="inline-flex items-center gap-2 bg-[#B58E58]/10 px-3 py-1 rounded-full mb-2.5 border border-[#B58E58]/20 shadow-2xs">
            <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
            <span className="text-[9px] sm:text-[9.5px] font-bold text-[#B58E58] tracking-[0.2em] sm:tracking-[0.25em] uppercase">
              Corporate B2B Hospitality Desk
            </span>
          </div>
          <h1 className="text-2xl sm:text-4xl md:text-5xl font-serif font-bold text-[#031D44] tracking-tight">
            Connect With Our Experts
          </h1>
          <p className="text-xs sm:text-sm text-gray-600 font-light mt-2 leading-relaxed px-2">
            Have inquiries regarding bulk hotel linen supplies, custom
            embroidery, or long-term partnerships? Our Canadian team is here for
            you.
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-5 md:gap-8 items-start">
          {/* Left Info Cards & Interactive Map */}
          <div className="lg:col-span-5 space-y-4 flex flex-col w-full">
            {/* Address Card */}
            <div className="bg-white p-4 sm:p-6 rounded-[20px] sm:rounded-[24px] border border-[#E5DCD0] shadow-2xs hover:border-[#B58E58] transition-all flex items-start gap-3.5 sm:gap-4 group">
              <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-[#031D44] text-[#B58E58] flex items-center justify-center flex-shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                <FiMapPin size={18} className="sm:w-[20px] sm:h-[20px]" />
              </div>
              <div className="min-w-0">
                <h3 className="text-sm sm:text-base font-serif font-bold text-[#031D44] mb-0.5 sm:mb-1">
                  Our Headquarters
                </h3>
                <p className="text-[11px] sm:text-xs text-gray-600 font-light leading-relaxed">
                  9 Mapleridge crescent,
                  <br />
                  Brandon R7A6P8,
                  <br />
                  Manitoba, Canada
                </p>
              </div>
            </div>

            {/* Phone Card */}
            <div className="bg-white p-4 sm:p-6 rounded-[20px] sm:rounded-[24px] border border-[#E5DCD0] shadow-2xs hover:border-[#B58E58] transition-all flex items-start gap-3.5 sm:gap-4 group">
              <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-[#031D44] text-[#B58E58] flex items-center justify-center flex-shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                <FiPhone size={18} className="sm:w-[20px] sm:h-[20px]" />
              </div>
              <div className="min-w-0">
                <h3 className="text-sm sm:text-base font-serif font-bold text-[#031D44] mb-0.5 sm:mb-1">
                  Direct Helpline
                </h3>
                <p className="text-[11px] sm:text-xs text-gray-600 font-light leading-relaxed">
                  +1 (204) 979-4044
                  <br />
                  Mon - Sat, 9:00 AM - 6:00 PM CST
                </p>
              </div>
            </div>

            {/* Email Card */}
            <div className="bg-white p-4 sm:p-6 rounded-[20px] sm:rounded-[24px] border border-[#E5DCD0] shadow-2xs hover:border-[#B58E58] transition-all flex items-start gap-3.5 sm:gap-4 group">
              <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-[#031D44] text-[#B58E58] flex items-center justify-center flex-shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                <FiMail size={18} className="sm:w-[20px] sm:h-[20px]" />
              </div>
              <div className="min-w-0">
                <h3 className="text-sm sm:text-base font-serif font-bold text-[#031D44] mb-0.5 sm:mb-1">
                  Email Inquiries
                </h3>
                <p className="text-[11px] sm:text-xs text-gray-600 font-light leading-relaxed break-all">
                  tapu_parikh@yahoo.com
                  <br />
                  gatewaylinen@gmail.com
                </p>
              </div>
            </div>

            {/* Interactive Map Preview Card */}
            <div className="bg-white p-2.5 sm:p-3 rounded-[20px] sm:rounded-[24px] border border-[#E5DCD0] shadow-2xs overflow-hidden">
              <div className="w-full h-[150px] sm:h-[180px] rounded-xl overflow-hidden border border-gray-200/50 shadow-inner">
                <iframe
                  title="Brandon Manitoba Map"
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d81559.45876313715!2d-99.98816!3d49.8482!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x52c1e65e638b6d85%3A0x44614e758784d531!2sBrandon%2C%20MB%2C%20Canada!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin"
                  width="100%"
                  height="100%"
                  style={{ border: 0 }}
                  allowFullScreen=""
                  loading="lazy"
                  referrerPolicy="no-referrer-when-downgrade"
                ></iframe>
              </div>
            </div>
          </div>

          {/* Right Contact Form with Trust Badges */}
          <div className="lg:col-span-7 bg-white p-5 sm:p-8 md:p-10 rounded-[20px] sm:rounded-[24px] md:rounded-[32px] border border-[#E5DCD0] shadow-sm relative flex flex-col justify-between w-full">
            <div>
              <span className="text-[9px] sm:text-[10px] font-bold text-[#B58E58] tracking-[0.2em] uppercase block mb-1">
                Secure Submission
              </span>
              <h3 className="text-xl sm:text-2xl md:text-3xl font-serif font-bold text-[#031D44] mb-1.5 sm:mb-2">
                Send Us a Message
              </h3>
              <p className="text-[11px] sm:text-xs text-gray-500 mb-5 sm:mb-6 font-light leading-relaxed">
                Fill out the form below and our corporate representative will
                get back to you within 24 hours.
              </p>

              {submitted ? (
                <div className="py-16 sm:py-20 flex flex-col items-center justify-center text-center">
                  <FiCheckCircle
                    size={48}
                    className="text-[#B58E58] mb-3 animate-bounce sm:w-[56px] sm:h-[56px]"
                  />
                  <h4 className="text-base sm:text-lg md:text-xl font-serif font-bold text-[#031D44]">
                    Message Sent Successfully!
                  </h4>
                  <p className="text-xs text-gray-500 mt-1">
                    Thank you for connecting with Gateway Linen.
                  </p>
                </div>
              ) : (
                <form
                  onSubmit={handleSubmit}
                  className="space-y-3.5 sm:space-y-4"
                >
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
                    <div>
                      <label className="block text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1">
                        Full Name <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="text"
                        required
                        value={formData.name}
                        onChange={(e) =>
                          setFormData({ ...formData, name: e.target.value })
                        }
                        placeholder="Enter your name"
                        className="w-full bg-[#FAF7F2] text-xs px-3.5 py-3 rounded-xl border border-[#E5DCD0] focus:outline-none focus:border-[#B58E58] shadow-2xs transition-all text-gray-800"
                      />
                    </div>
                    <div>
                      <label className="block text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1">
                        Email Address <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="email"
                        required
                        value={formData.email}
                        onChange={(e) =>
                          setFormData({ ...formData, email: e.target.value })
                        }
                        placeholder="name@company.ca"
                        className="w-full bg-[#FAF7F2] text-xs px-3.5 py-3 rounded-xl border border-[#E5DCD0] focus:outline-none focus:border-[#B58E58] shadow-2xs transition-all text-gray-800"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
                    <div>
                      <label className="block text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1">
                        Phone Number <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="text"
                        required
                        value={formData.phone}
                        onChange={(e) =>
                          setFormData({ ...formData, phone: e.target.value })
                        }
                        placeholder="+1 (xxx) xxx-xxxx"
                        className="w-full bg-[#FAF7F2] text-xs px-3.5 py-3 rounded-xl border border-[#E5DCD0] focus:outline-none focus:border-[#B58E58] shadow-2xs transition-all text-gray-800"
                      />
                    </div>
                    <div>
                      <label className="block text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1">
                        Subject <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="text"
                        required
                        value={formData.subject}
                        onChange={(e) =>
                          setFormData({ ...formData, subject: e.target.value })
                        }
                        placeholder="Bulk Inquiry / Support"
                        className="w-full bg-[#FAF7F2] text-xs px-3.5 py-3 rounded-xl border border-[#E5DCD0] focus:outline-none focus:border-[#B58E58] shadow-2xs transition-all text-gray-800"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1">
                      Your Message <span className="text-red-500">*</span>
                    </label>
                    <textarea
                      rows="3"
                      required
                      value={formData.message}
                      onChange={(e) =>
                        setFormData({ ...formData, message: e.target.value })
                      }
                      placeholder="Write your requirements here..."
                      className="w-full bg-[#FAF7F2] text-xs px-3.5 py-3 rounded-xl border border-[#E5DCD0] focus:outline-none focus:border-[#B58E58] shadow-2xs resize-none transition-all text-gray-800"
                    ></textarea>
                  </div>

                  <button
                    type="submit"
                    className="w-full py-3.5 sm:py-4 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-md transition-all cursor-pointer flex items-center justify-center gap-2 group"
                  >
                    <FiSend
                      size={15}
                      className="group-hover:translate-x-1 transition-transform"
                    />
                    <span>Send Message</span>
                  </button>
                </form>
              )}
            </div>

            {/* Floating Trust Badges Footer inside form card */}
            <div className="grid grid-cols-3 gap-2 mt-5 pt-4 border-t border-[#E5DCD0]">
              <div className="flex flex-col items-center text-center p-2 bg-[#FAF7F2] rounded-xl border border-[#E5DCD0]/60">
                <FiHeadphones className="text-[#B58E58] mb-0.5" size={15} />
                <span className="text-[9px] font-bold text-[#031D44]">
                  24/7 Support
                </span>
              </div>
              <div className="flex flex-col items-center text-center p-2 bg-[#FAF7F2] rounded-xl border border-[#E5DCD0]/60">
                <FiShield className="text-[#B58E58] mb-0.5" size={15} />
                <span className="text-[9px] font-bold text-[#031D44]">
                  Secure Inquiry
                </span>
              </div>
              <div className="flex flex-col items-center text-center p-2 bg-[#FAF7F2] rounded-xl border border-[#E5DCD0]/60">
                <FiClock className="text-[#B58E58] mb-0.5" size={15} />
                <span className="text-[9px] font-bold text-[#031D44]">
                  Fast Response
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ContactPage;
