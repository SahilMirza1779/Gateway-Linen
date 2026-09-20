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
    <div className="w-full bg-[#F0EAE1] min-h-screen py-16 px-4 md:px-10 font-sans relative overflow-hidden">
      {/* Background Decorative Gold Glows */}
      <div className="absolute -left-32 top-20 w-96 h-96 bg-[#B58E58]/10 rounded-full blur-3xl pointer-events-none"></div>
      <div className="absolute -right-32 bottom-20 w-96 h-96 bg-[#B58E58]/10 rounded-full blur-3xl pointer-events-none"></div>

      <div className="max-w-[1536px] mx-auto relative z-10">
        {/* Header Section */}
        <div className="text-center max-w-2xl mx-auto mb-16">
          <div className="inline-flex items-center gap-2 bg-[#B58E58]/20 px-4 py-1.5 rounded-full mb-4 border border-[#B58E58]/40 shadow-sm">
            <span className="w-1.5 h-1.5 rounded-full bg-[#B58E58]"></span>
            <span className="text-[10px] font-bold text-[#B58E58] tracking-[0.25em] uppercase">
              Corporate B2B Hospitality Desk
            </span>
          </div>
          <h1 className="text-3xl md:text-5xl font-serif font-bold text-[#031D44] tracking-tight">
            Connect With Our Experts
          </h1>
          <p className="text-xs md:text-sm text-gray-600 font-light mt-3 leading-relaxed">
            Have inquiries regarding bulk hotel linen supplies, custom
            embroidery, or long-term partnerships? Our Canadian team is here for
            you.
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-10">
          {/* Left Info Cards & Interactive Map */}
          <div className="lg:col-span-5 space-y-6 flex flex-col">
            {/* Address Card */}
            <div className="bg-[#F7F2EB] p-7 rounded-[28px] border border-[#E5DCD0] shadow-md hover:border-[#B58E58] transition-all flex items-start gap-5 group">
              <div className="w-12 h-12 rounded-2xl bg-[#031D44] text-[#B58E58] flex items-center justify-center flex-shrink-0 shadow-md group-hover:scale-110 transition-transform">
                <FiMapPin size={22} />
              </div>
              <div>
                <h3 className="text-base font-serif font-bold text-[#031D44] mb-1">
                  Our Headquarters
                </h3>
                <p className="text-xs text-gray-600 font-light leading-relaxed">
                  9 Mapleridge crescent,
                  <br />
                  Brandon R7A6P8,
                  <br />
                  Manitoba, Canada
                </p>
              </div>
            </div>

            {/* Phone Card */}
            <div className="bg-[#F7F2EB] p-7 rounded-[28px] border border-[#E5DCD0] shadow-md hover:border-[#B58E58] transition-all flex items-start gap-5 group">
              <div className="w-12 h-12 rounded-2xl bg-[#031D44] text-[#B58E58] flex items-center justify-center flex-shrink-0 shadow-md group-hover:scale-110 transition-transform">
                <FiPhone size={22} />
              </div>
              <div>
                <h3 className="text-base font-serif font-bold text-[#031D44] mb-1">
                  Direct Helpline
                </h3>
                <p className="text-xs text-gray-600 font-light leading-relaxed">
                  +1 (204) 979-4044
                  <br />
                  Mon - Sat, 9:00 AM - 6:00 PM CST
                </p>
              </div>
            </div>

            {/* Email Card */}
            <div className="bg-[#F7F2EB] p-7 rounded-[28px] border border-[#E5DCD0] shadow-md hover:border-[#B58E58] transition-all flex items-start gap-5 group">
              <div className="w-12 h-12 rounded-2xl bg-[#031D44] text-[#B58E58] flex items-center justify-center flex-shrink-0 shadow-md group-hover:scale-110 transition-transform">
                <FiMail size={22} />
              </div>
              <div>
                <h3 className="text-base font-serif font-bold text-[#031D44] mb-1">
                  Email Inquiries
                </h3>
                <p className="text-xs text-gray-600 font-light leading-relaxed break-all">
                  tapu_parikh@yahoo.com
                  <br />
                  gatewaylinen@gmail.com
                </p>
              </div>
            </div>

            {/* Interactive Map Preview Card */}
            <div className="bg-[#F7F2EB] p-4 rounded-[28px] border border-[#E5DCD0] shadow-md overflow-hidden flex-grow min-h-[200px]">
              <div className="w-full h-full rounded-2xl overflow-hidden border border-gray-200 shadow-inner">
                <iframe
                  title="Brandon Manitoba Map"
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d81559.45876313715!2d-99.98816!3d49.8482!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x52c1e65e638b6d85%3A0x44614e758784d531!2sBrandon%2C%20MB%2C%20Canada!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin"
                  width="100%"
                  height="100%"
                  style={{ border: 0, minHeight: "180px" }}
                  allowFullScreen=""
                  loading="lazy"
                  referrerPolicy="no-referrer-when-downgrade"
                ></iframe>
              </div>
            </div>
          </div>

          {/* Right Contact Form with Trust Badges */}
          <div className="lg:col-span-7 bg-[#F7F2EB] p-8 md:p-12 rounded-[32px] border border-[#E5DCD0] shadow-2xl relative flex flex-col justify-between">
            <div>
              <span className="text-[10px] font-bold text-[#B58E58] tracking-[0.2em] uppercase">
                Secure Submission
              </span>
              <h3 className="text-2xl md:text-3xl font-serif font-bold text-[#031D44] mt-1 mb-2">
                Send Us a Message
              </h3>
              <p className="text-xs text-gray-500 mb-8 font-light leading-relaxed">
                Fill out the form below and our corporate representative will
                get back to you within 24 hours.
              </p>

              {submitted ? (
                <div className="py-24 flex flex-col items-center justify-center text-center">
                  <FiCheckCircle
                    size={64}
                    className="text-[#B58E58] mb-4 animate-bounce"
                  />
                  <h4 className="text-xl font-serif font-bold text-[#031D44]">
                    Message Sent Successfully!
                  </h4>
                  <p className="text-xs text-gray-500 mt-1">
                    Thank you for connecting with Gateway Linen.
                  </p>
                </div>
              ) : (
                <form onSubmit={handleSubmit} className="space-y-5">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                      <label className="block text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1.5">
                        Full Name
                      </label>
                      <input
                        type="text"
                        required
                        value={formData.name}
                        onChange={(e) =>
                          setFormData({ ...formData, name: e.target.value })
                        }
                        placeholder="Enter your name"
                        className="w-full bg-white text-xs px-4 py-3.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-sm transition-all"
                      />
                    </div>
                    <div>
                      <label className="block text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1.5">
                        Email Address
                      </label>
                      <input
                        type="email"
                        required
                        value={formData.email}
                        onChange={(e) =>
                          setFormData({ ...formData, email: e.target.value })
                        }
                        placeholder="name@company.ca"
                        className="w-full bg-white text-xs px-4 py-3.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-sm transition-all"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                      <label className="block text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1.5">
                        Phone Number
                      </label>
                      <input
                        type="text"
                        required
                        value={formData.phone}
                        onChange={(e) =>
                          setFormData({ ...formData, phone: e.target.value })
                        }
                        placeholder="+1 (xxx) xxx-xxxx"
                        className="w-full bg-white text-xs px-4 py-3.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-sm transition-all"
                      />
                    </div>
                    <div>
                      <label className="block text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1.5">
                        Subject
                      </label>
                      <input
                        type="text"
                        required
                        value={formData.subject}
                        onChange={(e) =>
                          setFormData({ ...formData, subject: e.target.value })
                        }
                        placeholder="Bulk Inquiry / Support"
                        className="w-full bg-white text-xs px-4 py-3.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-sm transition-all"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-[11px] font-bold uppercase tracking-wider text-[#031D44] mb-1.5">
                      Your Message
                    </label>
                    <textarea
                      rows="4"
                      required
                      value={formData.message}
                      onChange={(e) =>
                        setFormData({ ...formData, message: e.target.value })
                      }
                      placeholder="Write your requirements here..."
                      className="w-full bg-white text-xs px-4 py-3.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#B58E58] shadow-sm resize-none transition-all"
                    ></textarea>
                  </div>

                  <button
                    type="submit"
                    className="w-full py-4 bg-[#031D44] hover:bg-[#B58E58] text-white text-xs font-bold tracking-widest uppercase rounded-xl shadow-lg transition-all cursor-pointer flex items-center justify-center gap-2 group"
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
            <div className="grid grid-cols-3 gap-3 mt-8 pt-6 border-t border-[#E5DCD0]">
              <div className="flex flex-col items-center text-center p-2 bg-white/60 rounded-xl border border-gray-200/50">
                <FiHeadphones className="text-[#B58E58] mb-1" size={18} />
                <span className="text-[10px] font-bold text-[#031D44]">
                  24/7 Support
                </span>
              </div>
              <div className="flex flex-col items-center text-center p-2 bg-white/60 rounded-xl border border-gray-200/50">
                <FiShield className="text-[#B58E58] mb-1" size={18} />
                <span className="text-[10px] font-bold text-[#031D44]">
                  Secure Inquiry
                </span>
              </div>
              <div className="flex flex-col items-center text-center p-2 bg-white/60 rounded-xl border border-gray-200/50">
                <FiClock className="text-[#B58E58] mb-1" size={18} />
                <span className="text-[10px] font-bold text-[#031D44]">
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
