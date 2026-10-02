import { useState } from "react";
import { FiPlus, FiMessageCircle, FiX, FiSend } from "react-icons/fi";
import { FaWhatsapp } from "react-icons/fa";

export default function FloatingWidget() {
  const [isOpen, setIsOpen] = useState(false);
  const [showAiChat, setShowAiChat] = useState(false);

  const [messages, setMessages] = useState([
    {
      sender: "ai",
      text: "Hello! I am Gateway Linen's AI Assistant. How can I help you with our hospitality supplies or bulk orders today?",
    },
  ]);
  const [inputMessage, setInputMessage] = useState("");
  const [userContact, setUserContact] = useState("");
  const [contactSubmitted, setContactSubmitted] = useState(false);

  const handleWhatsAppRedirect = () => {
    window.open(
      "https://wa.me/12049794044?text=Hello%20Gateway%20Linen,%20I%20have%20an%20inquiry%20regarding%20bulk%20hotel%20linens.",
      "_blank",
    );
  };

  const handleSendMessage = (e) => {
    e.preventDefault();
    if (!inputMessage.trim()) return;

    const userText = inputMessage;
    setMessages((prev) => [...prev, { sender: "user", text: userText }]);
    setInputMessage("");

    setTimeout(() => {
      let aiReply =
        "Our corporate desk will contact you shortly regarding your inquiry. Would you like to share your Mobile Number or Email for a quick callback?";

      const lower = userText.toLowerCase();
      if (
        lower.includes("price") ||
        lower.includes("cost") ||
        lower.includes("rate") ||
        lower.includes("wholesale")
      ) {
        aiReply =
          "For wholesale pricing and our commercial catalog, please check our 'Quote Builder' or 'Products' section. Minimum order quantity starts at 2 units.";
      } else if (
        lower.includes("shipping") ||
        lower.includes("delivery") ||
        lower.includes("time")
      ) {
        aiReply =
          "We provide priority commercial shipping across Canada and the US. Free shipping is automatically applied on orders above $350.00!";
      } else if (
        lower.includes("contact") ||
        lower.includes("phone") ||
        lower.includes("email")
      ) {
        aiReply =
          "You can reach us directly at +1 (204) 979-4044 or email us at gatewaylinen@gmail.com[cite: 3].";
      }

      setMessages((prev) => [...prev, { sender: "ai", text: aiReply }]);
    }, 800);
  };

  const handleContactSubmit = (e) => {
    e.preventDefault();
    if (!userContact.trim()) return;
    setContactSubmitted(true);
    setMessages((prev) => [
      ...prev,
      { sender: "user", text: `Contact details provided: ${userContact}` },
      {
        sender: "ai",
        text: "Thank you! Our support team has saved your contact details and will get in touch with you shortly.",
      },
    ]);
    setUserContact("");
  };

  return (
    <div className="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 z-[999] flex flex-col items-end font-sans">
      {/* Expanded Action Circular Icons */}
      {isOpen && (
        <div className="flex flex-col gap-3 mb-3 animate-in fade-in slide-in-from-bottom-2 duration-200">
          {/* AI Assistant Icon Button */}
          <button
            onClick={() => {
              setShowAiChat(true);
              setIsOpen(false);
            }}
            className="w-12 h-12 bg-[#031D44] hover:bg-[#B58E58] text-white rounded-full flex items-center justify-center shadow-xl transition-all cursor-pointer border-2 border-white group relative"
            title="AI Assistant Support"
          >
            <FiMessageCircle size={20} />
            <span className="absolute right-14 bg-[#031D44] text-white text-[10px] font-bold px-2.5 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-md pointer-events-none uppercase tracking-wider">
              AI Assistant
            </span>
          </button>

          {/* WhatsApp Icon Button */}
          <button
            onClick={handleWhatsAppRedirect}
            className="w-12 h-12 bg-[#25D366] hover:bg-[#20ba5a] text-white rounded-full flex items-center justify-center shadow-xl transition-all cursor-pointer border-2 border-white group relative"
            title="WhatsApp Direct Chat"
          >
            <FaWhatsapp size={22} />
            <span className="absolute right-14 bg-[#25D366] text-white text-[10px] font-bold px-2.5 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-md pointer-events-none uppercase tracking-wider">
              WhatsApp Chat
            </span>
          </button>
        </div>
      )}

      {/* Main Floating Trigger Button (+ Icon / X Icon) */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="w-12 h-12 sm:w-14 sm:h-14 bg-[#031D44] hover:bg-[#B58E58] text-white rounded-full flex items-center justify-center shadow-2xl transition-transform hover:scale-105 cursor-pointer border-2 border-white"
        title="Quick Support"
      >
        <FiPlus
          size={24}
          className={`transition-transform duration-300 ${isOpen ? "rotate-45" : "rotate-0"}`}
        />
      </button>

      {/* ================= AI CHATBOX MODAL WINDOW ================= */}
      {showAiChat && (
        <div className="fixed sm:absolute bottom-0 right-0 sm:bottom-20 sm:right-0 z-[1000] w-full sm:w-[360px] h-[480px] sm:h-[500px] bg-white sm:rounded-[24px] shadow-2xl border border-[#E5DCD0] flex flex-col overflow-hidden animate-in zoom-in-95 duration-200">
          {/* Chat Header */}
          <div className="bg-[#031D44] text-white p-3.5 sm:p-4 flex items-center justify-between border-b border-white/10">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 sm:w-9 sm:h-9 bg-[#B58E58] rounded-xl flex items-center justify-center font-serif font-bold text-white shadow-sm text-xs">
                AI
              </div>
              <div>
                <h3 className="text-xs font-bold uppercase tracking-wider">
                  Gateway AI Support
                </h3>
                <span className="text-[9px] text-emerald-400 font-medium flex items-center gap-1">
                  <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>{" "}
                  Online • Instant Answers
                </span>
              </div>
            </div>
            <button
              onClick={() => setShowAiChat(false)}
              className="text-gray-300 hover:text-white bg-white/10 p-1.5 rounded-full transition-colors cursor-pointer"
            >
              <FiX size={16} />
            </button>
          </div>

          {/* Chat Messages Body */}
          <div className="flex-1 p-3.5 sm:p-4 overflow-y-auto bg-[#FAF7F2] space-y-3 text-xs">
            {messages.map((msg, index) => (
              <div
                key={index}
                className={`flex ${msg.sender === "user" ? "justify-end" : "justify-start"}`}
              >
                <div
                  className={`max-w-[85%] p-3 rounded-2xl leading-relaxed shadow-2xs ${
                    msg.sender === "user"
                      ? "bg-[#031D44] text-white rounded-br-xs"
                      : "bg-white text-gray-800 border border-[#E5DCD0] rounded-bl-xs"
                  }`}
                >
                  {msg.text}
                </div>
              </div>
            ))}
          </div>

          {/* Contact Share Box */}
          {!contactSubmitted && (
            <div className="bg-white px-3.5 py-2.5 border-t border-[#E5DCD0]">
              <form onSubmit={handleContactSubmit} className="flex gap-2">
                <input
                  type="text"
                  value={userContact}
                  onChange={(e) => setUserContact(e.target.value)}
                  placeholder="Enter Phone or Email for callback..."
                  className="flex-1 bg-[#FAF7F2] border border-[#E5DCD0] rounded-xl px-3 py-2 text-[11px] text-gray-800 focus:outline-none focus:border-[#B58E58]"
                />
                <button
                  type="submit"
                  className="bg-[#B58E58] hover:bg-[#031D44] text-white px-3 py-2 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-colors cursor-pointer shrink-0"
                >
                  Submit
                </button>
              </form>
            </div>
          )}

          {/* Message Input Footer */}
          <form
            onSubmit={handleSendMessage}
            className="p-3 bg-white border-t border-[#E5DCD0] flex gap-2"
          >
            <input
              type="text"
              value={inputMessage}
              onChange={(e) => setInputMessage(e.target.value)}
              placeholder="Ask about linens, bulk orders..."
              className="flex-1 bg-[#FAF7F2] border border-[#E5DCD0] rounded-xl px-3 py-2.5 text-xs text-gray-800 focus:outline-none focus:border-[#B58E58]"
            />
            <button
              type="submit"
              className="bg-[#031D44] hover:bg-[#B58E58] text-white p-2.5 rounded-xl transition-colors cursor-pointer flex items-center justify-center shadow-sm shrink-0"
            >
              <FiSend size={15} />
            </button>
          </form>
        </div>
      )}
    </div>
  );
}
